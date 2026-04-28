<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Services\AppSettings;
use App\Services\ConnectWiseService;
use App\Services\MicrosoftGraphMailService;
use App\Services\MobileManagerService;
use App\Services\UserWelcomeEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class SettingsController extends Controller
{
    public function edit(Request $request, AppSettings $settings)
    {
        $editingUserId = $request->integer('edit_user') ?: null;
        $userSearch = trim((string) $request->query('user_search', ''));
        $userRole = $request->query('user_role');
        $userCompanyId = $request->query('user_company_id');
        $usersQuery = User::with('company')
            ->when($userSearch !== '', function ($query) use ($userSearch) {
                $query->where(function ($query) use ($userSearch) {
                    $query->where('name', 'like', '%'.$userSearch.'%')
                        ->orWhere('email', 'like', '%'.$userSearch.'%')
                        ->orWhereHas('company', fn ($query) => $query->where('name', 'like', '%'.$userSearch.'%'));
                });
            })
            ->when(in_array($userRole, ['admin', 'customer'], true), fn ($query) => $query->where('role', $userRole))
            ->when(filled($userCompanyId), fn ($query) => $query->where('company_id', $userCompanyId))
            ->orderBy('name');

        return view('admin.settings.edit', [
            'activeTab' => $request->query('tab', 'gocardless'),
            'editingUser' => $editingUserId ? User::find($editingUserId) : null,
            'gocardlessEnvironment' => $settings->get('gocardless.environment', config('services.gocardless.environment', 'sandbox')),
            'hasAccessToken' => $settings->has('gocardless.access_token') || filled(config('services.gocardless.access_token')),
            'hasWebhookSecret' => $settings->has('gocardless.webhook_secret') || filled(config('services.gocardless.webhook_secret')),
            'connectwiseBaseUrl' => $settings->get('connectwise.base_url', config('services.connectwise.base_url')),
            'connectwiseCompanyId' => $settings->get('connectwise.company_id', config('services.connectwise.company_id')),
            'connectwisePublicKey' => $settings->get('connectwise.public_key', config('services.connectwise.public_key')),
            'connectwiseSimAgreementTypeIds' => $settings->get('connectwise.sim_agreement_type_ids', implode(',', config('services.connectwise.sim_agreement_type_ids', []))),
            'hasConnectWisePrivateKey' => $settings->has('connectwise.private_key') || filled(config('services.connectwise.private_key')),
            'hasConnectWiseClientId' => $settings->has('connectwise.client_id') || filled(config('services.connectwise.client_id')),
            'connectwiseLastTestStatus' => $settings->get('connectwise.last_test_status'),
            'connectwiseLastTestMessage' => $settings->get('connectwise.last_test_message'),
            'connectwiseLastTestedAt' => $settings->get('connectwise.last_tested_at'),
            'connectwiseLastManualSyncStatus' => $settings->get('connectwise.last_manual_sync_status'),
            'connectwiseLastManualSyncMessage' => $settings->get('connectwise.last_manual_sync_message'),
            'connectwiseLastManualSyncedAt' => $settings->get('connectwise.last_manual_sync_at'),
            'mobileManagerBaseUrl' => $settings->get('mobilemanager.base_url', config('services.mobilemanager.base_url')),
            'mobileManagerCdrExportFolder' => $settings->get('mobilemanager.cdr_export_folder'),
            'hasMobileManagerApiKey' => $settings->has('mobilemanager.api_key') || filled(config('services.mobilemanager.api_key')),
            'hasMobileManagerApiSecret' => $settings->has('mobilemanager.api_secret') || filled(config('services.mobilemanager.api_secret')),
            'mobileManagerLastTestStatus' => $settings->get('mobilemanager.last_test_status'),
            'mobileManagerLastTestMessage' => $settings->get('mobilemanager.last_test_message'),
            'mobileManagerLastTestedAt' => $settings->get('mobilemanager.last_tested_at'),
            'microsoft365TenantId' => $settings->get('microsoft365.tenant_id', config('services.microsoft365.tenant_id')),
            'microsoft365ClientId' => $settings->get('microsoft365.client_id', config('services.microsoft365.client_id')),
            'microsoft365SenderEmail' => $settings->get('microsoft365.sender_email', config('services.microsoft365.sender_email')),
            'hasMicrosoft365ClientSecret' => $settings->has('microsoft365.client_secret') || filled(config('services.microsoft365.client_secret')),
            'microsoft365LastTestStatus' => $settings->get('microsoft365.last_test_status'),
            'microsoft365LastTestMessage' => $settings->get('microsoft365.last_test_message'),
            'microsoft365LastTestedAt' => $settings->get('microsoft365.last_tested_at'),
            'welcomeEmailSubject' => $settings->get('welcome_email.subject', UserWelcomeEmailService::DEFAULT_SUBJECT),
            'welcomeEmailBody' => $settings->get('welcome_email.body', UserWelcomeEmailService::DEFAULT_BODY),
            'users' => $usersQuery->get(),
            'userSearch' => $userSearch,
            'userRole' => $userRole,
            'userCompanyId' => $userCompanyId,
            'userTotalCount' => User::count(),
            'adminUserCount' => User::where('role', 'admin')->count(),
            'customerUserCount' => User::where('role', 'customer')->count(),
            'companies' => Company::orderBy('name')->get(),
        ]);
    }

    public function updateGoCardless(Request $request, AppSettings $settings)
    {
        $validated = $request->validate([
            'gocardless_environment' => ['required', Rule::in(['sandbox', 'live'])],
            'gocardless_access_token' => ['nullable', 'string', 'max:2000'],
            'gocardless_webhook_secret' => ['nullable', 'string', 'max:2000'],
            'clear_access_token' => ['nullable', 'boolean'],
            'clear_webhook_secret' => ['nullable', 'boolean'],
        ]);

        $settings->set('gocardless.environment', $validated['gocardless_environment']);

        if ($request->boolean('clear_access_token')) {
            $settings->set('gocardless.access_token', null, true);
        } elseif (filled($validated['gocardless_access_token'])) {
            $settings->set('gocardless.access_token', $validated['gocardless_access_token'], true);
        }

        if ($request->boolean('clear_webhook_secret')) {
            $settings->set('gocardless.webhook_secret', null, true);
        } elseif (filled($validated['gocardless_webhook_secret'])) {
            $settings->set('gocardless.webhook_secret', $validated['gocardless_webhook_secret'], true);
        }

        return redirect()->route('admin.settings.edit', ['tab' => 'gocardless'])->with('status', 'GoCardless settings saved.');
    }

    public function updateConnectWise(Request $request, AppSettings $settings)
    {
        $validated = $request->validate([
            'connectwise_base_url' => ['required', 'url', 'max:500'],
            'connectwise_company_id' => ['nullable', 'string', 'max:255'],
            'connectwise_public_key' => ['nullable', 'string', 'max:255'],
            'connectwise_private_key' => ['nullable', 'string', 'max:2000'],
            'connectwise_client_id' => ['nullable', 'string', 'max:2000'],
            'connectwise_sim_agreement_type_ids' => ['nullable', 'regex:/^\s*\d+(\s*,\s*\d+)*\s*$/'],
            'clear_connectwise_private_key' => ['nullable', 'boolean'],
            'clear_connectwise_client_id' => ['nullable', 'boolean'],
        ]);

        $settings->set('connectwise.base_url', $validated['connectwise_base_url']);
        $settings->set('connectwise.company_id', $validated['connectwise_company_id'] ?? null);
        $settings->set('connectwise.public_key', $validated['connectwise_public_key'] ?? null);
        $settings->set('connectwise.sim_agreement_type_ids', preg_replace('/\s+/', '', $validated['connectwise_sim_agreement_type_ids'] ?? ''));
        $settings->set('connectwise.last_test_status', null);
        $settings->set('connectwise.last_test_message', null);
        $settings->set('connectwise.last_tested_at', null);

        if ($request->boolean('clear_connectwise_private_key')) {
            $settings->set('connectwise.private_key', null, true);
        } elseif (filled($validated['connectwise_private_key'])) {
            $settings->set('connectwise.private_key', $validated['connectwise_private_key'], true);
        }

        if ($request->boolean('clear_connectwise_client_id')) {
            $settings->set('connectwise.client_id', null, true);
        } elseif (filled($validated['connectwise_client_id'])) {
            $settings->set('connectwise.client_id', $validated['connectwise_client_id'], true);
        }

        return redirect()->route('admin.settings.edit', ['tab' => 'connectwise'])->with('status', 'ConnectWise settings saved.');
    }

    public function testConnectWise(AppSettings $settings, ConnectWiseService $connectWise)
    {
        try {
            $typeIds = $connectWise->serviceAgreementTypeIds();

            if ($typeIds === []) {
                throw new RuntimeException('No service agreement type IDs are configured.');
            }

            $agreements = $connectWise->getServiceAgreements();

            $settings->set('connectwise.last_test_status', 'success');
            $settings->set('connectwise.last_test_message', 'Connected successfully. Found '.count($agreements).' service agreement record(s) for type IDs '.implode(',', $typeIds).'.');
            $settings->set('connectwise.last_tested_at', now()->toDateTimeString());

            return redirect()->route('admin.settings.edit', ['tab' => 'connectwise'])->with('status', 'ConnectWise PSA test successful.');
        } catch (Throwable $exception) {
            report($exception);

            $settings->set('connectwise.last_test_status', 'failed');
            $settings->set('connectwise.last_test_message', $exception->getMessage());
            $settings->set('connectwise.last_tested_at', now()->toDateTimeString());

            return redirect()
                ->route('admin.settings.edit', ['tab' => 'connectwise'])
                ->withErrors(['connectwise_test' => 'ConnectWise PSA test failed. Check the base URL, API member keys, client ID, permissions, and the configured service agreement type IDs.']);
        }
    }

    public function syncConnectWise(Request $request, AppSettings $settings)
    {
        $validated = $request->validate([
            'sync_type' => ['required', Rule::in(['agreements', 'invoices'])],
        ]);

        $command = $validated['sync_type'] === 'agreements'
            ? 'sync:connectwise-sim-agreements'
            : 'sync:connectwise-invoices';

        try {
            Artisan::call($command, ['--now' => true]);

            $output = trim(Artisan::output());
            $settings->set('connectwise.last_manual_sync_status', 'success');
            $settings->set('connectwise.last_manual_sync_message', $output ?: 'Sync completed successfully.');
            $settings->set('connectwise.last_manual_sync_at', now()->toDateTimeString());

            return redirect()
                ->route('admin.settings.edit', ['tab' => 'connectwise'])
                ->with('status', $output ?: 'ConnectWise sync completed successfully.');
        } catch (Throwable $exception) {
            report($exception);

            $settings->set('connectwise.last_manual_sync_status', 'failed');
            $settings->set('connectwise.last_manual_sync_message', $exception->getMessage());
            $settings->set('connectwise.last_manual_sync_at', now()->toDateTimeString());

            return redirect()
                ->route('admin.settings.edit', ['tab' => 'connectwise'])
                ->withErrors(['connectwise_sync' => 'ConnectWise sync failed. Check the connection status and settings.']);
        }
    }

    public function updateMobileManager(Request $request, AppSettings $settings)
    {
        $validated = $request->validate([
            'mobilemanager_base_url' => ['required', 'url', 'max:500'],
            'mobilemanager_cdr_export_folder' => ['nullable', 'uuid'],
            'mobilemanager_api_key' => ['nullable', 'string', 'max:2000'],
            'mobilemanager_api_secret' => ['nullable', 'string', 'max:2000'],
            'clear_mobilemanager_api_key' => ['nullable', 'boolean'],
            'clear_mobilemanager_api_secret' => ['nullable', 'boolean'],
        ]);

        $settings->set('mobilemanager.base_url', $validated['mobilemanager_base_url']);
        $settings->set('mobilemanager.cdr_export_folder', $validated['mobilemanager_cdr_export_folder'] ?? null);
        $settings->set('mobilemanager.last_test_status', null);
        $settings->set('mobilemanager.last_test_message', null);
        $settings->set('mobilemanager.last_tested_at', null);

        if ($request->boolean('clear_mobilemanager_api_key')) {
            $settings->set('mobilemanager.api_key', null, true);
        } elseif (filled($validated['mobilemanager_api_key'])) {
            $settings->set('mobilemanager.api_key', $validated['mobilemanager_api_key'], true);
        }

        if ($request->boolean('clear_mobilemanager_api_secret')) {
            $settings->set('mobilemanager.api_secret', null, true);
        } elseif (filled($validated['mobilemanager_api_secret'])) {
            $settings->set('mobilemanager.api_secret', $validated['mobilemanager_api_secret'], true);
        }

        return redirect()->route('admin.settings.edit', ['tab' => 'jola'])->with('status', 'Jola settings saved.');
    }

    public function testMobileManager(AppSettings $settings, MobileManagerService $mobileManager)
    {
        try {
            $sims = $mobileManager->getSims();

            $settings->set('mobilemanager.last_test_status', 'success');
            $settings->set('mobilemanager.last_test_message', 'Connected successfully. Found '.count($sims).' SIM record(s).');
            $settings->set('mobilemanager.last_tested_at', now()->toDateTimeString());

            return redirect()->route('admin.settings.edit', ['tab' => 'jola'])->with('status', 'Jola connection test successful.');
        } catch (Throwable $exception) {
            report($exception);

            $settings->set('mobilemanager.last_test_status', 'failed');
            $settings->set('mobilemanager.last_test_message', $exception->getMessage());
            $settings->set('mobilemanager.last_tested_at', now()->toDateTimeString());

            return redirect()
                ->route('admin.settings.edit', ['tab' => 'jola'])
                ->withErrors(['mobilemanager_test' => 'Jola connection test failed. Check the base URL, API key, and API secret.']);
        }
    }

    public function updateMicrosoft365(Request $request, AppSettings $settings)
    {
        $validated = $request->validate([
            'microsoft365_tenant_id' => ['nullable', 'string', 'max:255'],
            'microsoft365_client_id' => ['nullable', 'string', 'max:255'],
            'microsoft365_client_secret' => ['nullable', 'string', 'max:2000'],
            'microsoft365_sender_email' => ['nullable', 'email', 'max:255'],
            'clear_microsoft365_client_secret' => ['nullable', 'boolean'],
        ]);

        $settings->set('microsoft365.tenant_id', $validated['microsoft365_tenant_id'] ?? null);
        $settings->set('microsoft365.client_id', $validated['microsoft365_client_id'] ?? null);
        $settings->set('microsoft365.sender_email', $validated['microsoft365_sender_email'] ?? null);
        $settings->set('microsoft365.last_test_status', null);
        $settings->set('microsoft365.last_test_message', null);
        $settings->set('microsoft365.last_tested_at', null);

        if ($request->boolean('clear_microsoft365_client_secret')) {
            $settings->set('microsoft365.client_secret', null, true);
        } elseif (filled($validated['microsoft365_client_secret'])) {
            $settings->set('microsoft365.client_secret', $validated['microsoft365_client_secret'], true);
        }

        return redirect()->route('admin.settings.edit', ['tab' => 'microsoft365'])->with('status', 'Microsoft 365 settings saved.');
    }

    public function testMicrosoft365(AppSettings $settings, MicrosoftGraphMailService $mail)
    {
        $senderEmail = $settings->get('microsoft365.sender_email', config('services.microsoft365.sender_email'));

        try {
            $mail->sendHtml(
                $senderEmail,
                'SIM Portal Microsoft 365 test',
                '<p>This is a test email from the SIM Portal Microsoft 365 settings page.</p>'
            );

            $settings->set('microsoft365.last_test_status', 'success');
            $settings->set('microsoft365.last_test_message', 'Test email sent successfully to '.$senderEmail.'.');
            $settings->set('microsoft365.last_tested_at', now()->toDateTimeString());

            return redirect()->route('admin.settings.edit', ['tab' => 'microsoft365'])->with('status', 'Microsoft 365 email test successful.');
        } catch (Throwable $exception) {
            report($exception);

            $settings->set('microsoft365.last_test_status', 'failed');
            $settings->set('microsoft365.last_test_message', $exception->getMessage());
            $settings->set('microsoft365.last_tested_at', now()->toDateTimeString());

            return redirect()
                ->route('admin.settings.edit', ['tab' => 'microsoft365'])
                ->withErrors(['microsoft365_test' => 'Microsoft 365 email test failed. Check the saved settings, Mail.Send permission, admin consent, and sender mailbox.']);
        }
    }

    public function storeUser(Request $request, UserWelcomeEmailService $welcomeEmail)
    {
        $sendWelcomeEmail = $request->boolean('send_welcome_email');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => [$sendWelcomeEmail ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['admin', 'customer'])],
            'company_id' => ['nullable', 'exists:companies,id'],
            'send_welcome_email' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password'] ?? Str::random(48)),
            'role' => $validated['role'],
            'company_id' => $validated['role'] === 'customer' ? ($validated['company_id'] ?? null) : null,
            'email_verified_at' => now(),
        ]);

        if ($sendWelcomeEmail) {
            try {
                $welcomeEmail->send($user);
            } catch (Throwable $exception) {
                report($exception);

                return redirect()
                    ->route('admin.settings.edit', ['tab' => 'users'])
                    ->withErrors(['welcome_email' => 'User created, but the welcome email could not be sent. Check the Microsoft 365 settings.']);
            }
        }

        return redirect()->route('admin.settings.edit', ['tab' => 'users'])->with('status', $sendWelcomeEmail ? 'User created and welcome email sent.' : 'User created.');
    }

    public function updateWelcomeEmail(Request $request, AppSettings $settings)
    {
        $validated = $request->validate([
            'welcome_email_subject' => ['required', 'string', 'max:255'],
            'welcome_email_body' => ['required', 'string', 'max:10000'],
        ]);

        $settings->set('welcome_email.subject', $validated['welcome_email_subject']);
        $settings->set('welcome_email.body', $validated['welcome_email_body']);

        return redirect()->route('admin.settings.edit', ['tab' => 'users'])->with('status', 'Welcome email template saved.');
    }

    public function testWelcomeEmail(Request $request, UserWelcomeEmailService $welcomeEmail)
    {
        $validated = $request->validate([
            'welcome_email_subject' => ['required', 'string', 'max:255'],
            'welcome_email_body' => ['required', 'string', 'max:10000'],
            'test_user_id' => ['required', 'exists:users,id'],
        ]);

        $user = User::findOrFail($validated['test_user_id']);

        try {
            $welcomeEmail->sendUsingTemplate(
                $user,
                $validated['welcome_email_subject'],
                $validated['welcome_email_body'],
            );
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('admin.settings.edit', ['tab' => 'users'])
                ->withInput()
                ->withErrors(['welcome_email_test' => 'The test welcome email could not be sent. Check the Microsoft 365 settings.']);
        }

        return redirect()
            ->route('admin.settings.edit', ['tab' => 'users'])
            ->with('status', 'Test welcome email sent to '.$user->email.'.');
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in(['admin', 'customer'])],
            'company_id' => ['nullable', 'exists:companies,id'],
        ]);

        $updates = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'company_id' => $validated['role'] === 'customer' ? ($validated['company_id'] ?? null) : null,
        ];

        if (filled($validated['password'])) {
            $updates['password'] = Hash::make($validated['password']);
        }

        $user->update($updates);

        return redirect()->route('admin.settings.edit', ['tab' => 'users'])->with('status', 'User updated.');
    }

    public function sendWelcomeEmail(User $user, UserWelcomeEmailService $welcomeEmail)
    {
        try {
            $welcomeEmail->send($user);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('admin.settings.edit', ['tab' => 'users'])
                ->withErrors(['welcome_email' => 'The welcome email could not be sent. Check the Microsoft 365 settings.']);
        }

        return redirect()->route('admin.settings.edit', ['tab' => 'users'])->with('status', 'Welcome email sent.');
    }
}
