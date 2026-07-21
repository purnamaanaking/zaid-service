<?php

namespace Tests\Feature\Reminders;

use App\Models\CalendarEvent;
use App\Models\Reminder;
use App\Models\User;
use Tests\TestCase;

class ReminderApiTest extends TestCase
{
    public function test_user_can_create_and_list_event_reminder(): void
    {
        $user = User::factory()->active()->create();
        $event = CalendarEvent::query()->create(['user_id' => $user->id, 'title' => 'Meeting', 'starts_at' => '2026-07-20 10:00:00', 'timezone' => 'Asia/Jakarta']);
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/reminders', ['calendar_event_id' => $event->id, 'minutes_before' => 30, 'channel' => 'whatsapp'])->assertCreated()->assertJsonPath('data.reminder.calendar_event_id', $event->id);
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/reminders')->assertOk()->assertJsonPath('data.items.0.calendar_event_id', $event->id);
    }

    public function test_user_cannot_create_reminder_for_another_users_event(): void
    {
        $user = User::factory()->active()->create();
        $event = CalendarEvent::query()->create(['user_id' => User::factory()->active()->create()->id, 'title' => 'Private', 'starts_at' => now(), 'timezone' => 'Asia/Jakarta']);
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/reminders', ['calendar_event_id' => $event->id, 'minutes_before' => 15])->assertNotFound();
    }
}
