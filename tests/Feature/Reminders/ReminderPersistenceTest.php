<?php

namespace Tests\Feature\Reminders;

use App\Models\CalendarEvent;
use App\Models\Reminder;
use App\Models\Task;
use App\Models\User;
use App\Services\Tasks\TaskMutationService;
use Tests\TestCase;

class ReminderPersistenceTest extends TestCase
{
    public function test_reminder_belongs_to_task_and_event(): void
    {
        $user = User::factory()->active()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);
        $event = CalendarEvent::query()->create([
            'user_id' => $user->id,
            'title' => 'Meeting',
            'starts_at' => now()->addHour(),
            'timezone' => 'Asia/Jakarta',
        ]);

        $taskReminder = Reminder::query()->create([
            'user_id' => $user->id,
            'task_id' => $task->id,
            'minutes_before' => 30,
            'channel' => 'whatsapp',
            'remind_at' => now()->addMinutes(30),
        ]);
        $eventReminder = Reminder::query()->create([
            'user_id' => $user->id,
            'calendar_event_id' => $event->id,
            'minutes_before' => 15,
            'channel' => 'both',
            'remind_at' => now()->addMinutes(45),
        ]);

        $this->assertSame($task->id, $taskReminder->task->id);
        $this->assertSame($event->id, $eventReminder->calendarEvent->id);
        $this->assertCount(2, $user->reminders);
    }

    public function test_task_update_recalculates_and_complete_removes_pending_reminders(): void
    {
        $user = User::factory()->active()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'scheduled_date' => '2026-07-20',
            'scheduled_time' => '10:00:00',
            'timezone' => 'Asia/Jakarta',
        ]);
        $reminder = Reminder::query()->create([
            'user_id' => $user->id,
            'task_id' => $task->id,
            'minutes_before' => 30,
            'channel' => 'app',
            'remind_at' => '2026-07-20 09:30:00',
        ]);
        $service = app(TaskMutationService::class);

        $service->update($task, $user, ['scheduled_time' => '11:00:00']);
        $this->assertSame('10:30', $reminder->fresh()->remind_at->setTimezone('Asia/Jakarta')->format('H:i'));

        $service->complete($task->fresh(), $user);
        $this->assertDatabaseMissing('reminders', ['id' => $reminder->id]);
    }
}
