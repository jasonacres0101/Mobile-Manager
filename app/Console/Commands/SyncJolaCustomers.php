<?php

namespace App\Console\Commands;

use App\Models\JolaCustomer;
use App\Services\JolaCompanyMatcher;
use App\Services\MobileManagerService;
use Illuminate\Console\Command;

class SyncJolaCustomers extends Command
{
    protected $signature = 'sync:jola-customers';

    protected $description = 'Read Jola customers and upsert local display-only customer records';

    public function handle(MobileManagerService $mobileManager, JolaCompanyMatcher $matcher): int
    {
        $customers = $mobileManager->getCustomers();
        $syncedCustomerIds = [];

        foreach ($customers as $customer) {
            $customerId = $this->firstValue($customer, ['Id', 'id', 'CustomerId', 'customerId']);

            if (! $customerId) {
                $customerId = hash('sha256', json_encode($customer));
            }

            $syncedCustomerIds[] = (string) $customerId;

            $name = $this->stringValue($this->firstValue($customer, ['Name', 'name', 'CompanyName', 'companyName', 'CustomerName', 'customerName']));
            $company = $matcher->matchCustomer((string) $customerId, $name);

            JolaCustomer::updateOrCreate(
                ['mobilemanager_customer_id' => (string) $customerId],
                [
                    'company_id' => $company?->id,
                    'name' => $name,
                    'account_number' => $this->stringValue($this->firstValue($customer, ['AccountNumber', 'accountNumber', 'AccountNo', 'accountNo', 'Reference', 'reference'])),
                    'email' => $this->stringValue($this->firstValue($customer, ['Email', 'email', 'ContactEmail', 'contactEmail'])),
                    'phone' => $this->stringValue($this->firstValue($customer, ['Phone', 'phone', 'Telephone', 'telephone', 'Mobile', 'mobile'])),
                    'status' => $this->stringValue($this->firstValue($customer, ['Status.Name', 'status.name', 'Status', 'status', 'Active', 'active'])),
                    'raw_data' => $customer,
                    'last_synced_at' => now(),
                ],
            );
        }

        if ($syncedCustomerIds !== []) {
            JolaCustomer::whereNotIn('mobilemanager_customer_id', $syncedCustomerIds)->delete();
        }

        $this->info('Synced '.count($customers).' Jola customer records using read-only GET requests.');

        return self::SUCCESS;
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

    private function stringValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value) ?: null;
    }
}
