<?php

namespace App\Console\Commands;

use App\Jobs\SyncConnectWiseInvoicesForAgreementJob;
use App\Models\Agreement;
use App\Services\ConnectWiseService;
use Illuminate\Console\Command;

class SyncConnectWiseInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:connectwise-invoices {--now : Run the sync immediately instead of queueing jobs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue invoice refreshes for existing SIM agreements only';

    /**
     * Execute the console command.
     */
    public function handle(ConnectWiseService $connectWise): int
    {
        $typeIds = $connectWise->simAgreementTypeIds();

        $agreements = Agreement::query()
            ->whereIn('connectwise_agreement_type_id', $typeIds)
            ->pluck('id');

        foreach ($agreements as $agreementId) {
            $this->option('now')
                ? SyncConnectWiseInvoicesForAgreementJob::dispatchSync($agreementId)
                : SyncConnectWiseInvoicesForAgreementJob::dispatch($agreementId);
        }

        $this->info(($this->option('now') ? 'Synced ' : 'Queued ').$agreements->count().' SIM agreement invoice sync jobs.');

        return self::SUCCESS;
    }
}
