<?php

namespace Tests\Feature\Whatsapp;

use App\Contracts\Prompt\PromptParser;
use App\Models\PromptRequest;
use App\Models\Task;
use App\Models\User;
use App\Models\UserPhone;
use App\Models\WhatsappMessage;
use Tests\Fakes\Prompt\FakePromptParser;
use Tests\TestCase;

class WhatsappConfirmationFlowTest extends TestCase
{
    public function test_yes_reply_confirms_pending_whatsapp_update_instead_of_creating_new_prompt(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'READ',
            'confidence_score' => 0.10,
            'parse_status' => 'parsed',
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
            'phone_e164' => '+6281556796245',
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

        $pending = PromptRequest::query()->create([
            'user_id' => $user->id,
            'channel' => 'whatsapp',
            'raw_text' => 'rubah meeting jadi jam 22.00 bro',
            'normalized_text' => 'rubah meeting jadi jam 22.00 bro',
            'intent' => 'UPDATE',
            'confidence_score' => 0.92,
            'parse_status' => 'ambiguous',
            'extracted_entities' => [
                'entity_type' => 'task',
                'title' => 'meeting',
                'scheduled_date' => null,
                'scheduled_time' => '22:00:00',
                'all_day' => false,
                'recurrence' => null,
                'description' => null,
                'search_query' => 'meeting 21:30',
            ],
            'execution_status' => 'awaiting_confirmation',
        ]);

        app(\App\Services\Whatsapp\WhatsappWebhookService::class)->handleInbound([
            'event' => 'message',
            'payload' => [
                'id' => 'wamid-confirm-yes',
                'from' => '6281556796245@c.us',
                'to' => '6285182302209@c.us',
                'body' => 'iya betul',
                'fromMe' => false,
            ],
        ]);

        $task->refresh();
        $pending->refresh();

        $this->assertSame('22:00:00', $task->scheduled_time);
        $this->assertSame('executed', $pending->execution_status);
        $this->assertSame(1, PromptRequest::query()->where('user_id', $user->id)->count());

        $reply = WhatsappMessage::query()->where('wa_message_id', 'wamid-confirm-yes_reply')->firstOrFail();
        $this->assertStringContainsString('udah aku update', $reply->message_text);
    }

    public function test_no_reply_cancels_pending_whatsapp_prompt(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser());

        $user = User::factory()->active()->create();
        UserPhone::query()->create([
            'user_id' => $user->id,
            'phone_e164' => '+6281556796246',
            'is_verified' => true,
            'linked_for_whatsapp_at' => now(),
        ]);

        $pending = PromptRequest::query()->create([
            'user_id' => $user->id,
            'channel' => 'whatsapp',
            'raw_text' => 'hapus meeting itu',
            'normalized_text' => 'hapus meeting itu',
            'intent' => 'DELETE',
            'confidence_score' => 0.80,
            'parse_status' => 'ambiguous',
            'extracted_entities' => [
                'entity_type' => 'task',
                'title' => 'meeting',
                'scheduled_date' => null,
                'scheduled_time' => null,
                'all_day' => false,
                'recurrence' => null,
                'description' => null,
                'search_query' => 'meeting',
            ],
            'execution_status' => 'awaiting_confirmation',
        ]);

        app(\App\Services\Whatsapp\WhatsappWebhookService::class)->handleInbound([
            'event' => 'message',
            'payload' => [
                'id' => 'wamid-confirm-no',
                'from' => '6281556796246@c.us',
                'to' => '6285182302209@c.us',
                'body' => 'enggak jadi',
                'fromMe' => false,
            ],
        ]);

        $pending->refresh();
        $this->assertSame('rejected', $pending->execution_status);
        $this->assertSame(1, PromptRequest::query()->where('user_id', $user->id)->count());

        $reply = WhatsappMessage::query()->where('wa_message_id', 'wamid-confirm-no_reply')->firstOrFail();
        $this->assertStringContainsString('batalin dulu', $reply->message_text);
    }
}
