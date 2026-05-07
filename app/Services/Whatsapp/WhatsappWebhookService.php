<?php

namespace App\Services\Whatsapp;

use App\Models\UserPhone;
use App\Models\WhatsappMessage;
use App\Services\Prompt\PromptCommandService;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookService
{
    public function __construct(
        private readonly PromptCommandService $promptCommandService,
        private readonly WhatsappSenderService $senderService,
    ) {}

    /**
     * @param  array<string, mixed>  $webhookPayload
     */
    public function handleInbound(array $webhookPayload): void
    {
        $entries = $webhookPayload['entry'] ?? [];

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];
            foreach ($changes as $change) {
                $messages = $change['value']['messages'] ?? [];
                foreach ($messages as $message) {
                    $this->processMessage($message, $webhookPayload);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $message
     * @param  array<string, mixed>  $webhookPayload
     */
    private function processMessage(array $message, array $webhookPayload): void
    {
        $waMessageId = $message['id'] ?? null;
        $senderRaw = $message['from'] ?? null;
        $text = $message['text']['body'] ?? null;

        if ($waMessageId === null || $senderRaw === null || $text === null) {
            return;
        }

        $existing = WhatsappMessage::query()->where('wa_message_id', $waMessageId)->exists();
        if ($existing) {
            Log::info('Duplicate WhatsApp message ignored.', ['wa_message_id' => $waMessageId]);

            return;
        }

        $senderE164 = PhoneNumber::normalize($senderRaw);
        $botPhoneId = config('services.whatsapp.phone_number_id', 'bot');

        $userPhone = UserPhone::query()
            ->where('phone_e164', $senderE164)
            ->where('is_verified', true)
            ->whereNotNull('linked_for_whatsapp_at')
            ->first();

        if ($userPhone === null) {
            WhatsappMessage::query()->create([
                'user_id' => null,
                'direction' => 'inbound',
                'wa_message_id' => $waMessageId,
                'sender_phone_e164' => $senderE164,
                'recipient_phone_e164' => $botPhoneId,
                'message_text' => $text,
                'webhook_payload' => $webhookPayload,
                'processing_status' => 'failed',
            ]);

            $this->senderService->send(
                $senderRaw,
                'Nomor kamu belum terhubung ke akun Zaid. Silakan login dulu di aplikasi, lalu verifikasi nomor HP kamu untuk mulai pakai WhatsApp assistant.',
            );

            return;
        }

        $user = $userPhone->user;

        $inbound = WhatsappMessage::query()->create([
            'user_id' => $user->id,
            'direction' => 'inbound',
            'wa_message_id' => $waMessageId,
            'sender_phone_e164' => $senderE164,
            'recipient_phone_e164' => $botPhoneId,
            'message_text' => $text,
            'webhook_payload' => $webhookPayload,
            'processing_status' => 'parsed',
        ]);

        $result = $this->promptCommandService->process($user, $text, 'whatsapp');

        $inbound->update([
            'prompt_request_id' => $result['prompt_request_id'] ?? null,
            'processing_status' => 'executed',
        ]);

        $replyText = $result['human_response'] ?? 'Siap, perintah sudah diproses.';

        WhatsappMessage::query()->create([
            'user_id' => $user->id,
            'prompt_request_id' => $result['prompt_request_id'] ?? null,
            'direction' => 'outbound',
            'wa_message_id' => $waMessageId.'_reply',
            'sender_phone_e164' => $botPhoneId,
            'recipient_phone_e164' => $senderE164,
            'message_text' => $replyText,
            'processing_status' => 'replied',
        ]);

        $this->senderService->send($senderRaw, $replyText);
    }
}
