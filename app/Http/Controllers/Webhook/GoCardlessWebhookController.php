<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\GocardlessMandate;
use App\Models\Payment;
use App\Models\WebhookEvent;
use App\Services\GoCardlessService;
use GoCardlessPro\Core\Exception\InvalidSignatureException;
use GoCardlessPro\Webhook;
use Illuminate\Http\Request;

class GoCardlessWebhookController extends Controller
{
    public function __invoke(Request $request, GoCardlessService $goCardless)
    {
        $body = $request->getContent();
        $signature = $request->header('Webhook-Signature', '');
        $secret = $goCardless->webhookSecret();

        try {
            Webhook::parse($body, $signature, $secret);
        } catch (InvalidSignatureException) {
            abort(401, 'Invalid GoCardless webhook signature.');
        }

        $payload = json_decode($body, true) ?: [];

        foreach (($payload['events'] ?? []) as $event) {
            $stored = WebhookEvent::firstOrCreate(
                ['provider' => 'gocardless', 'event_id' => $event['id'] ?? null],
                ['payload' => $event],
            );

            if ($stored->processed_at) {
                continue;
            }

            $this->applyEvent($event);
            $stored->update(['processed_at' => now()]);
        }

        return response()->json(['status' => 'ok']);
    }

    private function applyEvent(array $event): void
    {
        $action = $event['action'] ?? null;
        $links = $event['links'] ?? [];

        if (! empty($links['payment'])) {
            $payment = Payment::where('gocardless_payment_id', $links['payment'])->first();

            if ($payment) {
                $payment->update(['status' => $action]);
                $payment->invoice?->update(['payment_status' => $action]);
            }
        }

        if (! empty($links['mandate'])) {
            $company = ! empty($links['customer'])
                ? Company::where('gocardless_customer_id', $links['customer'])->first()
                : null;

            $mandate = GocardlessMandate::where('mandate_id', $links['mandate'])->first();

            if ($mandate || $company) {
                GocardlessMandate::updateOrCreate(
                    ['mandate_id' => $links['mandate']],
                    [
                        'company_id' => $mandate?->company_id ?? $company?->id,
                        'status' => $action,
                    ],
                );
            }
        }
    }
}
