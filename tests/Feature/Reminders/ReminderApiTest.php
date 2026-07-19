<?php

namespace Tests\Feature\Reminders;

use App\Models\Reminder;
use App\Models\Task;
use App\Models\User;
use Tests\TestCase;

class ReminderApiTest extends TestCase
{
    public function test_user_can_create_and_list_task_reminder(): void
    {
        $user = User::factory()->active()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'scheduled_date' => '2026-07-20',
            'scheduled_time' => '10:00:00',
        ]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/reminders', [
            'task_id' => $task->id,
            'minutes_before' => 30,
            'channel' => 'whatsapp',
        ])->assertCreated()
            ->assertJsonPath('data.reminder.minutes_before', 30)
            ->assertJsonPath('data.reminder.channel', 'whatsapp');

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/reminders')
            ->assertOk()
            ->assertJsonPath('data.items.0.task_id', $task->id);
    }

    public function test_failed_reminders_are_visible_and_update_does_not_require_source_id(): void
    {
        $user = User::factory()->active()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'scheduled_date' => '2026-07-20',
            'scheduled_time' => '10:00:00',
        ]);
        $reminder = Reminder::query()->create([
            'user_id' => $user->id,
            'task_id' => $task->id,
            'minutes_before' => 30,
            'channel' => 'whatsapp',
            'remind_at' => '2026-07-20 09:30:00',
            'status' => 'failed',
            'error_message' => 'No verified WhatsApp phone.',
        ]);

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/reminders')
            ->assertOk()
            ->assertJsonPath('data.items.0.status', 'failed');

        $this->actingAs($user, 'sanctum')->patchJson('/api/v1/reminders/'.$reminder->id, [
            'minutes_before' => 60,
            'channel' => 'app',
        ])->assertOk()
            ->assertJsonPath('data.reminder.minutes_before', 60)
            ->assertJsonPath('data.reminder.status', 'pending');
    }

    public function test_user_cannot_create_reminder_for_another_users_task(): void
    {
        $user = User::factory()->active()->create();
        $task = Task::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/reminders', [
            'task_id' => $task->id,
            'minutes_before' => 15,
        ])->assertNotFound();

        $this->assertDatabaseCount('reminders', 0);
    }
}
