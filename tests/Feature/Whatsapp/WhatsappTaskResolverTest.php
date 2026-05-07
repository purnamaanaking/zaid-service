<?php

namespace Tests\Feature\Whatsapp;

use App\Contracts\Prompt\PromptParser;
use App\Models\Task;
use App\Models\User;
use App\Models\UserPhone;
use App\Models\WhatsappMessage;
use Tests\Fakes\Prompt\FakePromptParser;
use Tests\TestCase;

class WhatsappTaskResolverTest extends TestCase
{
    public function test_whatsapp_update_with_single_clear_match_executes_without_confirmation(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'UPDATE',
            'confidence_score' => 0.92,
            'parse_status' => 'ambiguous',
            'requires_confirmation' => false,
            'entities' => [
                'entity_type' => 'task',
                'title' => 'Meeting',
                'scheduled_date' => null,
                'scheduled_time' => '22:00:00',
                'all_day' => false,
                'recurrence' => null,
                'description' => null,
                'search_query' => 'meeting 21:30',
            ],
        ]));

        $user = User::factory()->active()->create();
        UserPhone::query()->create([
            'user_id' => $user->id,
            'phone_e164' => '+6281556796243',
            'is_verified' => true,
            'linked_for_whatsapp_at' => now(),
        ]);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Meeting',
            'status' => 'pending',
            'scheduled_date' => '2026-05-07',
            'scheduled_time' => '21:30:00',
            'timezone' => 'Asia/Jakarta',
        ]);

        app(\App\Services\Whatsapp\WhatsappWebhookService::class)->handleInbound([
            'event' => 'message',
            'payload' => [
                'id' => 'wamid-update-1',
                'from' => '6281556796243@c.us',
                'to' => '6285182302209@c.us',
                'body' => 'Edit meeting 21.30 jadi jam 22',
                'fromMe' => false,
            ],
        ]);

        $task->refresh();
        $this->assertSame('22:00:00', $task->scheduled_time);

        $reply = WhatsappMessage::query()->where('wa_message_id', 'wamid-update-1_reply')->firstOrFail();
        $this->assertStringContainsString('udah aku update', $reply->message_text);
    }

    public function test_whatsapp_update_with_multiple_matches_returns_clarifying_reply(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'UPDATE',
            'confidence_score' => 0.92,
            'parse_status' => 'ambiguous',
            'requires_confirmation' => false,
            'entities' => [
                'entity_type' => 'task',
                'title' => 'Meeting',
                'scheduled_date' => null,
                'scheduled_time' => '22:00:00',
                'all_day' => false,
                'recurrence' => null,
                'description' => null,
                'search_query' => 'meeting',
            ],
        ]));

        $user = User::factory()->active()->create();
        UserPhone::query()->create([
            'user_id' => $user->id,
            'phone_e164' => '+6281556796244',
            'is_verified' => true,
            'linked_for_whatsapp_at' => now(),
        ]);

        Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Meeting Client',
            'status' => 'pending',
            'scheduled_date' => '2026-05-07',
            'scheduled_time' => '21:30:00',
            'timezone' => 'Asia/Jakarta',
        ]);

        Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Meeting Internal',
            'status' => 'pending',
            'scheduled_date' => '2026-05-07',
            'scheduled_time' => '22:00:00',
            'timezone' => 'Asia/Jakarta',
        ]);

        app(\App\Services\Whatsapp\WhatsappWebhookService::class)->handleInbound([
            'event' => 'message',
            'payload' => [
                'id' => 'wamid-update-2',
                'from' => '6281556796244@c.us',
                'to' => '6285182302209@c.us',
                'body' => 'ubah meeting jadi jam 22',
                'fromMe' => false,
            ],
        ]);

        $reply = WhatsappMessage::query()->where('wa_message_id', 'wamid-update-2_reply')->firstOrFail();
        $this->assertStringContainsString('Aku nemu beberapa task yang mirip', $reply->message_text);
        $this->assertStringContainsString('Meeting Client', $reply->message_text);
        $this->assertStringContainsString('Meeting Internal', $reply->message_text);
    }
}
