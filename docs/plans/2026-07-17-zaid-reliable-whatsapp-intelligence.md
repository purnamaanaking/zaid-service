# Zaid Reliable WhatsApp Intelligence Implementation Plan

> **REQUIRED SUB-SKILL:** Use the executing-plans skill to implement this plan task-by-task.

**Goal:** Make Zaid reliably acknowledge every WhatsApp command, classify task versus calendar event, and execute multiple explicit commands from one message.

**Architecture:** Keep OpenCode/DeepSeek for natural-language extraction, but make output structured as an `actions` array. Validate and execute each action independently, record it in `prompt_actions`, then build reply from executed results. Add a narrow fallback response for malformed/empty model output so inbound WhatsApp never goes silent.

**Tech Stack:** Laravel 12, PostgreSQL, PHPUnit, WAHA, OpenAI-compatible OpenCode Zen API.

---

## Evidence / Root Cause

- Screenshot command contains two explicit lines: create a task due tomorrow, then create a meeting at 16:00 in Aruna.
- `app/Services/Whatsapp/WhatsappAgentService.php` accepts exactly one JSON `action` and runs one `executeAction()` call.
- Production logs show `WhatsApp agent returned non-JSON` for model outputs that are empty or plain text. Existing fallback returns text, but cannot execute a command.
- The deployed revision at inspection was behind local source, so production behavior must be retested only after CI deploy succeeds.
- Current model replay selected only one command. This is schema limitation, not proof DeepSeek cannot extract two commands.

## Task 1: Lock down current failures with tests

**Files:**
- Modify: `tests/Feature/Whatsapp/WhatsappAgentTest.php`

**Step 1: Write failing multi-action test**

Add a fake AI result with two create actions:

```php
[
  'reply' => 'Siap, dua-duanya sudah dicatat.',
  'actions' => [
    ['type' => 'create', 'data' => [
      'entity_type' => 'task',
      'title' => 'Tugas Besar 1',
      'scheduled_date' => now()->addDay()->format('Y-m-d'),
      'scheduled_time' => null,
      'all_day' => true,
    ]],
    ['type' => 'create', 'data' => [
      'entity_type' => 'event',
      'title' => 'Meeting',
      'description' => 'Lokasi: Aruna',
      'scheduled_date' => now()->format('Y-m-d'),
      'scheduled_time' => '16:00:00',
      'all_day' => false,
    ]],
  ],
]
```

Assert one `tasks` row and one `calendar_events` row belong to same user.

**Step 2: Run failing test**

Run:

```bash
php artisan test tests/Feature/Whatsapp/WhatsappAgentTest.php --filter=multi_command
```

Expected: FAIL because agent currently only reads `action`.

**Step 3: Add malformed model output test**

Fake an empty or non-JSON chat completion for a create command. Assert one outbound WhatsApp reply exists and inbound request becomes `failed`; assert no data mutation.

**Step 4: Run tests**

Run same command. Expected: failures prove both gaps.

**Step 5: Commit**

```bash
git add tests/Feature/Whatsapp/WhatsappAgentTest.php
git commit -m "test: cover WhatsApp multi-command and AI fallback"
```

## Task 2: Extend AI contract to multiple actions

**Files:**
- Modify: `app/Services/Whatsapp/WhatsappAgentService.php:20-118, 180-230, 370-420`
- Test: `tests/Feature/Whatsapp/WhatsappAgentTest.php`

**Step 1: Change prompt schema**

Replace nullable `action` contract with:

```json
{
  "reply": "short user-facing confirmation",
  "actions": [
    {
      "type": "create|read|update|delete|complete|confirm_create|confirm|cancel",
      "task_id": "uuid or null",
      "data": {
        "entity_type": "task|event",
        "title": "string or null",
        "scheduled_date": "YYYY-MM-DD or null",
        "scheduled_time": "HH:MM:SS or null",
        "description": "string or null",
        "all_day": false
      }
    }
  ]
}
```

Add prompt rules: split independent sentences/newlines into separate actions; preserve action order; for task/event explicit keywords, use correct `entity_type`; maximum 5 actions; if one action is ambiguous, return only that action as `confirm_create` rather than dropping clear actions.

**Step 2: Normalize legacy model outputs**

In `callOpenAi()`, accept legacy `action` by converting it to an array. Reject invalid `actions` shape as malformed model output.

**Step 3: Run test**

Run:

```bash
php artisan test tests/Feature/Whatsapp/WhatsappAgentTest.php --filter=multi_command
```

Expected: still FAIL until executor supports array.

**Step 4: Commit**

```bash
git add app/Services/Whatsapp/WhatsappAgentService.php
git commit -m "feat: request multiple WhatsApp actions from AI"
```

## Task 3: Execute actions safely and report outcomes

**Files:**
- Modify: `app/Services/Whatsapp/WhatsappAgentService.php:180-230, 722-920`
- Test: `tests/Feature/Whatsapp/WhatsappAgentTest.php`

**Step 1: Implement minimal action loop**

Replace one `executeAction()` call with a loop over `array_slice($actions, 0, 5)`. For each action:

- enforce `creationEntityType()` based on original text;
- call existing `executeAction()`;
- persist independent `PromptAction` rows through existing execution methods;
- collect successful result labels;
- catch `Throwable` per action, log action index/type without message body, then continue next action.

**Step 2: Build deterministic fallback reply**

If model reply is blank, use result-based response:

```php
'Siap, '.$successfulCount.' item sudah aku proses.'
```

If no action succeeded, reply:

```php
'Pesanmu masuk, tapi aku belum bisa baca perintahnya. Coba pisahkan perintah per baris ya bro.'
```

Do not return raw model text when it is malformed for a command. This prevents silent or misleading answers.

**Step 3: Add partial-success test**

Fake actions: valid task create, invalid update without task ID. Assert task remains created, outbound response says one item processed, and failure is logged.

**Step 4: Run tests**

```bash
php artisan test tests/Feature/Whatsapp/WhatsappAgentTest.php
```

Expected: PASS.

**Step 5: Commit**

```bash
git add app/Services/Whatsapp/WhatsappAgentService.php tests/Feature/Whatsapp/WhatsappAgentTest.php
git commit -m "feat: execute multiple WhatsApp actions safely"
```

## Task 4: Normalize date, time, and location before persistence

**Files:**
- Modify: `app/Services/Whatsapp/WhatsappAgentService.php`
- Test: `tests/Feature/Whatsapp/WhatsappAgentTest.php`

**Step 1: Write failing normalization tests**

Cases:

```text
buat task tugas besar 1 deadline besok
buat jadwal meeting jam 4 sore di aruna
buat meeting lusa jam 7 malam
```

Assert:

- task due date is tomorrow and all-day;
- event starts today `16:00:00`, description contains `Aruna`;
- event starts two days later `19:00:00`.

**Step 2: Implement only validation guards**

Keep date interpretation in AI, then validate before persistence:

- date must match `Y-m-d`, else use today only for event with explicit time; otherwise null;
- time must match `HH:MM:SS`, else null;
- `all_day` becomes true when task has no time;
- event has date/time default only when AI action explicitly creates event;
- reject invalid date/time instead of saving malformed values.

Do not build a second Indonesian NLP parser. AI does extraction; PHP validates its output.

**Step 3: Run tests**

```bash
php artisan test tests/Feature/Whatsapp/WhatsappAgentTest.php
```

Expected: PASS.

**Step 4: Commit**

```bash
git add app/Services/Whatsapp/WhatsappAgentService.php tests/Feature/Whatsapp/WhatsappAgentTest.php
git commit -m "fix: validate WhatsApp schedule fields"
```

## Task 5: Add request tracing and production verification

**Files:**
- Modify: `app/Services/Whatsapp/WhatsappAgentService.php`
- Modify: `app/Services/Whatsapp/WhatsappWebhookService.php`
- Test: `tests/Feature/Whatsapp/WhatsappAgentTest.php`

**Step 1: Log lifecycle, not message contents**

Log structured fields:

```php
Log::info('WhatsApp agent processed.', [
  'prompt_request_id' => $promptRequest->id,
  'action_count' => count($actions),
  'successful_action_count' => $successfulCount,
  'failed_action_count' => $failedCount,
]);
```

Webhook controller/service logs response send failure with `wa_message_id` and HTTP status only. Do not log user message body, model API key, or webhook secret.

**Step 2: Add one test**

Assert `Log::spy()` receives `WhatsApp agent processed.` for an action batch.

**Step 3: Run focused and full suites**

```bash
php artisan test tests/Feature/Whatsapp
php artisan test
```

Expected: all pass.

**Step 4: Commit and push**

```bash
git add app/Services/Whatsapp/WhatsappAgentService.php app/Services/Whatsapp/WhatsappWebhookService.php tests/Feature/Whatsapp
git commit -m "chore: trace WhatsApp agent outcomes"
git push
```

**Step 5: Verify GitHub Actions deployment**

Wait for green workflow. On `zaids`:

```bash
ssh zaids 'cd /var/www/zaid-service && git log -1 --oneline && systemctl is-active zaid-service-queue'
```

Expected: latest commit and `active`.

**Step 6: Real WhatsApp acceptance test**

Send exactly:

```text
Buat task tugas besar 1 deadline besok
Buat jadwal meeting jam 4 sore di Aruna
```

Verify:

```bash
ssh zaids 'cd /var/www/zaid-service && php artisan tinker --execute="dump(DB::table(\"tasks\")->latest()->first()); dump(DB::table(\"calendar_events\")->latest()->first());"'
```

Expected: one task and one event, one clear WhatsApp reply, no silent message.
