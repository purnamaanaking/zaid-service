# Google Calendar Sync

## Overview

Zaid supports Google Calendar OAuth connection plus two-way task/event synchronization in production.

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

## Sync directions

### Local → Google Calendar
Task mutations dispatch `SyncTaskToGoogleCalendarJob` when:
- the user has a connected Google Calendar integration
- the task has schedule data

Verified live:
- create task → create Google event
- update task → update Google event
- delete task → delete Google event

### Google Calendar → Local
The inbound sync pipeline uses:
- `SyncGoogleCalendarChangesCommand`
- `SyncGoogleCalendarConnectionJob`
- `GoogleCalendarInboundSyncService`

Behavior:
- paginates through all pages returned by Google Calendar
- persists the final `sync_token`
- reuses existing `calendar_event_links`
- avoids recreating linked cancelled events on full/backfill syncs

Verified live:
- Google Calendar events on 22 May were imported into local tasks after pagination and link-reuse fixes

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
