<?php

namespace App\Console\Commands;

use App\Models\JolaProduct;
use App\Services\MobileManagerService;
use Illuminate\Console\Command;

class SyncJolaProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:jola-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Read Jola tariff products and upsert local display-only product records';

    /**
     * Execute the console command.
     */
    public function handle(MobileManagerService $mobileManager): int
    {
        $products = $mobileManager->getTariffs();
        $syncedProductIds = [];

        foreach ($products as $product) {
            $productId = $this->firstValue($product, ['Id', 'id', 'TariffId', 'tariffId']);

            if (! $productId) {
                $productId = hash('sha256', json_encode($product));
            }

            $syncedProductIds[] = (string) $productId;

            JolaProduct::updateOrCreate(
                ['mobilemanager_product_id' => (string) $productId],
                [
                    'name' => $this->stringValue($this->firstValue($product, ['Name', 'name', 'Description', 'description', 'TariffName', 'tariffName'])),
                    'network' => $this->stringValue($this->firstValue($product, ['Operator', 'operator', 'Network.Name', 'network.name', 'Network', 'network', 'NetworkName', 'networkName'])),
                    'type' => $this->stringValue($this->firstValue($product, ['Type', 'type', 'TariffType', 'tariffType'])),
                    'allowance' => $this->allowanceValue($this->firstValue($product, ['DataAllowance', 'dataAllowance', 'Allowance', 'allowance', 'Data', 'data'])),
                    'monthly_cost' => $this->decimalValue($this->firstValue($product, ['MonthlyCost', 'monthlyCost', 'Price', 'price', 'Cost', 'cost'])),
                    'status' => $this->stringValue($this->firstValue($product, ['Status.Name', 'status.name', 'Status', 'status', 'Active', 'active'])),
                    'raw_data' => $product,
                    'last_synced_at' => now(),
                ],
            );
        }

        if ($syncedProductIds !== []) {
            JolaProduct::whereNotIn('mobilemanager_product_id', $syncedProductIds)->delete();
        }

        $this->info('Synced '.count($products).' Jola product records using read-only GET requests.');

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

    private function allowanceValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $kilobytes = (float) $value;

            if ($kilobytes >= 1048576) {
                return rtrim(rtrim(number_format($kilobytes / 1048576, 2), '0'), '.').' GB';
            }

            if ($kilobytes >= 1024) {
                return rtrim(rtrim(number_format($kilobytes / 1024, 2), '0'), '.').' MB';
            }

            return number_format($kilobytes, 0).' KB';
        }

        return $this->stringValue($value);
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

    private function decimalValue(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value) && preg_match('/\d+(\.\d+)?/', $value, $matches)) {
            return (float) $matches[0];
        }

        return null;
    }
}
