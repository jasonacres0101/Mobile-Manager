<?php

namespace App\Console\Commands;

use App\Jobs\SyncConnectWiseAgreementJob;
use App\Services\ConnectWiseService;
use Illuminate\Console\Command;

class SyncConnectWiseSimAgreements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:connectwise-sim-agreements {--now : Run the sync immediately instead of queueing jobs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue sync jobs for configured ConnectWise PSA SIM and fibre agreement types';

    /**
     * Execute the console command.
     */
    public function handle(ConnectWiseService $connectWise): int
    {
        $agreements = $connectWise->getServiceAgreements();

        foreach ($agreements as $agreement) {
            $this->option('now')
                ? SyncConnectWiseAgreementJob::dispatchSync($agreement)
                : SyncConnectWiseAgreementJob::dispatch($agreement);
        }

        $this->info(($this->option('now') ? 'Synced ' : 'Queued ').count($agreements).' configured service agreement sync jobs.');

        return self::SUCCESS;
    }
}
