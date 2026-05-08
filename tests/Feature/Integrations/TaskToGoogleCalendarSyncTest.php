<?php

namespace Tests\Feature\Integrations;

use App\Jobs\Calendar\SyncTaskToGoogleCalendarJob;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class TaskToGoogleCalendarSyncTest extends TestCase
{
    public function test_creating_a_scheduled_task_dispatches_calendar_sync_job_for_connected_user(): void
    {
        Bus::fake();

        $user = User::factory()->active()->create();
        $user->calendarConnections()->create([
            'provider' => 'google_calendar',
            'google_calendar_id' => 'primary',
            'encrypted_access_token' => 'enc-access',
            'encrypted_refresh_token' => 'enc-refresh',
            'token_expires_at' => now()->addHour(),
            'status' => 'connected',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/tasks', [
            'title' => 'Calendar Task',
            'scheduled_date' => '2026-05-23',
            'scheduled_time' => '10:00:00',
        ]);

        $response->assertCreated();

        Bus::assertDispatched(SyncTaskToGoogleCalendarJob::class);
    }

    public function test_creating_task_without_schedule_does_not_dispatch_calendar_sync_job(): void
    {
        Bus::fake();

        $user = User::factory()->active()->create();
        $user->calendarConnections()->create([
            'provider' => 'google_calendar',
            'google_calendar_id' => 'primary',
            'encrypted_access_token' => 'enc-access',
            'encrypted_refresh_token' => 'enc-refresh',
            'token_expires_at' => now()->addHour(),
            'status' => 'connected',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/tasks', [
            'title' => 'Unschedule Task',
        ]);

        $response->assertCreated();

        Bus::assertNotDispatched(SyncTaskToGoogleCalendarJob::class);
    }

    public function test_creating_task_for_unconnected_user_does_not_dispatch_calendar_sync_job(): void
    {
        Bus::fake();

        $user = User::factory()->active()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/tasks', [
            'title' => 'Local Only Task',
            'scheduled_date' => '2026-05-23',
            'scheduled_time' => '10:00:00',
        ]);

        $response->assertCreated();

        Bus::assertNotDispatched(SyncTaskToGoogleCalendarJob::class);
    }

    public function test_updating_scheduled_task_dispatches_calendar_sync_job_for_connected_user(): void
    {
        Bus::fake();

        $user = User::factory()->active()->create();
        $user->calendarConnections()->create([
            'provider' => 'google_calendar',
            'google_calendar_id' => 'primary',
            'encrypted_access_token' => 'enc-access',
            'encrypted_refresh_token' => 'enc-refresh',
            'token_expires_at' => now()->addHour(),
            'status' => 'connected',
        ]);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'scheduled_date' => '2026-05-23',
            'scheduled_time' => '10:00:00',
        ]);

        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/v1/tasks/{$task->id}", [
            'title' => 'Updated Calendar Task',
        ]);

        $response->assertOk();

        Bus::assertDispatched(SyncTaskToGoogleCalendarJob::class);
    }

    public function test_deleting_scheduled_task_dispatches_calendar_sync_job_for_connected_user(): void
    {
        Bus::fake();

        $user = User::factory()->active()->create();
        $user->calendarConnections()->create([
            'provider' => 'google_calendar',
            'google_calendar_id' => 'primary',
            'encrypted_access_token' => 'enc-access',
            'encrypted_refresh_token' => 'enc-refresh',
            'token_expires_at' => now()->addHour(),
            'status' => 'connected',
        ]);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'scheduled_date' => '2026-05-23',
            'scheduled_time' => '10:00:00',
        ]);

        // Create a calendar event link so delete knows to dispatch calendar job
        $connection = $user->calendarConnections()->first();
        \App\Models\CalendarEventLink::query()->create([
            'task_id' => $task->id,
            'user_calendar_connection_id' => $connection->id,
            'link_type' => 'calendar_event',
            'google_event_id' => 'test-event-id',
            'sync_status' => 'synced',
        ]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertOk();

        Bus::assertDispatched(SyncTaskToGoogleCalendarJob::class);
    }
}
