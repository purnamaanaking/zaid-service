# Google Calendar Two-Way Sync Implementation Plan

> **REQUIRED SUB-SKILL:** Use the executing-plans skill to implement this plan task-by-task.

**Goal:** Add full Google Calendar integration so scheduled tasks in Zaid sync to the user's Google Calendar, and Google Calendar event changes sync back into Zaid tasks.

**Architecture:** Keep `tasks` as the core local domain model, but add a durable sync layer with Google OAuth token storage, task↔event mapping, webhook/poll-driven change ingestion, and conflict metadata. Use one-way command execution at the domain layer and a dedicated calendar sync service to translate between Zaid task semantics and Google Calendar event semantics.

**Tech Stack:** Laravel 13, PostgreSQL, Laravel Socialite/Google OAuth, Google Calendar API v3, queued jobs, scheduler/console commands, Sanctum, PHPUnit.

---

## Design Summary

Current codebase only uses Google auth for identity verification. It does **not** persist Google OAuth access/refresh tokens, request calendar scopes, or store any mapping between Zaid tasks and Google Calendar events. Agenda reads come entirely from local `tasks`. To support real two-way sync, we need to introduce:

1. **Google Calendar OAuth scope + token persistence**
   - Request `openid email profile https://www.googleapis.com/auth/calendar`.
   - Persist Google refresh token and token metadata per user/provider.
   - Support reconnect/re-consent when refresh token is absent/revoked.

2. **Calendar integration state model**
   - Track which Google calendar is connected.
   - Track sync state (`connected`, `syncing`, `error`, `revoked`).
   - Track mapping from `tasks.id` to `google_event_id` plus remote etag/update version.

3. **Outgoing sync path (Zaid → Google Calendar)**
   - Task create/update/delete/complete/restore must enqueue sync jobs.
   - Recurring task support should be restricted for first release of sync unless recurrence translation is explicitly implemented.

4. **Incoming sync path (Google Calendar → Zaid)**
   - Use Google incremental sync (`syncToken`) via scheduled polling as the initial reliable mechanism.
   - Optionally add push watch channels later, but polling is the safer MVP for correctness.
   - Convert remote event changes into task creates/updates/deletes with source attribution.

5. **Conflict policy**
   - Two-way sync requires deterministic conflict handling.
   - Recommended initial rule: **last-write-wins with source timestamps**, plus per-record `last_synced_at`, `last_local_change_at`, `last_remote_change_at`, and a `sync_status` field for observability.
   - Record conflicts in a sync log table for debugging.

6. **Prompt / WhatsApp / App integration consistency**
   - Existing task mutations all go through `TaskMutationService` and prompt services. Calendar sync must hook into those same domain flows, not bypass them.

---

## Constraints and First-Release Guardrails

- Use **scheduled polling** first, not Google push watch, to reduce irreversible architectural complexity.
- Support only the user's **primary calendar** in V1 of sync unless product explicitly requires calendar selection.
- Treat local `tasks` as the canonical application model, but allow remote-created Google events to materialize as tasks.
- Skip unsupported event shapes in V1:
  - attendee-heavy meetings
  - conference metadata
  - reminders-specific overrides
  - complex RRULE recurrence (unless explicitly implemented)
  - tasks without any scheduled date/time mapping
- Add a visible sync status and error state rather than silently failing.

---

## Domain Changes Needed

### New persistence concepts

1. `user_calendar_connections`
   - one per user/provider calendar connection
   - stores calendar id, sync token, connection status, token metadata, last sync timestamps

2. `calendar_event_links`
   - maps local task to remote Google event
   - stores `google_event_id`, `etag`, `remote_updated_at`, `last_synced_payload_hash`, `sync_status`

3. `calendar_sync_logs`
   - append-only audit for sync attempts/errors/conflicts

4. Extend `user_identities` or create secure token storage
   - current `user_identities.provider_payload` is insufficient and unsafe as the long-term token store contract
   - store encrypted refresh token and token scopes explicitly

### Task model additions

Add fields/behavior to support sync provenance:
- `external_source` nullable (`google_calendar` / null)
- `external_last_modified_at`
- `local_last_modified_at`
- `sync_status`
- `sync_error`

If avoiding task table expansion initially, these may live in `calendar_event_links`, but `tasks` still needs enough metadata to support local/remote mutation decisions.

---

## Authentication / OAuth Design

Current `POST /api/v1/auth/google` uses an ID token for identity only. That is insufficient for Calendar access because Google Calendar requires OAuth authorization granting refreshable API access.

### Recommended auth design

Keep existing identity auth endpoint for account login, but add a **separate calendar connect flow**:

- `GET /api/v1/integrations/google-calendar/connect`
  - returns redirect URL or signed state payload
- `GET /api/v1/integrations/google-calendar/callback`
  - exchanges code for access/refresh token
  - stores encrypted refresh token
  - creates/updates `user_calendar_connections`
- `DELETE /api/v1/integrations/google-calendar`
  - disconnects integration and marks mappings stale
- `GET /api/v1/integrations/google-calendar/status`
  - returns connection/sync status

Why separate from login?
- avoids forcing Calendar scope during basic sign-in
- preserves current Google identity login behavior
- supports reconnect/re-consent independently

---

## Sync Flow Design

### Local → Google

When any scheduled task changes through:
- manual app CRUD
- prompt app flow
- WhatsApp prompt flow

then:
1. domain mutation completes locally
2. enqueue `SyncTaskToGoogleCalendarJob`
3. job loads mapping/token/connection
4. create/update/delete remote event
5. update `calendar_event_links`
6. write `calendar_sync_logs`

### Google → Local

Scheduled command every minute/five minutes:
1. fetch active calendar connections
2. use incremental sync with `syncToken`
3. for each changed event:
   - find existing `calendar_event_links`
   - if found, update corresponding local task
   - if not found and event qualifies, create local task + link
   - if event deleted/cancelled, soft-delete or cancel local task
4. persist new sync token
5. log outcomes/errors

### Conflict handling

For V1:
- compare `remote_updated_at` with `local_last_modified_at`
- if both changed since `last_synced_at`, mark conflict and choose last-write-wins while logging the conflict
- never silently discard conflict without a log record

---

## API Surface to Add

### Integration endpoints
- `GET /api/v1/integrations/google-calendar/status`
- `GET /api/v1/integrations/google-calendar/connect`
- `GET /api/v1/integrations/google-calendar/callback`
- `DELETE /api/v1/integrations/google-calendar`
- `POST /api/v1/integrations/google-calendar/resync`

### Response examples should include
- connection state
- connected calendar id/summary
- last sync time
- sync health/error

---

## Security Requirements

- Encrypt refresh tokens at rest.
- Validate OAuth `state` and user ownership.
- Request minimum required scopes.
- Support token refresh and revoke handling.
- Ensure remote events are only synced within the authenticated user's connection.
- Never expose raw Google tokens through API responses.

---

## Testing Strategy

Follow TDD rigorously.

### Unit tests
- token storage / encryption handling
- Google event ↔ task mapping transformer
- conflict resolver logic
- sync status transitions

### Feature tests
- connect/disconnect flow
- callback flow storing connection and tokens
- local task create triggers sync job dispatch
- remote event sync creates/updates/deletes tasks
- recurrence/event filtering behavior
- conflict logging behavior

### Integration tests (HTTP fake)
- Google Calendar API responses mocked with `Http::fake()`
- incremental sync token lifecycle
- 401 refresh token expired path

### Regression tests
- existing app/manual/prompt/WhatsApp task flows still work without calendar connection
- no calendar side effects when user is not connected

---

## Implementation Plan

### Task 1: Add calendar integration schema

**Files:**
- Create: `database/migrations/2026_05_07_000100_create_user_calendar_connections_table.php`
- Create: `database/migrations/2026_05_07_000200_create_calendar_event_links_table.php`
- Create: `database/migrations/2026_05_07_000300_create_calendar_sync_logs_table.php`
- Modify: `app/Models/User.php`
- Create: `app/Models/UserCalendarConnection.php`
- Create: `app/Models/CalendarEventLink.php`
- Create: `app/Models/CalendarSyncLog.php`
- Test: `tests/Feature/Integrations/GoogleCalendarSchemaTest.php`

**Step 1: Write the failing test**
Create a feature/schema test asserting the new tables and columns exist and relationships can be created.

**Step 2: Run test to verify it fails**
Run:
```bash
php artisan test tests/Feature/Integrations/GoogleCalendarSchemaTest.php
```
Expected: FAIL because tables/models do not exist.

**Step 3: Write minimal implementation**
Create the migrations and Eloquent models with relationships:
- `User -> hasOne(UserCalendarConnection::class)` or `hasMany` if future multi-calendar support is desired
- `Task -> hasOne(CalendarEventLink::class)`

**Step 4: Run test to verify it passes**
Run:
```bash
php artisan test tests/Feature/Integrations/GoogleCalendarSchemaTest.php
```
Expected: PASS

**Step 5: Commit**
```bash
git add database/migrations app/Models tests/Feature/Integrations/GoogleCalendarSchemaTest.php
git commit -m "feat: add google calendar sync schema"
```

---

### Task 2: Add integration config and secure token storage

**Files:**
- Modify: `config/services.php`
- Modify: `.env.example`
- Create: `app/Support/Security/EncryptedTokenStore.php` (or equivalent service)
- Test: `tests/Unit/Integrations/EncryptedTokenStoreTest.php`

**Step 1: Write the failing test**
Write a unit test asserting refresh tokens are encrypted before persistence and can be decrypted for sync usage.

**Step 2: Run test to verify it fails**
Run:
```bash
php artisan test tests/Unit/Integrations/EncryptedTokenStoreTest.php
```
Expected: FAIL because service/config does not exist.

**Step 3: Write minimal implementation**
Add config values:
- `GOOGLE_CALENDAR_SCOPES`
- callback URL
- optional polling interval defaults

Implement encrypted token helper/service using Laravel encryption facilities.

**Step 4: Run test to verify it passes**
Run:
```bash
php artisan test tests/Unit/Integrations/EncryptedTokenStoreTest.php
```
Expected: PASS

**Step 5: Commit**
```bash
git add config/services.php .env.example app/Support/Security tests/Unit/Integrations/EncryptedTokenStoreTest.php
git commit -m "feat: add secure token storage for calendar integration"
```

---

### Task 3: Build Google Calendar OAuth connect/callback flow

**Files:**
- Modify: `routes/api.php`
- Create: `app/Http/Controllers/Api/Integrations/GoogleCalendarConnectController.php`
- Create: `app/Http/Controllers/Api/Integrations/GoogleCalendarCallbackController.php`
- Create: `app/Http/Controllers/Api/Integrations/GoogleCalendarStatusController.php`
- Create: `app/Http/Controllers/Api/Integrations/GoogleCalendarDisconnectController.php`
- Create: `app/Services/Integrations/GoogleCalendarOAuthService.php`
- Test: `tests/Feature/Integrations/GoogleCalendarOAuthFlowTest.php`

**Step 1: Write the failing test**
Write feature tests for:
- authenticated user can request connect URL
- callback stores connection and tokens
- status endpoint returns connected state
- disconnect endpoint clears connection

**Step 2: Run test to verify it fails**
Run:
```bash
php artisan test tests/Feature/Integrations/GoogleCalendarOAuthFlowTest.php
```
Expected: FAIL because endpoints/services do not exist.

**Step 3: Write minimal implementation**
Implement OAuth code exchange using Google OAuth endpoint (not ID-token login flow), store refresh token securely, create/update connection row.

**Step 4: Run test to verify it passes**
Run:
```bash
php artisan test tests/Feature/Integrations/GoogleCalendarOAuthFlowTest.php
```
Expected: PASS

**Step 5: Commit**
```bash
git add routes/api.php app/Http/Controllers/Api/Integrations app/Services/Integrations tests/Feature/Integrations/GoogleCalendarOAuthFlowTest.php
git commit -m "feat: add google calendar oauth integration flow"
```

---

### Task 4: Implement Google Calendar API client + event transformer

**Files:**
- Create: `app/Services/Integrations/GoogleCalendarApiService.php`
- Create: `app/Services/Integrations/GoogleCalendarEventTransformer.php`
- Test: `tests/Unit/Integrations/GoogleCalendarEventTransformerTest.php`
- Test: `tests/Feature/Integrations/GoogleCalendarApiServiceTest.php`

**Step 1: Write the failing test**
Write tests asserting:
- local task converts to Google event payload correctly
- Google event converts back to local task payload correctly
- all-day and scheduled-time cases are handled explicitly

**Step 2: Run test to verify it fails**
Run:
```bash
php artisan test tests/Unit/Integrations/GoogleCalendarEventTransformerTest.php tests/Feature/Integrations/GoogleCalendarApiServiceTest.php
```
Expected: FAIL

**Step 3: Write minimal implementation**
Implement API wrapper methods:
- list changes with sync token
- create event
- update event
- delete event
- refresh access token when needed

**Step 4: Run test to verify it passes**
Run:
```bash
php artisan test tests/Unit/Integrations/GoogleCalendarEventTransformerTest.php tests/Feature/Integrations/GoogleCalendarApiServiceTest.php
```
Expected: PASS

**Step 5: Commit**
```bash
git add app/Services/Integrations tests/Unit/Integrations tests/Feature/Integrations
git commit -m "feat: add google calendar api client and transformer"
```

---

### Task 5: Hook local task mutations into outbound sync jobs

**Files:**
- Modify: `app/Services/Tasks/TaskMutationService.php`
- Create: `app/Jobs/Calendar/SyncTaskToGoogleCalendarJob.php`
- Create: `app/Listeners/...` or dispatch directly from mutation service
- Test: `tests/Feature/Integrations/TaskToGoogleCalendarSyncTest.php`

**Step 1: Write the failing test**
Write tests asserting that create/update/delete/complete/restore on a connected user's scheduled task dispatch the sync job.

**Step 2: Run test to verify it fails**
Run:
```bash
php artisan test tests/Feature/Integrations/TaskToGoogleCalendarSyncTest.php
```
Expected: FAIL

**Step 3: Write minimal implementation**
Dispatch sync jobs only when:
- user has active calendar connection
- task has schedule data relevant to calendar sync

Add explicit behavior for delete/complete/restore.

**Step 4: Run test to verify it passes**
Run:
```bash
php artisan test tests/Feature/Integrations/TaskToGoogleCalendarSyncTest.php
```
Expected: PASS

**Step 5: Commit**
```bash
git add app/Services/Tasks/TaskMutationService.php app/Jobs/Calendar tests/Feature/Integrations/TaskToGoogleCalendarSyncTest.php
git commit -m "feat: sync local task mutations to google calendar"
```

---

### Task 6: Persist task↔event mapping and sync logs

**Files:**
- Modify: `app/Jobs/Calendar/SyncTaskToGoogleCalendarJob.php`
- Modify: `app/Models/Task.php`
- Modify: `app/Models/CalendarEventLink.php`
- Test: `tests/Feature/Integrations/CalendarEventLinkingTest.php`

**Step 1: Write the failing test**
Write a test asserting that after remote create/update/delete succeeds, the mapping table and sync logs are updated.

**Step 2: Run test to verify it fails**
Run:
```bash
php artisan test tests/Feature/Integrations/CalendarEventLinkingTest.php
```
Expected: FAIL

**Step 3: Write minimal implementation**
Store `google_event_id`, etag, timestamps, sync status, and append log rows.

**Step 4: Run test to verify it passes**
Run:
```bash
php artisan test tests/Feature/Integrations/CalendarEventLinkingTest.php
```
Expected: PASS

**Step 5: Commit**
```bash
git add app/Models app/Jobs/Calendar tests/Feature/Integrations/CalendarEventLinkingTest.php
git commit -m "feat: persist calendar event links and sync logs"
```

---

### Task 7: Implement incremental Google → local sync command

**Files:**
- Modify: `routes/console.php`
- Create: `app/Console/Commands/SyncGoogleCalendarChangesCommand.php`
- Create: `app/Jobs/Calendar/SyncGoogleCalendarConnectionJob.php`
- Create: `app/Services/Integrations/GoogleCalendarInboundSyncService.php`
- Test: `tests/Feature/Integrations/GoogleCalendarInboundSyncTest.php`

**Step 1: Write the failing test**
Write feature tests covering:
- remote event creates local task
- remote event updates local task
- remote event cancellation deletes/cancels local task
- new sync token is persisted

**Step 2: Run test to verify it fails**
Run:
```bash
php artisan test tests/Feature/Integrations/GoogleCalendarInboundSyncTest.php
```
Expected: FAIL

**Step 3: Write minimal implementation**
Implement a scheduled command and per-connection sync service using Google incremental sync tokens.

**Step 4: Run test to verify it passes**
Run:
```bash
php artisan test tests/Feature/Integrations/GoogleCalendarInboundSyncTest.php
```
Expected: PASS

**Step 5: Commit**
```bash
git add routes/console.php app/Console/Commands app/Jobs/Calendar app/Services/Integrations tests/Feature/Integrations/GoogleCalendarInboundSyncTest.php
git commit -m "feat: sync google calendar changes into local tasks"
```

---

### Task 8: Add conflict detection and resolution

**Files:**
- Create: `app/Services/Integrations/CalendarSyncConflictResolver.php`
- Modify: inbound/outbound sync services
- Test: `tests/Unit/Integrations/CalendarSyncConflictResolverTest.php`
- Test: `tests/Feature/Integrations/GoogleCalendarConflictHandlingTest.php`

**Step 1: Write the failing test**
Write tests for simultaneous local and remote edits and assert the chosen last-write-wins policy plus conflict log creation.

**Step 2: Run test to verify it fails**
Run:
```bash
php artisan test tests/Unit/Integrations/CalendarSyncConflictResolverTest.php tests/Feature/Integrations/GoogleCalendarConflictHandlingTest.php
```
Expected: FAIL

**Step 3: Write minimal implementation**
Implement deterministic conflict resolution with explicit logging.

**Step 4: Run test to verify it passes**
Run:
```bash
php artisan test tests/Unit/Integrations/CalendarSyncConflictResolverTest.php tests/Feature/Integrations/GoogleCalendarConflictHandlingTest.php
```
Expected: PASS

**Step 5: Commit**
```bash
git add app/Services/Integrations tests/Unit/Integrations tests/Feature/Integrations
git commit -m "feat: add google calendar sync conflict handling"
```

---

### Task 9: Surface integration status in user-facing API

**Files:**
- Modify: `app/Http/Controllers/Api/User/MeController.php`
- Modify: `app/Http/Controllers/Api/Settings/SettingsController.php`
- Possibly create resources for integration state
- Test: `tests/Feature/Integrations/GoogleCalendarStatusExposureTest.php`

**Step 1: Write the failing test**
Write tests asserting `/me` or `/settings` exposes Google Calendar connection + sync health summary.

**Step 2: Run test to verify it fails**
Run:
```bash
php artisan test tests/Feature/Integrations/GoogleCalendarStatusExposureTest.php
```
Expected: FAIL

**Step 3: Write minimal implementation**
Expose safe, non-secret integration status fields only.

**Step 4: Run test to verify it passes**
Run:
```bash
php artisan test tests/Feature/Integrations/GoogleCalendarStatusExposureTest.php
```
Expected: PASS

**Step 5: Commit**
```bash
git add app/Http/Controllers/Api/User app/Http/Controllers/Api/Settings tests/Feature/Integrations/GoogleCalendarStatusExposureTest.php
git commit -m "feat: expose google calendar integration status"
```

---

### Task 10: Add operational safeguards and docs

**Files:**
- Modify: `.env.example`
- Modify: `docs/API-SPEC.md`
- Modify: `docs/ERD.md`
- Modify: `docs/PRD.md` (if product behavior changed)
- Create: `docs/google-calendar-sync.md`
- Test: `tests/Feature/Integrations/GoogleCalendarFailureModesTest.php`

**Step 1: Write the failing test**
Write tests for revoked token / expired sync token / disconnected user behavior.

**Step 2: Run test to verify it fails**
Run:
```bash
php artisan test tests/Feature/Integrations/GoogleCalendarFailureModesTest.php
```
Expected: FAIL

**Step 3: Write minimal implementation**
Handle revoked tokens by marking connection errored and surfacing reconnect guidance. Update docs and env samples.

**Step 4: Run test to verify it passes**
Run:
```bash
php artisan test tests/Feature/Integrations/GoogleCalendarFailureModesTest.php
```
Expected: PASS

**Step 5: Commit**
```bash
git add .env.example docs tests/Feature/Integrations/GoogleCalendarFailureModesTest.php
git commit -m "docs: finalize google calendar sync integration behavior"
```

---

## Notes for the Implementer

- Do **not** overload current `/auth/google` identity endpoint with calendar authorization code exchange.
- Keep Google Calendar integration optional per user.
- Do not perform direct Google API writes inside controllers.
- Prefer queued jobs for all remote sync actions.
- Ensure WhatsApp prompt flows and manual app flows both trigger the same sync pipeline through `TaskMutationService`.
- Reuse existing audit style (`TaskChange`, `PromptRequest`, `PromptAction`) where possible.
- Make all Google API calls fakeable via Laravel `Http::fake()` for tests.
- Record enough metadata to debug sync drift later.

## Verification Checklist

Before considering implementation complete:
- [ ] User can connect Google Calendar from authenticated app flow
- [ ] Refresh token is stored encrypted
- [ ] Scheduled task create/update/delete syncs to Google Calendar
- [ ] Remote Google event create/update/delete syncs into local tasks
- [ ] Conflict cases are logged and resolved deterministically
- [ ] Existing non-calendar users experience no regression
- [ ] App and WhatsApp task mutations still work
- [ ] Docs and API spec reflect the new behavior

