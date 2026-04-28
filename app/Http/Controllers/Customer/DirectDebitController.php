<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\GocardlessMandate;
use App\Services\GoCardlessService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class DirectDebitController extends Controller
{
    public function setup(Request $request, GoCardlessService $goCardless)
    {
        $company = $request->user()->company;
        abort_unless($company, 403);

        if ($request->boolean('start')) {
            $callbackToken = Str::uuid()->toString();
            $flow = $goCardless->createBillingRequestFlow(
                $company,
                route('customer.direct-debit.callback', ['token' => $callbackToken]),
                $request->user()->email,
            );

            $request->session()->put('gocardless_billing_request_id', $flow['billing_request_id']);
            Cache::put($this->billingRequestCacheKey($company->id), $flow['billing_request_id'], now()->addDay());
            Cache::put($this->callbackTokenCacheKey($callbackToken), [
                'company_id' => $company->id,
                'billing_request_id' => $flow['billing_request_id'],
            ], now()->addDay());

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
        $user = $request->user();
        $company = $user?->company;
        $billingRequestId = $request->session()->pull('gocardless_billing_request_id');

        if (! $billingRequestId && $company) {
            $billingRequestId = Cache::pull($this->billingRequestCacheKey($company->id));
        }

        if ((! $company || ! $billingRequestId) && $request->filled('token')) {
            $payload = Cache::pull($this->callbackTokenCacheKey((string) $request->string('token')));

            if (is_array($payload)) {
                $company ??= Company::find(data_get($payload, 'company_id'));
                $billingRequestId ??= data_get($payload, 'billing_request_id');
            }
        }

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

    private function billingRequestCacheKey(int $companyId): string
    {
        return "company:{$companyId}:gocardless_billing_request_id";
    }

    private function callbackTokenCacheKey(string $token): string
    {
        return "gocardless:callback-token:{$token}";
    }
}
