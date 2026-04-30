<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $company = $request->user()->company;

        abort_unless($company, 403);

        return view('customer.dashboard', [
            'company' => $company,
            'simCount' => $company->sims()->count(),
            'fibreCount' => $company->fibreConnections()->count(),
            'invoiceCount' => $company->invoices()->count(),
            'openBalance' => $company->invoices()->sum('balance'),
            'mandate' => $company->currentMandate(),
            'recentInvoices' => $company->invoices()->with('payments')->latest()->take(5)->get(),
            'recentSims' => $company->sims()->latest()->take(5)->get(),
            'recentFibreConnections' => $company->fibreConnections()->latest()->take(5)->get(),
            'nextPayment' => $company->payments()->whereNotNull('charge_date')->orderBy('charge_date')->first(),
        ]);
    }
}
