<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MicrosoftGraphMailService
{
    public function __construct(private AppSettings $settings) {}

    public function configured(): bool
    {
        return filled($this->tenantId())
            && filled($this->clientId())
            && filled($this->clientSecret())
            && filled($this->senderEmail());
    }

    public function sendHtml(string $to, string $subject, string $html): void
    {
        if (! $this->configured()) {
            throw new RuntimeException('Microsoft 365 mail settings are not configured.');
        }

        $sender = $this->senderEmail();
        $endpoint = 'https://graph.microsoft.com/v1.0/users/'.rawurlencode($sender).'/sendMail';

        $this->graph()
            ->post($endpoint, [
                'message' => [
                    'subject' => $subject,
                    'body' => [
                        'contentType' => 'HTML',
                        'content' => $html,
                    ],
                    'toRecipients' => [
                        [
                            'emailAddress' => [
                                'address' => $to,
                            ],
                        ],
                    ],
                ],
                'saveToSentItems' => true,
            ])
            ->throw();
    }

    private function graph(): PendingRequest
    {
        return Http::withToken($this->accessToken())
            ->acceptJson()
            ->asJson();
    }

    private function accessToken(): string
    {
        $tenantId = Str::of($this->tenantId())->trim('/')->toString();

        $response = Http::asForm()
            ->acceptJson()
            ->post("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token", [
                'client_id' => $this->clientId(),
                'client_secret' => $this->clientSecret(),
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ])
            ->throw()
            ->json();

        if (blank($response['access_token'] ?? null)) {
            throw new RuntimeException('Microsoft 365 did not return an access token.');
        }

        return $response['access_token'];
    }

    private function tenantId(): ?string
    {
        return $this->settings->get('microsoft365.tenant_id', config('services.microsoft365.tenant_id'));
    }

    private function clientId(): ?string
    {
        return $this->settings->get('microsoft365.client_id', config('services.microsoft365.client_id'));
    }

    private function clientSecret(): ?string
    {
        return $this->settings->get('microsoft365.client_secret', config('services.microsoft365.client_secret'));
    }

    private function senderEmail(): ?string
    {
        return $this->settings->get('microsoft365.sender_email', config('services.microsoft365.sender_email'));
    }
}
