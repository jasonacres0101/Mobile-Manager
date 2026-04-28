<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\GoCardlessService;
use Illuminate\Console\Command;

class CollectDueGoCardlessPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:collect-due-gocardless {--dry-run : Show eligible invoices without creating GoCardless payments}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Collect eligible company invoices through GoCardless auto collection rules';

    /**
     * Execute the console command.
     */
    public function handle(GoCardlessService $goCardless): int
    {
        $eligibleInvoices = Invoice::query()
            ->with(['company.mandates'])
            ->whereNull('gocardless_payment_id')
            ->where(function ($query) {
                $query->whereNull('payment_status')
                    ->orWhereNotIn('payment_status', ['created', 'pending_submission', 'submitted', 'confirmed', 'paid_out', 'paid']);
            })
            ->where('balance', '>', 0)
            ->whereNotNull('due_date')
            ->get()
            ->filter(fn (Invoice $invoice) => $this->isEligible($invoice));

        foreach ($eligibleInvoices as $invoice) {
            if ($this->option('dry-run')) {
                $this->line("Eligible: {$invoice->invoice_number} ({$invoice->company->name}) £{$invoice->balance}");
                continue;
            }

            $goCardless->createPaymentForInvoice($invoice);
            $this->info("Queued collection for {$invoice->invoice_number} ({$invoice->company->name}).");
        }

        $this->info($eligibleInvoices->count().' invoice(s) eligible for auto collection.');

        return self::SUCCESS;
    }

    private function isEligible(Invoice $invoice): bool
    {
        $company = $invoice->company;

        if (! $company?->auto_collect_enabled) {
            return false;
        }

        if (! $company->mandates->contains(fn ($mandate) => in_array($mandate->status, ['active', 'submitted', 'pending_submission', 'created'], true))) {
            return false;
        }

        if ((float) $invoice->balance < (float) $company->auto_collect_min_balance) {
            return false;
        }

        if ($company->auto_collect_max_amount !== null && (float) $invoice->balance > (float) $company->auto_collect_max_amount) {
            return false;
        }

        return $invoice->due_date->copy()
            ->subDays((int) $company->auto_collect_days_before_due)
            ->isPast();
    }
}
