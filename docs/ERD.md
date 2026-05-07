# Zaid Entity Relationship Design (ERD)

## 1. Overview

This ERD is designed for the Zaid MVP, where:
- **Mobile app** is the only authentication and onboarding entry point
- **Google Auth** is the only login method
- **Phone number verification via OTP** is mandatory
- **WhatsApp sender number** is matched against the user’s verified phone number
- **Task and calendar interactions** can happen via:
  - mobile app manual UI
  - mobile app prompt AI
  - WhatsApp prompt AI
- all data is shared across one backend and one source of truth database

The schema below is optimized for:
- secure user identity handling
- extensible prompt-based command processing
- auditable task mutations
- future support for reminders, recurrence, and sync tracking

---

## 2. High-Level Domain Model

Core entities:
- `users`
- `user_identities`
- `phone_verifications`
- `user_phones`
- `user_sessions`
- `tasks`
- `task_recurrences`
- `task_occurrences` (optional but recommended for scheduling clarity)
- `task_changes`
- `prompt_requests`
- `prompt_actions`
- `whatsapp_messages`
- `otp_attempts`
- `device_installations` (optional for push/app telemetry)

---

## 3. Mermaid ER Diagram

```mermaid
erDiagram
    users ||--o{ user_identities : has
    users ||--o{ user_phones : has
    users ||--o{ user_sessions : has
    users ||--o{ tasks : owns
    users ||--o{ prompt_requests : submits
    users ||--o{ whatsapp_messages : sends_or_receives

    user_phones ||--o{ phone_verifications : verifies
    user_phones ||--o{ otp_attempts : tracks

    tasks ||--o| task_recurrences : may_have
    tasks ||--o{ task_occurrences : generates
    tasks ||--o{ task_changes : logs

    prompt_requests ||--o{ prompt_actions : produces
    prompt_requests ||--o{ whatsapp_messages : may_reference
    prompt_requests ||--o{ task_changes : may_trigger

    users {
      uuid id PK
      string google_subject UNIQUE
      string email UNIQUE
      string full_name
      string avatar_url
      string status
      timestamp phone_verified_at
      timestamp onboarded_at
      timestamp last_active_at
      timestamp created_at
      timestamp updated_at
      timestamp deleted_at
    }

    user_identities {
      uuid id PK
      uuid user_id FK
      string provider
      string provider_subject
      string provider_email
      json provider_payload
      timestamp created_at
      timestamp updated_at
    }

    user_phones {
      uuid id PK
      uuid user_id FK
      string phone_e164 UNIQUE
      string phone_local
      string country_code
      boolean is_primary
      boolean is_verified
      timestamp verified_at
      timestamp linked_for_whatsapp_at
      timestamp created_at
      timestamp updated_at
      timestamp deleted_at
    }

    phone_verifications {
      uuid id PK
      uuid user_phone_id FK
      string otp_code_hash
      string channel
      string status
      timestamp expires_at
      timestamp verified_at
      integer attempt_count
      timestamp created_at
      timestamp updated_at
    }

    otp_attempts {
      uuid id PK
      uuid user_phone_id FK
      uuid phone_verification_id FK
      string attempt_type
      string status
      string ip_address
      string user_agent
      timestamp created_at
    }

    user_sessions {
      uuid id PK
      uuid user_id FK
      string platform
      string refresh_token_hash
      string device_name
      string device_id
      string ip_address
      timestamp last_seen_at
      timestamp expires_at
      timestamp revoked_at
      timestamp created_at
      timestamp updated_at
    }

    tasks {
      uuid id PK
      uuid user_id FK
      string source_channel
      string source_prompt_request_id
      string title
      text description
      string status
      date scheduled_date
      time scheduled_time
      string timezone
      boolean all_day
      boolean is_recurring
      timestamp completed_at
      timestamp archived_at
      timestamp deleted_at
      timestamp created_at
      timestamp updated_at
    }

    task_recurrences {
      uuid id PK
      uuid task_id FK
      string recurrence_type
      integer interval_value
      string day_of_week
      integer day_of_month
      date end_date
      integer occurrence_limit
      json rrule_payload
      timestamp created_at
      timestamp updated_at
    }

    task_occurrences {
      uuid id PK
      uuid task_id FK
      date occurrence_date
      time occurrence_time
      string status
      timestamp completed_at
      timestamp created_at
      timestamp updated_at
    }

    task_changes {
      uuid id PK
      uuid task_id FK
      uuid user_id FK
      uuid prompt_request_id FK
      string actor_channel
      string action_type
      json before_state
      json after_state
      timestamp created_at
    }

    prompt_requests {
      uuid id PK
      uuid user_id FK
      string channel
      string raw_text
      string normalized_text
      string intent
      decimal confidence_score
      string parse_status
      json extracted_entities
      json execution_summary
      string execution_status
      timestamp created_at
      timestamp updated_at
    }

    prompt_actions {
      uuid id PK
      uuid prompt_request_id FK
      string action_type
      string target_entity_type
      string target_entity_id
      integer execution_order
      string status
      json payload
      json result_payload
      timestamp created_at
      timestamp updated_at
    }

    whatsapp_messages {
      uuid id PK
      uuid user_id FK
      uuid prompt_request_id FK
      string direction
      string wa_message_id UNIQUE
      string sender_phone_e164
      string recipient_phone_e164
      text message_text
      json webhook_payload
      string processing_status
      timestamp delivered_at
      timestamp read_at
      timestamp created_at
      timestamp updated_at
    }
```

---

## 4. Entity Definitions

## 4.1 `users`
Primary account record.

### Purpose
Represents the app-level identity after Google authentication and onboarding.

### Key fields
- `id`: internal UUID
- `google_subject`: unique Google user identifier
- `email`: primary account email from Google
- `full_name`: display name
- `avatar_url`: profile image
- `status`: account lifecycle status
- `phone_verified_at`: nullable until OTP succeeds
- `onboarded_at`: set once onboarding is complete

### Suggested statuses
- `provisional`
- `active`
- `suspended`
- `deleted`

### Notes
A user should not be considered fully active until phone verification is complete.

---

## 4.2 `user_identities`
Stores external identity provider mappings.

### Purpose
Normalizes login identity sources even if MVP only supports Google.

### Key fields
- `provider`: `google`
- `provider_subject`: stable provider user id
- `provider_email`: email from provider
- `provider_payload`: raw/normalized OAuth profile

### Why keep this table?
Even though `users.google_subject` could be enough in MVP, this table makes expansion cleaner if Apple or other providers are added later.

---

## 4.3 `user_phones`
Stores one or more phone numbers associated with a user.

### Purpose
This is the authoritative phone mapping for OTP verification and WhatsApp sender matching.

### Key fields
- `phone_e164`: normalized phone number for exact matching
- `phone_local`: original/local representation for UI
- `country_code`: numeric or ISO context
- `is_primary`: main phone for the user
- `is_verified`: whether OTP verified
- `linked_for_whatsapp_at`: when the number became active for WA matching

### Constraints
- Unique verified `phone_e164`
- A phone number should belong to only one active user account in MVP

---

## 4.4 `phone_verifications`
Stores OTP challenge lifecycles.

### Purpose
Tracks OTP creation, expiration, and verification outcomes.

### Key fields
- `otp_code_hash`: never store OTP plaintext
- `channel`: e.g. `sms`, `whatsapp_otp` if ever added later
- `status`: `pending`, `verified`, `expired`, `cancelled`, `failed`
- `expires_at`
- `attempt_count`

### Notes
Only the latest active pending verification should typically be accepted per phone.

---

## 4.5 `otp_attempts`
Stores each verification-related attempt.

### Purpose
Improves auditability, security analytics, rate limiting, and fraud detection.

### Example attempt types
- `send_otp`
- `resend_otp`
- `verify_otp`

### Example statuses
- `success`
- `invalid_code`
- `expired`
- `rate_limited`

---

## 4.6 `user_sessions`
Stores refreshable authenticated app sessions.

### Purpose
Supports mobile session management and token revocation.

### Key fields
- `platform`: `ios`, `android`
- `refresh_token_hash`
- `device_name`
- `device_id`
- `last_seen_at`
- `revoked_at`

---

## 4.7 `tasks`
Main productivity object for MVP.

### Purpose
Represents a user’s task/reminder/calendar item in a simplified unified model.

### Key fields
- `source_channel`: `app_manual`, `app_prompt`, `whatsapp`
- `source_prompt_request_id`: optional link to originating prompt
- `title`
- `description`
- `status`
- `scheduled_date`
- `scheduled_time`
- `timezone`
- `all_day`
- `is_recurring`
- `completed_at`
- `deleted_at`

### Suggested statuses
- `pending`
- `completed`
- `cancelled`
- `archived`

### Modeling note
For MVP, tasks, reminders, and agenda items can be unified into one table to reduce complexity. A future `type` column may be added if separation becomes necessary.

---

## 4.8 `task_recurrences`
Stores recurrence configuration.

### Purpose
Represents repeated schedule rules.

### Supported patterns in MVP
- daily
- weekly
- monthly
- custom interval (limited)

### Key fields
- `recurrence_type`
- `interval_value`
- `day_of_week`
- `day_of_month`
- `end_date`
- `occurrence_limit`
- `rrule_payload`

### Notes
A simplified recurrence model is sufficient for MVP, while `rrule_payload` preserves flexibility.

---

## 4.9 `task_occurrences`
Materialized or logical instances of recurring tasks.

### Purpose
Allows a recurring task to produce per-date instances, making agenda rendering and completion handling easier.

### Key fields
- `occurrence_date`
- `occurrence_time`
- `status`
- `completed_at`

### Recommendation
Optional for strict MVP, but strongly recommended if recurring tasks are part of the first release.

---

## 4.10 `task_changes`
Mutation audit log.

### Purpose
Tracks every meaningful create/update/delete state transition for a task.

### Key fields
- `actor_channel`: `app_manual`, `app_prompt`, `whatsapp`, `system`
- `action_type`: `create`, `update`, `delete`, `complete`, `restore`
- `before_state`
- `after_state`
- `prompt_request_id`: nullable if manual action

### Benefit
Critical for debugging AI actions and for future undo/recovery tooling.

---

## 4.11 `prompt_requests`
Top-level AI interaction log.

### Purpose
Stores every natural language instruction from app or WhatsApp.

### Key fields
- `channel`: `app_prompt`, `whatsapp`
- `raw_text`
- `normalized_text`
- `intent`
- `confidence_score`
- `parse_status`
- `extracted_entities`
- `execution_summary`
- `execution_status`

### Example parse statuses
- `parsed`
- `ambiguous`
- `unsupported`
- `failed`

### Example execution statuses
- `pending`
- `confirmed`
- `executed`
- `partially_executed`
- `rejected`
- `failed`

---

## 4.12 `prompt_actions`
Atomic actions derived from a prompt.

### Purpose
A single prompt can produce multiple internal actions. Example: “besok buat meeting jam 9 dan ingetin follow-up jam 1”.

### Key fields
- `action_type`: `read`, `create`, `update`, `delete`
- `target_entity_type`: usually `task`
- `target_entity_id`
- `execution_order`
- `payload`
- `result_payload`

---

## 4.13 `whatsapp_messages`
Webhook-level inbound/outbound message log.

### Purpose
Tracks all WhatsApp message exchanges relevant to business logic and support debugging.

### Key fields
- `direction`: `inbound`, `outbound`
- `wa_message_id`: provider message id
- `sender_phone_e164`
- `recipient_phone_e164`
- `message_text`
- `webhook_payload`
- `processing_status`

### Suggested statuses
- `received`
- `parsed`
- `executed`
- `replied`
- `failed`
- `ignored_duplicate`

---

## 5. Relationship Summary

### User Relationships
- One `user` can have many `user_identities`
- One `user` can have many `user_phones`
- One `user` can have many `user_sessions`
- One `user` can own many `tasks`
- One `user` can create many `prompt_requests`
- One `user` can be associated with many `whatsapp_messages`

### Phone Relationships
- One `user_phone` can have many `phone_verifications`
- One `user_phone` can have many `otp_attempts`

### Task Relationships
- One `task` may have one `task_recurrence`
- One `task` may have many `task_occurrences`
- One `task` may have many `task_changes`

### Prompt Relationships
- One `prompt_request` can create many `prompt_actions`
- One `prompt_request` may relate to one or more `task_changes`
- One `prompt_request` may be linked to one `whatsapp_message` inbound record and one or more outbound replies

---

## 6. Recommended SQL-Oriented Table Sketch

## 6.1 users
```sql
users (
  id uuid primary key,
  google_subject varchar(255) unique not null,
  email varchar(255) unique not null,
  full_name varchar(255),
  avatar_url text,
  status varchar(50) not null default 'provisional',
  phone_verified_at timestamp null,
  onboarded_at timestamp null,
  last_active_at timestamp null,
  created_at timestamp not null,
  updated_at timestamp not null,
  deleted_at timestamp null
)
```

## 6.2 user_phones
```sql
user_phones (
  id uuid primary key,
  user_id uuid not null references users(id),
  phone_e164 varchar(30) not null unique,
  phone_local varchar(30),
  country_code varchar(10),
  is_primary boolean not null default true,
  is_verified boolean not null default false,
  verified_at timestamp null,
  linked_for_whatsapp_at timestamp null,
  created_at timestamp not null,
  updated_at timestamp not null,
  deleted_at timestamp null
)
```

## 6.3 phone_verifications
```sql
phone_verifications (
  id uuid primary key,
  user_phone_id uuid not null references user_phones(id),
  otp_code_hash varchar(255) not null,
  channel varchar(30) not null default 'sms',
  status varchar(30) not null default 'pending',
  expires_at timestamp not null,
  verified_at timestamp null,
  attempt_count integer not null default 0,
  created_at timestamp not null,
  updated_at timestamp not null
)
```

## 6.4 tasks
```sql
tasks (
  id uuid primary key,
  user_id uuid not null references users(id),
  source_channel varchar(30) not null,
  source_prompt_request_id uuid null references prompt_requests(id),
  title varchar(255) not null,
  description text,
  status varchar(30) not null default 'pending',
  scheduled_date date,
  scheduled_time time,
  timezone varchar(100) not null default 'Asia/Jakarta',
  all_day boolean not null default false,
  is_recurring boolean not null default false,
  completed_at timestamp null,
  archived_at timestamp null,
  deleted_at timestamp null,
  created_at timestamp not null,
  updated_at timestamp not null
)
```

## 6.5 prompt_requests
```sql
prompt_requests (
  id uuid primary key,
  user_id uuid not null references users(id),
  channel varchar(30) not null,
  raw_text text not null,
  normalized_text text,
  intent varchar(30),
  confidence_score numeric(5,4),
  parse_status varchar(30) not null,
  extracted_entities jsonb,
  execution_summary jsonb,
  execution_status varchar(30) not null default 'pending',
  created_at timestamp not null,
  updated_at timestamp not null
)
```

---

## 7. Index Recommendations

### Identity / Auth
- `users(google_subject)` unique index
- `users(email)` unique index
- `user_phones(phone_e164)` unique index
- `phone_verifications(user_phone_id, status)`

### Tasks
- `tasks(user_id, scheduled_date)`
- `tasks(user_id, status)`
- `tasks(user_id, deleted_at)`
- `task_occurrences(task_id, occurrence_date)`

### Prompt / Messaging
- `prompt_requests(user_id, created_at desc)`
- `prompt_requests(channel, execution_status)`
- `whatsapp_messages(wa_message_id)` unique index
- `whatsapp_messages(sender_phone_e164, created_at desc)`

---

## 8. Data Normalization Rules

### Phone numbers
Always normalize to E.164 for matching and uniqueness.
Examples:
- `08123456789` → `+628123456789`
- `628123456789` → `+628123456789`

### Dates and times
- Store date and time in structured columns.
- Store timezone explicitly, defaulting to `Asia/Jakarta` in MVP.
- For AI parsing, persist raw extracted entities before normalization for traceability.

### Soft delete strategy
Use `deleted_at` for recoverability and auditability on user-facing records like tasks.

---

## 9. Lifecycle Flows Mapped to Tables

## 9.1 User Onboarding
1. Google auth succeeds
2. Insert/update `users`
3. Insert `user_identities`
4. User submits phone number
5. Insert/update `user_phones`
6. Create `phone_verifications`
7. Log `otp_attempts`
8. OTP success updates:
   - `phone_verifications.status = verified`
   - `user_phones.is_verified = true`
   - `user_phones.verified_at = now()`
   - `user_phones.linked_for_whatsapp_at = now()`
   - `users.phone_verified_at = now()`
   - `users.status = active`
   - `users.onboarded_at = now()`

## 9.2 WhatsApp Prompt Handling
1. Inbound webhook creates `whatsapp_messages`
2. Sender number normalized
3. Lookup `user_phones.phone_e164`
4. If matched and verified, resolve `user_id`
5. Create `prompt_requests`
6. Create one or more `prompt_actions`
7. Execute domain action(s)
8. Create `task_changes` if task state changed
9. Store outbound `whatsapp_messages`

## 9.3 App Prompt Handling
1. User submits text prompt from app
2. Create `prompt_requests`
3. Parse to `prompt_actions`
4. If confirmation needed, wait for user action
5. Execute mutations on `tasks`
6. Log `task_changes`
7. Return structured UI result

---

## 10. Suggested Status Enums

### users.status
- `provisional`
- `active`
- `suspended`
- `deleted`

### phone_verifications.status
- `pending`
- `verified`
- `expired`
- `cancelled`
- `failed`

### tasks.status
- `pending`
- `completed`
- `cancelled`
- `archived`

### prompt_requests.parse_status
- `parsed`
- `ambiguous`
- `unsupported`
- `failed`

### prompt_requests.execution_status
- `pending`
- `awaiting_confirmation`
- `executed`
- `partially_executed`
- `rejected`
- `failed`

### whatsapp_messages.processing_status
- `received`
- `parsed`
- `executed`
- `replied`
- `failed`
- `ignored_duplicate`

---

## 11. Security Considerations in the Data Model

- Never store plaintext OTP codes.
- Refresh tokens should be stored hashed.
- Webhook payloads may contain sensitive metadata; access should be restricted.
- Prompt logs should be retained carefully because they may contain personal schedule details.
- WhatsApp sender validation must rely only on normalized verified phone records.

---

## 12. MVP Simplification Recommendations

If implementation speed is the priority, the following simplifications are acceptable:

1. Keep `tasks`, `reminders`, and `events` in a single `tasks` table.
2. Make `task_occurrences` optional until recurring execution becomes complex.
3. Keep one active phone number per user in MVP.
4. Keep one active device session per device.
5. Use `prompt_requests` + `task_changes` for observability instead of building full event sourcing.

---

## 13. Future Expansion Paths

The ERD is compatible with future additions such as:
- push notification schedules
- reminder delivery logs
- AI conversation threads
- shared tasks and collaboration
- premium subscription plans
- app/web multi-platform sessions
- multilingual prompt parsing
- assistant memory/preferences

---

## 14. Final ERD Summary

The recommended Zaid MVP database model is built around:
- **app-first account identity** through `users` + `user_identities`
- **secure WhatsApp linking** through `user_phones` + `phone_verifications`
- **shared productivity records** through `tasks`
- **auditable AI interactions** through `prompt_requests`, `prompt_actions`, and `task_changes`
- **channel observability** through `whatsapp_messages`

This structure supports the confirmed product behavior:
- onboarding only in app,
- interaction through both WhatsApp and mobile app,
- prompt support in both channels,
- shared synchronized task/calendar data.