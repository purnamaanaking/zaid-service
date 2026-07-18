<?php

namespace Tests\Feature\Reminders;

use App\Models\CalendarEvent;
use App\Models\Reminder;
use App\Models\Task;
use App\Models\User;
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
}
