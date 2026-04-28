<?php

namespace Tests\Feature;

use App\Models\Sim;
use App\Models\User;
use App\Models\Company;
use App\Models\JolaProduct;
use App\Models\JolaCustomer;
use App\Services\AppSettings;
use App\Services\MobileManagerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MobileManagerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_manager_service_uses_get_requests_with_basic_auth(): void
    {
        config([
            'services.mobilemanager.base_url' => 'https://developers.mobilemanager.co.uk',
            'services.mobilemanager.api_key' => 'api-key',
            'services.mobilemanager.api_secret' => 'api-secret',
        ]);

        Http::fake([
            'developers.mobilemanager.co.uk/*' => Http::response([], 200),
        ]);

        app(MobileManagerService::class)->getSims();

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && str_contains($request->url(), '/api/v1/sims')
                && $request->hasHeader('Authorization', 'Basic '.base64_encode('api-key:api-secret'));
        });
    }

    public function test_jola_sync_upserts_sims_and_stores_raw_data(): void
    {
        config([
            'services.mobilemanager.base_url' => 'https://developers.mobilemanager.co.uk',
            'services.mobilemanager.api_key' => 'api-key',
            'services.mobilemanager.api_secret' => 'api-secret',
        ]);

        Http::fake([
            'developers.mobilemanager.co.uk/*' => Http::response([
                [
                    'id' => 'SIM-123',
                    'customerId' => 'CUS-9',
                    'iccid' => '8944000000000000001',
                    'msisdn' => '447700900123',
                    'network' => 'O2',
                    'tariff' => '5GB',
                    'status' => 'Active',
                ],
            ], 200),
        ]);

        $this->artisan('sync:jola-sims')->assertSuccessful();

        $sim = Sim::where('mobilemanager_sim_id', 'SIM-123')->firstOrFail();

        $this->assertSame('CUS-9', $sim->mobilemanager_customer_id);
        $this->assertSame('8944000000000000001', $sim->iccid);
        $this->assertSame('447700900123', $sim->msisdn);
        $this->assertSame('O2', $sim->network);
        $this->assertSame('SIM-123', $sim->raw_data['id']);
        $this->assertNotNull($sim->last_synced_at);

        Http::assertSent(fn ($request) => $request->method() === 'GET');
    }

    public function test_jola_sync_enriches_existing_connectwise_sim_by_iccid(): void
    {
        config([
            'services.mobilemanager.base_url' => 'https://developers.mobilemanager.co.uk',
            'services.mobilemanager.api_key' => 'api-key',
            'services.mobilemanager.api_secret' => 'api-secret',
        ]);

        $existing = Sim::create([
            'connectwise_addition_id' => 800001,
            'mobile_number' => '447700900123',
            'sim_number' => '8944000000000000001',
            'network' => 'O2',
            'tariff' => 'ConnectWise tariff',
            'monthly_cost' => 12.50,
            'status' => 'Active',
        ]);

        Http::fake([
            'developers.mobilemanager.co.uk/*' => Http::response([
                [
                    'id' => 'SIM-123',
                    'customerId' => 'CUS-9',
                    'iccid' => '8944000000000000001',
                    'msisdn' => '447700900123',
                    'network' => 'O2',
                    'tariff' => '5GB',
                    'status' => 'Active',
                ],
            ], 200),
        ]);

        $this->artisan('sync:jola-sims')->assertSuccessful();

        $this->assertSame(1, Sim::count());

        $existing->refresh();

        $this->assertSame('SIM-123', $existing->mobilemanager_sim_id);
        $this->assertSame('CUS-9', $existing->mobilemanager_customer_id);
        $this->assertSame('8944000000000000001', $existing->iccid);
        $this->assertSame('447700900123', $existing->msisdn);
        $this->assertSame('SIM-123', $existing->raw_data['id']);
    }

    public function test_jola_sync_handles_mobile_manager_uppercase_sim_fields(): void
    {
        config([
            'services.mobilemanager.base_url' => 'https://developers.mobilemanager.co.uk',
            'services.mobilemanager.api_key' => 'api-key',
            'services.mobilemanager.api_secret' => 'api-secret',
        ]);

        $existing = Sim::create([
            'connectwise_addition_id' => 800001,
            'iccid' => '89444610709900073604',
            'sim_number' => '89444610709900073604',
            'monthly_cost' => 12.50,
            'status' => 'Active',
        ]);

        Http::fake([
            'developers.mobilemanager.co.uk/*' => Http::response([
                [
                    'Id' => 'ffbba6bf-f2bb-4c09-a91b-62e16251e5b3',
                    'ICCID' => '89444610709900073604',
                    'MobileNumber' => '883190602012644',
                    'Operator' => 'RoamNet 901',
                    'State' => 'Active',
                    'Tariff' => 'RoamNet 901 M2M UK4NetEU 20GB 1M',
                ],
            ], 200),
        ]);

        $this->artisan('sync:jola-sims')->assertSuccessful();

        $existing->refresh();

        $this->assertSame('ffbba6bf-f2bb-4c09-a91b-62e16251e5b3', $existing->mobilemanager_sim_id);
        $this->assertSame('883190602012644', $existing->mobile_number);
        $this->assertSame('RoamNet 901', $existing->network);
        $this->assertSame('RoamNet 901 M2M UK4NetEU 20GB 1M', $existing->tariff);
        $this->assertSame('Active', $existing->status);
    }

    public function test_jola_sync_reads_customer_specific_sims_for_matching(): void
    {
        config([
            'services.mobilemanager.base_url' => 'https://developers.mobilemanager.co.uk',
            'services.mobilemanager.api_key' => 'api-key',
            'services.mobilemanager.api_secret' => 'api-secret',
        ]);

        JolaCustomer::create([
            'mobilemanager_customer_id' => 'JOLA-CUSTOMER-1',
            'name' => 'Test',
        ]);

        $existing = Sim::create([
            'connectwise_addition_id' => 800001,
            'iccid' => '89444610709900073604',
            'sim_number' => '89444610709900073604',
            'monthly_cost' => 12.50,
            'status' => 'Active',
        ]);

        Http::fake([
            'developers.mobilemanager.co.uk/api/v1/sims*' => Http::response([], 200),
            'developers.mobilemanager.co.uk/api/v1/customers/JOLA-CUSTOMER-1/sims*' => Http::response([
                [
                    'Id' => 'ffbba6bf-f2bb-4c09-a91b-62e16251e5b3',
                    'ICCID' => '89444610709900073604',
                    'MobileNumber' => '883190602012644',
                    'Operator' => 'RoamNet 901',
                    'State' => 'Active',
                    'Tariff' => 'RoamNet 901 M2M UK4NetEU 20GB 1M',
                    'CustomerId' => null,
                ],
            ], 200),
        ]);

        $this->artisan('sync:jola-sims')->assertSuccessful();

        $existing->refresh();

        $this->assertSame('ffbba6bf-f2bb-4c09-a91b-62e16251e5b3', $existing->mobilemanager_sim_id);
        $this->assertSame('JOLA-CUSTOMER-1', $existing->mobilemanager_customer_id);
        $this->assertSame('883190602012644', $existing->mobile_number);
    }

    public function test_jola_customer_sync_links_matching_portal_company(): void
    {
        config([
            'services.mobilemanager.base_url' => 'https://developers.mobilemanager.co.uk',
            'services.mobilemanager.api_key' => 'api-key',
            'services.mobilemanager.api_secret' => 'api-secret',
        ]);

        $company = Company::create([
            'name' => 'Test Client Ltd',
            'connectwise_company_id' => 900001,
        ]);

        Http::fake([
            'developers.mobilemanager.co.uk/*' => Http::response([
                [
                    'Id' => 'JOLA-123',
                    'Name' => 'Test Client',
                    'Email' => 'billing@example.test',
                ],
            ], 200),
        ]);

        $this->artisan('sync:jola-customers')->assertSuccessful();

        $company->refresh();
        $jolaCustomer = JolaCustomer::where('mobilemanager_customer_id', 'JOLA-123')->firstOrFail();

        $this->assertSame('JOLA-123', $company->mobilemanager_customer_id);
        $this->assertTrue($company->is($jolaCustomer->company));
    }

    public function test_mobile_manager_service_can_use_saved_settings_before_env_config(): void
    {
        app(AppSettings::class)->set('mobilemanager.base_url', 'https://jola-settings.test');
        app(AppSettings::class)->set('mobilemanager.api_key', 'saved-key', true);
        app(AppSettings::class)->set('mobilemanager.api_secret', 'saved-secret', true);

        Http::fake([
            'jola-settings.test/*' => Http::response([], 200),
        ]);

        app(MobileManagerService::class)->getSims();

        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && str_contains($request->url(), 'jola-settings.test/api/v1/sims')
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('saved-key:saved-secret')));
    }

    public function test_mobile_manager_service_reads_tariffs_with_get_request(): void
    {
        config([
            'services.mobilemanager.base_url' => 'https://developers.mobilemanager.co.uk',
            'services.mobilemanager.api_key' => 'api-key',
            'services.mobilemanager.api_secret' => 'api-secret',
        ]);

        Http::fake([
            'developers.mobilemanager.co.uk/*' => Http::response([
                ['id' => 'TAR-1', 'name' => '10GB SIM Only'],
            ], 200),
        ]);

        $tariffs = app(MobileManagerService::class)->getTariffs();

        $this->assertSame('TAR-1', $tariffs[0]['id']);

        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && str_contains($request->url(), '/api/v1/tariffs')
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('api-key:api-secret')));
    }

    public function test_admin_can_view_read_only_jola_products_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        JolaProduct::create([
            'mobilemanager_product_id' => 'TAR-1',
            'name' => '10GB SIM Only',
            'network' => 'O2',
            'status' => 'Active',
            'raw_data' => ['id' => 'TAR-1'],
            'last_synced_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.jola-products.index'))
            ->assertOk()
            ->assertSee('Jola Products')
            ->assertSee('10GB SIM Only')
            ->assertSee('O2')
            ->assertSee('not linked');
    }

    public function test_jola_products_sync_upserts_read_only_tariffs(): void
    {
        config([
            'services.mobilemanager.base_url' => 'https://developers.mobilemanager.co.uk',
            'services.mobilemanager.api_key' => 'api-key',
            'services.mobilemanager.api_secret' => 'api-secret',
        ]);

        Http::fake([
            'developers.mobilemanager.co.uk/*' => Http::response([
                [
                    'Id' => 'TAR-1',
                    'Name' => '10GB SIM Only',
                    'Operator' => 'O2',
                    'DataAllowance' => 10485760,
                ],
            ], 200),
        ]);

        $this->artisan('sync:jola-products')->assertSuccessful();

        $product = JolaProduct::where('mobilemanager_product_id', 'TAR-1')->firstOrFail();

        $this->assertSame('10GB SIM Only', $product->name);
        $this->assertSame('O2', $product->network);
        $this->assertSame('10 GB', $product->allowance);
        $this->assertNull($product->monthly_cost);
        $this->assertSame('TAR-1', $product->raw_data['Id']);
        $this->assertNotNull($product->last_synced_at);

        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && str_contains($request->url(), '/api/v1/tariffs'));
    }

    public function test_jola_customers_sync_upserts_read_only_customers(): void
    {
        config([
            'services.mobilemanager.base_url' => 'https://developers.mobilemanager.co.uk',
            'services.mobilemanager.api_key' => 'api-key',
            'services.mobilemanager.api_secret' => 'api-secret',
        ]);

        Http::fake([
            'developers.mobilemanager.co.uk/*' => Http::response([
                [
                    'Id' => 'CUS-1',
                    'Name' => 'Example Customer Ltd',
                    'AccountNumber' => 'ACC-100',
                    'Email' => 'billing@example.com',
                    'Phone' => '01234567890',
                    'Active' => true,
                ],
            ], 200),
        ]);

        $this->artisan('sync:jola-customers')->assertSuccessful();

        $customer = JolaCustomer::where('mobilemanager_customer_id', 'CUS-1')->firstOrFail();

        $this->assertSame('Example Customer Ltd', $customer->name);
        $this->assertSame('ACC-100', $customer->account_number);
        $this->assertSame('billing@example.com', $customer->email);
        $this->assertSame('01234567890', $customer->phone);
        $this->assertSame('Yes', $customer->status);
        $this->assertSame('CUS-1', $customer->raw_data['Id']);
        $this->assertNotNull($customer->last_synced_at);

        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && str_contains($request->url(), '/api/v1/customers'));
    }

    public function test_admin_can_view_read_only_jola_customers_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        JolaCustomer::create([
            'mobilemanager_customer_id' => 'CUS-1',
            'name' => 'Example Customer Ltd',
            'account_number' => 'ACC-100',
            'raw_data' => ['Id' => 'CUS-1'],
            'last_synced_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.jola-customers.index'))
            ->assertOk()
            ->assertSee('Jola Customers')
            ->assertSee('Example Customer Ltd')
            ->assertSee('ACC-100')
            ->assertSee('Portal company');
    }

    public function test_admin_can_view_jola_customer_details_with_live_get_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        app(AppSettings::class)->set('mobilemanager.cdr_export_folder', '9919ccca-791f-4949-a284-0839f6837122');
        $customer = JolaCustomer::create([
            'mobilemanager_customer_id' => 'CUS-1',
            'name' => 'Example Customer Ltd',
            'account_number' => 'ACC-100',
            'raw_data' => ['Id' => 'CUS-1', 'Name' => 'Example Customer Ltd'],
            'last_synced_at' => now(),
        ]);

        $mobileManager = \Mockery::mock(MobileManagerService::class);
        $mobileManager->shouldReceive('getCustomer')->once()->with('CUS-1')->andReturn([
            'Id' => 'CUS-1',
            'Name' => 'Example Customer Ltd',
            'BillingEmail' => 'billing@example.com',
        ]);
        $mobileManager->shouldReceive('getCustomerSims')->once()->with('CUS-1')->andReturn([
            [
                'Id' => 'SIM-1',
                'Msisdn' => '447700900123',
                'Iccid' => '8944000000000000001',
            ],
        ]);
        $this->app->instance(MobileManagerService::class, $mobileManager);

        $this->actingAs($admin)
            ->get(route('admin.jola-customers.show', $customer))
            ->assertOk()
            ->assertSee('Jola Customer')
            ->assertSee('Example Customer Ltd')
            ->assertSee('billing@example.com')
            ->assertSee('447700900123')
            ->assertSee('8944000000000000001');
    }

    public function test_admin_can_view_read_only_jola_customer_sim_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        app(AppSettings::class)->set('mobilemanager.cdr_export_folder', '9919ccca-791f-4949-a284-0839f6837122');
        $customer = JolaCustomer::create([
            'mobilemanager_customer_id' => 'CUS-1',
            'name' => 'Example Customer Ltd',
            'account_number' => 'ACC-100',
            'raw_data' => ['Id' => 'CUS-1', 'Name' => 'Example Customer Ltd'],
            'last_synced_at' => now(),
        ]);

        $mobileManager = \Mockery::mock(MobileManagerService::class);
        $mobileManager->shouldReceive('getSim')->once()->with('SIM-1')->andReturn([
            'Allownace' => 12582912,
            'Barred' => false,
            'BoltonAllowance' => 0,
            'ContactEmail' => 'jason@micronet-support.com',
            'Id' => 'SIM-1',
            'MobileNumber' => '447700900123',
            'ICCID' => '8944000000000000001',
            'Operator' => 'O2',
            'Tariff' => '10GB SIM Only',
            'TariffAllowance' => 20971520,
            'Usage' => 10215291,
            'AllowanceUsedPercent' => 49,
            'LastSeenCountry' => 'GBRVF',
            'LastSeenNetwork' => '23415',
            'LastSeenAt' => '2026-04-16T12:33:33',
            'State' => 'Active',
        ]);
        $this->app->instance(MobileManagerService::class, $mobileManager);

        $this->actingAs($admin)
            ->get(route('admin.jola-customers.sims.show', [$customer, 'SIM-1']))
            ->assertOk()
            ->assertSee('Jola SIM Details')
            ->assertSee('Read-only Jola data')
            ->assertSee('Download CDR')
            ->assertSee('447700900123-monthly-', false)
            ->assertSee('447700900123')
            ->assertSee('8944000000000000001')
            ->assertSee('O2')
            ->assertSee('10GB SIM Only')
            ->assertSee('12 GB')
            ->assertSee('20 GB')
            ->assertSee('9.74 GB')
            ->assertSee('49%')
            ->assertSee('GBRVF')
            ->assertSee('Vodafone UK')
            ->assertSee('23415')
            ->assertSee('MCC')
            ->assertSee('MNC')
            ->assertSee('jason@micronet-support.com');
    }

}
