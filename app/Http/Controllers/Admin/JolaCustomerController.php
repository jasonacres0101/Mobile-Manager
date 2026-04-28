<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JolaCustomer;
use App\Services\MobileManagerService;
use App\Services\AppSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class JolaCustomerController extends Controller
{
    public function index()
    {
        return view('admin.jola-customers.index', [
            'customers' => JolaCustomer::with('company')->latest('last_synced_at')->paginate(25),
        ]);
    }

    public function show(JolaCustomer $jolaCustomer, MobileManagerService $mobileManager)
    {
        $liveCustomer = null;
        $customerSims = collect();
        $error = null;

        try {
            $liveCustomer = $mobileManager->getCustomer($jolaCustomer->mobilemanager_customer_id);
            $customerSims = collect($mobileManager->getCustomerSims($jolaCustomer->mobilemanager_customer_id));
        } catch (Throwable $exception) {
            report($exception);

            $error = 'Unable to load live Jola customer details. Showing cached data only.';
        }

        return view('admin.jola-customers.show', [
            'customer' => $jolaCustomer,
            'liveCustomer' => $liveCustomer,
            'customerSims' => $customerSims,
            'error' => $error,
        ]);
    }

    public function showSim(JolaCustomer $jolaCustomer, string $jolaSimId, MobileManagerService $mobileManager, AppSettings $settings)
    {
        $liveSim = null;
        $error = null;

        try {
            $liveSim = $mobileManager->getSim($jolaSimId);
        } catch (Throwable $exception) {
            report($exception);

            $error = 'Unable to load live Jola SIM details.';
        }

        return view('admin.jola-customers.sim-show', [
            'customer' => $jolaCustomer,
            'jolaSimId' => $jolaSimId,
            'liveSim' => $liveSim,
            'error' => $error,
            'cdrExportFolder' => $settings->get('mobilemanager.cdr_export_folder'),
        ]);
    }

    public function sync(Request $request)
    {
        try {
            Artisan::call('sync:jola-customers');

            return redirect()
                ->route('admin.jola-customers.index')
                ->with('status', trim(Artisan::output()) ?: 'Jola customers synced.');
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('admin.jola-customers.index')
                ->withErrors(['jola_customers' => 'Unable to sync Jola customers. Check the Jola settings and connection status.']);
        }
    }
}
