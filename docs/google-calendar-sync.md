# Google Calendar & Tasks Sync

## Overview

Zaid supports Google Calendar OAuth connection plus two-way synchronization for both **Calendar Events** and **Google Tasks** in production.

Production domain used by the live server:
- `https://zaid-assist.my.id`

## Current behavior

- Google identity login and Google Calendar connection are separate flows.
- Google Calendar OAuth callback is public and resolves the user via signed/encrypted `state`.
- Scheduled task create/update/delete in Zaid syncs outbound to the connected primary Google Calendar.
- Remote Google Calendar event changes sync inbound into local tasks.
- Initial inbound sync now paginates through all Google Calendar event pages before storing the final `sync_token`.
- Conflict handling currently uses **remote_wins** when both local and remote changed after the last sync.

## OAuth flow

Use these endpoints after the user is authenticated:

- `GET /api/v1/integrations/google-calendar/connect`
- `GET /api/v1/integrations/google-calendar/callback`
- `GET /api/v1/integrations/google-calendar/status`
- `DELETE /api/v1/integrations/google-calendar`

After a successful callback, the browser is redirected to:
- `GET /integrations/google-calendar/connected`

### Required Google Cloud redirect URI

Register this in the Google OAuth client:
- `https://zaid-assist.my.id/api/v1/integrations/google-calendar/callback`

## Environment variables

- `APP_URL=https://zaid-assist.my.id`
- `GOOGLE_CALENDAR_REDIRECT_URI=https://zaid-assist.my.id/api/v1/integrations/google-calendar/callback`
- `GOOGLE_CALENDAR_SCOPES`
- `GOOGLE_CALENDAR_PRIMARY_ID`
- `GOOGLE_CALENDAR_SYNC_INTERVAL_MINUTES`

## Sync routing

| Task has `scheduled_time`? | Syncs to | API |
|---|---|---|
| Yes | Google Calendar Event | Calendar API v3 |
| No | Google Tasks | Tasks API v1 |

## Sync directions

### Local → Google Calendar Events
Task mutations dispatch `SyncTaskToGoogleCalendarJob` when:
- the user has a connected Google Calendar integration
- the task has `scheduled_time`

### Local → Google Tasks
Task mutations dispatch `SyncTaskToGoogleTasksJob` when:
- the user has a connected Google Calendar integration
- the task does NOT have `scheduled_time`

Both directions support: create, update, delete, complete.

### Google Calendar → Local (real-time)
Uses Google Calendar push notifications (webhooks):
- Webhook endpoint: `POST /api/v1/webhooks/google-calendar`
- Watch channel registered on OAuth connect
- Auto-renewed hourly
- Changes synced within seconds

### Google Tasks → Local (polling)
Google Tasks API does not support push notifications, so we poll:
- Every 2 minutes via scheduler
- Uses incremental sync tokens
- `SyncGoogleTasksConnectionJob` + `GoogleTasksInboundSyncService`

## Failure handling

- Expired/rejected sync tokens mark the connection `error` with `last_error_message`.
- Revoked/expired refresh tokens mark the connection `revoked` with `last_error_message`.
- Integration status is surfaced through `/api/v1/me` and `/api/v1/settings`.
- If Google Calendar list lookup for `primary` fails, OAuth callback falls back to the calendar metadata endpoint.
- Success redirect uses `/integrations/google-calendar/connected` so browser OAuth no longer lands on a 404 page.

## Important limitations

- Conflict policy is currently `remote_wins`.
- Complex Google Calendar recurrence semantics are not fully implemented.
- Multi-calendar support is not implemented; the integration targets the connected primary calendar.
- The inbound first sync may be long on large calendars because it now walks all pages before storing `sync_token`.
- Google Tasks API does not support webhooks, so inbound sync relies on 2-minute polling.
- Google Tasks requires the `https://www.googleapis.com/auth/tasks` scope (added automatically). Users who connected before this feature must reconnect to grant the Tasks scope.
