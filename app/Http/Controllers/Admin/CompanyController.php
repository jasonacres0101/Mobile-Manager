<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\GoCardlessService;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->input('sort', 'name');
        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';
        $perPage = min((int) $request->input('per_page', 25), 100);
        $sortable = ['name', 'connectwise_company_id', 'agreements_count', 'sims_count', 'invoices_count'];

        if (! in_array($sort, $sortable, true)) {
            $sort = 'name';
        }

        return view('admin.companies.index', [
            'companies' => Company::query()
                ->withCount(['agreements', 'sims', 'invoices'])
                ->when($request->filled('q'), function ($query) use ($request) {
                    $search = $request->input('q');

                    $query->where(function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('connectwise_company_id', 'like', "%{$search}%")
                            ->orWhere('gocardless_customer_id', 'like', "%{$search}%")
                            ->orWhere('mobilemanager_customer_id', 'like', "%{$search}%");
                    });
                })
                ->orderBy($sort, $direction)
                ->paginate($perPage)
                ->withQueryString(),
            'filters' => $request->only(['q', 'sort', 'direction', 'per_page']),
        ]);
    }

    public function show(Company $company)
    {
        $company->load([
            'agreements.invoices',
            'agreements.sims',
            'sims.agreement',
            'invoices.payments',
            'payments.invoice',
            'mandates',
            'users',
            'jolaCustomer',
        ]);

        return view('admin.companies.show', [
            'company' => $company,
            'jolaSims' => $company->sims->filter(fn ($sim) => filled($sim->mobilemanager_sim_id)),
        ]);
    }

    public function updateAutoCollect(Request $request, Company $company)
    {
        $validated = $request->validate([
            'auto_collect_enabled' => ['nullable', 'boolean'],
            'auto_collect_days_before_due' => ['required', 'integer', 'min:-30', 'max:30'],
            'auto_collect_min_balance' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'auto_collect_max_amount' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
        ]);

        $company->update([
            'auto_collect_enabled' => $request->boolean('auto_collect_enabled'),
            'auto_collect_days_before_due' => $validated['auto_collect_days_before_due'],
            'auto_collect_min_balance' => $validated['auto_collect_min_balance'],
            'auto_collect_max_amount' => $validated['auto_collect_max_amount'] ?? null,
        ]);

        return redirect()->route('admin.companies.show', $company)->with('status', 'Auto collection settings saved.');
    }

    public function refreshGoCardlessMandates(Company $company, GoCardlessService $goCardless)
    {
        $count = $goCardless->refreshMandatesForCompany($company);

        return redirect()
            ->route('admin.companies.show', $company)
            ->with('status', "Refreshed {$count} GoCardless mandate(s).");
    }

    public function refreshGoCardlessPayments(Company $company, GoCardlessService $goCardless)
    {
        $count = $goCardless->refreshPaymentsForCompany($company);

        return redirect()
            ->route('admin.companies.show', $company)
            ->with('status', "Refreshed {$count} GoCardless payment(s).");
    }
}
