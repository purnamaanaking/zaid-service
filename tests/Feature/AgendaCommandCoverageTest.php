<?php

namespace Tests\Feature;

use App\Contracts\Prompt\PromptParser;
use App\Models\CalendarEvent;
use App\Models\Reminder;
use App\Models\User;
use Tests\Fakes\Prompt\FakePromptParser;
use Tests\TestCase;

class AgendaCommandCoverageTest extends TestCase
{
    private function parser(array $entities): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => $entities['action'] === 'DELETE_EVENTS' ? 'DELETE' : ($entities['action'] === 'UPDATE_EVENTS' ? 'UPDATE' : 'READ'),
            'confidence_score' => .98,
            'parse_status' => 'parsed',
            'requires_confirmation' => false,
            'entities' => $entities,
        ]));
    }

    public function test_prompt_includes_selected_date_range_in_parser_context(): void
    {
        $parser = new FakePromptParser([
            'intent' => 'READ', 'confidence_score' => .98, 'parse_status' => 'parsed', 'requires_confirmation' => false,
            'entities' => ['action' => 'LIST_EVENTS'],
        ]);
        $this->app->bind(PromptParser::class, fn () => $parser);
        $user = User::factory()->active()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', [
            'text' => 'cek agenda', 'selected_from' => '2026-08-21', 'selected_to' => '2026-08-27',
        ])->assertOk();

        $this->assertStringContainsString('Selected date range: 2026-08-21 to 2026-08-27', $parser->lastContext);
    }

    public function test_selected_range_overrides_parser_date_for_created_event(): void
    {
        $this->parser(['action' => 'CREATE_EVENTS', 'title' => 'Gym', 'scheduled_date' => '2026-08-21', 'scheduled_dates' => ['2026-08-21'], 'scheduled_time' => '14:00:00']);
        $user = User::factory()->active()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', [
            'text' => 'jadwalkan gym jam 2 siang', 'selected_from' => '2026-08-23', 'selected_to' => '2026-08-25',
        ])->assertOk();

        $this->assertDatabaseCount('calendar_events', 3);
        $this->assertDatabaseHas('calendar_events', ['user_id' => $user->id, 'title' => 'Gym', 'starts_at' => '2026-08-23 14:00:00']);
        $this->assertDatabaseHas('calendar_events', ['user_id' => $user->id, 'title' => 'Gym', 'starts_at' => '2026-08-25 14:00:00']);
    }

    public function test_low_confidence_command_requires_confirmation_before_delete(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'DELETE', 'confidence_score' => .42, 'parse_status' => 'parsed', 'requires_confirmation' => false,
            'entities' => ['action' => 'DELETE_EVENTS', 'target_event_ids' => []],
        ]));
        $user = User::factory()->active()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'hapus meeting itu'])
            ->assertOk()->assertJsonPath('data.requires_confirmation', true)->assertJsonPath('data.parse_status', 'ambiguous');
    }

    public function test_low_confidence_list_executes_without_confirmation(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'READ', 'confidence_score' => .42, 'parse_status' => 'parsed', 'requires_confirmation' => false,
            'entities' => ['action' => 'LIST_EVENTS', 'from' => '2026-07-22', 'to' => '2026-07-28'],
        ]));
        $user = User::factory()->active()->create();
        CalendarEvent::query()->create(['user_id' => $user->id, 'title' => 'Meeting', 'starts_at' => '2026-07-24 08:00:00', 'timezone' => 'Asia/Jakarta']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'list jadwal minggu ini'])
            ->assertOk()
            ->assertJsonPath('data.requires_confirmation', false)
            ->assertJsonPath('data.result.items.0.title', 'Meeting');
    }

    public function test_busy_week_answer_includes_verified_event_count(): void
    {
        $this->parser(['action' => 'CHECK_AVAILABILITY', 'from' => '2026-07-22', 'to' => '2026-07-28', 'human_response' => 'Minggu ini cukup padat.']);
        $user = User::factory()->active()->create();
        CalendarEvent::query()->create(['user_id' => $user->id, 'title' => 'Meeting', 'starts_at' => '2026-07-24 08:00:00', 'timezone' => 'Asia/Jakarta']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'minggu ini terlalu sibuk gak?'])
            ->assertOk()->assertJsonPath('data.human_response', "Minggu ini cukup padat.\n\n1. Meeting · 24 Jul, 08:00\n\n1 jadwal ditemukan.");
    }

    public function test_list_replaces_ai_event_list_with_one_canonical_time_range(): void
    {
        $this->parser(['action' => 'LIST_EVENTS', 'from' => '2026-07-22', 'to' => '2026-07-28', 'human_response' => "Agenda minggu ini:\n1. Meeting - 24 Juli 2026 pukul 08:00"]);
        $user = User::factory()->active()->create();
        CalendarEvent::query()->create(['user_id' => $user->id, 'title' => 'Meeting', 'starts_at' => '2026-07-24 08:00:00', 'ends_at' => '2026-07-24 18:00:00', 'timezone' => 'Asia/Jakarta']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => '1 minggu kedepan ada jadwal apa aja coba list'])
            ->assertOk()
            ->assertJsonPath('data.human_response', "Agenda kamu:\n\n1. Meeting · 24 Jul, 08:00-18:00")
            ->assertJsonPath('data.result.items.0.title', 'Meeting')
            ->assertJsonCount(1, 'data.result.items');
    }

    public function test_list_ignores_natural_language_range_as_a_text_search(): void
    {
        $this->parser(['action' => 'LIST_EVENTS', 'from' => '2026-08-21', 'to' => '2026-08-27', 'search_query' => 'seminggu ke depan']);
        $user = User::factory()->active()->create();
        CalendarEvent::query()->create(['user_id' => $user->id, 'title' => 'Lomba gemastik', 'starts_at' => '2026-08-23 09:00:00', 'timezone' => 'Asia/Jakarta']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'list jadwal seminggu ke depan'])
            ->assertOk()->assertJsonPath('data.result.items.0.title', 'Lomba gemastik');
    }

    public function test_list_without_ai_response_includes_each_event_once(): void
    {
        $this->parser(['action' => 'LIST_EVENTS', 'from' => '2026-08-21', 'to' => '2026-08-27']);
        $user = User::factory()->active()->create();
        CalendarEvent::query()->create(['user_id' => $user->id, 'title' => 'Lomba gemastik', 'starts_at' => '2026-08-23 09:00:00', 'timezone' => 'Asia/Jakarta']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'list jadwal seminggu ke depan'])
            ->assertOk()
            ->assertJsonPath('data.human_response', "Agenda kamu:\n\n1. Lomba gemastik · 23 Aug, 09:00");
    }

    public function test_list_replaces_ai_empty_message_when_events_exist(): void
    {
        $this->parser(['action' => 'LIST_EVENTS', 'from' => '2026-08-21', 'to' => '2026-08-27', 'human_response' => 'Tidak ada jadwal yang tercatat pada periode ini.']);
        $user = User::factory()->active()->create();
        CalendarEvent::query()->create(['user_id' => $user->id, 'title' => 'Lomba gemastik', 'starts_at' => '2026-08-23 09:00:00', 'timezone' => 'Asia/Jakarta']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'list jadwal seminggu ke depan'])
            ->assertOk()
            ->assertJsonPath('data.human_response', "Agenda kamu:\n\n1. Lomba gemastik · 23 Aug, 09:00");
    }

    public function test_lists_only_requested_week(): void
    {
        $this->parser(['action' => 'LIST_EVENTS', 'from' => '2026-07-22', 'to' => '2026-07-28']);
        $user = User::factory()->active()->create();
        CalendarEvent::query()->create(['user_id' => $user->id, 'title' => 'This week', 'starts_at' => '2026-07-24 08:00:00', 'timezone' => 'Asia/Jakarta']);
        CalendarEvent::query()->create(['user_id' => $user->id, 'title' => 'Next month', 'starts_at' => '2026-08-01 08:00:00', 'timezone' => 'Asia/Jakarta']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'cek jadwal 1 minggu ke depan'])
            ->assertOk()->assertJsonPath('data.result.items.0.title', 'This week')->assertJsonCount(1, 'data.result.items');
    }

    public function test_recurring_schedule_without_date_range_requests_clarification(): void
    {
        $this->parser(['action' => 'CREATE_EVENTS', 'title' => 'Makan siang', 'scheduled_time' => '12:00:00', 'recurrence' => ['type' => 'weekly', 'day_of_week' => 'monday']]);
        $user = User::factory()->active()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'buat makan siang setiap Senin jam 12'])
            ->assertOk()
            ->assertJsonPath('data.requires_confirmation', true)
            ->assertJsonPath('data.parse_status', 'ambiguous')
            ->assertJsonPath('data.human_response', 'Jadwal berulang butuh tanggal mulai dan tanggal selesai.');

        $this->assertDatabaseCount('calendar_events', 0);
    }

    public function test_creates_weekly_schedule_for_every_date_in_range(): void
    {
        $this->parser(['action' => 'CREATE_EVENTS', 'title' => 'Makan siang', 'scheduled_time' => '12:00:00', 'range_start' => '2026-08-01', 'range_end' => '2026-08-31', 'recurrence' => ['type' => 'weekly', 'day_of_week' => 'monday']]);
        $user = User::factory()->active()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'buat makan siang setiap Senin dari 1 sampai 31 Agustus 2026 jam 12'])->assertOk();

        $this->assertDatabaseCount('calendar_events', 5);
        $this->assertDatabaseHas('calendar_events', ['user_id' => $user->id, 'starts_at' => '2026-08-03 12:00:00']);
        $this->assertDatabaseHas('calendar_events', ['user_id' => $user->id, 'starts_at' => '2026-08-31 12:00:00']);
    }

    public function test_updates_all_ai_selected_events(): void
    {
        $user = User::factory()->active()->create();
        $first = CalendarEvent::query()->create(['user_id' => $user->id, 'title' => 'Gym', 'starts_at' => '2026-07-27 08:00:00', 'timezone' => 'Asia/Jakarta']);
        $second = CalendarEvent::query()->create(['user_id' => $user->id, 'title' => 'Gym', 'starts_at' => '2026-07-28 08:00:00', 'timezone' => 'Asia/Jakarta']);
        $this->parser(['action' => 'UPDATE_EVENTS', 'target_event_ids' => [$first->id, $second->id], 'changes' => ['title' => 'Jogging pagi', 'scheduled_time' => '06:00:00'], 'human_response' => 'Sip, gym tanggal 27–28 sudah jadi jogging pagi jam 6.']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'ganti event tanggal 27-28 jadi jogging pagi jam 6'])
            ->assertOk()->assertJsonPath('data.human_response', 'Sip, gym tanggal 27–28 sudah jadi jogging pagi jam 6.');

        $this->assertDatabaseHas('calendar_events', ['id' => $first->id, 'title' => 'Jogging pagi', 'starts_at' => '2026-07-27 06:00:00']);
        $this->assertDatabaseHas('calendar_events', ['id' => $second->id, 'title' => 'Jogging pagi', 'starts_at' => '2026-07-28 06:00:00']);
    }

    public function test_sets_reminder_on_ai_selected_event(): void
    {
        $user = User::factory()->active()->create();
        $event = CalendarEvent::query()->create(['user_id' => $user->id, 'title' => 'Meeting', 'starts_at' => '2026-07-27 08:00:00', 'timezone' => 'Asia/Jakarta']);
        $this->parser(['action' => 'SET_REMINDER', 'target_event_ids' => [$event->id], 'reminder_minutes_before' => 30, 'reminder_channel' => 'app']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'ingatkan meeting 30 menit sebelum lewat app'])
            ->assertOk()->assertJsonPath('data.human_response', 'Reminder diatur untuk 1 jadwal.');

        $this->assertDatabaseHas('reminders', ['calendar_event_id' => $event->id, 'minutes_before' => 30, 'channel' => 'app']);
    }
}
