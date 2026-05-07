# Zaid Service Backend Implementation Plan

> **REQUIRED SUB-SKILL:** Use the executing-plans skill to implement this plan task-by-task.

**Goal:** Build the Zaid Laravel backend MVP for Google-based onboarding, phone verification, task/calendar APIs, prompt processing, WhatsApp webhook integration, and sync/observability foundations.

**Architecture:** The backend is a Laravel 13 JSON API using Sanctum for mobile token auth and Socialite/Google token verification for account entry. Domain logic is organized around app-first identity, shared task storage, a unified prompt execution service for mobile app + WhatsApp, and audited mutations/logging for sync and supportability.

**Tech Stack:** Laravel 13, PHP 8.3, PostgreSQL, Laravel Sanctum, Laravel Socialite, PHPUnit, Laravel Form Requests, Eloquent Models, Jobs/Queues, WhatsApp Cloud API webhook integration.

---

## Preconditions / Environment Setup

Before starting Task 1, ensure local environment supports the chosen stack.

### Required environment fixes
- Enable PHP extensions:
  - `pdo_pgsql`
  - `pgsql`
- Recommended for tests/dev convenience:
  - `pdo_sqlite`
  - `sqlite3`
- Provision PostgreSQL database:
  - database: `zaid_service`
- Update `zaid-service/.env`

### Verify environment
Run:
```bash
cd zaid-service
php -m | grep -Ei 'pdo|pgsql|sqlite'
php artisan about
```
Expected:
- output contains `PDO`, `pdo_pgsql`, `pgsql`
- Laravel boots without database driver errors

---

### Task 1: Stabilize Laravel API baseline

**Files:**
- Modify: `zaid-service/bootstrap/app.php`
- Modify: `zaid-service/routes/api.php`
- Modify: `zaid-service/config/auth.php`
- Modify: `zaid-service/app/Models/User.php`
- Create: `zaid-service/tests/Feature/Health/ApiHealthTest.php`

**Step 1: Write the failing test**

Create `tests/Feature/Health/ApiHealthTest.php`:
```php
<?php

namespace Tests\Feature\Health;

use Tests\TestCase;

class ApiHealthTest extends TestCase
{
    public function test_api_health_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()->assertJson([
            'success' => true,
            'message' => 'Zaid service is healthy',
        ]);
    }
}
```

**Step 2: Run test to verify it fails**

Run:
```bash
cd zaid-service
php artisan test tests/Feature/Health/ApiHealthTest.php
```
Expected: FAIL because `/api/health` does not exist.

**Step 3: Write minimal implementation**
- wire API routing if needed
- add `/api/health` endpoint in `routes/api.php`
- ensure auth defaults are API-ready
- update `User` model to use `HasApiTokens`

**Step 4: Run test to verify it passes**

Run:
```bash
cd zaid-service
php artisan test tests/Feature/Health/ApiHealthTest.php
```
Expected: PASS

**Step 5: Commit**
```bash
git add bootstrap/app.php routes/api.php config/auth.php app/Models/User.php tests/Feature/Health/ApiHealthTest.php
git commit -m "chore: stabilize laravel api baseline"
```

---

### Task 2: Replace default user schema with Zaid identity schema

**Files:**
- Modify: `zaid-service/database/migrations/0001_01_01_000000_create_users_table.php`
- Create: `zaid-service/database/migrations/2026_05_06_000100_create_user_identities_table.php`
- Create: `zaid-service/database/migrations/2026_05_06_000200_create_user_phones_table.php`
- Create: `zaid-service/database/migrations/2026_05_06_000300_create_phone_verifications_table.php`
- Create: `zaid-service/database/migrations/2026_05_06_000400_create_otp_attempts_table.php`
- Create: `zaid-service/tests/Feature/Auth/IdentitySchemaTest.php`

**Step 1: Write the failing test**

Create `tests/Feature/Auth/IdentitySchemaTest.php`:
```php
<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IdentitySchemaTest extends TestCase
{
    public function test_identity_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('user_identities'));
        $this->assertTrue(Schema::hasTable('user_phones'));
        $this->assertTrue(Schema::hasTable('phone_verifications'));
        $this->assertTrue(Schema::hasTable('otp_attempts'));
    }

    public function test_users_table_contains_zaid_identity_columns(): void
    {
        foreach (['google_subject', 'email', 'full_name', 'avatar_url', 'status', 'phone_verified_at', 'onboarded_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('users', $column), $column);
        }
    }
}
```

**Step 2: Run test to verify it fails**

Run:
```bash
cd zaid-service
php artisan test tests/Feature/Auth/IdentitySchemaTest.php
```
Expected: FAIL due to missing tables/columns.

**Step 3: Write minimal implementation**
- modify default users migration to Zaid fields
- create related identity/phone/verification migrations
- add foreign keys, unique constraints, soft delete where appropriate

**Step 4: Run test to verify it passes**

Run:
```bash
cd zaid-service
php artisan migrate:fresh --env=testing
php artisan test tests/Feature/Auth/IdentitySchemaTest.php
```
Expected: PASS

**Step 5: Commit**
```bash
git add database/migrations tests/Feature/Auth/IdentitySchemaTest.php
git commit -m "feat: add identity and phone verification schema"
```

---

### Task 3: Implement Eloquent models and relationships for identity domain

**Files:**
- Modify: `zaid-service/app/Models/User.php`
- Create: `zaid-service/app/Models/UserIdentity.php`
- Create: `zaid-service/app/Models/UserPhone.php`
- Create: `zaid-service/app/Models/PhoneVerification.php`
- Create: `zaid-service/app/Models/OtpAttempt.php`
- Create: `zaid-service/tests/Unit/Models/UserRelationshipsTest.php`

**Step 1: Write the failing test**

Create `tests/Unit/Models/UserRelationshipsTest.php` to assert relationship methods exist and return expected relation classes.

**Step 2: Run test to verify it fails**

Run:
```bash
cd zaid-service
php artisan test tests/Unit/Models/UserRelationshipsTest.php
```
Expected: FAIL because models/relations do not exist.

**Step 3: Write minimal implementation**
- add fillable/casts to `User`
- add `identities()`, `phones()` relations
- create new models with `belongsTo` / `hasMany`

**Step 4: Run test to verify it passes**

Run:
```bash
cd zaid-service
php artisan test tests/Unit/Models/UserRelationshipsTest.php
```
Expected: PASS

**Step 5: Commit**
```bash
git add app/Models tests/Unit/Models/UserRelationshipsTest.php
git commit -m "feat: add identity domain models"
```

---

### Task 4: Add auth/onboarding state service primitives

**Files:**
- Create: `zaid-service/app/Enums/UserStatus.php`
- Create: `zaid-service/app/Support/PhoneNumber.php`
- Create: `zaid-service/app/Services/Auth/OnboardingStateService.php`
- Create: `zaid-service/tests/Unit/Auth/OnboardingStateServiceTest.php`
- Create: `zaid-service/tests/Unit/Support/PhoneNumberTest.php`

**Step 1: Write the failing tests**
- test phone normalization to E.164-ish Indonesia-friendly formatting
- test onboarding next-step resolution:
  - provisional user without phone => `phone_input`
  - phone attached but unverified => `verify_otp`
  - verified user => `dashboard`

**Step 2: Run tests to verify they fail**

Run:
```bash
cd zaid-service
php artisan test tests/Unit/Auth/OnboardingStateServiceTest.php tests/Unit/Support/PhoneNumberTest.php
```
Expected: FAIL due to missing classes.

**Step 3: Write minimal implementation**
- add enum constants
- add small phone normalizer utility
- add onboarding state resolver service

**Step 4: Run tests to verify they pass**

Run same command. Expected: PASS

**Step 5: Commit**
```bash
git add app/Enums app/Support app/Services/Auth tests/Unit/Auth tests/Unit/Support
git commit -m "feat: add onboarding state primitives"
```

---

### Task 5: Implement Google auth endpoint contract

**Files:**
- Create: `zaid-service/app/Http/Controllers/Api/Auth/GoogleAuthController.php`
- Create: `zaid-service/app/Http/Requests/Auth/GoogleAuthRequest.php`
- Create: `zaid-service/app/Services/Auth/GoogleAuthService.php`
- Create: `zaid-service/app/Contracts/Auth/GoogleTokenVerifier.php`
- Create: `zaid-service/tests/Fakes/Auth/FakeGoogleTokenVerifier.php`
- Create: `zaid-service/tests/Feature/Auth/GoogleAuthTest.php`
- Modify: `zaid-service/routes/api.php`
- Modify: `zaid-service/config/services.php`

**Step 1: Write the failing test**
Create feature tests covering:
- new Google login creates provisional user and returns token + `phone_input`
- existing verified user returns `dashboard`
- invalid token returns 422/401

**Step 2: Run test to verify it fails**

Run:
```bash
cd zaid-service
php artisan test tests/Feature/Auth/GoogleAuthTest.php
```
Expected: FAIL because endpoint/service do not exist.

**Step 3: Write minimal implementation**
- add `POST /api/v1/auth/google`
- implement request validation
- inject token verifier contract for testability
- create/update user + user identity
- issue Sanctum token
- return onboarding state payload

**Step 4: Run test to verify it passes**

Run same command. Expected: PASS

**Step 5: Commit**
```bash
git add app/Http/Controllers/Api/Auth app/Http/Requests/Auth app/Services/Auth app/Contracts/Auth tests/Fakes/Auth tests/Feature/Auth/GoogleAuthTest.php routes/api.php config/services.php
git commit -m "feat: add google auth endpoint"
```

---

### Task 6: Implement onboarding phone submission endpoint

**Files:**
- Create: `zaid-service/app/Http/Controllers/Api/Onboarding/PhoneOnboardingController.php`
- Create: `zaid-service/app/Http/Requests/Onboarding/SubmitPhoneRequest.php`
- Create: `zaid-service/app/Services/Auth/PhoneVerificationService.php`
- Create: `zaid-service/app/Jobs/Auth/SendOtpJob.php`
- Create: `zaid-service/tests/Feature/Onboarding/SubmitPhoneTest.php`
- Modify: `zaid-service/routes/api.php`

**Step 1: Write the failing test**
Cover:
- verified/authenticated user can submit phone and receive verification payload
- phone is normalized and stored
- phone already linked to another active account is rejected

**Step 2: Run test to verify it fails**

Run:
```bash
cd zaid-service
php artisan test tests/Feature/Onboarding/SubmitPhoneTest.php
```
Expected: FAIL.

**Step 3: Write minimal implementation**
- add authenticated endpoint `POST /api/v1/onboarding/phone`
- normalize phone
- create/update `user_phones`
- create pending `phone_verifications`
- dispatch `SendOtpJob`
- return verification payload

**Step 4: Run test to verify it passes**

Run same command. Expected: PASS

**Step 5: Commit**
```bash
git add app/Http/Controllers/Api/Onboarding app/Http/Requests/Onboarding app/Services/Auth app/Jobs/Auth tests/Feature/Onboarding/SubmitPhoneTest.php routes/api.php
git commit -m "feat: add phone submission onboarding endpoint"
```

---

### Task 7: Implement OTP verification, resend, onboarding status, and me endpoints

**Files:**
- Create: `zaid-service/app/Http/Requests/Onboarding/VerifyOtpRequest.php`
- Create: `zaid-service/app/Http/Requests/Onboarding/ResendOtpRequest.php`
- Create: `zaid-service/app/Http/Controllers/Api/Onboarding/VerifyPhoneOtpController.php`
- Create: `zaid-service/app/Http/Controllers/Api/Onboarding/ResendOtpController.php`
- Create: `zaid-service/app/Http/Controllers/Api/Onboarding/OnboardingStatusController.php`
- Create: `zaid-service/app/Http/Controllers/Api/User/MeController.php`
- Create: `zaid-service/tests/Feature/Onboarding/VerifyOtpTest.php`
- Create: `zaid-service/tests/Feature/Onboarding/ResendOtpTest.php`
- Create: `zaid-service/tests/Feature/User/MeEndpointTest.php`
- Modify: `zaid-service/routes/api.php`

**Step 1: Write the failing tests**
Cover:
- valid OTP activates user and links phone for WhatsApp
- invalid OTP rejected
- expired OTP rejected
- resend creates new pending challenge subject to cooldown
- `/api/v1/onboarding/status` returns correct next step
- `/api/v1/me` returns current profile

**Step 2: Run tests to verify they fail**

Run:
```bash
cd zaid-service
php artisan test tests/Feature/Onboarding/VerifyOtpTest.php tests/Feature/Onboarding/ResendOtpTest.php tests/Feature/User/MeEndpointTest.php
```
Expected: FAIL.

**Step 3: Write minimal implementation**
- implement OTP verification/update logic
- update user status to active on success
- add resend handling
- add onboarding status and me controllers

**Step 4: Run tests to verify they pass**

Run same command. Expected: PASS

**Step 5: Commit**
```bash
git add app/Http/Requests/Onboarding app/Http/Controllers/Api/Onboarding app/Http/Controllers/Api/User tests/Feature/Onboarding tests/Feature/User routes/api.php
git commit -m "feat: complete onboarding api flow"
```

---

### Task 8: Add onboarding-complete middleware / gate for protected feature APIs

**Files:**
- Create: `zaid-service/app/Http/Middleware/EnsurePhoneVerified.php`
- Modify: `zaid-service/bootstrap/app.php`
- Create: `zaid-service/tests/Feature/Auth/EnsurePhoneVerifiedMiddlewareTest.php`

**Step 1: Write the failing test**
Test that provisional users are blocked from `/api/v1/tasks` and active users are allowed.

**Step 2: Run test to verify it fails**

Run:
```bash
cd zaid-service
php artisan test tests/Feature/Auth/EnsurePhoneVerifiedMiddlewareTest.php
```
Expected: FAIL.

**Step 3: Write minimal implementation**
- add middleware
- register alias
- apply to future protected route groups

**Step 4: Run test to verify it passes**

Run same command. Expected: PASS

**Step 5: Commit**
```bash
git add app/Http/Middleware bootstrap/app.php tests/Feature/Auth/EnsurePhoneVerifiedMiddlewareTest.php
git commit -m "feat: enforce phone verification on protected apis"
```

---

### Task 9: Add task domain schema

**Files:**
- Create: `zaid-service/database/migrations/2026_05_06_000500_create_tasks_table.php`
- Create: `zaid-service/database/migrations/2026_05_06_000600_create_task_recurrences_table.php`
- Create: `zaid-service/database/migrations/2026_05_06_000700_create_task_changes_table.php`
- Create: `zaid-service/tests/Feature/Tasks/TaskSchemaTest.php`

**Step 1: Write the failing test**
Assert task tables exist with required columns (`title`, `status`, `scheduled_date`, `scheduled_time`, `source_channel`, `deleted_at`).

**Step 2: Run test to verify it fails**

Run:
```bash
cd zaid-service
php artisan test tests/Feature/Tasks/TaskSchemaTest.php
```
Expected: FAIL.

**Step 3: Write minimal implementation**
- create migrations for tasks, recurrences, task changes

**Step 4: Run test to verify it passes**

Run same command after `migrate:fresh`. Expected: PASS

**Step 5: Commit**
```bash
git add database/migrations tests/Feature/Tasks/TaskSchemaTest.php
git commit -m "feat: add task domain schema"
```

---

### Task 10: Implement task models, factories, and relationships

**Files:**
- Create: `zaid-service/app/Models/Task.php`
- Create: `zaid-service/app/Models/TaskRecurrence.php`
- Create: `zaid-service/app/Models/TaskChange.php`
- Create: `zaid-service/database/factories/TaskFactory.php`
- Create: `zaid-service/tests/Unit/Tasks/TaskModelTest.php`

**Step 1: Write the failing test**
Test casts/fillable and task relation methods.

**Step 2: Run test to verify it fails**

Run:
```bash
cd zaid-service
php artisan test tests/Unit/Tasks/TaskModelTest.php
```
Expected: FAIL.

**Step 3: Write minimal implementation**
- add models, factories, relations

**Step 4: Run test to verify it passes**
Expected: PASS

**Step 5: Commit**
```bash
git add app/Models database/factories tests/Unit/Tasks/TaskModelTest.php
git commit -m "feat: add task models and relationships"
```

---

### Task 11: Implement manual task CRUD API

**Files:**
- Create: `zaid-service/app/Http/Controllers/Api/Tasks/TaskController.php`
- Create: `zaid-service/app/Http/Requests/Tasks/StoreTaskRequest.php`
- Create: `zaid-service/app/Http/Requests/Tasks/UpdateTaskRequest.php`
- Create: `zaid-service/app/Http/Resources/TaskResource.php`
- Create: `zaid-service/app/Services/Tasks/TaskMutationService.php`
- Create: `zaid-service/tests/Feature/Tasks/TaskCrudTest.php`
- Modify: `zaid-service/routes/api.php`

**Step 1: Write the failing test**
Cover:
- create task
- list tasks filtered by date
- show task detail
- patch task
- soft delete task

**Step 2: Run test to verify it fails**

Run:
```bash
cd zaid-service
php artisan test tests/Feature/Tasks/TaskCrudTest.php
```
Expected: FAIL.

**Step 3: Write minimal implementation**
- resource controller routes under Sanctum + verified middleware
- create/update/delete using service layer
- log task changes for mutations

**Step 4: Run test to verify it passes**
Expected: PASS

**Step 5: Commit**
```bash
git add app/Http/Controllers/Api/Tasks app/Http/Requests/Tasks app/Http/Resources app/Services/Tasks tests/Feature/Tasks/TaskCrudTest.php routes/api.php
git commit -m "feat: add task crud api"
```

---

### Task 12: Implement agenda and calendar summary endpoints

**Files:**
- Create: `zaid-service/app/Http/Controllers/Api/Agenda/DayAgendaController.php`
- Create: `zaid-service/app/Http/Controllers/Api/Calendar/MonthCalendarController.php`
- Create: `zaid-service/app/Services/Agenda/AgendaQueryService.php`
- Create: `zaid-service/tests/Feature/Agenda/DayAgendaTest.php`
- Create: `zaid-service/tests/Feature/Calendar/MonthCalendarTest.php`
- Modify: `zaid-service/routes/api.php`

**Step 1: Write the failing tests**
- daily agenda returns selected day items
- calendar month returns per-day counts

**Step 2: Run tests to verify they fail**

Run:
```bash
cd zaid-service
php artisan test tests/Feature/Agenda/DayAgendaTest.php tests/Feature/Calendar/MonthCalendarTest.php
```
Expected: FAIL.

**Step 3: Write minimal implementation**
- implement query service + controllers

**Step 4: Run tests to verify they pass**
Expected: PASS

**Step 5: Commit**
```bash
git add app/Http/Controllers/Api/Agenda app/Http/Controllers/Api/Calendar app/Services/Agenda tests/Feature/Agenda tests/Feature/Calendar routes/api.php
git commit -m "feat: add agenda and calendar summary endpoints"
```

---

### Task 13: Add prompt logging schema and models

**Files:**
- Create: `zaid-service/database/migrations/2026_05_06_000800_create_prompt_requests_table.php`
- Create: `zaid-service/database/migrations/2026_05_06_000900_create_prompt_actions_table.php`
- Create: `zaid-service/app/Models/PromptRequest.php`
- Create: `zaid-service/app/Models/PromptAction.php`
- Create: `zaid-service/tests/Feature/Prompts/PromptSchemaTest.php`

**Step 1: Write the failing test**
Assert prompt tables exist with required execution columns.

**Step 2: Run test to verify it fails**

Run:
```bash
cd zaid-service
php artisan test tests/Feature/Prompts/PromptSchemaTest.php
```
Expected: FAIL.

**Step 3: Write minimal implementation**
- add prompt migrations and models

**Step 4: Run test to verify it passes**
Expected: PASS

**Step 5: Commit**
```bash
git add database/migrations app/Models tests/Feature/Prompts/PromptSchemaTest.php
git commit -m "feat: add prompt logging schema"
```

---

### Task 14: Implement unified prompt command contract and parser abstraction

**Files:**
- Create: `zaid-service/app/Data/Prompt/ParsedPromptData.php`
- Create: `zaid-service/app/Contracts/Prompt/PromptParser.php`
- Create: `zaid-service/app/Services/Prompt/PromptCommandService.php`
- Create: `zaid-service/app/Services/Prompt/PromptExecutionService.php`
- Create: `zaid-service/tests/Fakes/Prompt/FakePromptParser.php`
- Create: `zaid-service/tests/Unit/Prompt/PromptCommandServiceTest.php`

**Step 1: Write the failing test**
Cover normalization of parser result and routing of CREATE/READ/UPDATE/DELETE intents.

**Step 2: Run test to verify it fails**

Run:
```bash
cd zaid-service
php artisan test tests/Unit/Prompt/PromptCommandServiceTest.php
```
Expected: FAIL.

**Step 3: Write minimal implementation**
- define parser contract
- define parsed payload DTO/data object
- add command orchestration service with no external LLM dependency yet

**Step 4: Run test to verify it passes**
Expected: PASS

**Step 5: Commit**
```bash
git add app/Data/Prompt app/Contracts/Prompt app/Services/Prompt tests/Fakes/Prompt tests/Unit/Prompt/PromptCommandServiceTest.php
git commit -m "feat: add unified prompt command abstraction"
```

---

### Task 15: Implement mobile prompt API

**Files:**
- Create: `zaid-service/app/Http/Controllers/Api/Prompts/PromptController.php`
- Create: `zaid-service/app/Http/Requests/Prompts/SubmitPromptRequest.php`
- Create: `zaid-service/app/Http/Requests/Prompts/ConfirmPromptRequest.php`
- Create: `zaid-service/tests/Feature/Prompts/PromptApiTest.php`
- Modify: `zaid-service/routes/api.php`

**Step 1: Write the failing test**
Cover:
- create-task prompt from app
- read-agenda prompt from app
- ambiguous prompt returns confirmation payload
- confirm endpoint completes execution

**Step 2: Run test to verify it fails**

Run:
```bash
cd zaid-service
php artisan test tests/Feature/Prompts/PromptApiTest.php
```
Expected: FAIL.

**Step 3: Write minimal implementation**
- `POST /api/v1/prompts`
- `POST /api/v1/prompts/{id}/confirm`
- `GET /api/v1/prompts/{id}`
- store prompt request / actions / results

**Step 4: Run test to verify it passes**
Expected: PASS

**Step 5: Commit**
```bash
git add app/Http/Controllers/Api/Prompts app/Http/Requests/Prompts tests/Feature/Prompts/PromptApiTest.php routes/api.php
git commit -m "feat: add mobile prompt api"
```

---

### Task 16: Add WhatsApp message schema and model

**Files:**
- Create: `zaid-service/database/migrations/2026_05_06_001000_create_whatsapp_messages_table.php`
- Create: `zaid-service/app/Models/WhatsappMessage.php`
- Create: `zaid-service/tests/Feature/Whatsapp/WhatsappSchemaTest.php`

**Step 1: Write the failing test**
Assert `whatsapp_messages` table exists with unique `wa_message_id`, sender/recipient phone columns, direction, processing_status.

**Step 2: Run test to verify it fails**

Run:
```bash
cd zaid-service
php artisan test tests/Feature/Whatsapp/WhatsappSchemaTest.php
```
Expected: FAIL.

**Step 3: Write minimal implementation**
- add migration + model

**Step 4: Run test to verify it passes**
Expected: PASS

**Step 5: Commit**
```bash
git add database/migrations app/Models tests/Feature/Whatsapp/WhatsappSchemaTest.php
git commit -m "feat: add whatsapp message logging schema"
```

---

### Task 17: Implement WhatsApp webhook verification and unknown-sender fallback

**Files:**
- Create: `zaid-service/app/Http/Controllers/Api/Webhooks/WhatsappWebhookController.php`
- Create: `zaid-service/app/Http/Requests/Webhooks/WhatsappWebhookRequest.php`
- Create: `zaid-service/app/Services/Whatsapp/WhatsappWebhookService.php`
- Create: `zaid-service/app/Contracts/Whatsapp/WhatsappResponder.php`
- Create: `zaid-service/tests/Fakes/Whatsapp/FakeWhatsappResponder.php`
- Create: `zaid-service/tests/Feature/Whatsapp/WhatsappWebhookTest.php`
- Modify: `zaid-service/routes/api.php`
- Modify: `zaid-service/config/services.php`

**Step 1: Write the failing test**
Cover:
- GET verify challenge success
- duplicate inbound message ignored
- unknown sender receives onboarding fallback response

**Step 2: Run test to verify it fails**

Run:
```bash
cd zaid-service
php artisan test tests/Feature/Whatsapp/WhatsappWebhookTest.php
```
Expected: FAIL.

**Step 3: Write minimal implementation**
- webhook verify route
- inbound message extraction and logging
- dedupe by `wa_message_id`
- unknown sender fallback via responder contract

**Step 4: Run test to verify it passes**
Expected: PASS

**Step 5: Commit**
```bash
git add app/Http/Controllers/Api/Webhooks app/Http/Requests/Webhooks app/Services/Whatsapp app/Contracts/Whatsapp tests/Fakes/Whatsapp tests/Feature/Whatsapp/WhatsappWebhookTest.php routes/api.php config/services.php
git commit -m "feat: add whatsapp webhook baseline"
```

---

### Task 18: Connect WhatsApp webhook to shared prompt execution service

**Files:**
- Modify: `zaid-service/app/Services/Whatsapp/WhatsappWebhookService.php`
- Modify: `zaid-service/app/Services/Prompt/PromptExecutionService.php`
- Create: `zaid-service/tests/Feature/Whatsapp/WhatsappPromptExecutionTest.php`

**Step 1: Write the failing test**
Cover:
- verified sender asking for agenda gets data from shared prompt execution path
- verified sender creating a task persists task and outbound reply

**Step 2: Run test to verify it fails**

Run:
```bash
cd zaid-service
php artisan test tests/Feature/Whatsapp/WhatsappPromptExecutionTest.php
```
Expected: FAIL.

**Step 3: Write minimal implementation**
- resolve sender phone to active verified user
- create prompt request with `channel=whatsapp`
- call shared prompt execution service
- persist outbound reply log

**Step 4: Run test to verify it passes**
Expected: PASS

**Step 5: Commit**
```bash
git add app/Services/Whatsapp app/Services/Prompt tests/Feature/Whatsapp/WhatsappPromptExecutionTest.php
git commit -m "feat: connect whatsapp webhook to prompt engine"
```

---

### Task 19: Add settings/profile endpoints needed by mobile app

**Files:**
- Create: `zaid-service/database/migrations/2026_05_06_001100_create_user_settings_table.php`
- Create: `zaid-service/app/Models/UserSetting.php`
- Create: `zaid-service/app/Http/Controllers/Api/Settings/SettingsController.php`
- Create: `zaid-service/app/Http/Requests/Settings/UpdateSettingsRequest.php`
- Create: `zaid-service/tests/Feature/Settings/SettingsApiTest.php`
- Modify: `zaid-service/routes/api.php`

**Step 1: Write the failing test**
Cover GET/PATCH settings for theme, timezone, default task time, reminder offset.

**Step 2: Run test to verify it fails**

Run:
```bash
cd zaid-service
php artisan test tests/Feature/Settings/SettingsApiTest.php
```
Expected: FAIL.

**Step 3: Write minimal implementation**
- user settings table/model
- settings endpoints

**Step 4: Run test to verify it passes**
Expected: PASS

**Step 5: Commit**
```bash
git add database/migrations app/Models app/Http/Controllers/Api/Settings app/Http/Requests/Settings tests/Feature/Settings/SettingsApiTest.php routes/api.php
git commit -m "feat: add user settings api"
```

---

### Task 20: Add rate limiting, logging, and API exception formatting

**Files:**
- Modify: `zaid-service/bootstrap/app.php`
- Create: `zaid-service/app/Exceptions/ApiExceptionRenderer.php`
- Create: `zaid-service/app/Support/ApiResponse.php`
- Create: `zaid-service/tests/Feature/Foundation/ApiErrorFormatTest.php`

**Step 1: Write the failing test**
Cover standardized JSON error envelope and throttled OTP endpoint response.

**Step 2: Run test to verify it fails**

Run:
```bash
cd zaid-service
php artisan test tests/Feature/Foundation/ApiErrorFormatTest.php
```
Expected: FAIL.

**Step 3: Write minimal implementation**
- register rate limiters for OTP and prompt endpoints
- standardize validation/auth/not-found responses
- add helper for consistent success envelope

**Step 4: Run test to verify it passes**
Expected: PASS

**Step 5: Commit**
```bash
git add bootstrap/app.php app/Exceptions app/Support tests/Feature/Foundation/ApiErrorFormatTest.php
git commit -m "feat: standardize api errors and throttling"
```

---

### Task 21: Add end-to-end MVP flow tests

**Files:**
- Create: `zaid-service/tests/Feature/Mvp/OnboardingToTaskFlowTest.php`
- Create: `zaid-service/tests/Feature/Mvp/WhatsappLinkedUserFlowTest.php`

**Step 1: Write the failing tests**
Scenarios:
- Google auth → submit phone → verify OTP → create manual task → read agenda
- linked WhatsApp sender → create task via webhook → app task list contains created task

**Step 2: Run tests to verify they fail**

Run:
```bash
cd zaid-service
php artisan test tests/Feature/Mvp/OnboardingToTaskFlowTest.php tests/Feature/Mvp/WhatsappLinkedUserFlowTest.php
```
Expected: FAIL.

**Step 3: Write minimal implementation**
Only fix missing integration gaps found by tests. Do not add extra features.

**Step 4: Run tests to verify they pass**
Expected: PASS

**Step 5: Commit**
```bash
git add tests/Feature/Mvp app routes config
git commit -m "test: cover core mvp backend flows"
```

---

### Task 22: Documentation and developer handoff updates

**Files:**
- Modify: `zaid-service/README.md`
- Create: `zaid-service/docs/api-endpoints.md`
- Create: `zaid-service/docs/local-setup.md`
- Create: `zaid-service/docs/testing.md`

**Step 1: Write the failing check**
This is doc work; use a checklist instead of executable test:
- README explains setup, env, migrate, test
- docs explain implemented routes and dependencies

**Step 2: Run verification command**
```bash
cd zaid-service
php artisan route:list
php artisan test
```
Expected: commands succeed after implementation.

**Step 3: Write minimal implementation**
- update README and add backend docs

**Step 4: Run verification**
Same commands; confirm route list and tests are clean.

**Step 5: Commit**
```bash
git add README.md docs
git commit -m "docs: add backend setup and api docs"
```

---

## Final Verification Checklist

Before claiming the backend is complete:
- [ ] `php -m` includes `pdo_pgsql` and `pgsql`
- [ ] `php artisan migrate:fresh` succeeds
- [ ] `php artisan route:list` succeeds
- [ ] `php artisan test` passes
- [ ] Auth flow returns correct onboarding state transitions
- [ ] Phone verification gates protected APIs
- [ ] Task CRUD works for verified users
- [ ] App prompt API uses shared prompt execution service
- [ ] WhatsApp webhook uses verified phone matching and unknown-sender fallback
- [ ] Logging/audit records exist for task mutations and prompt executions

---

## Recommended Execution Grouping

### Phase 1: Identity Foundation
- Task 1
- Task 2
- Task 3
- Task 4
- Task 5
- Task 6
- Task 7
- Task 8

### Phase 2: Core Task Domain
- Task 9
- Task 10
- Task 11
- Task 12

### Phase 3: Prompt Engine
- Task 13
- Task 14
- Task 15

### Phase 4: WhatsApp Integration
- Task 16
- Task 17
- Task 18

### Phase 5: Sync & Observability / Polish
- Task 19
- Task 20
- Task 21
- Task 22

---

## Blockers Already Known

1. Local PHP lacks database drivers needed for PostgreSQL and SQLite-based tests.
2. Real Google token verification should be wrapped behind a contract so tests can fake it.
3. Real OTP delivery provider is not selected yet; implement a provider contract/job boundary first.
4. Real WhatsApp outbound provider credentials are not configured yet; use responder contract/fake in tests.
5. Real LLM/parser provider is not chosen yet; start with parser contract + fake parser in tests.

---

Plan complete and saved to `docs/plans/2026-05-06-zaid-service-backend-implementation.md`.

Two execution options:

**1. Subagent-Driven (this session)** - I dispatch fresh subagent per task, review between tasks, fast iteration

**2. Parallel Session (separate)** - Open new session with executing-plans, batch execution with checkpoints

Which approach?