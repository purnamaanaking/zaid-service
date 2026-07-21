<?php

namespace App\Services\Integrations;

use App\Models\CalendarEvent;
use Carbon\Carbon;

class GoogleCalendarEventTransformer
{
    public function eventToGoogleEvent(CalendarEvent $event): array
    {
        $payload = array_filter([
            'summary' => $event->title,
            'description' => $event->description,
        ], fn ($value) => $value !== null && $value !== '');

        $timezone = $event->timezone ?: 'Asia/Jakarta';
        if ($event->all_day) {
            $payload['start'] = ['date' => $event->starts_at->format('Y-m-d')];
            $payload['end'] = ['date' => ($event->ends_at ?: $event->starts_at->copy()->addDay())->format('Y-m-d')];

            return $payload;
        }

        $payload['start'] = ['dateTime' => $event->starts_at->toIso8601String(), 'timeZone' => $timezone];
        $payload['end'] = ['dateTime' => ($event->ends_at ?: $event->starts_at->copy()->addHour())->toIso8601String(), 'timeZone' => $timezone];

        return $payload;
    }

    /** @param array<string, mixed> $event */
    public function googleEventToEventData(array $event): array
    {
        $start = $event['start'] ?? [];
        $end = $event['end'] ?? [];
        $allDay = isset($start['date']) && ! isset($start['dateTime']);
        $timezone = $start['timeZone'] ?? 'Asia/Jakarta';

        return [
            'title' => $event['summary'] ?? 'Untitled event',
            'description' => $event['description'] ?? null,
            'starts_at' => $allDay
                ? Carbon::parse($start['date'], $timezone)
                : Carbon::parse($start['dateTime'] ?? now(), $timezone),
            'ends_at' => isset($end['dateTime']) || isset($end['date'])
                ? Carbon::parse($end['dateTime'] ?? $end['date'], $timezone)
                : null,
            'timezone' => $timezone,
            'all_day' => $allDay,
        ];
    }
}
