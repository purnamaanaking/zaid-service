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

    public function test_chat_answers_yesterday_schedule_from_natural_follow_up(): void
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
            'title' => 'Task kemarin',
            'scheduled_date' => now()->subDay()->format('Y-m-d'),
            'scheduled_time' => null,
        ]);

        $reply = $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', [
            'text' => 'jadwal kemarin apa?',
        ])->assertOk()->json('data.human_response');

        $this->assertStringContainsString('Task kemarin', $reply);
        $this->assertStringContainsString(now()->subDay()->format('Y-m-d'), $reply);
    }

    public function test_failed_parser_still_deletes_task_named_in_current_message(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'READ',
            'confidence_score' => 0,
            'parse_status' => 'failed',
            'requires_confirmation' => false,
            'entities' => [],
        ]));
        $user = User::factory()->active()->create();
        $target = Task::factory()->create(['user_id' => $user->id, 'title' => 'Tubes 2']);
        $other = Task::factory()->create(['user_id' => $user->id, 'title' => 'Dieng Culture Festival']);

        $reply = $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', [
            'text' => 'hapus task tubes 2',
        ])->assertOk()->json('data.human_response');

        $this->assertSoftDeleted('tasks', ['id' => $target->id]);
        $this->assertDatabaseHas('tasks', ['id' => $other->id, 'deleted_at' => null]);
        $this->assertStringContainsString('Tubes 2', $reply);
    }

    public function test_delete_follow_up_uses_target_from_previous_message(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'READ',
            'confidence_score' => 0,
            'parse_status' => 'failed',
            'requires_confirmation' => false,
            'entities' => [],
        ]));
        $user = User::factory()->active()->create();
        $target = Task::factory()->create(['user_id' => $user->id, 'title' => 'Tubes 2']);
        \App\Models\PromptRequest::query()->create([
            'user_id' => $user->id,
            'channel' => 'app_prompt',
            'raw_text' => 'hapus task tubes 2',
            'normalized_text' => 'hapus task tubes 2',
            'parse_status' => 'failed',
            'execution_status' => 'failed',
        ]);

        $reply = $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', [
            'text' => 'hapus bro',
        ])->assertOk()->json('data.human_response');

        $this->assertSoftDeleted('tasks', ['id' => $target->id]);
        $this->assertStringContainsString('Tubes 2', $reply);
        $this->assertStringNotContainsString('Halo bro', $reply);
    }

    public function test_chat_returns_task_summary_when_parser_cannot_handle_casual_task_question(): void
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
            'title' => 'Tugas Besar 1',
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'scheduled_time' => null,
        ]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'gua ada task ga?'])
            ->assertOk()
            ->assertJsonPath('data.human_response', "Kamu punya 1 task pending:\n1. Tugas Besar 1");
    }

    public function test_chat_reads_last_week_range_instead_of_reusing_previous_day(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'READ',
            'confidence_score' => 0.95,
            'parse_status' => 'parsed',
            'requires_confirmation' => false,
            'entities' => [
                'entity_type' => 'task',
                'title' => null,
                'scheduled_date' => now()->format('Y-m-d'),
                'scheduled_time' => null,
                'all_day' => false,
                'recurrence' => null,
                'description' => null,
                'search_query' => 'satu minggu terakhir jadwalnya apa aja',
            ],
        ]));
        $user = User::factory()->active()->create();
        Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Meeting minggu ini',
            'scheduled_date' => now()->subDays(3)->format('Y-m-d'),
            'scheduled_time' => '09:00:00',
        ]);
        Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Meeting lama',
            'scheduled_date' => now()->subDays(8)->format('Y-m-d'),
            'scheduled_time' => '09:00:00',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', [
            'text' => 'satu minggu terakhir jadwalnya apaaja',
        ])->assertOk();

        $reply = $response->json('data.human_response');
        $this->assertStringContainsString('Meeting minggu ini', $reply);
        $this->assertStringNotContainsString('Meeting lama', $reply);
    }

    public function test_chat_lists_tasks_and_calendar_events_for_same_day(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'READ',
            'confidence_score' => 0.95,
            'parse_status' => 'parsed',
            'requires_confirmation' => false,
            'entities' => [
                'entity_type' => 'task',
                'title' => null,
                'scheduled_date' => now()->format('Y-m-d'),
                'scheduled_time' => null,
                'all_day' => false,
                'recurrence' => null,
                'description' => null,
                'search_query' => 'list task dan jadwal hari ini',
            ],
        ]));
        $user = User::factory()->active()->create();
        Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Kirim laporan',
            'scheduled_date' => now()->format('Y-m-d'),
            'scheduled_time' => null,
        ]);
        \App\Models\CalendarEvent::query()->create([
            'user_id' => $user->id,
            'title' => 'Meeting tim',
            'starts_at' => now()->setTime(14, 0),
            'timezone' => 'Asia/Jakarta',
            'all_day' => false,
        ]);

        $reply = $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', [
            'text' => 'minta tolong list task dan jadwal hari ini',
        ])->assertOk()->json('data.human_response');

        $this->assertStringContainsString('Kirim laporan', $reply);
        $this->assertStringContainsString('Meeting tim', $reply);
    }

    public function test_delete_with_clear_match_does_not_require_generic_confirmation(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'DELETE',
            'confidence_score' => 0.95,
            'parse_status' => 'parsed',
            'requires_confirmation' => true,
            'entities' => [
                'entity_type' => 'event',
                'title' => 'meeting',
                'scheduled_date' => now()->format('Y-m-d'),
                'scheduled_time' => '07:00:00',
                'all_day' => false,
                'recurrence' => null,
                'description' => null,
                'search_query' => 'meeting hari ini jam 7',
            ],
        ]));
        $user = User::factory()->active()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Meeting',
            'scheduled_date' => now()->format('Y-m-d'),
            'scheduled_time' => '07:00:00',
        ]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', [
            'text' => 'bat jadwal meeting hari ini jam 7',
        ])->assertOk()->assertJsonPath('data.requires_confirmation', false);

        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
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

    public function test_user_can_submit_prompt(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'CREATE',
            'confidence_score' => 0.97,
            'parse_status' => 'parsed',
            'requires_confirmation' => false,
            'entities' => [
                'entity_type' => 'task',
                'title' => 'Laporan Penjualan',
                'scheduled_date' => '2026-05-23',
                'scheduled_time' => '10:00:00',
                'all_day' => false,
                'recurrence' => [
                    'type' => 'weekly',
                    'day_of_week' => 'friday',
                    'interval' => 1,
                ],
                'description' => null,
                'search_query' => null,
            ],
        ]));

        $user = User::factory()->active()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', [
            'text' => 'Buat task laporan penjualan setiap Jumat jam 10 pagi',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.intent', 'CREATE')
            ->assertJsonPath('data.result.action', 'create_task');
    }
}
