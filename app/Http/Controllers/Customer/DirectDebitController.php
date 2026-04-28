<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\GocardlessMandate;
use App\Services\GoCardlessService;
use Illuminate\Http\Request;

class DirectDebitController extends Controller
{
    public function setup(Request $request, GoCardlessService $goCardless)
    {
        $company = $request->user()->company;
        abort_unless($company, 403);

        if ($request->boolean('start')) {
            $flow = $goCardless->createBillingRequestFlow(
                $company,
                route('customer.direct-debit.callback'),
                $request->user()->email,
            );

            $request->session()->put('gocardless_billing_request_id', $flow['billing_request_id']);

            return redirect()->away($flow['redirect_url']);
        }

        return view('customer.direct-debit.setup', [
            'company' => $company,
            'mandate' => $company->mandates()->latest()->first(),
            'payments' => $company->payments()->with('invoice')->latest()->take(10)->get(),
        ]);
    }

    public function refresh(Request $request, GoCardlessService $goCardless)
    {
        $company = $request->user()->company;
        abort_unless($company, 403);

        $mandates = $goCardless->refreshMandatesForCompany($company);
        $payments = $goCardless->refreshPaymentsForCompany($company);

        return redirect()
            ->route('customer.direct-debit.setup')
            ->with('status', "Refreshed {$mandates} mandate(s) and {$payments} payment(s).");
    }

    public function callback(Request $request, GoCardlessService $goCardless)
    {
        $company = $request->user()->company;
        $billingRequestId = $request->session()->pull('gocardless_billing_request_id');

        if ($company && $billingRequestId) {
            $summary = $goCardless->billingRequestSummary($billingRequestId);
            $mandateId = $summary['mandate_id'] ?? null;

            if (! $company->gocardless_customer_id && ! empty($summary['customer_id'])) {
                $company->update(['gocardless_customer_id' => $summary['customer_id']]);
            }

            if ($mandateId) {
                GocardlessMandate::updateOrCreate(
                    ['mandate_id' => $mandateId],
                    [
                        'company_id' => $company->id,
                        'status' => 'created',
                    ],
                );
            }
        }

        return redirect()
            ->route('customer.direct-debit.setup')
            ->with('status', 'Direct Debit setup returned from GoCardless. Use refresh status if the mandate is not shown yet.');
    }
}
