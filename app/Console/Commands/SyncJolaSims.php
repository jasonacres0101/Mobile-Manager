<?php

namespace App\Console\Commands;

use App\Models\JolaCustomer;
use App\Models\Sim;
use App\Services\JolaCompanyMatcher;
use App\Services\MobileManagerService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SyncJolaSims extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:jola-sims';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Read SIM data from Mobile Manager / Jola and upsert local SIM records';

    /**
     * Execute the console command.
     */
    public function handle(MobileManagerService $mobileManager, JolaCompanyMatcher $matcher): int
    {
        $sims = $this->readOnlySims($mobileManager);

        foreach ($sims as $simData) {
            $mobileManagerSimId = $this->firstValue($simData, ['Id', 'id', 'SimId', 'simId']);

            if (! $mobileManagerSimId) {
                continue;
            }

            $iccid = $this->firstValue($simData, ['ICCID', 'iccid', 'Iccid', 'iccId']);
            $msisdn = $this->firstValue($simData, ['MSISDN', 'msisdn']);
            $mobileNumber = $this->firstValue($simData, ['MobileNumber', 'mobileNumber', 'mobile_number', 'msisdn']);
            $simNumber = $this->firstValue($simData, ['SimNumber', 'simNumber', 'ICCID', 'iccid', 'Iccid', 'iccId']);
            $mobileManagerCustomerId = $this->firstValue($simData, ['CustomerId', 'customerId', 'customer.id', 'Customer.Id']);
            $company = $this->companyForJolaCustomer($mobileManagerCustomerId, $simData, $matcher);

            $sim = $this->findMatchingSim((string) $mobileManagerSimId, $iccid, $msisdn, $mobileNumber, $simNumber) ?? new Sim;

            $sim->fill([
                'company_id' => $sim->company_id ?: $company?->id,
                'mobilemanager_sim_id' => (string) $mobileManagerSimId,
                'mobilemanager_customer_id' => $mobileManagerCustomerId,
                'iccid' => $iccid,
                'msisdn' => $msisdn,
                'mobile_number' => $mobileNumber,
                'sim_number' => $simNumber,
                'network' => $this->firstValue($simData, ['Operator', 'operator', 'Network.Name', 'network.name', 'Network', 'network']),
                'tariff' => $this->firstValue($simData, ['Tariff.Name', 'tariff.name', 'Tariff', 'tariff']),
                'status' => $this->firstValue($simData, ['State', 'state', 'Status.Name', 'status.name', 'Status', 'status']),
                'raw_data' => $simData,
                'last_synced_at' => now(),
            ])->save();
        }

        $this->info('Synced '.count($sims).' Jola SIM records using read-only GET requests.');

        return self::SUCCESS;
    }

    private function readOnlySims(MobileManagerService $mobileManager): Collection
    {
        $sims = collect($mobileManager->getSims());

        JolaCustomer::query()
            ->select(['mobilemanager_customer_id'])
            ->get()
            ->each(function (JolaCustomer $customer) use ($mobileManager, $sims) {
                collect($mobileManager->getCustomerSims($customer->mobilemanager_customer_id))
                    ->each(function (array $sim) use ($customer, $sims) {
                        if (! data_get($sim, 'CustomerId') && ! data_get($sim, 'customerId')) {
                            data_set($sim, 'CustomerId', $customer->mobilemanager_customer_id);
                        }

                        $sims->push($sim);
                    });
            });

        return $sims
            ->filter(fn ($sim) => is_array($sim))
            ->unique(fn (array $sim) => $this->firstValue($sim, ['Id', 'id', 'SimId', 'simId'])
                ?: $this->firstValue($sim, ['ICCID', 'iccid', 'Iccid', 'iccId'])
                ?: json_encode($sim)
            )
            ->values();
    }

    private function findMatchingSim(string $mobileManagerSimId, ?string $iccid, ?string $msisdn, ?string $mobileNumber, ?string $simNumber): ?Sim
    {
        return Sim::where('mobilemanager_sim_id', $mobileManagerSimId)
            ->when($iccid, fn ($query) => $query->orWhere('iccid', $iccid)->orWhere('sim_number', $iccid))
            ->when($simNumber, fn ($query) => $query->orWhere('sim_number', $simNumber)->orWhere('iccid', $simNumber))
            ->when($msisdn, fn ($query) => $query->orWhere('msisdn', $msisdn)->orWhere('mobile_number', $msisdn))
            ->when($mobileNumber, fn ($query) => $query->orWhere('mobile_number', $mobileNumber)->orWhere('msisdn', $mobileNumber))
            ->orderByRaw('connectwise_addition_id is null')
            ->first();
    }

    private function companyForJolaCustomer(mixed $mobileManagerCustomerId, array $simData, JolaCompanyMatcher $matcher)
    {
        if (! $mobileManagerCustomerId) {
            return null;
        }

        $jolaCustomer = JolaCustomer::where('mobilemanager_customer_id', (string) $mobileManagerCustomerId)->first();

        if ($jolaCustomer?->company) {
            return $jolaCustomer->company;
        }

        $company = $matcher->matchCustomer(
            (string) $mobileManagerCustomerId,
            data_get($simData, 'customer.name', data_get($simData, 'customerName'))
        );

        if ($company && $jolaCustomer) {
            $jolaCustomer->update(['company_id' => $company->id]);
        }

        return $company;
    }

    private function firstValue(array $data, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = data_get($data, $key);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
