<?php

namespace Tests\Feature\Reminders;

use App\Models\Reminder;
use App\Models\Task;
use App\Models\User;
use App\Models\UserPhone;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReminderDeliveryTest extends TestCase
{
    public function test_due_whatsapp_reminder_is_sent_once(): void
    {
        config(['services.whatsapp.driver' => 'waha', 'services.waha.base_url' => 'http://waha.test']);
        Http::fake(['http://waha.test/api/sendText' => Http::response([], 201)]);
        $user = User::factory()->active()->create();
        UserPhone::query()->create([
            'user_id' => $user->id,
            'phone_e164' => '+628123456789',
            'is_verified' => true,
            'linked_for_whatsapp_at' => now(),
        ]);
        $task = Task::factory()->create(['user_id' => $user->id, 'title' => 'Meeting', 'scheduled_date' => now()->format('Y-m-d'), 'scheduled_time' => '10:00:00']);
        $reminder = Reminder::query()->create(['user_id' => $user->id, 'task_id' => $task->id, 'minutes_before' => 30, 'channel' => 'whatsapp', 'remind_at' => now()->subMinute()]);

        $this->artisan('reminders:send-due')->assertExitCode(0);

        $this->assertSame('sent', $reminder->fresh()->status);
        Http::assertSentCount(1);
    }
}
