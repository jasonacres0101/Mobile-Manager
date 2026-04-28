<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ConnectWiseService
{
    public function __construct(private AppSettings $settings) {}

    public function getSimAgreements(): array
    {
        $typeIds = $this->simAgreementTypeIds();

        if ($typeIds === []) {
            return [];
        }

        return $this->getPaginated('finance/agreements', [
            'conditions' => $this->agreementTypeConditions($typeIds),
        ]);
    }

    public function getAgreementAdditions(int|string $agreementId): array
    {
        return $this->getPaginated("finance/agreements/{$agreementId}/additions");
    }

    public function getInvoicesForAgreement(int|string $agreementId): array
    {
        return $this->getPaginated('finance/invoices', [
            'conditions' => "agreement/id={$agreementId}",
        ]);
    }

    public function getCompany(int|string $companyId): array
    {
        return $this->client()
            ->get("company/companies/{$companyId}")
            ->throw()
            ->json();
    }

    public function simAgreementTypeIds(): array
    {
        $configured = $this->settings->get('connectwise.sim_agreement_type_ids');
        $typeIds = $configured !== null && $configured !== ''
            ? explode(',', $configured)
            : config('services.connectwise.sim_agreement_type_ids', []);

        return collect($typeIds)
            ->map(fn ($id) => (int) trim((string) $id))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function agreementTypeConditions(array $typeIds): string
    {
        return collect($typeIds)
            ->map(fn (int $id) => "type/id={$id}")
            ->implode(' OR ');
    }

    private function getPaginated(string $endpoint, array $query = []): array
    {
        $page = 1;
        $pageSize = 100;
        $records = [];

        do {
            $response = $this->client()
                ->get($endpoint, array_merge($query, [
                    'page' => $page,
                    'pageSize' => $pageSize,
                ]))
                ->throw()
                ->json();

            $batch = is_array($response) ? $response : [];
            $records = array_merge($records, $batch);
            $page++;
        } while (count($batch) === $pageSize);

        return $records;
    }

    private function client(): PendingRequest
    {
        $companyId = $this->settings->get('connectwise.company_id', config('services.connectwise.company_id'));
        $publicKey = $this->settings->get('connectwise.public_key', config('services.connectwise.public_key'));
        $privateKey = $this->settings->get('connectwise.private_key', config('services.connectwise.private_key'));
        $clientId = $this->settings->get('connectwise.client_id', config('services.connectwise.client_id'));

        $token = base64_encode("{$companyId}+{$publicKey}:{$privateKey}");

        return Http::baseUrl(Str::of($this->settings->get('connectwise.base_url', config('services.connectwise.base_url')))->rtrim('/')->toString())
            ->withHeaders([
                'Authorization' => "Basic {$token}",
                'clientId' => $clientId,
                'Accept' => 'application/json',
            ]);
    }
}
