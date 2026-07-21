<?php

namespace App\Services\Agenda;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Support\Collection;

class AgendaQueryService
{
    /** @return Collection<int, CalendarEvent> */
    public function dayAgenda(User $user, string $date): Collection
    {
        return CalendarEvent::query()->where('user_id', $user->id)->whereDate('starts_at', $date)->orderBy('starts_at')->get();
    }

    /** @return array<int, array{date: string, event_count: int}> */
    public function monthSummary(User $user, string $yearMonth): array
    {
        return CalendarEvent::query()->where('user_id', $user->id)->whereRaw("to_char(starts_at, 'YYYY-MM') = ?", [$yearMonth])->get(['starts_at'])
            ->groupBy(fn (CalendarEvent $event) => $event->starts_at->format('Y-m-d'))
            ->map(fn (Collection $events, string $date) => ['date' => $date, 'event_count' => $events->count()])
            ->values()->all();
    }
}
