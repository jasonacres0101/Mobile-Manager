<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ConnectWiseService
{
    public function __construct(private ?AppSettings $settings = null) {}

    public function getSimAgreements(): array
    {
        return $this->getAgreementsForTypeIds($this->simAgreementTypeIds());
    }

    public function getFibreAgreements(): array
    {
        return $this->getAgreementsForTypeIds($this->fibreAgreementTypeIds());
    }

    public function getServiceAgreements(): array
    {
        return $this->getAgreementsForTypeIds($this->serviceAgreementTypeIds());
    }

    public function getAgreementsForTypeIds(array $typeIds): array
    {
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

    public function getInvoiceItems(int|string $invoiceId): array
    {
        try {
            return $this->getPaginated('procurement/products', [
                'conditions' => "invoice/id={$invoiceId}",
            ]);
        } catch (RequestException $exception) {
            if ($exception->response?->status() !== 404) {
                throw $exception;
            }
        }

        try {
            return $this->getPaginated("finance/invoices/{$invoiceId}/products");
        } catch (RequestException $exception) {
            if ($exception->response?->status() !== 404) {
                throw $exception;
            }
        }

        $invoice = $this->client()
            ->get("finance/invoices/{$invoiceId}")
            ->throw()
            ->json();

        foreach (['products', 'invoiceProducts', 'lineItems', 'details', 'items'] as $key) {
            $items = data_get($invoice, $key);

            if (is_array($items)) {
                return $items;
            }
        }

        return [];
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
        return $this->configuredAgreementTypeIds(
            'connectwise.sim_agreement_type_ids',
            config('services.connectwise.sim_agreement_type_ids', []),
        );
    }

    public function fibreAgreementTypeIds(): array
    {
        return $this->configuredAgreementTypeIds(
            'connectwise.fibre_agreement_type_ids',
            config('services.connectwise.fibre_agreement_type_ids', []),
        );
    }

    public function serviceAgreementTypeIds(): array
    {
        return collect([
            ...$this->simAgreementTypeIds(),
            ...$this->fibreAgreementTypeIds(),
        ])->unique()->values()->all();
    }

    public function agreementServiceType(int $agreementTypeId): ?string
    {
        if (in_array($agreementTypeId, $this->simAgreementTypeIds(), true)) {
            return 'sim';
        }

        if (in_array($agreementTypeId, $this->fibreAgreementTypeIds(), true)) {
            return 'fibre';
        }

        return null;
    }

    private function agreementTypeConditions(array $typeIds): string
    {
        return collect($typeIds)
            ->map(fn (int $id) => "type/id={$id}")
            ->implode(' OR ');
    }

    private function configuredAgreementTypeIds(string $settingKey, array $default): array
    {
        $configured = $this->settings()?->get($settingKey);
        $typeIds = $configured !== null && $configured !== ''
            ? explode(',', $configured)
            : $default;

        return collect($typeIds)
            ->map(fn ($id) => (int) trim((string) $id))
            ->filter()
            ->unique()
            ->values()
            ->all();
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
        $companyId = $this->settings()?->get('connectwise.company_id', config('services.connectwise.company_id')) ?? config('services.connectwise.company_id');
        $publicKey = $this->settings()?->get('connectwise.public_key', config('services.connectwise.public_key')) ?? config('services.connectwise.public_key');
        $privateKey = $this->settings()?->get('connectwise.private_key', config('services.connectwise.private_key')) ?? config('services.connectwise.private_key');
        $clientId = $this->settings()?->get('connectwise.client_id', config('services.connectwise.client_id')) ?? config('services.connectwise.client_id');

        $token = base64_encode("{$companyId}+{$publicKey}:{$privateKey}");

        return Http::baseUrl(Str::of($this->settings()?->get('connectwise.base_url', config('services.connectwise.base_url')) ?? config('services.connectwise.base_url'))->rtrim('/')->toString())
            ->withHeaders([
                'Authorization' => "Basic {$token}",
                'clientId' => $clientId,
                'Accept' => 'application/json',
            ]);
    }

    private function settings(): ?AppSettings
    {
        return $this->settings ??= app(AppSettings::class);
    }
}
