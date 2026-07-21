<?php

namespace Tests\Feature;

use App\Contracts\Prompt\PromptParser;
use App\Models\CalendarEvent;
use App\Models\User;
use Tests\Fakes\Prompt\FakePromptParser;
use Tests\TestCase;

class PromptEventCommandTest extends TestCase
{
    public function test_delete_prompt_does_not_remove_multiple_events_on_same_date(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'DELETE',
            'confidence_score' => 0.98,
            'parse_status' => 'parsed',
            'requires_confirmation' => false,
            'entities' => ['title' => null, 'scheduled_date' => '2026-07-17'],
        ]));
        $user = User::factory()->active()->create();
        $first = CalendarEvent::query()->create(['user_id' => $user->id, 'title' => 'Meeting pagi', 'starts_at' => '2026-07-17 09:00:00', 'timezone' => 'Asia/Jakarta']);
        $second = CalendarEvent::query()->create(['user_id' => $user->id, 'title' => 'Meeting sore', 'starts_at' => '2026-07-17 15:00:00', 'timezone' => 'Asia/Jakarta']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'hapus jadwal tanggal 17 juli'])
            ->assertOk()
            ->assertJsonPath('data.human_response', 'Ada 2 jadwal pada tanggal ini. Sebut judul atau jamnya dulu.');

        $this->assertDatabaseHas('calendar_events', ['id' => $first->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('calendar_events', ['id' => $second->id, 'deleted_at' => null]);
    }

    public function test_delete_prompt_targets_event_title_and_date(): void
    {
        $this->app->bind(PromptParser::class, fn () => new FakePromptParser([
            'intent' => 'DELETE',
            'confidence_score' => 0.98,
            'parse_status' => 'parsed',
            'requires_confirmation' => false,
            'entities' => [
                'title' => 'Meeting',
                'scheduled_date' => '2026-07-07',
            ],
        ]));
        $user = User::factory()->active()->create();
        $target = CalendarEvent::query()->create(['user_id' => $user->id, 'title' => 'Meeting', 'starts_at' => '2026-07-07 09:00:00', 'timezone' => 'Asia/Jakarta']);
        $other = CalendarEvent::query()->create(['user_id' => $user->id, 'title' => 'Meeting', 'starts_at' => '2026-07-21 09:00:00', 'timezone' => 'Asia/Jakarta']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/prompts', ['text' => 'hapus jadwal meeting tanggal 7 juli'])
            ->assertOk()
            ->assertJsonPath('data.human_response', 'Event sudah dihapus.');

        $this->assertSoftDeleted('calendar_events', ['id' => $target->id]);
        $this->assertDatabaseHas('calendar_events', ['id' => $other->id, 'deleted_at' => null]);
    }
}
