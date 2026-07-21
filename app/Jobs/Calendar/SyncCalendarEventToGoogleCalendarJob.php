<?php

namespace App\Jobs\Calendar;

use App\Models\CalendarEvent;
use App\Models\CalendarEventLink;
use App\Models\CalendarSyncLog;
use App\Services\Integrations\GoogleCalendarApiService;
use App\Services\Integrations\GoogleCalendarEventTransformer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncCalendarEventToGoogleCalendarJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $eventId, public readonly string $action) {}

    public function handle(GoogleCalendarApiService $api, GoogleCalendarEventTransformer $transformer): void
    {
        $event = CalendarEvent::withTrashed()->with('user.calendarConnections')->findOrFail($this->eventId);
        $connection = $event->user->calendarConnections()->where('provider', 'google_calendar')->where('status', 'connected')->first();
        if (! $connection) return;

        $link = CalendarEventLink::query()->where('calendar_event_id', $event->id)->first();
        if ($this->action === 'delete' && $link) {
            $response = $api->deleteEvent($connection, $link->google_event_id);
        } else {
            $payload = $transformer->eventToGoogleEvent($event);
            $response = $link ? $api->updateEvent($connection, $link->google_event_id, $payload) : $api->createEvent($connection, $payload);
            $link = CalendarEventLink::query()->updateOrCreate(['calendar_event_id' => $event->id], [
                'user_calendar_connection_id' => $connection->id,
                'google_event_id' => $response['data']['id'] ?? $link?->google_event_id,
                'google_event_etag' => $response['data']['etag'] ?? null,
                'remote_status' => $response['data']['status'] ?? null,
                'remote_updated_at' => isset($response['data']['updated']) ? now()->parse($response['data']['updated']) : null,
                'last_synced_at' => now(),
                'last_synced_payload_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
                'sync_status' => $response['ok'] ? 'synced' : 'failed',
                'sync_error' => $response['ok'] ? null : json_encode($response['data']),
            ]);
        }

        if ($link) $link->update(['last_synced_at' => now(), 'sync_status' => $response['ok'] ? 'synced' : 'failed', 'sync_error' => $response['ok'] ? null : json_encode($response['data'])]);
        CalendarSyncLog::query()->create([
            'user_id' => $event->user_id,
            'calendar_event_id' => $event->id,
            'user_calendar_connection_id' => $connection->id,
            'calendar_event_link_id' => $link?->id,
            'direction' => 'outbound', 'action' => $this->action,
            'status' => $response['ok'] ? 'success' : 'failed', 'context' => $response['data'],
            'error_message' => $response['ok'] ? null : json_encode($response['data']), 'logged_at' => now(),
        ]);
    }
}
