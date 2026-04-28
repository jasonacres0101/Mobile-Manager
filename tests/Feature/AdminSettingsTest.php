<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Company;
use App\Models\User;
use App\Services\AppSettings;
use App\Services\ConnectWiseService;
use App\Services\MicrosoftGraphMailService;
use App\Services\MobileManagerService;
use App\Services\UserWelcomeEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_gocardless_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put(route('admin.settings.gocardless.update'), [
                'gocardless_environment' => 'sandbox',
                'gocardless_access_token' => 'token-123',
                'gocardless_webhook_secret' => 'secret-123',
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'gocardless']));

        $this->assertSame('sandbox', AppSetting::getValue('gocardless.environment'));
        $this->assertSame('token-123', AppSetting::getValue('gocardless.access_token'));
        $this->assertSame('secret-123', AppSetting::getValue('gocardless.webhook_secret'));
        $this->assertNotSame('token-123', AppSetting::where('key', 'gocardless.access_token')->value('value'));
    }

    public function test_admin_can_save_connectwise_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put(route('admin.settings.connectwise.update'), [
                'connectwise_base_url' => 'https://api-na.myconnectwise.net/v4_6_release/apis/3.0',
                'connectwise_company_id' => 'cw-company',
                'connectwise_public_key' => 'cw-public',
                'connectwise_private_key' => 'cw-private',
                'connectwise_client_id' => 'cw-client',
                'connectwise_sim_agreement_type_ids' => '12, 18',
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'connectwise']));

        $this->assertSame('https://api-na.myconnectwise.net/v4_6_release/apis/3.0', AppSetting::getValue('connectwise.base_url'));
        $this->assertSame('cw-company', AppSetting::getValue('connectwise.company_id'));
        $this->assertSame('cw-public', AppSetting::getValue('connectwise.public_key'));
        $this->assertSame('cw-private', AppSetting::getValue('connectwise.private_key'));
        $this->assertSame('cw-client', AppSetting::getValue('connectwise.client_id'));
        $this->assertSame('12,18', AppSetting::getValue('connectwise.sim_agreement_type_ids'));
        $this->assertNotSame('cw-private', AppSetting::where('key', 'connectwise.private_key')->value('value'));
    }

    public function test_admin_can_test_connectwise_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $connectWise = Mockery::mock(ConnectWiseService::class);
        $connectWise->shouldReceive('simAgreementTypeIds')->once()->andReturn([12, 18]);
        $connectWise->shouldReceive('getSimAgreements')->once()->andReturn([
            ['id' => 1001],
            ['id' => 1002],
        ]);
        $this->app->instance(ConnectWiseService::class, $connectWise);

        $this->actingAs($admin)
            ->post(route('admin.settings.connectwise.test'))
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'connectwise']));

        $this->assertSame('success', AppSetting::getValue('connectwise.last_test_status'));
        $this->assertSame('Connected successfully. Found 2 SIM agreement record(s) for type IDs 12,18.', AppSetting::getValue('connectwise.last_test_message'));
        $this->assertNotNull(AppSetting::getValue('connectwise.last_tested_at'));
    }

    public function test_admin_can_trigger_connectwise_sync_from_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Artisan::shouldReceive('call')->once()->with('sync:connectwise-sim-agreements', ['--now' => true])->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('Synced 3 SIM agreement sync jobs.');

        $this->actingAs($admin)
            ->post(route('admin.settings.connectwise.sync'), [
                'sync_type' => 'agreements',
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'connectwise']));

        $this->assertSame('success', AppSetting::getValue('connectwise.last_manual_sync_status'));
        $this->assertSame('Synced 3 SIM agreement sync jobs.', AppSetting::getValue('connectwise.last_manual_sync_message'));
        $this->assertNotNull(AppSetting::getValue('connectwise.last_manual_sync_at'));
    }

    public function test_admin_can_save_jola_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put(route('admin.settings.jola.update'), [
                'mobilemanager_base_url' => 'https://developers.mobilemanager.co.uk',
                'mobilemanager_cdr_export_folder' => '9919ccca-791f-4949-a284-0839f6837122',
                'mobilemanager_api_key' => 'jola-key',
                'mobilemanager_api_secret' => 'jola-secret',
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'jola']));

        $this->assertSame('https://developers.mobilemanager.co.uk', AppSetting::getValue('mobilemanager.base_url'));
        $this->assertSame('9919ccca-791f-4949-a284-0839f6837122', AppSetting::getValue('mobilemanager.cdr_export_folder'));
        $this->assertSame('jola-key', AppSetting::getValue('mobilemanager.api_key'));
        $this->assertSame('jola-secret', AppSetting::getValue('mobilemanager.api_secret'));
        $this->assertNotSame('jola-secret', AppSetting::where('key', 'mobilemanager.api_secret')->value('value'));
    }

    public function test_admin_can_test_jola_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $mobileManager = Mockery::mock(MobileManagerService::class);
        $mobileManager->shouldReceive('getSims')->once()->andReturn([
            ['id' => 'sim-1'],
            ['id' => 'sim-2'],
        ]);
        $this->app->instance(MobileManagerService::class, $mobileManager);

        $this->actingAs($admin)
            ->post(route('admin.settings.jola.test'))
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'jola']));

        $this->assertSame('success', AppSetting::getValue('mobilemanager.last_test_status'));
        $this->assertSame('Connected successfully. Found 2 SIM record(s).', AppSetting::getValue('mobilemanager.last_test_message'));
        $this->assertNotNull(AppSetting::getValue('mobilemanager.last_tested_at'));
    }

    public function test_admin_can_save_microsoft365_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put(route('admin.settings.microsoft365.update'), [
                'microsoft365_tenant_id' => 'tenant-123',
                'microsoft365_client_id' => 'client-123',
                'microsoft365_client_secret' => 'secret-123',
                'microsoft365_sender_email' => 'billing@example.com',
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'microsoft365']));

        $this->assertSame('tenant-123', AppSetting::getValue('microsoft365.tenant_id'));
        $this->assertSame('client-123', AppSetting::getValue('microsoft365.client_id'));
        $this->assertSame('secret-123', AppSetting::getValue('microsoft365.client_secret'));
        $this->assertSame('billing@example.com', AppSetting::getValue('microsoft365.sender_email'));
        $this->assertNotSame('secret-123', AppSetting::where('key', 'microsoft365.client_secret')->value('value'));
    }

    public function test_admin_can_test_microsoft365_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        app(AppSettings::class)->set('microsoft365.sender_email', 'billing@example.com');

        $mail = Mockery::mock(MicrosoftGraphMailService::class);
        $mail->shouldReceive('sendHtml')
            ->once()
            ->with('billing@example.com', 'SIM Portal Microsoft 365 test', Mockery::type('string'));
        $this->app->instance(MicrosoftGraphMailService::class, $mail);

        $this->actingAs($admin)
            ->post(route('admin.settings.microsoft365.test'))
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'microsoft365']));

        $this->assertSame('success', AppSetting::getValue('microsoft365.last_test_status'));
        $this->assertSame('Test email sent successfully to billing@example.com.', AppSetting::getValue('microsoft365.last_test_message'));
        $this->assertNotNull(AppSetting::getValue('microsoft365.last_tested_at'));
    }

    public function test_admin_can_create_user_from_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $company = Company::create([
            'name' => 'Acme Ltd',
            'connectwise_company_id' => 123,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.settings.users.store'), [
                'name' => 'Customer User',
                'email' => 'customer@example.com',
                'password' => 'Password123!',
                'role' => 'customer',
                'company_id' => $company->id,
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'users']));

        $user = User::where('email', 'customer@example.com')->firstOrFail();

        $this->assertSame('customer', $user->role);
        $this->assertSame($company->id, $user->company_id);
        $this->assertTrue(Hash::check('Password123!', $user->password));
    }

    public function test_admin_can_create_user_with_welcome_email_instead_of_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $company = Company::create([
            'name' => 'Acme Ltd',
            'connectwise_company_id' => 123,
        ]);

        $welcomeEmail = Mockery::mock(UserWelcomeEmailService::class);
        $welcomeEmail->shouldReceive('send')->once()->with(Mockery::type(User::class));
        $this->app->instance(UserWelcomeEmailService::class, $welcomeEmail);

        $this->actingAs($admin)
            ->post(route('admin.settings.users.store'), [
                'name' => 'Welcome User',
                'email' => 'welcome@example.com',
                'role' => 'customer',
                'company_id' => $company->id,
                'send_welcome_email' => '1',
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'users']));

        $user = User::where('email', 'welcome@example.com')->firstOrFail();

        $this->assertSame('customer', $user->role);
        $this->assertSame($company->id, $user->company_id);
        $this->assertNotNull($user->password);
    }

    public function test_admin_can_update_user_from_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($admin)
            ->put(route('admin.settings.users.update', $user), [
                'name' => 'Updated Admin',
                'email' => 'updated@example.com',
                'password' => 'NewPassword123!',
                'role' => 'admin',
                'company_id' => null,
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'users']));

        $user->refresh();

        $this->assertSame('Updated Admin', $user->name);
        $this->assertSame('updated@example.com', $user->email);
        $this->assertSame('admin', $user->role);
        $this->assertNull($user->company_id);
        $this->assertTrue(Hash::check('NewPassword123!', $user->password));
    }

    public function test_admin_can_filter_users_in_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $company = Company::create([
            'name' => 'Acme Ltd',
            'connectwise_company_id' => 123,
        ]);
        User::factory()->create([
            'name' => 'Acme Customer',
            'email' => 'acme@example.com',
            'role' => 'customer',
            'company_id' => $company->id,
        ]);
        User::factory()->create([
            'name' => 'Other Customer',
            'email' => 'other@example.com',
            'role' => 'customer',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.settings.edit', [
                'tab' => 'users',
                'user_search' => 'Acme',
                'user_role' => 'customer',
                'user_company_id' => $company->id,
            ]))
            ->assertOk()
            ->assertSee('Acme Customer')
            ->assertDontSee('Other Customer');
    }

    public function test_admin_can_send_welcome_email_to_existing_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'customer']);

        $welcomeEmail = Mockery::mock(UserWelcomeEmailService::class);
        $welcomeEmail->shouldReceive('send')->once()->with(Mockery::on(fn (User $sentUser) => $sentUser->is($user)));
        $this->app->instance(UserWelcomeEmailService::class, $welcomeEmail);

        $this->actingAs($admin)
            ->post(route('admin.settings.users.welcome-email', $user))
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'users']));
    }

    public function test_admin_can_save_welcome_email_template(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put(route('admin.settings.users.welcome-email-template'), [
                'welcome_email_subject' => 'Welcome {name}',
                'welcome_email_body' => '<p>Hello {name}, use {password_setup_url}</p>',
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'users']));

        $this->assertSame('Welcome {name}', AppSetting::getValue('welcome_email.subject'));
        $this->assertSame('<p>Hello {name}, use {password_setup_url}</p>', AppSetting::getValue('welcome_email.body'));
    }

    public function test_admin_can_send_test_welcome_email_template(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'customer']);

        $welcomeEmail = Mockery::mock(UserWelcomeEmailService::class);
        $welcomeEmail->shouldReceive('sendUsingTemplate')
            ->once()
            ->with(
                Mockery::on(fn (User $sentUser) => $sentUser->is($user)),
                'Test subject {name}',
                '<p>Test body {password_setup_url}</p>',
            );
        $this->app->instance(UserWelcomeEmailService::class, $welcomeEmail);

        $this->actingAs($admin)
            ->post(route('admin.settings.users.welcome-email-template.test'), [
                'welcome_email_subject' => 'Test subject {name}',
                'welcome_email_body' => '<p>Test body {password_setup_url}</p>',
                'test_user_id' => $user->id,
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'users']));
    }

    public function test_welcome_email_service_uses_saved_template(): void
    {
        app(AppSettings::class)->set('welcome_email.subject', 'Portal access for {name}');
        app(AppSettings::class)->set('welcome_email.body', '<p>Hi {name} at {company}</p><p>{password_setup_url}</p>');

        $company = Company::create([
            'name' => 'Acme Ltd',
            'connectwise_company_id' => 123,
        ]);
        $user = User::factory()->create([
            'name' => 'Customer User',
            'email' => 'customer@example.com',
            'company_id' => $company->id,
            'role' => 'customer',
        ]);

        $mail = Mockery::mock(MicrosoftGraphMailService::class);
        $mail->shouldReceive('sendHtml')
            ->once()
            ->with(
                'customer@example.com',
                'Portal access for Customer User',
                Mockery::on(fn (string $html) => str_contains($html, 'Hi Customer User at Acme Ltd')
                    && str_contains($html, 'images/micronet-logo.svg')
                    && str_contains($html, 'width="150"')
                    && str_contains($html, 'reset-password')
                    && ! str_contains($html, '{password_setup_url}'))
            );
        $this->app->instance(MicrosoftGraphMailService::class, $mail);

        app(UserWelcomeEmailService::class)->send($user);
    }

    public function test_microsoft_graph_mail_service_uses_client_credentials_and_send_mail(): void
    {
        app(AppSettings::class)->set('microsoft365.tenant_id', 'tenant-123');
        app(AppSettings::class)->set('microsoft365.client_id', 'client-123');
        app(AppSettings::class)->set('microsoft365.client_secret', 'secret-123', true);
        app(AppSettings::class)->set('microsoft365.sender_email', 'billing@example.com');

        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => 'graph-token'], 200),
            'graph.microsoft.com/*' => Http::response(null, 202),
        ]);

        app(MicrosoftGraphMailService::class)->sendHtml('customer@example.com', 'Welcome', '<p>Hello</p>');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'login.microsoftonline.com/tenant-123/oauth2/v2.0/token')
            && $request['client_id'] === 'client-123'
            && $request['client_secret'] === 'secret-123'
            && $request['scope'] === 'https://graph.microsoft.com/.default'
            && $request['grant_type'] === 'client_credentials');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'graph.microsoft.com/v1.0/users/billing%40example.com/sendMail')
            && $request->hasHeader('Authorization', 'Bearer graph-token')
            && $request['message']['subject'] === 'Welcome'
            && $request['message']['toRecipients'][0]['emailAddress']['address'] === 'customer@example.com');
    }
}
