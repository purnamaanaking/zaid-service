<?php

namespace App\Services\Integrations;

use App\Models\CalendarEvent;
use App\Models\CalendarEventLink;
use App\Models\CalendarSyncLog;
use App\Models\UserCalendarConnection;

class GoogleCalendarInboundSyncService
{
    public function __construct(
        private readonly GoogleCalendarApiService $api,
        private readonly GoogleCalendarEventTransformer $transformer,
        private readonly CalendarSyncConflictResolver $conflictResolver,
    ) {}

    public function syncConnection(UserCalendarConnection $connection): void
    {
        $result = $this->api->listChanges($connection, $connection->sync_token);
        if (! $result['ok']) return;
        foreach ($result['items'] as $remote) $this->applyEvent($connection, $remote);
        if ($result['next_sync_token']) $connection->update(['sync_token' => $result['next_sync_token'], 'last_synced_at' => now(), 'status' => 'connected']);
    }

    /** @param array<string, mixed> $remote */
    private function applyEvent(UserCalendarConnection $connection, array $remote): void
    {
        $link = CalendarEventLink::query()->where('user_calendar_connection_id', $connection->id)->where('google_event_id', $remote['id'])->first();
        $event = $link?->calendarEvent()->withTrashed()->first();
        $updatedAt = isset($remote['updated']) ? now()->parse($remote['updated']) : null;

        if (($remote['status'] ?? 'confirmed') === 'cancelled') {
            if ($event && ! $event->trashed()) $event->delete();
            if ($link) $link->update(['remote_status' => 'cancelled', 'remote_updated_at' => $updatedAt, 'last_synced_at' => now(), 'sync_status' => 'synced']);
            if ($event && $link) $this->log($connection, $event, $link, 'delete', $remote);
            return;
        }

        $data = $this->transformer->googleEventToEventData($remote);
        if ($event) {
            $decision = $this->conflictResolver->resolve($link->last_synced_at, $event->updated_at, $updatedAt);
            if ($decision['resolution'] === 'remote_wins') $event->update($data);
        } else {
            $event = CalendarEvent::query()->create(['user_id' => $connection->user_id] + $data);
            $link = CalendarEventLink::query()->create(['calendar_event_id' => $event->id, 'user_calendar_connection_id' => $connection->id, 'google_event_id' => $remote['id']]);
        }

        $link->update(['google_event_etag' => $remote['etag'] ?? null, 'remote_status' => $remote['status'] ?? null, 'remote_updated_at' => $updatedAt, 'last_synced_at' => now(), 'last_synced_payload_hash' => hash('sha256', json_encode($remote, JSON_THROW_ON_ERROR)), 'sync_status' => 'synced', 'sync_error' => null]);
        $this->log($connection, $event, $link, $event->wasRecentlyCreated ? 'create' : 'update', $remote);
    }

    /** @param array<string, mixed> $context */
    private function log(UserCalendarConnection $connection, CalendarEvent $event, CalendarEventLink $link, string $action, array $context): void
    {
        CalendarSyncLog::query()->create(['user_id' => $connection->user_id, 'calendar_event_id' => $event->id, 'user_calendar_connection_id' => $connection->id, 'calendar_event_link_id' => $link->id, 'direction' => 'inbound', 'action' => $action, 'status' => 'success', 'context' => $context, 'logged_at' => now()]);
    }
}
