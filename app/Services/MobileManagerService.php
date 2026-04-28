<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MobileManagerService
{
    public function __construct(private AppSettings $settings) {}

    public function getCustomers(): array
    {
        return $this->getPaginated('api/v1/customers');
    }

    public function getCustomer(int|string $customerId): array
    {
        return $this->get("api/v1/customers/{$customerId}");
    }

    public function getCustomerSims(int|string $customerId): array
    {
        return $this->getPaginated("api/v1/customers/{$customerId}/sims");
    }

    public function getSims(): array
    {
        return $this->getPaginated('api/v1/sims');
    }

    public function getSim(int|string $simId): array
    {
        return $this->get("api/v1/sims/{$simId}");
    }

    public function getOrders(): array
    {
        return $this->getPaginated('api/v1/orders', ['pageSize' => 500]);
    }

    public function getTariffs(): array
    {
        return $this->getPaginated('api/v1/tariffs');
    }

    public function getTariffBoltOns(int|string $tariffId): array
    {
        return $this->getPaginated("api/v1/tariffs/{$tariffId}/boltons");
    }

    public function getOrder(int|string $orderId): array
    {
        return $this->get("api/v1/orders/{$orderId}");
    }

    private function get(string $endpoint, array $query = []): array
    {
        try {
            return $this->client()
                ->get($endpoint, $query)
                ->throw()
                ->json() ?? [];
        } catch (RequestException $exception) {
            report($exception);

            throw $exception;
        }
    }

    private function getPaginated(string $endpoint, array $query = []): array
    {
        $page = 1;
        $pageSize = (int) ($query['pageSize'] ?? 100);
        $records = [];

        do {
            $response = $this->client()
                ->get($endpoint, array_merge($query, [
                    'page' => $page,
                    'pageSize' => $pageSize,
                ]))
                ->throw();

            $batch = $response->json() ?? [];
            $batch = $this->recordsFromResponse($batch);
            $records = array_merge($records, $batch);

            $totalCount = (int) ($response->header('X-Total-Count') ?? 0);
            $hasMoreByTotal = $totalCount > 0 && count($records) < $totalCount;
            $hasMoreByPageSize = count($batch) === $pageSize;
            $page++;
        } while ($hasMoreByTotal || ($totalCount === 0 && $hasMoreByPageSize));

        return $records;
    }

    private function recordsFromResponse(array $response): array
    {
        foreach (['data', 'items', 'results', 'value'] as $key) {
            if (isset($response[$key]) && is_array($response[$key])) {
                return $response[$key];
            }
        }

        return array_is_list($response) ? $response : [$response];
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(Str::of($this->settings->get('mobilemanager.base_url', config('services.mobilemanager.base_url')))->rtrim('/')->toString())
            ->withBasicAuth(
                (string) $this->settings->get('mobilemanager.api_key', config('services.mobilemanager.api_key')),
                (string) $this->settings->get('mobilemanager.api_secret', config('services.mobilemanager.api_secret')),
            )
            ->acceptJson();
    }
}
