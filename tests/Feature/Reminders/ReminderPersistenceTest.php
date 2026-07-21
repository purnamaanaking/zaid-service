<?php

namespace Tests\Feature\Reminders;

use App\Models\CalendarEvent;
use App\Models\Reminder;
use App\Models\User;
use Tests\TestCase;

class ReminderPersistenceTest extends TestCase
{
    public function test_reminder_belongs_to_event_and_moves_when_event_changes(): void
    {
        $user = User::factory()->active()->create();
        $event = CalendarEvent::query()->create(['user_id' => $user->id, 'title' => 'Meeting', 'starts_at' => '2026-07-20 10:00:00', 'timezone' => 'Asia/Jakarta']);
        $reminder = Reminder::query()->create(['user_id' => $user->id, 'calendar_event_id' => $event->id, 'minutes_before' => 30, 'channel' => 'app', 'remind_at' => '2026-07-20 09:30:00']);
        $this->assertSame($event->id, $reminder->calendarEvent->id);
        $this->actingAs($user, 'sanctum')->patchJson('/api/v1/events/'.$event->id, ['starts_at' => '2026-07-20 11:00:00'])->assertOk();
        $this->assertSame('10:30', $reminder->fresh()->remind_at->setTimezone('Asia/Jakarta')->format('H:i'));
    }
}
