<?php

namespace App\Services\Integrations;

use App\Models\Task;
use Carbon\Carbon;

class GoogleCalendarEventTransformer
{
    /**
     * @return array<string, mixed>
     */
    public function taskToGoogleEvent(Task $task): array
    {
        $payload = [
            'summary' => $task->title,
            'description' => $task->description,
        ];

        $timezone = $task->timezone ?: 'Asia/Jakarta';
        $scheduledDate = $task->scheduled_date?->format('Y-m-d');

        if ($task->all_day) {
            $payload['start'] = [
                'date' => $scheduledDate,
            ];
            $payload['end'] = [
                'date' => Carbon::parse($scheduledDate)->addDay()->format('Y-m-d'),
            ];
        } else {
            $start = Carbon::parse($scheduledDate.' '.$task->scheduled_time, $timezone);
            $end = (clone $start)->addHour();

            $payload['start'] = [
                'dateTime' => $start->toIso8601String(),
                'timeZone' => $timezone,
            ];
            $payload['end'] = [
                'dateTime' => $end->toIso8601String(),
                'timeZone' => $timezone,
            ];
        }

        if ($task->is_recurring && $task->relationLoaded('recurrence') && $task->recurrence) {
            $payload['recurrence'] = [$this->buildRecurrenceRule($task)];
        }

        return array_filter($payload, fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, mixed>  $event
     * @return array<string, mixed>
     */
    public function googleEventToTaskData(array $event): array
    {
        $start = $event['start'] ?? [];
        $isAllDay = isset($start['date']) && ! isset($start['dateTime']);

        return [
            'title' => $event['summary'] ?? 'Untitled event',
            'description' => $event['description'] ?? null,
            'scheduled_date' => $isAllDay
                ? ($start['date'] ?? null)
                : Carbon::parse((string) ($start['dateTime'] ?? now()->toIso8601String()))->format('Y-m-d'),
            'scheduled_time' => $isAllDay
                ? null
                : Carbon::parse((string) ($start['dateTime'] ?? now()->toIso8601String()))->format('H:i:s'),
            'timezone' => $start['timeZone'] ?? 'Asia/Jakarta',
            'all_day' => $isAllDay,
            'status' => ($event['status'] ?? 'confirmed') === 'cancelled' ? 'cancelled' : 'pending',
        ];
    }

    private function buildRecurrenceRule(Task $task): string
    {
        $recurrence = $task->recurrence;
        $interval = $recurrence->interval_value ?: 1;

        return match ($recurrence->recurrence_type) {
            'weekly' => 'RRULE:FREQ=WEEKLY;INTERVAL='.$interval.';BYDAY='.$this->toByDay((string) $recurrence->day_of_week),
            'daily' => 'RRULE:FREQ=DAILY;INTERVAL='.$interval,
            'monthly' => 'RRULE:FREQ=MONTHLY;INTERVAL='.$interval,
            default => 'RRULE:FREQ=DAILY;INTERVAL=1',
        };
    }

    private function toByDay(string $dayOfWeek): string
    {
        return match (strtolower($dayOfWeek)) {
            'monday' => 'MO',
            'tuesday' => 'TU',
            'wednesday' => 'WE',
            'thursday' => 'TH',
            'friday' => 'FR',
            'saturday' => 'SA',
            'sunday' => 'SU',
            default => 'MO',
        };
    }
}
