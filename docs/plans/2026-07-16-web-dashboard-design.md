# Zaid Web Dashboard Implementation Plan

> **REQUIRED SUB-SKILL:** Use the executing-plans skill to implement this plan task-by-task.

**Goal:** After successful phone OTP verification, send users to a Zaid-owned web workspace with a month calendar, daily agenda, task actions, and an AI prompt composer.

**Architecture:** Keep Laravel Blade plus vanilla browser JavaScript. `/app` remains login and phone onboarding; once API returns `onboarding.next_step = dashboard`, browser navigates to `/dashboard`. Dashboard reads the existing Sanctum bearer token from `localStorage`, calls current task/calendar/prompt APIs, and renders local Zaid data only. No Google Calendar or Google Tasks API, scope, sync, or UI is used.

**Tech Stack:** Laravel Blade, existing REST API, Sanctum bearer token in browser localStorage, vanilla JavaScript, existing CSS conventions. No new packages.

---

## Product flow

1. Visitor opens `/app`.
2. Google Sign-In returns Zaid access token. Browser stores it as `zaid_web_access_token`.
3. User submits phone number, receives OTP, then verifies OTP.
4. `POST /api/v1/onboarding/phone/verify` returns active user with `data.onboarding.next_step = dashboard`.
5. Browser removes temporary OTP state and navigates to `/dashboard`.
6. `/dashboard` checks token through `GET /api/v1/onboarding/status` and `GET /api/v1/me`.
   - no token / 401: clear local state, redirect `/app`;
   - phone not verified: redirect `/app`;
   - active: render workspace.
7. Dashboard loads current month with `GET /api/v1/calendar/month?month=YYYY-MM`, selected day with `GET /api/v1/agenda/day?date=YYYY-MM-DD`, and active tasks with `GET /api/v1/tasks?include_completed=false`.
8. User can create and change tasks directly or type a natural-language request.
9. AI prompt uses `POST /api/v1/prompts`. If confirmation required, dashboard shows backend-provided confirmation text and calls `POST /api/v1/prompts/{id}/confirm` only after explicit user approval.
10. After any successful mutation, reload visible calendar, selected agenda, and task list from API. No optimistic state or custom client cache in MVP.

## MVP screens and behavior

### `/app` — onboarding only

- Existing sign-in, phone input, and OTP screens stay.
- Remove current `step-done` terminal screen.
- Successful OTP redirects to `/dashboard` immediately.
- If user revisits `/app` already active, redirect to `/dashboard` after `GET /onboarding/status`.
- If OTP browser state is missing, preserve current fallback: return user to phone input and require a new OTP.

### `/dashboard` — single responsive workspace

**Desktop layout:** left navigation rail, center calendar, right agenda/task rail. Use current Zaid dark-purple visual language; do not imitate Google Calendar branding.

**Navigation rail**
- Zaid logo / wordmark.
- Today button selects today.
- Calendar button resets month calendar focus.
- Tasks button scrolls/focuses task list; not a separate route in MVP.
- User name/email from `/me`.
- Logout clears `zaid_web_access_token`, `zaid_verification_id`, `zaid_phone_number`, then navigates `/app`.

**Center calendar**
- Header has previous month, `Today`, next month, and `Month` label only.
- Seven weekday headings and 6x7 date grid.
- Calendar summary endpoint provides task count per day; date cells show a count dot/badge.
- Selected day has a strong focus state; today has a secondary ring.
- Clicking a day sets selected date and loads its agenda.
- Calendar does not support drag/drop, recurring-event visual expansion, week/day view, timezone editing, or Google import in MVP.

**Right agenda / task rail**
- Header: selected date, task count, `+ Task` control.
- Task cards show title, time or `All day`, status, and source.
- Checkbox completes pending task through `POST /api/v1/tasks/{id}/complete`; completed task disappears from active list after refresh.
- Task row click opens an inline editor with title, optional description, date, time, all-day checkbox, save, and delete controls.
- `+ Task` opens same inline editor empty. Submit calls `POST /api/v1/tasks`; edits call `PATCH /api/v1/tasks/{id}`; delete calls `DELETE /api/v1/tasks/{id}`.
- Inputs use native HTML `date`, `time`, and checkbox controls. No picker dependency.

**AI prompt composer**
- Persistent composer at bottom/above agenda: text input plus `Ask Zaid` button.
- Placeholder examples: `Besok jam 10 follow up client 30 menit`, `Buat reminder bayar internet hari Jumat`.
- On submit disable controls and show processing state.
- API response with executed result: show concise success message, clear input, refresh data.
- API response with `requires_confirmation = true`: open confirmation panel with backend question and safe action description. Buttons: `Confirm` / `Cancel`.
  - Confirm: `POST /api/v1/prompts/{id}/confirm` body `{ "confirmed": true }`.
  - Cancel: same endpoint body `{ "confirmed": false }` if backend supports cancellation; otherwise close UI and state this limitation before implementation. Do not invent action fields.
- Errors show API `message`; validation errors show first field error. Prompt is never executed twice on page reload.

## API contract used

| UI action | Existing endpoint | Request |
|---|---|---|
| Session check | `GET /api/v1/onboarding/status` | Bearer token |
| Profile | `GET /api/v1/me` | Bearer token |
| Month markers | `GET /api/v1/calendar/month?month=YYYY-MM` | Bearer token |
| Selected-day agenda | `GET /api/v1/agenda/day?date=YYYY-MM-DD` | Bearer token |
| Active task list | `GET /api/v1/tasks?include_completed=false` | Bearer token |
| Create task | `POST /api/v1/tasks` | title, description, scheduled_date, scheduled_time, timezone, all_day |
| Edit task | `PATCH /api/v1/tasks/{id}` | changed task fields |
| Delete task | `DELETE /api/v1/tasks/{id}` | Bearer token |
| Complete task | `POST /api/v1/tasks/{id}/complete` | Bearer token |
| AI command | `POST /api/v1/prompts` | `{ "text": "..." }` |
| Confirm AI action | `POST /api/v1/prompts/{id}/confirm` | `{ "confirmed": true }` |

## Explicit non-goals

- Google Calendar / Google Tasks sync, OAuth scopes, import, export, and UI.
- Week/day calendar view, drag-and-drop scheduling, shared calendars, invitations, and real-time updates.
- New SPA framework, component library, or JavaScript dependency.
- Offline mode, push notifications, attachment UI, advanced recurrence editor, and analytics.
- Rebuilding existing mobile app screens in web.

## Implementation tasks

### Task 1: Add dashboard route and test public shell

**Files:**
- Modify: `routes/web.php`
- Create: `resources/views/dashboard.blade.php`
- Modify: `tests/Feature/LandingPageTest.php`

**Step 1: Write failing test**

Add a `test_dashboard_route_renders_workspace_shell` feature test:

```php
$this->get('/dashboard')
    ->assertOk()
    ->assertSee('Zaid Workspace')
    ->assertSee('Calendar')
    ->assertSee('Ask Zaid');
```

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/LandingPageTest.php --filter=dashboard_route`

Expected: FAIL because `/dashboard` does not exist.

**Step 3: Add minimal route and Blade shell**

```php
Route::get('/dashboard', fn () => view('dashboard'));
```

Render semantic placeholders for workspace heading, calendar region, agenda region, and AI composer. Do not add API code yet.

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/LandingPageTest.php --filter=dashboard_route`

Expected: PASS.

**Step 5: Commit**

```bash
git add routes/web.php resources/views/dashboard.blade.php tests/Feature/LandingPageTest.php
git commit -m "Add web dashboard shell"
```

### Task 2: Redirect completed onboarding to dashboard

**Files:**
- Modify: `resources/views/app.blade.php`
- Modify: `tests/Feature/LandingPageTest.php`

**Step 1: Write failing test**

Extend app flow test to require dashboard redirect source:

```php
->assertSee("window.location.assign('/dashboard')", false)
->assertDontSee('Setup complete')
```

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/LandingPageTest.php --filter=app_flow`

Expected: FAIL because onboarding shows a terminal `step-done` screen.

**Step 3: Implement minimal redirect**

- Remove `step-done` markup and `stepDone` JavaScript references.
- In OTP success handler, remove temporary OTP storage and call `window.location.assign('/dashboard')`.
- In `afterLoginFlow()` and `refreshState()`, redirect active users (`next_step !== phone_input` and `!== verify_otp`) to `/dashboard`.
- Keep invalid/missing OTP fallback unchanged.

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/LandingPageTest.php --filter=app_flow`

Expected: PASS.

**Step 5: Commit**

```bash
git add resources/views/app.blade.php tests/Feature/LandingPageTest.php
git commit -m "Redirect completed web onboarding to dashboard"
```

### Task 3: Add authenticated dashboard bootstrap and logout

**Files:**
- Modify: `resources/views/dashboard.blade.php`
- Modify: `tests/Feature/LandingPageTest.php`

**Step 1: Write failing test**

Add static page assertions for `zaid_web_access_token`, `/api/v1/onboarding/status`, `/api/v1/me`, and `Logout`.

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/LandingPageTest.php --filter=dashboard`

Expected: FAIL because shell has no authentication bootstrap.

**Step 3: Implement minimal client session guard**

- Reuse existing localStorage keys exactly.
- Add one `api(path, options)` helper: prefix `/api/v1`, attach `Accept: application/json` and Bearer token, parse JSON, throw API error body.
- On load: if no token, `window.location.replace('/app')`.
- Fetch onboarding status then `/me`.
- Non-dashboard status redirects `/app`; `401` clears all Zaid localStorage keys then redirects `/app`.
- Populate name/email in sidebar.
- Logout calls `/auth/logout` best-effort, clears keys, redirects `/app`.

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/LandingPageTest.php --filter=dashboard`

Expected: PASS.

**Step 5: Commit**

```bash
git add resources/views/dashboard.blade.php tests/Feature/LandingPageTest.php
git commit -m "Guard web dashboard session"
```

### Task 4: Render selectable month calendar and daily agenda

**Files:**
- Modify: `resources/views/dashboard.blade.php`
- Modify: `tests/Feature/LandingPageTest.php`

**Step 1: Write failing test**

Assert dashboard includes calendar navigation, `calendar/month`, `agenda/day`, calendar grid `role="grid"`, and agenda container.

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/LandingPageTest.php --filter=dashboard`

Expected: FAIL because calendar behavior is absent.

**Step 3: Implement minimal calendar functions**

Add plain functions:

```js
const formatDate = (date) => date.toISOString().slice(0, 10);
const formatMonth = (date) => formatDate(date).slice(0, 7);
```

- Maintain `visibleMonth` as first day of visible month and `selectedDate` as local current date string.
- `loadMonth()` calls `/calendar/month?month=${formatMonth(visibleMonth)}` and maps `days` by date.
- Build 42 buttons from Sunday before first month date through Saturday after final month date. Each date button contains day number and task count if nonzero.
- `selectDate(date)` updates selected visual state and calls `loadAgenda()`.
- `loadAgenda()` calls `/agenda/day?date=${selectedDate}` and renders task cards.
- Previous/next controls mutate `visibleMonth` by one native `Date#setMonth` operation. Today selects today and makes its month visible.
- Use `textContent`, never `innerHTML` for task/user supplied strings.

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/LandingPageTest.php --filter=dashboard`

Expected: PASS.

**Step 5: Commit**

```bash
git add resources/views/dashboard.blade.php tests/Feature/LandingPageTest.php
git commit -m "Render web calendar and daily agenda"
```

### Task 5: Add manual task create, edit, complete, and delete

**Files:**
- Modify: `resources/views/dashboard.blade.php`
- Modify: `tests/Feature/LandingPageTest.php`
- Verify: `tests/Feature/Tasks/TaskCrudTest.php`

**Step 1: Write failing test**

Dashboard static test must require native task form inputs, `/tasks`, `/complete`, and `DELETE` usage. Add a backend task update test only if existing task CRUD tests do not cover all required behavior.

**Step 2: Run tests to verify they fail**

Run:

```bash
php artisan test tests/Feature/LandingPageTest.php --filter=dashboard
php artisan test tests/Feature/Tasks/TaskCrudTest.php
```

Expected: dashboard assertion FAIL; existing backend suite remains PASS.

**Step 3: Implement minimal task controls**

- Add `+ Task` control and hidden inline editor.
- Fields: title required, description optional, native `date`, native `time`, all-day checkbox.
- New task default date equals `selectedDate`, timezone `Asia/Jakarta`.
- `saveTask()` selects `POST /tasks` or `PATCH /tasks/${id}`. Build payload with `scheduled_time: null` when all-day.
- Complete button calls `POST /tasks/${id}/complete`.
- Delete button requires browser `window.confirm`, then calls `DELETE /tasks/${id}`.
- Every successful mutation calls `await Promise.all([loadMonth(), loadAgenda(), loadTaskList()])`.
- Render task values through DOM node `textContent`; never interpolate task title/description into HTML.

**Step 4: Run tests to verify they pass**

Run:

```bash
php artisan test tests/Feature/LandingPageTest.php
php artisan test tests/Feature/Tasks/TaskCrudTest.php
```

Expected: PASS.

**Step 5: Commit**

```bash
git add resources/views/dashboard.blade.php tests/Feature/LandingPageTest.php tests/Feature/Tasks/TaskCrudTest.php
git commit -m "Add web task controls"
```

### Task 6: Add AI prompt and explicit confirmation UI

**Files:**
- Modify: `resources/views/dashboard.blade.php`
- Modify: `tests/Feature/LandingPageTest.php`
- Verify: `tests/Feature/Prompts/PromptApiTest.php`

**Step 1: Write failing test**

Require dashboard source to contain prompt composer, `/prompts`, `/confirm`, and `confirmed`.

**Step 2: Run tests to verify they fail**

Run:

```bash
php artisan test tests/Feature/LandingPageTest.php --filter=dashboard
php artisan test tests/Feature/Prompts/PromptApiTest.php
```

Expected: dashboard assertion FAIL; backend prompt tests PASS.

**Step 3: Implement minimal prompt flow**

- Add textarea/input and submit button, disabled while request runs.
- Send trimmed text only. Do nothing for empty input.
- POST `/prompts` with `{ text }`.
- If `data.requires_confirmation` is false, show `message`, clear composer, refresh all dashboard data.
- If true, keep returned `prompt_request_id` in one local `pendingPrompt` variable. Render `confirmation.question` using `textContent`, not markup. Confirm calls `/prompts/${id}/confirm` with `{ confirmed: true }`; then clear pending state and refresh.
- Cancel behavior must be verified against current `PromptCommandService::confirm()` behavior before writing it. If backend accepts false, call it. If it does not, hide panel and do not claim cancellation persisted.
- Do not implement free-form target selection unless backend response/action contract currently supports it.

**Step 4: Run tests to verify they pass**

Run:

```bash
php artisan test tests/Feature/LandingPageTest.php
php artisan test tests/Feature/Prompts/PromptApiTest.php
```

Expected: PASS.

**Step 5: Commit**

```bash
git add resources/views/dashboard.blade.php tests/Feature/LandingPageTest.php
git commit -m "Add AI prompt actions to web dashboard"
```

### Task 7: Responsive, accessibility, and error-path polish

**Files:**
- Modify: `resources/views/dashboard.blade.php`
- Modify: `tests/Feature/LandingPageTest.php`

**Step 1: Write failing test**

Require semantic landmarks, calendar `aria-label`, agenda live region, keyboard-focusable controls, and user-facing loading/error containers.

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/LandingPageTest.php --filter=dashboard`

Expected: FAIL for absent semantics.

**Step 3: Implement minimal polish**

- Use `main`, `nav`, `section`, `aside`, buttons, labels, and visible focus states.
- Calendar date buttons include `aria-label="July 16, 2026, 2 tasks"` shape.
- Status area uses `role="status" aria-live="polite"`.
- Desktop uses three columns; below `900px` stack calendar before agenda; below `640px` hide nonessential sidebar labels but retain controls.
- Load errors leave current rendered data intact and show retry button. Do not blank workspace.
- `prefers-reduced-motion` disables transition animation.

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/LandingPageTest.php`

Expected: PASS.

**Step 5: Commit**

```bash
git add resources/views/dashboard.blade.php tests/Feature/LandingPageTest.php
git commit -m "Polish responsive web dashboard"
```

### Task 8: Full regression verification and deploy handoff

**Files:**
- Modify only if verification exposes a real issue.

**Step 1: Run targeted web and API tests**

```bash
php artisan test tests/Feature/LandingPageTest.php
php artisan test tests/Feature/Onboarding
php artisan test tests/Feature/Tasks/TaskCrudTest.php
php artisan test tests/Feature/Prompts/PromptApiTest.php
```

Expected: all pass.

**Step 2: Run full server suite**

```bash
php artisan test
```

Expected: all pass. Fix regressions before proceeding.

**Step 3: Static validation**

```bash
git diff --check
php artisan route:list --path=dashboard
```

Expected: no whitespace errors; `GET|HEAD dashboard` route appears.

**Step 4: Manual smoke test before deployment**

1. Open `/app` in clean browser storage.
2. Sign in, submit phone, complete OTP.
3. Confirm redirect to `/dashboard`.
4. Verify current month and selected-day agenda load.
5. Create a dated task and see date count refresh.
6. Complete it and verify agenda refresh.
7. Submit safe AI task prompt; inspect result.
8. Submit ambiguous/destructive prompt; confirm panel must appear before mutation.
9. Logout; revisit `/dashboard`; must redirect `/app`.

**Step 5: Commit and push**

```bash
git status --short
git add resources/views/app.blade.php resources/views/dashboard.blade.php routes/web.php tests/Feature
git commit -m "Add Zaid web planning dashboard"
git push origin main
```
