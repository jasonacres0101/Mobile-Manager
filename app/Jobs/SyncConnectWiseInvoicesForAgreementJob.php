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
use Illuminate\Support\Str;

class SyncConnectWiseInvoicesForAgreementJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $agreementId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ConnectWiseService $connectWise): void
    {
        $agreement = Agreement::with('company')->findOrFail($this->agreementId);

        if ($connectWise->agreementServiceType((int) $agreement->connectwise_agreement_type_id) === null) {
            return;
        }

        foreach ($connectWise->getInvoicesForAgreement($agreement->connectwise_agreement_id) as $invoice) {
            $invoiceRecord = Invoice::updateOrCreate(
                ['connectwise_invoice_id' => data_get($invoice, 'id')],
                [
                    'company_id' => $agreement->company_id,
                    'agreement_id' => $agreement->id,
                    'invoice_number' => data_get($invoice, 'invoiceNumber', data_get($invoice, 'identifier', data_get($invoice, 'id'))),
                    'invoice_date' => data_get($invoice, 'invoiceDate', data_get($invoice, 'date')),
                    'due_date' => data_get($invoice, 'dueDate'),
                    'total' => data_get($invoice, 'total', 0) ?: 0,
                    'balance' => data_get($invoice, 'balance', 0) ?: 0,
                    'status' => data_get($invoice, 'status.name', data_get($invoice, 'status')),
                ],
            );

            $this->syncInvoiceItems($connectWise, $agreement, $agreement->company, $invoiceRecord);
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
