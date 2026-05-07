<?php

namespace App\Services\Whatsapp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaApiService
{
    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $apiKey = config('services.waha.api_key');
        if (! empty($apiKey)) {
            $headers['X-Api-Key'] = $apiKey;
        }

        return $headers;
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.waha.base_url', ''), '/');
    }

    private function sessionName(): string
    {
        return (string) config('services.waha.session', 'default');
    }

    /**
     * @return array<string, mixed>
     */
    public function listSessions(): array
    {
        $response = Http::withHeaders($this->headers())
            ->get($this->baseUrl().'/api/sessions');

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'data' => $response->json() ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getSession(?string $session = null): array
    {
        $session = $session ?: $this->sessionName();

        $response = Http::withHeaders($this->headers())
            ->get($this->baseUrl().'/api/sessions/'.$session);

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'data' => $response->json() ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createOrStartSession(?string $webhookUrl = null): array
    {
        $session = $this->sessionName();
        $payload = [
            'name' => $session,
        ];

        if ($webhookUrl) {
            $payload['config'] = [
                'webhooks' => [
                    [
                        'url' => $webhookUrl,
                        'events' => ['message', 'message.any'],
                        'hmac' => empty(config('services.waha.webhook_secret')) ? null : [
                            'key' => config('services.waha.webhook_secret'),
                        ],
                    ],
                ],
            ];
        }

        // remove null hmac if no secret
        if (isset($payload['config']['webhooks'][0]['hmac']) && $payload['config']['webhooks'][0]['hmac'] === null) {
            unset($payload['config']['webhooks'][0]['hmac']);
        }

        $response = Http::withHeaders($this->headers())
            ->post($this->baseUrl().'/api/sessions', $payload);

        if (! $response->successful() && $response->status() !== 409) {
            Log::error('WAHA session create/start failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return [
            'ok' => $response->successful() || $response->status() === 409,
            'status' => $response->status(),
            'data' => $response->json() ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getQr(?string $session = null): array
    {
        $session = $session ?: $this->sessionName();

        $response = Http::withHeaders($this->headers())
            ->get($this->baseUrl().'/api/'.$session.'/auth/qr');

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'data' => $response->json() ?? [],
        ];
    }

    public function resolvePhoneNumberFromLid(string $lid, ?string $session = null): ?string
    {
        $session = $session ?: $this->sessionName();
        $lookup = rawurlencode($lid);

        $response = Http::withHeaders($this->headers())
            ->get($this->baseUrl().'/api/'.$session.'/lids/'.$lookup);

        if (! $response->successful()) {
            return null;
        }

        $phoneNumber = $response->json('pn');

        return is_string($phoneNumber) && $phoneNumber !== ''
            ? preg_replace('/@c\.us$/', '', $phoneNumber)
            : null;
    }
}
