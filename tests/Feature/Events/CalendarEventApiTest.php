<?php

namespace Tests\Feature\Events;

use App\Models\CalendarEvent;
use App\Models\User;
use Tests\TestCase;

class CalendarEventApiTest extends TestCase
{
    public function test_event_lookup_excludes_soft_deleted_event(): void
    {
        $user = User::factory()->active()->create();
        $deleted = CalendarEvent::query()->create(['user_id' => $user->id, 'title' => 'Deleted', 'starts_at' => '2026-07-07 09:00:00', 'timezone' => 'Asia/Jakarta']);
        $visible = CalendarEvent::query()->create(['user_id' => $user->id, 'title' => 'Visible', 'starts_at' => '2026-07-08 09:00:00', 'timezone' => 'Asia/Jakarta']);
        $deleted->delete();

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/events?from=2026-07-01&to=2026-07-31')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', $visible->id);
    }

    public function test_user_can_manage_own_events(): void
    {
        $user = User::factory()->active()->create();
        $event = $this->actingAs($user, 'sanctum')->postJson('/api/v1/events', [
            'title' => 'Client meeting',
            'starts_at' => '2026-07-17T10:00:00+07:00',
            'ends_at' => '2026-07-17T11:00:00+07:00',
        ])->assertCreated()->json('data.event');

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/events?from=2026-07-01&to=2026-07-31')
            ->assertOk()->assertJsonPath('data.items.0.id', $event['id']);

        $this->actingAs($user, 'sanctum')->patchJson("/api/v1/events/{$event['id']}", ['title' => 'Updated meeting'])
            ->assertOk()->assertJsonPath('data.event.title', 'Updated meeting');

        $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/events/{$event['id']}")->assertOk();
        $this->assertSoftDeleted(CalendarEvent::class, ['id' => $event['id']]);
    }
}
