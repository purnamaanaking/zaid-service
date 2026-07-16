# Separate Events and Task Lists Implementation Plan

> **REQUIRED SUB-SKILL:** Use the executing-plans skill to implement this plan task-by-task.

**Goal:** Make Calendar store independent Zaid events and make Tasks use user-owned task lists, so events never become tasks and tasks never become calendar events.

**Architecture:** Add `calendar_events` and `task_lists` as first-class user-owned models. Keep existing `tasks` for task work only, adding nullable `task_list_id`; preserve all existing task data in a default list during migration. Calendar gets dedicated Event CRUD and monthly/weekly feed endpoints. The dashboard creates either an Event or Task from a date popup, while Tasks renders user-created list columns.

**Tech Stack:** Laravel migrations, Eloquent, Laravel JSON controllers/resources/tests, existing Blade + browser JavaScript. No new package.

---

## Data contract

### Calendar event

`calendar_events`
- `id` UUID primary key
- `user_id` UUID, foreign key, cascade delete
- `title` string max 255
- `description` nullable text
- `starts_at` nullable timestamp
- `ends_at` nullable timestamp
- `timezone` string default `Asia/Jakarta`
- `all_day` boolean default false
- timestamps, soft delete

Event requirements:
- title required
- if `all_day=false`, `starts_at` required
- if `ends_at` supplied, it must not precede `starts_at`
- Calendar event APIs never query or mutate `tasks`.

### Task list

`task_lists`
- `id` UUID primary key
- `user_id` UUID, foreign key, cascade delete
- `name` string max 100
- `color` nullable string max 20
- `position` unsigned integer default 0
- timestamps, soft delete
- unique active name per user through application validation; no global name constraint

`tasks.task_list_id`
- nullable UUID foreign key to `task_lists`, null on list delete
- existing tasks moved into one per-user `My Tasks` list during migration.

Task requirements:
- task CRUD accepts `task_list_id`, validates list ownership
- current `scheduled_date` remains a task due/scheduled date. It does not create an event.

## API contract

### Events
- `GET /api/v1/events?from=YYYY-MM-DD&to=YYYY-MM-DD`
- `POST /api/v1/events`
- `PATCH /api/v1/events/{event}`
- `DELETE /api/v1/events/{event}`

### Task lists
- `GET /api/v1/task-lists`
- `POST /api/v1/task-lists`
- `PATCH /api/v1/task-lists/{taskList}`
- `DELETE /api/v1/task-lists/{taskList}`

All require Sanctum + `phone.verified` and scope by authenticated user.

## UI flow

1. Calendar uses `/events`, not `/tasks`, for event pills and week timeline.
2. Clicking date opens action chooser: **Event** or **Task**.
3. Event opens event form: title, notes, start date/time, end date/time, all-day; save sends event API.
4. Task opens existing task form, prefilled selected date; save sends task API.
5. Tasks page loads lists and tasks. Each user list becomes one horizontal column.
6. User creates a list with `+ New list`; then chooses list when creating a task.
7. Clicking task opens existing detail edit form. Task row includes complete checkbox and delete action in form.
8. Task list deletion must ask browser confirmation. It unassigns tasks through DB `nullOnDelete`; UI shows those in `My Tasks` only after user explicitly moves/creates task there. Do not delete tasks.

## TDD tasks

### Task 1: Event persistence and CRUD API

**Files:**
- Create: `database/migrations/*_create_calendar_events_table.php`
- Create: `app/Models/CalendarEvent.php`
- Create: `app/Http/Controllers/Api/Events/EventController.php`
- Create: `app/Http/Resources/CalendarEventResource.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Events/CalendarEventApiTest.php`

1. Write feature tests for user-scoped create/list/update/delete and validation of end-before-start.
2. Run `php artisan test tests/Feature/Events/CalendarEventApiTest.php`; confirm RED.
3. Add migration/model/resource/controller/routes with the API contract above.
4. Run test; confirm green.
5. Commit: `Add calendar event API`.

### Task 2: Task list persistence and task ownership

**Files:**
- Create: `database/migrations/*_create_task_lists_table.php`
- Create: `database/migrations/*_add_task_list_id_to_tasks_table.php`
- Create: `app/Models/TaskList.php`
- Create: `app/Http/Controllers/Api/TaskLists/TaskListController.php`
- Modify: `app/Models/Task.php`
- Modify: task store/update requests, task mutation service, task resource, routes
- Test: `tests/Feature/TaskLists/TaskListApiTest.php`, `tests/Feature/Tasks/TaskCrudTest.php`

1. Write failing tests: user creates/list/renames/deletes own list, cannot use another user's `task_list_id`, and task response includes task list ID.
2. Run both test files; confirm RED.
3. Add migrations/model/controller/routes. Validate ownership through the authenticated user relation before create/update.
4. Backfill existing task rows: create `My Tasks` list per user with tasks and assign only those tasks; empty users receive list lazily when first requested/created.
5. Run targeted tests green.
6. Commit: `Add task lists to task API`.

### Task 3: Separate calendar event UI and date action chooser

**Files:**
- Modify: `resources/views/dashboard.blade.php`
- Modify: `tests/Feature/LandingPageTest.php`

1. Write failing static dashboard test for `/events`, `event-modal`, and `date-action-modal`.
2. Replace calendar render data source: fetch event feed from `/events` for visible month/week range. Do not put task data on calendar.
3. Date click opens chooser modal, not agenda.
4. Add Event and Task buttons. Event opens dedicated event form; Task opens task form.
5. Event form saves/updates/deletes events and refreshes calendar only.
6. Run dashboard test green and browser JavaScript syntax check.
7. Commit: `Separate calendar events from tasks`.

### Task 4: Task list board UI

**Files:**
- Modify: `resources/views/dashboard.blade.php`
- Modify: `tests/Feature/LandingPageTest.php`

1. Write failing static test requiring `task-lists`, `New list`, and list-column container.
2. On Tasks page load, fetch `/task-lists` plus `/tasks?include_completed=false` and completed tasks.
3. Render each list as a horizontal column. Put unassigned tasks in a `My Tasks` fallback column only if such tasks exist.
4. Add create-list inline form. It posts `{name, color}` to `/task-lists`.
5. New task from a list column passes that list ID. Edit task exposes a native `<select>` for task list.
6. List delete asks confirmation and calls DELETE. Refresh lists/tasks.
7. Keep task detail edit form and complete interaction.
8. Run dashboard test green and JavaScript syntax check.
9. Commit: `Add task list board`.

### Task 5: Regression verification

Run:

```bash
php artisan test tests/Feature/Events/CalendarEventApiTest.php
php artisan test tests/Feature/TaskLists/TaskListApiTest.php
php artisan test tests/Feature/Tasks/TaskCrudTest.php
php artisan test tests/Feature/LandingPageTest.php
php artisan test tests/Feature/Prompts/PromptApiTest.php
php artisan route:list --path=events
php artisan route:list --path=task-lists
git diff --check
```

Then run `php artisan test`. Fix regressions caused by this scope; report unrelated pre-existing external WAHA failures separately.
