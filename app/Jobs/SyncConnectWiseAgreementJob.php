<?php

namespace App\Jobs;

use App\Models\Agreement;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Sim;
use App\Services\ConnectWiseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;

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

        if (! in_array($agreementTypeId, $connectWise->simAgreementTypeIds(), true)) {
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
                'name' => data_get($this->connectWiseAgreement, 'name', 'SIM agreement'),
                'status' => $this->agreementStatus($this->connectWiseAgreement),
                'start_date' => data_get($this->connectWiseAgreement, 'startDate'),
                'end_date' => data_get($this->connectWiseAgreement, 'endDate'),
                'last_synced_at' => now(),
            ],
        );

        foreach ($connectWise->getAgreementAdditions($agreement->connectwise_agreement_id) as $addition) {
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
                ],
            );
        }

        $this->syncInvoices($connectWise, $agreement, $company);
    }

    private function syncInvoices(ConnectWiseService $connectWise, Agreement $agreement, Company $company): void
    {
        foreach ($connectWise->getInvoicesForAgreement($agreement->connectwise_agreement_id) as $invoice) {
            Invoice::updateOrCreate(
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
        }
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
