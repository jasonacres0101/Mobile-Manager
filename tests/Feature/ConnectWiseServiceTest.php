<?php

namespace Tests\Feature;

use App\Jobs\SyncConnectWiseAgreementJob;
use App\Models\Agreement;
use App\Models\Company;
use App\Models\FibreConnection;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Sim;
use App\Services\AppSettings;
use App\Services\ConnectWiseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConnectWiseServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_requests_only_configured_sim_agreement_types(): void
    {
        config([
            'services.connectwise.base_url' => 'https://connectwise.test/apis/3.0',
            'services.connectwise.company_id' => 'demo',
            'services.connectwise.public_key' => 'public',
            'services.connectwise.private_key' => 'private',
            'services.connectwise.client_id' => 'client',
            'services.connectwise.sim_agreement_type_ids' => ['12', '18'],
        ]);

        Http::fake([
            'connectwise.test/*' => Http::response([], 200),
        ]);

        app(ConnectWiseService::class)->getSimAgreements();

        Http::assertSent(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);

            return str_contains($request->url(), '/finance/agreements')
                && ($query['conditions'] ?? null) === 'type/id=12 OR type/id=18';
        });
    }

    public function test_it_can_use_saved_settings_before_env_config(): void
    {
        app(AppSettings::class)->set('connectwise.base_url', 'https://connectwise-settings.test/apis/3.0');
        app(AppSettings::class)->set('connectwise.company_id', 'settings-company');
        app(AppSettings::class)->set('connectwise.public_key', 'settings-public');
        app(AppSettings::class)->set('connectwise.private_key', 'settings-private', true);
        app(AppSettings::class)->set('connectwise.client_id', 'settings-client', true);
        app(AppSettings::class)->set('connectwise.sim_agreement_type_ids', '44,55');

        Http::fake([
            'connectwise-settings.test/*' => Http::response([], 200),
        ]);

        app(ConnectWiseService::class)->getSimAgreements();

        Http::assertSent(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);

            return str_contains($request->url(), 'connectwise-settings.test')
                && $request->hasHeader('Authorization', 'Basic '.base64_encode('settings-company+settings-public:settings-private'))
                && $request->hasHeader('clientId', 'settings-client')
                && ($query['conditions'] ?? null) === 'type/id=44 OR type/id=55';
        });
    }

    public function test_connectwise_sync_uses_addition_description_for_sim_tariff(): void
    {
        $job = new SyncConnectWiseAgreementJob([
            'id' => 700001,
            'type' => ['id' => 35],
            'company' => ['id' => 900001, 'name' => 'Demo Company'],
            'name' => 'SIM Agreement',
            'status' => ['name' => 'Active'],
        ]);

        $job->handle(new class extends ConnectWiseService
        {
            public function __construct() {}

            public function simAgreementTypeIds(): array
            {
                return [35];
            }

            public function getAgreementAdditions(int|string $agreementId): array
            {
                return [
                    [
                        'id' => 800001,
                        'description' => 'Unlimited UK Data SIM',
                        'customFields' => [
                            ['caption' => 'Micronet - Service Type', 'value' => 'Sim'],
                        ],
                        'product' => ['id' => 12345, 'name' => 'CW Product Name'],
                        'unitPrice' => 15,
                        'status' => ['name' => 'Active'],
                    ],
                ];
            }

            public function getInvoicesForAgreement(int|string $agreementId): array
            {
                return [];
            }

            public function getCompany(int|string $companyId): array
            {
                return ['name' => 'Demo Company'];
            }
        });

        $this->assertSame('Unlimited UK Data SIM', Sim::firstOrFail()->tariff);
    }

    public function test_connectwise_sync_maps_iccid_custom_field_to_sim_iccid(): void
    {
        $job = new SyncConnectWiseAgreementJob([
            'id' => 700001,
            'type' => ['id' => 35],
            'company' => ['id' => 900001, 'name' => 'Demo Company'],
            'name' => 'SIM Agreement',
            'status' => ['name' => 'Active'],
        ]);

        $job->handle(new class extends ConnectWiseService
        {
            public function __construct() {}

            public function simAgreementTypeIds(): array
            {
                return [35];
            }

            public function getAgreementAdditions(int|string $agreementId): array
            {
                return [
                    [
                        'id' => 800001,
                        'description' => 'Unlimited UK Data SIM',
                        'customFields' => [
                            ['caption' => 'Micronet - Service Type', 'value' => 'Sim'],
                            ['caption' => 'ICCID', 'value' => '8944000000000000001'],
                        ],
                        'unitPrice' => 15,
                        'status' => ['name' => 'Active'],
                    ],
                ];
            }

            public function getInvoicesForAgreement(int|string $agreementId): array
            {
                return [];
            }

            public function getCompany(int|string $companyId): array
            {
                return ['name' => 'Demo Company'];
            }
        });

        $sim = Sim::firstOrFail();

        $this->assertSame('8944000000000000001', $sim->iccid);
        $this->assertSame('8944000000000000001', $sim->sim_number);
    }

    public function test_connectwise_sync_defaults_open_agreement_status_to_active(): void
    {
        $job = new SyncConnectWiseAgreementJob([
            'id' => 700001,
            'type' => ['id' => 35],
            'company' => ['id' => 900001, 'name' => 'Demo Company'],
            'name' => 'SIM Agreement',
            'startDate' => '2026-04-13',
            'endDate' => null,
        ]);

        $job->handle(new class extends ConnectWiseService
        {
            public function __construct() {}

            public function simAgreementTypeIds(): array
            {
                return [35];
            }

            public function getAgreementAdditions(int|string $agreementId): array
            {
                return [
                    [
                        'id' => 800001,
                        'description' => 'Unlimited UK Data SIM',
                        'customFields' => [
                            ['caption' => 'Micronet - Service Type', 'value' => 'Sim'],
                        ],
                        'unitPrice' => 15,
                        'status' => ['name' => 'Active'],
                    ],
                ];
            }

            public function getInvoicesForAgreement(int|string $agreementId): array
            {
                return [];
            }

            public function getCompany(int|string $companyId): array
            {
                return ['name' => 'Demo Company'];
            }
        });

        $this->assertSame('Active', Agreement::firstOrFail()->status);
    }

    public function test_connectwise_invoice_sync_can_run_immediately(): void
    {
        config([
            'services.connectwise.base_url' => 'https://connectwise.test/apis/3.0',
            'services.connectwise.company_id' => 'demo',
            'services.connectwise.public_key' => 'public',
            'services.connectwise.private_key' => 'private',
            'services.connectwise.client_id' => 'client',
            'services.connectwise.sim_agreement_type_ids' => ['35'],
        ]);

        $company = Company::create([
            'name' => 'Demo Company',
            'connectwise_company_id' => 900001,
        ]);
        Agreement::create([
            'company_id' => $company->id,
            'connectwise_agreement_id' => 700001,
            'connectwise_agreement_type_id' => 35,
            'service_type' => 'service',
            'name' => 'SIM Agreement',
            'status' => 'Active',
        ]);

        Sim::create([
            'company_id' => $company->id,
            'agreement_id' => Agreement::firstOrFail()->id,
            'connectwise_addition_id' => 800001,
            'mobile_number' => '447700900001',
            'monthly_cost' => 15,
            'status' => 'Active',
        ]);

        FibreConnection::create([
            'company_id' => $company->id,
            'agreement_id' => Agreement::firstOrFail()->id,
            'connectwise_addition_id' => 800002,
            'service_identifier' => 'FTTP 100',
            'monthly_cost' => 55,
            'status' => 'Active',
        ]);

        Http::fake([
            'connectwise.test/apis/3.0/procurement/products*' => Http::response([
                [
                    'id' => 910001,
                    'agreementAddition' => ['id' => 800001],
                    'description' => 'Unlimited UK Data SIM',
                    'quantity' => 1,
                    'unitPrice' => 15,
                    'total' => 15,
                ],
                [
                    'id' => 910002,
                    'agreementAddition' => ['id' => 800002],
                    'description' => 'FTTP 100',
                    'quantity' => 1,
                    'unitPrice' => 55,
                    'total' => 55,
                ],
            ], 200),
            'connectwise.test/apis/3.0/finance/invoices*' => Http::response([
                [
                    'id' => 600001,
                    'invoiceNumber' => 'INV-1001',
                    'total' => 89.25,
                    'balance' => 89.25,
                    'status' => ['name' => 'New'],
                ],
            ], 200),
        ]);

        $this->artisan('sync:connectwise-invoices --now')
            ->expectsOutput('Synced 1 configured service agreement invoice sync jobs.')
            ->assertSuccessful();

        $this->assertSame('INV-1001', Invoice::firstOrFail()->invoice_number);
        $this->assertCount(2, InvoiceItem::all());
        $this->assertSame(['fibre', 'sim'], InvoiceItem::query()->orderBy('service_type')->pluck('service_type')->all());
    }
}
