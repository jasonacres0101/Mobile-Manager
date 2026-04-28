<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agreement;
use App\Models\AppSetting;
use App\Models\Company;
use App\Models\FibreConnection;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Sim;
use App\Services\GoCardlessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $matchedSims = Sim::whereNotNull('connectwise_addition_id')->whereNotNull('mobilemanager_sim_id')->count();
        $connectWiseOnlySims = Sim::whereNotNull('connectwise_addition_id')->whereNull('mobilemanager_sim_id')->count();
        $jolaOnlySims = Sim::whereNull('connectwise_addition_id')->whereNotNull('mobilemanager_sim_id')->count();

        return view('admin.dashboard', [
            'companyCount' => Company::count(),
            'agreementCount' => Agreement::count(),
            'simCount' => Sim::count(),
            'fibreCount' => FibreConnection::count(),
            'invoiceCount' => Invoice::count(),
            'paymentCount' => Payment::count(),
            'openBalance' => Invoice::sum('balance'),
            'pendingPayments' => Payment::whereIn('status', ['created', 'pending_submission', 'submitted'])->count(),
            'matchedSims' => $matchedSims,
            'connectWiseOnlySims' => $connectWiseOnlySims,
            'jolaOnlySims' => $jolaOnlySims,
            'statuses' => [
                'connectwise' => [
                    'label' => 'ConnectWise PSA',
                    'status' => AppSetting::getValue('connectwise.last_test_status'),
                    'message' => AppSetting::getValue('connectwise.last_test_message'),
                    'synced_at' => AppSetting::getValue('connectwise.last_manual_sync_at'),
                    'sync_message' => AppSetting::getValue('connectwise.last_manual_sync_message'),
                ],
                'jola' => [
                    'label' => 'Jola',
                    'status' => AppSetting::getValue('mobilemanager.last_test_status'),
                    'message' => AppSetting::getValue('mobilemanager.last_test_message'),
                    'synced_at' => AppSetting::getValue('jola.last_manual_sync_at'),
                    'sync_message' => AppSetting::getValue('jola.last_manual_sync_message'),
                ],
                'gocardless' => [
                    'label' => 'GoCardless',
                    'status' => filled(config('services.gocardless.access_token')) || AppSetting::getValue('gocardless.access_token') ? 'configured' : null,
                    'message' => 'Local testing can refresh mandates and payments without webhooks.',
                    'synced_at' => AppSetting::getValue('gocardless.last_manual_refresh_at'),
                    'sync_message' => AppSetting::getValue('gocardless.last_manual_refresh_message'),
                ],
            ],
        ]);
    }

    public function sync(Request $request, GoCardlessService $goCardless)
    {
        $validated = $request->validate([
            'sync_type' => ['required', 'in:connectwise_agreements,connectwise_invoices,jola_customers,jola_sims,jola_products,gocardless_payments'],
        ]);

        try {
            $message = match ($validated['sync_type']) {
                'connectwise_agreements' => $this->runCommand('sync:connectwise-sim-agreements', ['--now' => true], 'connectwise'),
                'connectwise_invoices' => $this->runCommand('sync:connectwise-invoices', ['--now' => true], 'connectwise'),
                'jola_customers' => $this->runCommand('sync:jola-customers', [], 'jola'),
                'jola_sims' => $this->runCommand('sync:jola-sims', [], 'jola'),
                'jola_products' => $this->runCommand('sync:jola-products', [], 'jola'),
                'gocardless_payments' => $this->refreshGoCardless($goCardless),
            };

            return redirect()->route('admin.dashboard')->with('status', $message);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('admin.dashboard')
                ->withErrors(['sync' => $exception->getMessage()]);
        }
    }

    private function runCommand(string $command, array $parameters, string $provider): string
    {
        Artisan::call($command, $parameters);

        $message = trim(Artisan::output()) ?: 'Sync completed.';
        AppSetting::setValue("{$provider}.last_manual_sync_message", $message);
        AppSetting::setValue("{$provider}.last_manual_sync_at", now()->toDateTimeString());

        return $message;
    }

    private function refreshGoCardless(GoCardlessService $goCardless): string
    {
        $mandates = Company::query()
            ->whereNotNull('gocardless_customer_id')
            ->get()
            ->sum(fn (Company $company) => $goCardless->refreshMandatesForCompany($company));
        $payments = $goCardless->refreshAllLocalPayments();
        $message = "Refreshed {$mandates} mandate(s) and {$payments} payment(s).";

        AppSetting::setValue('gocardless.last_manual_refresh_message', $message);
        AppSetting::setValue('gocardless.last_manual_refresh_at', now()->toDateTimeString());

        return $message;
    }
}
