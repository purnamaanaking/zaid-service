<?php

namespace App\Services\Agenda;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;

class AgendaQueryService
{
    /**
     * @return Collection<int, Task>
     */
    public function dayAgenda(User $user, string $date): Collection
    {
        return Task::query()
            ->where('user_id', $user->id)
            ->whereDate('scheduled_date', $date)
            ->where('status', '!=', 'cancelled')
            ->with('recurrence')
            ->orderBy('scheduled_time')
            ->get();
    }

    /**
     * @return array<int, array{date: string, task_count: int, has_pending: bool}>
     */
    public function monthSummary(User $user, string $yearMonth): array
    {
        $tasks = Task::query()
            ->where('user_id', $user->id)
            ->whereRaw("to_char(scheduled_date, 'YYYY-MM') = ?", [$yearMonth])
            ->where('status', '!=', 'cancelled')
            ->get(['scheduled_date', 'status']);

        $grouped = $tasks->groupBy(fn (Task $t) => $t->scheduled_date->format('Y-m-d'));

        return $grouped->map(fn (Collection $dayTasks, string $date) => [
            'date' => $date,
            'task_count' => $dayTasks->count(),
            'has_pending' => $dayTasks->contains('status', 'pending'),
        ])->values()->all();
    }
}
