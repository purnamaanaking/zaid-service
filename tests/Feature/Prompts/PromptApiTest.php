<?php

namespace Tests\Feature\Prompts;

use App\Contracts\Prompt\PromptParser;
use App\Models\Task;
use App\Models\User;
use Tests\Fakes\Prompt\FakePromptParser;
use Tests\TestCase;

class PromptApiTest extends TestCase
{
    public function test_user_can_view_prompt_history(): void
    {
        $user = User::factory()->active()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'cek jadwal hari ini']);

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/prompts')
            ->assertOk()
            ->assertJsonPath('data.items.0.text', 'cek jadwal hari ini');
    }

    public function test_chat_answers_today_schedule_without_waiting_for_parser_retry(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'READ',
            'confidence_score' => 0,
            'parse_status' => 'failed',
            'requires_confirmation' => false,
            'entities' => [],
        ]));
        $user = User::factory()->active()->create();
        Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Tubes 2',
            'scheduled_date' => now()->format('Y-m-d'),
            'scheduled_time' => null,
        ]);
        \App\Models\CalendarEvent::query()->create([
            'user_id' => $user->id,
            'title' => 'Meeting hari ini',
            'starts_at' => now()->setTime(7, 0),
            'timezone' => 'Asia/Jakarta',
            'all_day' => false,
        ]);

        $reply = $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', [
            'text' => 'cek jadwal hari ini',
        ])->assertOk()->json('data.human_response');

        $this->assertStringContainsString('Meeting hari ini', $reply);
        $this->assertStringNotContainsString('Aku belum nangkep', $reply);
    }

    public function test_user_can_permanently_clear_own_prompt_history(): void
    {
        $user = User::factory()->active()->create();
        $otherUser = User::factory()->active()->create();
        \App\Models\PromptRequest::query()->create(['user_id' => $user->id, 'channel' => 'app_prompt', 'raw_text' => 'test', 'normalized_text' => 'test', 'parse_status' => 'parsed']);
        \App\Models\PromptRequest::query()->create(['user_id' => $otherUser->id, 'channel' => 'app_prompt', 'raw_text' => 'test', 'normalized_text' => 'test', 'parse_status' => 'parsed']);

        $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/prompts')->assertOk();

        $this->assertDatabaseMissing('prompt_requests', ['user_id' => $user->id, 'channel' => 'app_prompt']);
        $this->assertDatabaseHas('prompt_requests', ['user_id' => $otherUser->id, 'channel' => 'app_prompt']);
    }

    public function test_announcement_requires_confirmation_then_creates_calendar_event(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'CREATE',
            'confidence_score' => 0.98,
            'parse_status' => 'parsed',
            'requires_confirmation' => true,
            'entities' => [
                'entity_type' => 'event',
                'title' => 'Team Sync',
                'scheduled_date' => '2026-07-21',
                'scheduled_time' => '09:00:00',
                'all_day' => false,
                'description' => "Lokasi: Google Meet\nAgenda: Progress sprint; Review bug",
            ],
        ]));
        $user = User::factory()->active()->create();

        $pending = $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', [
            'text' => 'Team Sync Tanggal 21 Juli 2026 Waktu 09.00 - 10.00 WIB Lokasi Google Meet Agenda Progress sprint Review bug',
        ])->assertOk()->assertJsonPath('data.requires_confirmation', true);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts/'.$pending->json('data.prompt_request_id').'/confirm', ['confirmed' => true])
            ->assertOk()
            ->assertJsonPath('data.result.action', 'create_event');

        $this->assertDatabaseHas('calendar_events', ['user_id' => $user->id, 'title' => 'Team Sync']);
    }

    public function test_prompt_deletes_matching_calendar_event_not_task(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'DELETE',
            'confidence_score' => 0.97,
            'parse_status' => 'parsed',
            'requires_confirmation' => false,
            'entities' => [
                'entity_type' => 'event',
                'title' => 'Meeting',
                'scheduled_date' => '2026-07-07',
                'scheduled_time' => null,
                'all_day' => false,
                'description' => null,
            ],
        ]));
        $user = User::factory()->active()->create();
        $event = \App\Models\CalendarEvent::query()->create([
            'user_id' => $user->id,
            'title' => 'Meeting harian',
            'starts_at' => '2026-07-07 09:00:00',
            'timezone' => 'Asia/Jakarta',
        ]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', [
            'text' => 'hapus jadwal meeting tgl 7 juli',
        ])->assertOk()->assertJsonPath('data.result.action', 'delete_event');

        $this->assertSoftDeleted('calendar_events', ['id' => $event->id]);
    }

    public function test_prompt_updates_matching_calendar_event_time(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'UPDATE',
            'confidence_score' => 0.97,
            'parse_status' => 'parsed',
            'requires_confirmation' => false,
            'entities' => [
                'entity_type' => 'event',
                'title' => 'Meeting',
                'scheduled_date' => '2026-07-07',
                'scheduled_time' => '15:00:00',
                'all_day' => false,
                'description' => null,
            ],
        ]));
        $user = User::factory()->active()->create();
        $event = \App\Models\CalendarEvent::query()->create([
            'user_id' => $user->id,
            'title' => 'Meeting harian',
            'starts_at' => '2026-07-07 09:00:00',
            'timezone' => 'Asia/Jakarta',
        ]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', [
            'text' => 'pindah jadwal meeting tgl 7 juli ke jam 3 sore',
        ])->assertOk()->assertJsonPath('data.result.action', 'update_event');

        $this->assertDatabaseHas('calendar_events', ['id' => $event->id, 'starts_at' => '2026-07-07 15:00:00']);
    }

    public function test_selected_date_overrides_parser_date_for_created_event(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'CREATE',
            'confidence_score' => 0.97,
            'parse_status' => 'parsed',
            'requires_confirmation' => false,
            'entities' => [
                'entity_type' => 'event',
                'title' => 'Meeting',
                'scheduled_date' => '2026-07-07',
                'scheduled_time' => '15:00:00',
                'all_day' => false,
                'description' => 'Agenda harian',
            ],
        ]));
        $user = User::factory()->active()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', [
            'text' => 'meeting jam 3 sore, agenda meeting harian',
            'selected_date' => '2026-07-21',
        ])->assertOk()->assertJsonPath('data.result.event.starts_at', '2026-07-21T08:00:00.000000Z');

        $this->assertDatabaseHas('calendar_events', [
            'user_id' => $user->id,
            'title' => 'Meeting',
            'starts_at' => '2026-07-21 15:00:00',
        ]);
    }

}
