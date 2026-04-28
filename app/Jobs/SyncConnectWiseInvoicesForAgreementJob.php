<?php

namespace App\Jobs;

use App\Models\Agreement;
use App\Models\Invoice;
use App\Services\ConnectWiseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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

        if (! in_array((int) $agreement->connectwise_agreement_type_id, $connectWise->simAgreementTypeIds(), true)) {
            return;
        }

        foreach ($connectWise->getInvoicesForAgreement($agreement->connectwise_agreement_id) as $invoice) {
            Invoice::updateOrCreate(
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
        }
    }
}
