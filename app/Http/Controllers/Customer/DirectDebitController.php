<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\GocardlessMandate;
use App\Services\GoCardlessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class DirectDebitController extends Controller
{
    public function setup(Request $request, GoCardlessService $goCardless)
    {
        $company = $request->user()->company;
        abort_unless($company, 403);

        if ($request->boolean('start')) {
            $flow = $goCardless->createBillingRequestFlow(
                $company,
                fn (string $billingRequestId) => URL::temporarySignedRoute(
                    'customer.direct-debit.callback',
                    now()->addDay(),
                    [
                        'company' => $company,
                        'billing_request_id' => $billingRequestId,
                    ],
                ),
                $request->user()->email,
            );

            $company->update([
                'gocardless_billing_request_id' => $flow['billing_request_id'],
            ]);

            return redirect()->away($flow['redirect_url']);
        }

        return view('customer.direct-debit.setup', [
            'company' => $company,
            'mandate' => $company->currentMandate(),
            'payments' => $company->payments()->with('invoice')->latest()->take(10)->get(),
        ]);
    }

    public function refresh(Request $request, GoCardlessService $goCardless)
    {
        $company = $request->user()->company;
        abort_unless($company, 403);

        try {
            $mandates = $goCardless->refreshMandatesForCompany($company);
            $payments = $goCardless->refreshPaymentsForCompany($company);
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('customer.direct-debit.setup')
                ->withErrors([
                    'direct_debit_refresh' => 'Unable to refresh GoCardless status right now. Please try again in a moment.',
                ]);
        }

        return redirect()
            ->route('customer.direct-debit.setup')
            ->with('status', "Refreshed {$mandates} mandate(s) and {$payments} payment(s).");
    }

    public function callback(Request $request, Company $company, GoCardlessService $goCardless)
    {
        $user = $request->user();
        $billingRequestId = (string) $request->string('billing_request_id');

        if ($company && $billingRequestId) {
            $summary = $goCardless->billingRequestSummary($billingRequestId);
            $mandateId = $summary['mandate_id'] ?? null;

            if (! $company->gocardless_customer_id && ! empty($summary['customer_id'])) {
                $company->update(['gocardless_customer_id' => $summary['customer_id']]);
            }

            if ($mandateId) {
                $mandate = GocardlessMandate::updateOrCreate(
                    ['mandate_id' => $mandateId],
                    [
                        'company_id' => $company->id,
                        'status' => 'created',
                    ],
                );

                try {
                    $goCardless->refreshMandate($mandate);
                } catch (\Throwable) {
                    // Some mandates are not immediately queryable on return; manual refresh/webhooks remain a fallback.
                }

            }
        }

        $status = 'Direct Debit setup returned from GoCardless. Use refresh status if the mandate is not shown yet.';

        if ($user?->company_id === $company?->id) {
            return redirect()
                ->route('customer.direct-debit.setup')
                ->with('status', $status);
        }

        return redirect()
            ->route('login')
            ->with('status', $status);
    }
}
