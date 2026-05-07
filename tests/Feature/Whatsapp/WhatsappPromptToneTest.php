<?php

namespace Tests\Feature\Whatsapp;

use App\Contracts\Prompt\PromptParser;
use App\Models\User;
use App\Models\UserPhone;
use App\Models\WhatsappMessage;
use Tests\Fakes\Prompt\FakePromptParser;
use Tests\TestCase;

class WhatsappPromptToneTest extends TestCase
{
    public function test_whatsapp_supported_read_prompt_uses_more_natural_agenda_reply(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'READ',
            'confidence_score' => 0.96,
            'parse_status' => 'parsed',
            'requires_confirmation' => false,
            'entities' => [
                'entity_type' => 'task',
                'title' => null,
                'scheduled_date' => '2026-05-22',
                'scheduled_time' => null,
                'all_day' => false,
                'recurrence' => null,
                'description' => null,
                'search_query' => 'cek tanggal 22 mei jadwal apa',
            ],
        ]));

        $user = User::factory()->active()->create();
        $user->tasks()->create([
            'source_channel' => 'app_manual',
            'title' => 'Task1',
            'status' => 'pending',
            'scheduled_date' => '2026-05-22',
            'scheduled_time' => '00:00:00',
            'timezone' => 'Asia/Jakarta',
            'all_day' => false,
            'is_recurring' => false,
        ]);

        UserPhone::query()->create([
            'user_id' => $user->id,
            'phone_e164' => '+6281556796240',
            'is_verified' => true,
            'linked_for_whatsapp_at' => now(),
        ]);

        $service = app(\App\Services\Whatsapp\WhatsappWebhookService::class);
        $service->handleInbound([
            'event' => 'message',
            'payload' => [
                'id' => 'wamid-tone-1',
                'from' => '6281556796240@c.us',
                'to' => '6285182302209@c.us',
                'body' => 'Cek tanggal 22 mei jadwal apa',
                'fromMe' => false,
            ],
        ]);

        $reply = WhatsappMessage::query()
            ->where('wa_message_id', 'wamid-tone-1_reply')
            ->firstOrFail();

        $this->assertStringContainsString('Jadwal kamu di tanggal 2026-05-22', $reply->message_text);
        $this->assertStringContainsString('1. Task1 - 00:00:00', $reply->message_text);
    }

    public function test_whatsapp_unsupported_prompt_uses_more_helpful_reply(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'READ',
            'confidence_score' => 0.2,
            'parse_status' => 'unsupported',
            'requires_confirmation' => false,
            'entities' => [
                'entity_type' => 'task',
                'title' => null,
                'scheduled_date' => null,
                'scheduled_time' => null,
                'all_day' => false,
                'recurrence' => null,
                'description' => null,
                'search_query' => null,
            ],
        ]));

        $user = User::factory()->active()->create();

        UserPhone::query()->create([
            'user_id' => $user->id,
            'phone_e164' => '+6281556796241',
            'is_verified' => true,
            'linked_for_whatsapp_at' => now(),
        ]);

        $service = app(\App\Services\Whatsapp\WhatsappWebhookService::class);
        $service->handleInbound([
            'event' => 'message',
            'payload' => [
                'id' => 'wamid-tone-2',
                'from' => '6281556796241@c.us',
                'to' => '6285182302209@c.us',
                'body' => 'Bisa baca gambar?',
                'fromMe' => false,
            ],
        ]);

        $reply = WhatsappMessage::query()
            ->where('wa_message_id', 'wamid-tone-2_reply')
            ->firstOrFail();

        $this->assertStringContainsString('Aku belum bisa bantu untuk permintaan itu', $reply->message_text);
        $this->assertStringContainsString('Coba minta cek jadwal', $reply->message_text);
    }
}
