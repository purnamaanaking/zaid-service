# Google Calendar Sync

## Overview

Zaid now supports Google Calendar integration with a dedicated OAuth connect flow and two-way synchronization primitives.

## Current behavior

- Users authenticate into Zaid with Google identity as before.
- Google Calendar connection is a separate integration flow.
- Scheduled tasks created/updated/deleted in Zaid can be synced to the user's connected Google Calendar.
- Remote Google Calendar event changes can be synced back into local tasks using incremental sync tokens.
- Conflict handling currently uses **last-write-wins**, with **remote changes winning** when both local and remote changed since the last sync.

## OAuth flow

Use these endpoints after the user is authenticated:

- `GET /api/v1/integrations/google-calendar/connect`
- `GET /api/v1/integrations/google-calendar/callback`
- `GET /api/v1/integrations/google-calendar/status`
- `DELETE /api/v1/integrations/google-calendar`

## Environment variables

- `GOOGLE_CALENDAR_REDIRECT_URI`
- `GOOGLE_CALENDAR_SCOPES`
- `GOOGLE_CALENDAR_PRIMARY_ID`
- `GOOGLE_CALENDAR_SYNC_INTERVAL_MINUTES`

## Sync directions

### Local → Google Calendar
Task mutations dispatch `SyncTaskToGoogleCalendarJob` when:
- the user has a connected Google Calendar integration
- the task has schedule data

### Google Calendar → Local
The inbound sync pipeline uses:
- `SyncGoogleCalendarChangesCommand`
- `SyncGoogleCalendarConnectionJob`
- `GoogleCalendarInboundSyncService`

Incremental sync persists `sync_token` per connection.

## Failure handling

- Expired/rejected sync tokens mark the connection `error` with `last_error_message`.
- Revoked/expired refresh tokens mark the connection `revoked` with `last_error_message`.
- Integration status is surfaced through `/api/v1/me` and `/api/v1/settings`.

## Important limitations

- Conflict policy is currently remote-wins.
- Complex Google Calendar recurrence semantics are not fully implemented.
- Multi-calendar support is not implemented; the integration targets the configured primary calendar.
