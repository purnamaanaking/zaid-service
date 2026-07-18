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
        private readonly WhatsappAgentService $agentService,
    ) {}

    /**
     * @param  array<string, mixed>  $webhookPayload
     */
    public function handleInbound(array $webhookPayload): void
    {
        $driver = config('services.whatsapp.driver', 'waha');

        if ($driver === 'waha') {
            $this->handleWahaInbound($webhookPayload);

            return;
        }

        $this->handleMetaInbound($webhookPayload);
    }

    /**
     * @param  array<string, mixed>  $webhookPayload
     */
    private function handleMetaInbound(array $webhookPayload): void
    {
        $entries = $webhookPayload['entry'] ?? [];

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];
            foreach ($changes as $change) {
                $messages = $change['value']['messages'] ?? [];
                foreach ($messages as $message) {
                    $this->processNormalizedMessage([
                        'id' => $message['id'] ?? null,
                        'from' => $message['from'] ?? null,
                        'to' => config('services.whatsapp.phone_number_id', 'bot'),
                        'text' => $message['text']['body'] ?? null,
                        'source' => 'meta',
                        'has_media' => false,
                        'media' => null,
                        'attachments' => null,
                    ], $webhookPayload);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $webhookPayload
     */
    private function handleWahaInbound(array $webhookPayload): void
    {
        $event = $webhookPayload['event'] ?? null;
        $payload = $webhookPayload['payload'] ?? null;

        if (! in_array($event, ['message', 'message.any'], true) || ! is_array($payload)) {
            return;
        }

        if (($payload['fromMe'] ?? false) === true || ($payload['source'] ?? null) === 'api') {
            return;
        }

        $attachments = [];
        if (($payload['hasMedia'] ?? false) === true && isset($payload['media']) && is_array($payload['media'])) {
            $media = $payload['media'];
            $mime = $media['mimetype'] ?? null;
            $url = $media['url'] ?? null;

            if ($url && is_string($mime) && str_starts_with($mime, 'image/')) {
                $dataUrl = app(WahaApiService::class)->downloadMediaAsDataUrl($url, $mime);

                $attachments[] = [
                    'type' => 'image',
                    'url' => $url,
                    'data_url' => $dataUrl,
                    'mime_type' => $mime,
                ];
            }
        }

        $this->processNormalizedMessage([
            'id' => $payload['id'] ?? null,
            'from' => $payload['_data']['key']['remoteJidAlt'] ?? $payload['from'] ?? null,
            'to' => $payload['to'] ?? ($webhookPayload['me']['id'] ?? 'bot'),
            'text' => $payload['body'] ?? '',
            'source' => 'waha',
            'has_media' => ! empty($attachments),
            'media' => $payload['media'] ?? null,
            'attachments' => $attachments ?: null,
        ], $webhookPayload);
    }

    /**
     * @param  array{
     *   id: string|null,
     *   from: string|null,
     *   to: string|null,
     *   text: string|null,
     *   source: string,
     *   has_media: bool,
     *   media: mixed,
     *   attachments: array<int, array<string, mixed>>|null
     * }  $message
     * @param  array<string, mixed>  $webhookPayload
     */
    private function processNormalizedMessage(array $message, array $webhookPayload): void
    {
        $waMessageId = $message['id'];
        $senderRaw = $message['from'];
        $text = $message['text'] ?? '';

        if ($waMessageId === null || $senderRaw === null) {
            return;
        }

        $recipient = $message['to'] ?? 'bot';
        $senderE164 = $this->resolveSenderPhoneE164($senderRaw);
        $userPhone = $this->findVerifiedLinkedUserPhone($senderE164);

        if ($userPhone === null) {
            $inserted = WhatsappMessage::query()->insertOrIgnore([
                'id' => (string) str()->uuid(),
                'user_id' => null,
                'direction' => 'inbound',
                'wa_message_id' => $waMessageId,
                'sender_phone_e164' => $senderE164,
                'recipient_phone_e164' => (string) $recipient,
                'message_text' => $text,
                'webhook_payload' => json_encode($webhookPayload, JSON_THROW_ON_ERROR),
                'processing_status' => 'failed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted === 0) {
                Log::info('Duplicate WhatsApp message ignored.', ['wa_message_id' => $waMessageId]);

                return;
            }

            $this->senderService->send(
                $senderE164,
                'Nomor ini belum terdaftar di Zaid. Daftar dulu lewat aplikasi atau browser: https://zaidassistant.id/ . Setelah masuk, verifikasi nomor WhatsApp kamu. Kalau sudah terverifikasi, chat nomor ini lagi untuk mulai pakai Zaid.',
            );

            return;
        }

        $user = $userPhone->user;

        $inserted = WhatsappMessage::query()->insertOrIgnore([
            'id' => (string) str()->uuid(),
            'user_id' => $user->id,
            'direction' => 'inbound',
            'wa_message_id' => $waMessageId,
            'sender_phone_e164' => $senderE164,
            'recipient_phone_e164' => (string) $recipient,
            'message_text' => $text,
            'webhook_payload' => json_encode($webhookPayload, JSON_THROW_ON_ERROR),
            'processing_status' => 'parsed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($inserted === 0) {
            Log::info('Duplicate WhatsApp message ignored.', ['wa_message_id' => $waMessageId]);

            return;
        }

        $inbound = WhatsappMessage::query()->where('wa_message_id', $waMessageId)->firstOrFail();

        $result = $this->agentService->handle(
            $user,
            $text,
            'whatsapp',
            $message['attachments'],
        );

        $inbound->update([
            'prompt_request_id' => $result['prompt_request_id'] ?? null,
            'processing_status' => 'executed',
        ]);

        $replyText = $result['human_response'] ?? 'Siap, aku bantu proses ya.';

        WhatsappMessage::query()->create([
            'user_id' => $user->id,
            'prompt_request_id' => $result['prompt_request_id'] ?? null,
            'direction' => 'outbound',
            'wa_message_id' => $waMessageId.'_reply',
            'sender_phone_e164' => (string) $recipient,
            'recipient_phone_e164' => $senderE164,
            'message_text' => $replyText,
            'processing_status' => 'replied',
        ]);

        $this->senderService->send($senderE164, $replyText);
    }

    private function resolveSenderPhoneE164(string $senderRaw): string
    {
        if (str_ends_with($senderRaw, '@lid')) {
            $resolved = app(WahaApiService::class)->resolvePhoneNumberFromLid($senderRaw);

            if ($resolved !== null) {
                return PhoneNumber::normalize($resolved);
            }
        }

        return PhoneNumber::normalize($senderRaw);
    }

    private function findVerifiedLinkedUserPhone(string $senderE164): ?UserPhone
    {
        return UserPhone::query()
            ->where('phone_e164', $senderE164)
            ->where('is_verified', true)
            ->whereNotNull('linked_for_whatsapp_at')
            ->first();
    }


}
