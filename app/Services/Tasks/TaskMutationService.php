<?php

namespace App\Services\Tasks;

use App\Jobs\Calendar\SyncTaskToGoogleCalendarJob;
use App\Jobs\Calendar\SyncTaskToGoogleTasksJob;
use App\Models\Task;
use App\Models\TaskChange;
use App\Models\TaskRecurrence;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TaskMutationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data, string $channel = 'app_manual', ?string $promptRequestId = null): Task
    {
        return DB::transaction(function () use ($user, $data, $channel, $promptRequestId): Task {
            $task = Task::query()->create([
                'user_id' => $user->id,
                'source_channel' => $channel,
                'source_prompt_request_id' => $promptRequestId,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'google_task_list_id' => $data['google_task_list_id'] ?? null,
                'google_task_list_title' => $data['google_task_list_title'] ?? null,
                'status' => 'pending',
                'scheduled_date' => $data['scheduled_date'] ?? null,
                'scheduled_time' => $data['scheduled_time'] ?? null,
                'timezone' => $data['timezone'] ?? 'Asia/Jakarta',
                'all_day' => $data['all_day'] ?? false,
                'is_recurring' => isset($data['recurrence']),
            ]);

            if (isset($data['recurrence'])) {
                TaskRecurrence::query()->create([
                    'task_id' => $task->id,
                    'recurrence_type' => $data['recurrence']['type'],
                    'interval_value' => $data['recurrence']['interval'] ?? 1,
                    'day_of_week' => $data['recurrence']['day_of_week'] ?? null,
                    'day_of_month' => $data['recurrence']['day_of_month'] ?? null,
                    'end_date' => $data['recurrence']['end_date'] ?? null,
                    'occurrence_limit' => $data['recurrence']['occurrence_limit'] ?? null,
                ]);
            }

            TaskChange::query()->create([
                'task_id' => $task->id,
                'user_id' => $user->id,
                'prompt_request_id' => $promptRequestId,
                'actor_channel' => $channel,
                'action_type' => 'create',
                'before_state' => null,
                'after_state' => $task->toArray(),
                'created_at' => now(),
            ]);

            $task = $task->load('recurrence');

            $this->dispatchCalendarSyncIfNeeded($user, $task, 'upsert');

            return $task;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Task $task, User $user, array $data, string $channel = 'app_manual', ?string $promptRequestId = null): Task
    {
        return DB::transaction(function () use ($task, $user, $data, $channel, $promptRequestId): Task {
            $beforeState = $task->toArray();

            $task->update(array_filter([
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
                'google_task_list_id' => $data['google_task_list_id'] ?? null,
                'google_task_list_title' => $data['google_task_list_title'] ?? null,
                'scheduled_date' => $data['scheduled_date'] ?? null,
                'scheduled_time' => $data['scheduled_time'] ?? null,
                'timezone' => $data['timezone'] ?? null,
                'all_day' => $data['all_day'] ?? null,
            ], fn ($v) => $v !== null));

            if (isset($data['recurrence'])) {
                TaskRecurrence::query()->updateOrCreate(
                    ['task_id' => $task->id],
                    [
                        'recurrence_type' => $data['recurrence']['type'],
                        'interval_value' => $data['recurrence']['interval'] ?? 1,
                        'day_of_week' => $data['recurrence']['day_of_week'] ?? null,
                        'day_of_month' => $data['recurrence']['day_of_month'] ?? null,
                    ],
                );
                $task->update(['is_recurring' => true]);
            }

            TaskChange::query()->create([
                'task_id' => $task->id,
                'user_id' => $user->id,
                'prompt_request_id' => $promptRequestId,
                'actor_channel' => $channel,
                'action_type' => 'update',
                'before_state' => $beforeState,
                'after_state' => $task->fresh()->toArray(),
                'created_at' => now(),
            ]);

            $task = $task->fresh()->load('recurrence');

            $this->dispatchCalendarSyncIfNeeded($user, $task, 'upsert');

            return $task;
        });
    }

    public function delete(Task $task, User $user, string $channel = 'app_manual', ?string $promptRequestId = null): void
    {
        DB::transaction(function () use ($task, $user, $channel, $promptRequestId): void {
            TaskChange::query()->create([
                'task_id' => $task->id,
                'user_id' => $user->id,
                'prompt_request_id' => $promptRequestId,
                'actor_channel' => $channel,
                'action_type' => 'delete',
                'before_state' => $task->toArray(),
                'after_state' => null,
                'created_at' => now(),
            ]);

            $task->delete();

            $this->dispatchCalendarSyncIfNeeded($user, $task, 'delete');
        });
    }

    public function complete(Task $task, User $user, string $channel = 'app_manual'): Task
    {
        return DB::transaction(function () use ($task, $user, $channel): Task {
            $beforeState = $task->toArray();

            $task->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            TaskChange::query()->create([
                'task_id' => $task->id,
                'user_id' => $user->id,
                'actor_channel' => $channel,
                'action_type' => 'complete',
                'before_state' => $beforeState,
                'after_state' => $task->fresh()->toArray(),
                'created_at' => now(),
            ]);

            $task = $task->fresh();

            $this->dispatchCalendarSyncIfNeeded($user, $task, 'upsert');

            return $task;
        });
    }

    public function restore(Task $task, User $user, string $channel = 'app_manual'): Task
    {
        return DB::transaction(function () use ($task, $user, $channel): Task {
            $task->restore();

            TaskChange::query()->create([
                'task_id' => $task->id,
                'user_id' => $user->id,
                'actor_channel' => $channel,
                'action_type' => 'restore',
                'before_state' => null,
                'after_state' => $task->fresh()->toArray(),
                'created_at' => now(),
            ]);

            $task = $task->fresh();

            $this->dispatchCalendarSyncIfNeeded($user, $task, 'upsert');

            return $task;
        });
    }

    private function dispatchCalendarSyncIfNeeded(User $user, Task $task, string $action): void
    {
        $hasConnection = $user->calendarConnections()
            ->where('provider', 'google_calendar')
            ->where('status', 'connected')
            ->exists();

        if (! $hasConnection) {
            return;
        }

        // If deleting, check existing link type to dispatch correct job
        if ($action === 'delete') {
            $link = $task->calendarEventLink;
            if ($link && $link->link_type === 'google_task') {
                SyncTaskToGoogleTasksJob::dispatch($task->id, $action);
            } elseif ($link) {
                SyncTaskToGoogleCalendarJob::dispatch($task->id, $action);
            }

            return;
        }

        // Tasks with scheduled_time → Google Calendar Event
        // Tasks without scheduled_time → Google Tasks
        if ($task->scheduled_time !== null) {
            SyncTaskToGoogleCalendarJob::dispatch($task->id, $action);
        } else {
            SyncTaskToGoogleTasksJob::dispatch($task->id, $action);
        }
    }
}
