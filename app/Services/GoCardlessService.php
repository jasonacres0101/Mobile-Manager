<?php

namespace App\Services;

use App\Models\Company;
use App\Models\GocardlessMandate;
use App\Models\Invoice;
use App\Models\Payment;
use GoCardlessPro\Client;
use GoCardlessPro\Environment;
use Illuminate\Support\Str;

class GoCardlessService
{
    public function __construct(private AppSettings $settings) {}

    public function client(): Client
    {
        return new Client([
            'access_token' => $this->accessToken(),
            'environment' => $this->environment() === 'live'
                ? Environment::LIVE
                : Environment::SANDBOX,
        ]);
    }

    public function accessToken(): ?string
    {
        return $this->settings->get('gocardless.access_token', config('services.gocardless.access_token'));
    }

    public function environment(): string
    {
        return $this->settings->get('gocardless.environment', config('services.gocardless.environment', 'sandbox'));
    }

    public function webhookSecret(): ?string
    {
        return $this->settings->get('gocardless.webhook_secret', config('services.gocardless.webhook_secret'));
    }

    public function ensureCustomer(Company $company, ?string $email = null): string
    {
        if ($company->gocardless_customer_id) {
            return $company->gocardless_customer_id;
        }

        $customer = $this->client()->customers()->create([
            'params' => [
                'company_name' => $company->name,
                'email' => $email,
            ],
            'headers' => [
                'Idempotency-Key' => "company-{$company->id}-customer",
            ],
        ]);

        $company->update(['gocardless_customer_id' => $customer->id]);

        return $customer->id;
    }

    public function createBillingRequestFlow(Company $company, string $redirectUri, ?string $email = null): array
    {
        $customerId = $this->ensureCustomer($company, $email);

        $billingRequest = $this->client()->billingRequests()->create([
            'params' => [
                'mandate_request' => [
                    'scheme' => 'bacs',
                    'currency' => 'GBP',
                ],
                'links' => [
                    'customer' => $customerId,
                ],
            ],
            'headers' => [
                'Idempotency-Key' => 'billing-request-'.$company->id.'-'.Str::uuid(),
            ],
        ]);

        $flow = $this->client()->billingRequestFlows()->create([
            'params' => [
                'redirect_uri' => $redirectUri,
                'exit_uri' => route('customer.direct-debit.setup'),
                'links' => [
                    'billing_request' => $billingRequest->id,
                ],
            ],
        ]);

        return [
            'billing_request_id' => $billingRequest->id,
            'redirect_url' => $flow->authorisation_url,
        ];
    }

    public function mandateIdFromBillingRequest(string $billingRequestId): ?string
    {
        $billingRequest = $this->client()->billingRequests()->get($billingRequestId);

        return $billingRequest->links->mandate ?? null;
    }

    public function refreshMandatesForCompany(Company $company): int
    {
        if (! $company->gocardless_customer_id) {
            return 0;
        }

        $response = $this->client()->mandates()->list([
            'params' => [
                'customer' => $company->gocardless_customer_id,
                'limit' => 100,
            ],
        ]);

        foreach ($response->records as $mandate) {
            GocardlessMandate::updateOrCreate(
                ['mandate_id' => $mandate->id],
                [
                    'company_id' => $company->id,
                    'status' => $mandate->status,
                ],
            );
        }

        return count($response->records);
    }

    public function refreshPayment(Payment $payment): Payment
    {
        $goCardlessPayment = $this->client()->payments()->get($payment->gocardless_payment_id);

        $payment->update([
            'status' => $goCardlessPayment->status ?? $payment->status,
            'charge_date' => $goCardlessPayment->charge_date ?? $payment->charge_date,
        ]);

        $payment->invoice?->update([
            'payment_status' => $payment->status,
            'gocardless_payment_id' => $payment->gocardless_payment_id,
        ]);

        return $payment->refresh();
    }

    public function refreshPaymentsForCompany(Company $company): int
    {
        return $company->payments()
            ->whereNotNull('gocardless_payment_id')
            ->get()
            ->each(fn (Payment $payment) => $this->refreshPayment($payment))
            ->count();
    }

    public function refreshAllLocalPayments(): int
    {
        return Payment::query()
            ->whereNotNull('gocardless_payment_id')
            ->get()
            ->each(fn (Payment $payment) => $this->refreshPayment($payment))
            ->count();
    }

    public function collectBlockReason(Invoice $invoice): ?string
    {
        if ($invoice->gocardless_payment_id) {
            return 'Payment already requested';
        }

        if ((float) $invoice->balance <= 0) {
            return 'No balance to collect';
        }

        $hasMandate = $invoice->company?->mandates()
            ->whereIn('status', ['active', 'submitted', 'pending_submission', 'created'])
            ->exists();

        if (! $hasMandate) {
            return 'No usable Direct Debit mandate';
        }

        return null;
    }

    public function createPaymentForInvoice(Invoice $invoice): Payment
    {
        if ($reason = $this->collectBlockReason($invoice)) {
            throw new \RuntimeException($reason);
        }

        $mandate = GocardlessMandate::where('company_id', $invoice->company_id)
            ->whereIn('status', ['active', 'submitted', 'pending_submission', 'created'])
            ->latest()
            ->firstOrFail();

        $amountInPence = (int) round(((float) $invoice->balance ?: (float) $invoice->total) * 100);

        $gcPayment = $this->client()->payments()->create([
            'params' => [
                'amount' => $amountInPence,
                'currency' => 'GBP',
                'links' => [
                    'mandate' => $mandate->mandate_id,
                ],
                'metadata' => [
                    'invoice_id' => (string) $invoice->id,
                    'connectwise_invoice_id' => (string) $invoice->connectwise_invoice_id,
                    'invoice_number' => $invoice->invoice_number,
                ],
            ],
            'headers' => [
                'Idempotency-Key' => "invoice-{$invoice->id}-payment",
            ],
        ]);

        $payment = Payment::updateOrCreate(
            ['gocardless_payment_id' => $gcPayment->id],
            [
                'company_id' => $invoice->company_id,
                'invoice_id' => $invoice->id,
                'amount' => $amountInPence / 100,
                'currency' => 'GBP',
                'status' => $gcPayment->status ?? 'created',
                'charge_date' => $gcPayment->charge_date ?? null,
            ],
        );

        $invoice->update([
            'gocardless_payment_id' => $gcPayment->id,
            'payment_status' => $gcPayment->status ?? 'created',
        ]);

        return $payment;
    }
}
