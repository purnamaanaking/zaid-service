<?php

namespace App\Services\Whatsapp;

use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappSenderService
{
    public function send(string $recipientPhone, string $text): bool
    {
        $driver = config('services.whatsapp.driver', 'waha');

        if ($driver === 'waha') {
            return $this->sendViaWaha($recipientPhone, $text);
        }

        return $this->sendViaMeta($recipientPhone, $text);
    }

    private function sendViaMeta(string $recipientPhone, string $text): bool
    {
        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $accessToken = config('services.whatsapp.access_token');

        if (empty($phoneNumberId) || empty($accessToken)) {
            Log::warning('Meta WhatsApp sender not configured. Message not sent.', [
                'recipient' => $recipientPhone,
                'text' => $text,
            ]);

            return false;
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
            Log::error('Meta WhatsApp send failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
                'recipient' => $recipientPhone,
            ]);

            return false;
        }

        return true;
    }

    private function sendViaWaha(string $recipientPhone, string $text): bool
    {
        $baseUrl = rtrim((string) config('services.waha.base_url'), '/');
        $session = (string) config('services.waha.session', 'default');
        $apiKey = config('services.waha.api_key');

        if ($baseUrl === '') {
            Log::warning('WAHA sender not configured. Message not sent.', [
                'recipient' => $recipientPhone,
                'text' => $text,
            ]);

            return false;
        }

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if (! empty($apiKey)) {
            $headers['X-Api-Key'] = $apiKey;
        }

        $chatId = preg_replace('/\D/', '', PhoneNumber::normalize($recipientPhone)).'@c.us';

        $response = Http::withHeaders($headers)
            ->post("{$baseUrl}/api/sendText", [
                'session' => $session,
                'chatId' => $chatId,
                'text' => $text,
            ]);

        if (! $response->successful()) {
            Log::error('WAHA send failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
                'recipient' => $recipientPhone,
                'chat_id' => $chatId,
            ]);

            return false;
        }

        return true;
    }
}
