<?php

namespace App\Jobs;

use App\Models\Agreement;
use App\Models\Company;
use App\Models\FibreConnection;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Sim;
use App\Services\ConnectWiseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SyncConnectWiseAgreementJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $connectWiseAgreement)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ConnectWiseService $connectWise): void
    {
        $agreementTypeId = (int) data_get($this->connectWiseAgreement, 'type.id');

        if (! in_array($agreementTypeId, $connectWise->serviceAgreementTypeIds(), true)) {
            return;
        }

        $connectWiseCompanyId = (int) data_get($this->connectWiseAgreement, 'company.id');
        $companyName = data_get($this->connectWiseAgreement, 'company.name');

        if (! $companyName && $connectWiseCompanyId) {
            $companyName = data_get($connectWise->getCompany($connectWiseCompanyId), 'name');
        }

        $company = Company::updateOrCreate(
            ['connectwise_company_id' => $connectWiseCompanyId],
            ['name' => $companyName ?: 'Unknown company'],
        );

        $agreement = Agreement::updateOrCreate(
            ['connectwise_agreement_id' => data_get($this->connectWiseAgreement, 'id')],
            [
                'company_id' => $company->id,
                'connectwise_agreement_type_id' => $agreementTypeId,
                'service_type' => 'service',
                'name' => data_get($this->connectWiseAgreement, 'name', 'Service agreement'),
                'status' => $this->agreementStatus($this->connectWiseAgreement),
                'start_date' => data_get($this->connectWiseAgreement, 'startDate'),
                'end_date' => data_get($this->connectWiseAgreement, 'endDate'),
                'last_synced_at' => now(),
            ],
        );

        foreach ($connectWise->getAgreementAdditions($agreement->connectwise_agreement_id) as $addition) {
            match ($this->additionServiceType($addition)) {
                'sim' => $this->syncSimAddition($company, $agreement, $addition),
                'fibre' => $this->syncFibreAddition($company, $agreement, $addition),
                default => null,
            };
        }

        $this->syncInvoices($connectWise, $agreement, $company);
    }

    private function syncSimAddition(Company $company, Agreement $agreement, array $addition): void
    {
        FibreConnection::where('connectwise_addition_id', data_get($addition, 'id'))->delete();

        Sim::updateOrCreate(
            ['connectwise_addition_id' => data_get($addition, 'id')],
            [
                'company_id' => $company->id,
                'agreement_id' => $agreement->id,
                'mobile_number' => $this->additionValue($addition, ['mobile_number', 'mobile number', 'msisdn']),
                'sim_number' => $this->additionValue($addition, ['sim_number', 'sim number', 'iccid']),
                'iccid' => $this->additionValue($addition, ['iccid', 'sim_number', 'sim number']),
                'network' => $this->additionValue($addition, ['network', 'carrier']),
                'tariff' => $this->additionDescription($addition)
                    ?: $this->additionValue($addition, ['tariff'])
                    ?: data_get($addition, 'product.name'),
                'monthly_cost' => data_get($addition, 'unitPrice', data_get($addition, 'price', 0)) ?: 0,
                'status' => data_get($addition, 'status.name', data_get($addition, 'status', 'active')),
                'last_synced_at' => now(),
            ],
        );
    }

    private function syncFibreAddition(Company $company, Agreement $agreement, array $addition): void
    {
        Sim::where('connectwise_addition_id', data_get($addition, 'id'))->delete();

        FibreConnection::updateOrCreate(
            ['connectwise_addition_id' => data_get($addition, 'id')],
            [
                'company_id' => $company->id,
                'agreement_id' => $agreement->id,
                'service_identifier' => $this->additionValue($addition, ['service identifier', 'service id', 'service_reference', 'service reference'])
                    ?: data_get($addition, 'product.name'),
                'circuit_reference' => $this->additionValue($addition, ['circuit reference', 'circuit ref', 'circuit', 'access reference']),
                'access_type' => $this->additionValue($addition, ['access type', 'bearer', 'line type'])
                    ?: $this->additionDescription($addition),
                'bandwidth' => $this->additionValue($addition, ['bandwidth', 'speed', 'download speed']),
                'location_address' => $this->additionValue($addition, ['address', 'site address', 'installation address']),
                'monthly_cost' => data_get($addition, 'unitPrice', data_get($addition, 'price', 0)) ?: 0,
                'status' => data_get($addition, 'status.name', data_get($addition, 'status', 'active')),
                'raw_data' => $addition,
                'last_synced_at' => now(),
            ],
        );
    }

    private function syncInvoices(ConnectWiseService $connectWise, Agreement $agreement, Company $company): void
    {
        foreach ($connectWise->getInvoicesForAgreement($agreement->connectwise_agreement_id) as $invoice) {
            $invoiceRecord = Invoice::updateOrCreate(
                ['connectwise_invoice_id' => data_get($invoice, 'id')],
                [
                    'company_id' => $company->id,
                    'agreement_id' => $agreement->id,
                    'invoice_number' => data_get($invoice, 'invoiceNumber', data_get($invoice, 'identifier', data_get($invoice, 'id'))),
                    'invoice_date' => data_get($invoice, 'invoiceDate', data_get($invoice, 'date')),
                    'due_date' => data_get($invoice, 'dueDate'),
                    'total' => data_get($invoice, 'total', 0) ?: 0,
                    'balance' => data_get($invoice, 'balance', 0) ?: 0,
                    'status' => data_get($invoice, 'status.name', data_get($invoice, 'status')),
                ],
            );

            $this->syncInvoiceItems($connectWise, $agreement, $company, $invoiceRecord);
        }
    }

    private function syncInvoiceItems(ConnectWiseService $connectWise, Agreement $agreement, Company $company, Invoice $invoice): void
    {
        $lineItems = $connectWise->getInvoiceItems($invoice->connectwise_invoice_id);
        $keptKeys = [];

        foreach ($lineItems as $index => $lineItem) {
            $sourceKey = $this->invoiceItemSourceKey($invoice, $lineItem, $index);
            $keptKeys[] = $sourceKey;
            $additionId = $this->invoiceItemAdditionId($lineItem);

            InvoiceItem::updateOrCreate(
                ['source_key' => $sourceKey],
                [
                    'invoice_id' => $invoice->id,
                    'company_id' => $company->id,
                    'agreement_id' => $agreement->id,
                    'connectwise_invoice_line_id' => data_get($lineItem, 'id'),
                    'connectwise_addition_id' => $additionId,
                    'description' => $this->invoiceItemDescription($lineItem),
                    'service_type' => $this->invoiceItemServiceType($agreement, $company, $lineItem, $additionId),
                    'quantity' => $this->decimalValue(data_get($lineItem, 'quantity', data_get($lineItem, 'qty'))),
                    'unit_price' => $this->decimalValue(data_get($lineItem, 'unitPrice', data_get($lineItem, 'price'))),
                    'line_total' => $this->decimalValue(data_get($lineItem, 'total', data_get($lineItem, 'extendedPrice', data_get($lineItem, 'amount')))),
                    'raw_data' => $lineItem,
                ],
            );
        }

        $invoice->items()->whereNotIn('source_key', $keptKeys)->delete();
    }

    private function invoiceItemSourceKey(Invoice $invoice, array $lineItem, int $index): string
    {
        $lineId = data_get($lineItem, 'id');

        if ($lineId) {
            return 'cw-line-'.$lineId;
        }

        return 'cw-line-'.$invoice->connectwise_invoice_id.'-'.$index.'-'.md5(json_encode([
            $this->invoiceItemDescription($lineItem),
            data_get($lineItem, 'quantity', data_get($lineItem, 'qty')),
            data_get($lineItem, 'total', data_get($lineItem, 'extendedPrice', data_get($lineItem, 'amount'))),
        ]));
    }

    private function invoiceItemAdditionId(array $lineItem): ?int
    {
        foreach ([
            'agreementAddition.id',
            'agreementAdditionId',
            'agreementAddition/id',
            'agreementAddition.id.value',
            'addition.id',
            'additionId',
        ] as $path) {
            $value = data_get($lineItem, $path);

            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    private function invoiceItemDescription(array $lineItem): ?string
    {
        return $this->stringValue(data_get($lineItem, 'description'))
            ?: $this->stringValue(data_get($lineItem, 'product.name'))
            ?: $this->stringValue(data_get($lineItem, 'item.name'))
            ?: $this->stringValue(data_get($lineItem, 'catalogItem.name'));
    }

    private function invoiceItemServiceType(Agreement $agreement, Company $company, array $lineItem, ?int $additionId): string
    {
        if ($additionId && Sim::query()->where('connectwise_addition_id', $additionId)->exists()) {
            return 'sim';
        }

        if ($additionId && FibreConnection::query()->where('connectwise_addition_id', $additionId)->exists()) {
            return 'fibre';
        }

        $description = strtolower((string) ($this->invoiceItemDescription($lineItem) ?? ''));

        if (Str::contains($description, ['fibre', 'fiber', 'fttp', 'fttc', 'leased line', 'ethernet', 'broadband'])) {
            return 'fibre';
        }

        if (Str::contains($description, ['sim', 'mobile', 'msisdn', 'iccid'])) {
            return 'sim';
        }

        $hasSims = $agreement->sims()->exists() || $company->sims()->exists();
        $hasFibre = $agreement->fibreConnections()->exists() || $company->fibreConnections()->exists();

        return match (true) {
            $hasSims && $hasFibre => 'mixed',
            $hasFibre => 'fibre',
            default => 'sim',
        };
    }

    private function decimalValue(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function additionServiceType(array $addition): ?string
    {
        $serviceType = strtolower((string) ($this->additionValue($addition, ['micronet - service type']) ?? ''));

        return match ($serviceType) {
            'sim' => 'sim',
            'fibre', 'fiber' => 'fibre',
            default => null,
        };
    }

    private function agreementStatus(array $agreement): string
    {
        $status = $this->stringValue(data_get($agreement, 'status.name'))
            ?: $this->stringValue(data_get($agreement, 'status'))
            ?: $this->stringValue(data_get($agreement, 'agreementStatus.name'))
            ?: $this->stringValue(data_get($agreement, 'agreementStatus'));

        if ($status) {
            return $status;
        }

        if (data_get($agreement, 'cancelledFlag') || data_get($agreement, 'inactiveFlag')) {
            return 'Inactive';
        }

        return data_get($agreement, 'endDate') ? 'Ended' : 'Active';
    }

    private function additionValue(array $addition, array $names): ?string
    {
        foreach ($names as $name) {
            $direct = data_get($addition, $name);

            if ($direct) {
                return $this->stringValue($direct);
            }
        }

        foreach (Arr::wrap(data_get($addition, 'customFields', [])) as $field) {
            $caption = strtolower((string) data_get($field, 'caption', data_get($field, 'name')));

            if (in_array($caption, $names, true)) {
                return $this->stringValue(data_get($field, 'value'));
            }
        }

        return null;
    }

    private function additionDescription(array $addition): ?string
    {
        return $this->stringValue(data_get($addition, 'description'))
            ?: $this->stringValue(data_get($addition, 'product.description'))
            ?: $this->additionValue($addition, ['description']);
    }

    private function stringValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        foreach (['name', 'identifier', 'id', 'value'] as $key) {
            $nested = data_get($value, $key);

            if (is_scalar($nested) && $nested !== '') {
                return (string) $nested;
            }
        }

        return json_encode($value) ?: null;
    }
}
