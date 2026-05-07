<?php

namespace App\Services\Whatsapp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappSenderService
{
    public function send(string $recipientPhone, string $text): void
    {
        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $accessToken = config('services.whatsapp.access_token');

        if (empty($phoneNumberId) || empty($accessToken)) {
            Log::warning('WhatsApp sender not configured. Message not sent.', [
                'recipient' => $recipientPhone,
                'text' => $text,
            ]);

            return;
        }

        $response = Http::withToken($accessToken)
            ->post("https://graph.facebook.com/v18.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => preg_replace('/\D/', '', $recipientPhone),
                'type' => 'text',
                'text' => [
                    'body' => $text,
                ],
            ]);

        if (! $response->successful()) {
            Log::error('WhatsApp send failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
                'recipient' => $recipientPhone,
            ]);
        }
    }
}
