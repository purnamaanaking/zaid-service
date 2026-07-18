# Reminder Notifications Implementation Plan

> **REQUIRED SUB-SKILL:** Use the executing-plans skill to implement this plan task-by-task.

**Goal:** Let users attach configurable reminders to tasks and calendar events, defaulting to WhatsApp with an in-app reminder status/list.

**Architecture:** Store one reminder row per task/event with a relative offset (`minutes_before`) and delivery channel (`whatsapp`, `app`, or `both`). A minute scheduler claims due reminders, sends WhatsApp through the existing sender, records delivery status, and exposes in-app reminders through a small authenticated API. AI returns structured reminder fields; server-side validation and Eloquent perform all database writes.

**Tech Stack:** Laravel Eloquent migrations/models, Laravel scheduler and queue, existing `WhatsappSenderService`, Blade dashboard JavaScript, PHPUnit.

---

### Task 1: Add reminder persistence

**Files:**
- Create: `database/migrations/2026_07_18_000100_create_reminders_table.php`
- Create: `app/Models/Reminder.php`
- Modify: `app/Models/Task.php`
- Modify: `app/Models/CalendarEvent.php`
- Modify: `app/Services/Tasks/TaskMutationService.php`
- Test: `tests/Feature/Reminders/ReminderPersistenceTest.php`

**Behavior:** Reminder belongs to either task or calendar event, has `minutes_before`, `channel`, `remind_at`, `status`, `sent_at`, and `error_message`. Supported channels: `whatsapp`, `app`, `both`. One pending reminder per source item/offset/channel. Delete source cleanup removes pending reminders.

### Task 2: Add reminder API

**Files:**
- Create: `app/Http/Controllers/Api/Reminders/ReminderController.php`
- Create: `app/Http/Requests/Reminders/StoreReminderRequest.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Reminders/ReminderApiTest.php`

**Behavior:** Authenticated users can list their reminders and create/update/delete reminders for their own task/event. Validate offset as positive minutes, channel allow-list, source ownership, and calculate `remind_at` from source start time. API never accepts raw SQL or arbitrary user IDs.

### Task 3: Send due WhatsApp reminders

**Files:**
- Create: `app/Jobs/Reminders/SendDueRemindersJob.php`
- Create: `app/Console/Commands/SendDueRemindersCommand.php`
- Modify: `routes/console.php`
- Test: `tests/Feature/Reminders/ReminderDeliveryTest.php`

**Behavior:** Scheduler runs every minute. Claim due pending reminders transactionally, send WhatsApp to verified linked primary phone, mark sent or failed, and avoid duplicate sends. `app` channel marks reminder available in-app without WhatsApp send; `both` does both. Message includes item title, start time, and remaining offset.

### Task 4: Add reminder fields to prompt schema

**Files:**
- Modify: `app/Services/Prompt/OpenAiPromptParser.php`
- Modify: `app/Services/Prompt/PromptCommandService.php`
- Modify: `app/Services/Whatsapp/WhatsappAgentService.php`
- Modify: `app/Services/Tasks/TaskMutationService.php`
- Test: `tests/Unit/Prompt/OpenAiPromptParserTest.php`
- Test: `tests/Feature/Prompts/PromptApiTest.php`
- Test: `tests/Feature/Whatsapp/WhatsappAgentTest.php`

**Behavior:** Parse phrases such as `ingatkan meeting 30 menit sebelumnya`, `reminder 1 jam sebelum`, and `kirim lewat app`. AI emits `reminder_minutes_before` and `reminder_channel`; server creates reminder only after task/event creation. Existing commands remain unchanged.

### Task 5: Add dashboard reminder controls

**Files:**
- Modify: `resources/views/dashboard.blade.php`
- Modify: `tests/Feature/LandingPageTest.php`

**Behavior:** Add reminder select to task/event composer with presets 15 minutes, 30 minutes, 1 hour, 1 day, custom minutes, and channel select defaulting WhatsApp. Add in-app reminder indicator/list with pending/sent/failed states. Use existing custom modal styles; no browser notification permission or new dependency in MVP.

### Task 6: Verify and document runtime scheduling

**Files:**
- Modify: `README.md` or `docs/reminders.md`
- Modify: deployment workflow only if scheduler installation is missing.

**Commands:**
- `php artisan migrate:fresh --force`
- `php artisan test`
- `npm run build`
- `php artisan reminders:send-due --dry-run` if implemented
- Verify production scheduler/cron invokes `php artisan schedule:run` every minute.

**Acceptance:** A task/event with default WhatsApp reminder creates one pending reminder, due scheduler sends exactly once, failed sends are recorded, app list shows status, and prompt-created reminders use the same path.
