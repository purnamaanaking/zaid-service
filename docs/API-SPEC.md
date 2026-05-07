# Zaid API Specification

## 1. Overview

This document defines the API surface for the Zaid MVP backend.

Zaid supports:
- **Mobile App authentication and onboarding**
- **Phone verification via OTP**
- **Task/calendar CRUD from mobile app**
- **Prompt-based commands from mobile app**
- **Prompt-based commands from WhatsApp webhook**
- **Shared synchronized task data across channels**

---

## 2. API Design Principles

1. **App-first authentication**  
   Only the mobile app performs login and onboarding.

2. **Verified phone as WhatsApp identity bridge**  
   WhatsApp access is granted only by matching sender number with a verified phone record.

3. **Single command engine**  
   Prompt inputs from app and WhatsApp should converge to a shared internal command-processing service.

4. **Separation of concerns**  
   Channel adapters should stay thin; domain logic lives in service/application layers.

5. **Safe defaults**  
   Unknown WhatsApp senders receive safe onboarding instructions.

---

## 3. Recommended API Structure

### Public API groups
- `Auth API`
- `Onboarding API`
- `Phone Verification API`
- `User API`
- `Task API`
- `Prompt API`
- `Sync/Agenda API`
- `Google Calendar Integration API`
- `WhatsApp Webhook API`
- `Settings API`

### Suggested base path
```http
/api/v1
```

---

## 4. Authentication Model

## 4.1 Client Auth Strategy
Recommended model:
- Mobile app uses Google Sign-In SDK client-side
- Mobile app sends Google ID token / auth token to backend
- Backend verifies token
- Backend issues app session tokens

### Suggested tokens
- short-lived access token (JWT or opaque token)
- long-lived refresh token

### Suggested headers
```http
Authorization: Bearer <access_token>
Content-Type: application/json
```

---

## 5. Standard Response Format

## 5.1 Success Response
```json
{
  "success": true,
  "message": "Task created successfully",
  "data": {}
}
```

## 5.2 Error Response
```json
{
  "success": false,
  "message": "Phone number not verified",
  "error": {
    "code": "PHONE_NOT_VERIFIED",
    "details": null
  }
}
```

## 5.3 Validation Error Response
```json
{
  "success": false,
  "message": "Validation failed",
  "error": {
    "code": "VALIDATION_ERROR",
    "details": {
      "phone_number": ["Phone number is required"]
    }
  }
}
```

---

## 6. Auth API

## 6.1 Continue with Google
Authenticates or creates a provisional user.

### Endpoint
```http
POST /api/v1/auth/google
```

### Request Body
```json
{
  "id_token": "google-id-token-from-client",
  "device": {
    "platform": "ios",
    "device_id": "device-123",
    "device_name": "iPhone 15"
  }
}
```

### Behavior
- Verify Google token
- Find or create user
- Create/update user identity
- Create session tokens
- Return onboarding status

### Success Response
```json
{
  "success": true,
  "message": "Authenticated successfully",
  "data": {
    "access_token": "jwt-access-token",
    "refresh_token": "refresh-token",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": {
      "id": "uuid",
      "email": "user@gmail.com",
      "full_name": "Zaid User",
      "avatar_url": "https://...",
      "status": "provisional",
      "phone_verified": false
    },
    "onboarding": {
      "required": true,
      "next_step": "phone_input"
    }
  }
}
```

### Notes
If the user already completed onboarding:
```json
{
  "onboarding": {
    "required": false,
    "next_step": "dashboard"
  }
}
```

---

## 6.2 Refresh Session
```http
POST /api/v1/auth/refresh
```

### Request Body
```json
{
  "refresh_token": "refresh-token"
}
```

### Response
Returns a new access token and optionally a rotated refresh token.

---

## 6.3 Logout
```http
POST /api/v1/auth/logout
```

### Auth Required
Yes

### Request Body
```json
{
  "refresh_token": "refresh-token"
}
```

### Behavior
Revokes the session.

---

## 7. Onboarding and Phone Verification API

## 7.1 Submit Phone Number
Stores/updates the user phone number and sends OTP.

### Endpoint
```http
POST /api/v1/onboarding/phone
```

### Auth Required
Yes

### Request Body
```json
{
  "phone_number": "081234567890",
  "country_code": "ID"
}
```

### Server Actions
- normalize phone to E.164
- ensure phone not linked to another active account
- create/update phone record
- create OTP verification challenge
- send OTP

### Success Response
```json
{
  "success": true,
  "message": "OTP sent successfully",
  "data": {
    "phone_number": "+6281234567890",
    "verification_id": "uuid",
    "expires_in_seconds": 300,
    "next_step": "verify_otp"
  }
}
```

---

## 7.2 Verify OTP
### Endpoint
```http
POST /api/v1/onboarding/phone/verify
```

### Auth Required
Yes

### Request Body
```json
{
  "verification_id": "uuid",
  "otp_code": "123456"
}
```

### Behavior
- validate OTP
- mark phone as verified
- activate user account
- mark phone linked for WhatsApp usage

### Success Response
```json
{
  "success": true,
  "message": "Phone verified successfully",
  "data": {
    "user": {
      "id": "uuid",
      "status": "active",
      "phone_verified": true,
      "phone_number": "+6281234567890"
    },
    "onboarding": {
      "completed": true,
      "next_step": "dashboard"
    }
  }
}
```

---

## 7.3 Resend OTP
### Endpoint
```http
POST /api/v1/onboarding/phone/resend-otp
```

### Auth Required
Yes

### Request Body
```json
{
  "phone_number": "+6281234567890"
}
```

### Behavior
Creates a new OTP challenge if allowed by rate limiting.

---

## 7.4 Get Onboarding Status
### Endpoint
```http
GET /api/v1/onboarding/status
```

### Auth Required
Yes

### Response
```json
{
  "success": true,
  "data": {
    "user_status": "provisional",
    "phone_verified": false,
    "required": true,
    "next_step": "verify_otp"
  }
}
```

---

## 8. User API

## 8.1 Get Current User
```http
GET /api/v1/me
```

### Response
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "email": "user@gmail.com",
    "full_name": "Zaid User",
    "avatar_url": "https://...",
    "phone_number": "+6281234567890",
    "phone_verified": true,
    "status": "active",
    "settings": {
      "default_task_time": "09:00",
      "theme": "light",
      "timezone": "Asia/Jakarta"
    },
    "integrations": {
      "google_calendar": {
        "connected": true,
        "google_calendar_id": "primary",
        "google_calendar_summary": "Primary Calendar",
        "status": "connected",
        "last_synced_at": "2026-05-07T12:00:00Z",
        "last_error_message": null
      }
    }
  }
}
```

---

## 8.2 Update Profile
```http
PATCH /api/v1/me
```

### Request Body
```json
{
  "full_name": "Updated Name"
}
```

---

## 8.3 Replace Phone Number
### Endpoint
```http
POST /api/v1/me/phone/change
```

### Behavior
Starts a new verification flow for a replacement number.

---

## 9. Task API

## 9.1 Create Task (Manual App Flow)
```http
POST /api/v1/tasks
```

### Auth Required
Yes

### Preconditions
User must have `phone_verified = true`

### Request Body
```json
{
  "title": "Laporan Penjualan",
  "description": "Kirim ke tim setiap Jumat",
  "scheduled_date": "2026-05-23",
  "scheduled_time": "10:00:00",
  "timezone": "Asia/Jakarta",
  "all_day": false,
  "recurrence": {
    "type": "weekly",
    "day_of_week": "friday",
    "interval": 1
  }
}
```

### Success Response
```json
{
  "success": true,
  "message": "Task created successfully",
  "data": {
    "task": {
      "id": "uuid",
      "title": "Laporan Penjualan",
      "status": "pending",
      "scheduled_date": "2026-05-23",
      "scheduled_time": "10:00:00",
      "is_recurring": true,
      "source_channel": "app_manual"
    }
  }
}
```

---

## 9.2 List Tasks
```http
GET /api/v1/tasks
```

### Query Parameters
- `date=2026-05-23`
- `from=2026-05-01`
- `to=2026-05-31`
- `status=pending`
- `include_completed=false`
- `search=laporan`

### Response
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": "uuid",
        "title": "Laporan Penjualan",
        "scheduled_date": "2026-05-23",
        "scheduled_time": "10:00:00",
        "status": "pending"
      }
    ],
    "meta": {
      "total": 1
    }
  }
}
```

---

## 9.3 Get Task Detail
```http
GET /api/v1/tasks/{taskId}
```

### Response
```json
{
  "success": true,
  "data": {
    "task": {
      "id": "uuid",
      "title": "Laporan Penjualan",
      "description": "Kirim ke tim setiap Jumat",
      "scheduled_date": "2026-05-23",
      "scheduled_time": "10:00:00",
      "timezone": "Asia/Jakarta",
      "status": "pending",
      "recurrence": {
        "type": "weekly",
        "day_of_week": "friday",
        "interval": 1
      },
      "created_at": "2026-05-06T10:00:00Z",
      "updated_at": "2026-05-06T10:00:00Z"
    }
  }
}
```

---

## 9.4 Update Task
```http
PATCH /api/v1/tasks/{taskId}
```

### Request Body
```json
{
  "title": "Laporan Penjualan Mingguan",
  "scheduled_time": "11:00:00"
}
```

### Notes
- Partial update allowed
- log mutation in audit table

---

## 9.5 Delete Task
```http
DELETE /api/v1/tasks/{taskId}
```

### Behavior
Soft delete recommended.

### Success Response
```json
{
  "success": true,
  "message": "Task deleted successfully",
  "data": {
    "task_id": "uuid"
  }
}
```

---

## 9.6 Complete Task
```http
POST /api/v1/tasks/{taskId}/complete
```

### Response
Marks the task as completed.

---

## 9.7 Restore Task
```http
POST /api/v1/tasks/{taskId}/restore
```

---

## 10. Agenda / Calendar API

## 10.1 Get Daily Agenda
```http
GET /api/v1/agenda/day?date=2026-05-23
```

### Response
```json
{
  "success": true,
  "data": {
    "date": "2026-05-23",
    "items": [
      {
        "id": "uuid",
        "title": "Laporan Penjualan",
        "time": "10:00:00",
        "status": "pending"
      }
    ]
  }
}
```

---

## 10.2 Get Calendar Summary
```http
GET /api/v1/calendar/month?month=2026-05
```

### Response
Returns per-day counts and summary items for month view.

Example:
```json
{
  "success": true,
  "data": {
    "month": "2026-05",
    "days": [
      {
        "date": "2026-05-23",
        "task_count": 2,
        "has_pending": true
      }
    ]
  }
}
```

---

## 11. Prompt API (Mobile App)

## 11.1 Submit Prompt
```http
POST /api/v1/prompts
```

### Auth Required
Yes

### Request Body
```json
{
  "text": "Buat task laporan penjualan setiap Jumat jam 10 pagi"
}
```

### Behavior
- create prompt log
- parse intent and entities
- optionally request confirmation
- optionally execute immediately
- return structured result

### Success Response (auto-executed)
```json
{
  "success": true,
  "message": "Prompt processed successfully",
  "data": {
    "prompt_request_id": "uuid",
    "parse_status": "parsed",
    "intent": "CREATE",
    "confidence_score": 0.97,
    "requires_confirmation": false,
    "result": {
      "action": "create_task",
      "task": {
        "id": "uuid",
        "title": "Laporan Penjualan",
        "scheduled_time": "10:00:00",
        "recurrence": {
          "type": "weekly",
          "day_of_week": "friday"
        }
      }
    }
  }
}
```

### Success Response (confirmation required)
```json
{
  "success": true,
  "message": "Confirmation required",
  "data": {
    "prompt_request_id": "uuid",
    "parse_status": "ambiguous",
    "intent": "DELETE",
    "confidence_score": 0.62,
    "requires_confirmation": true,
    "confirmation": {
      "type": "select_target",
      "question": "Task mana yang ingin dihapus?",
      "candidates": [
        {
          "id": "uuid-1",
          "title": "Meeting client",
          "scheduled_date": "2026-05-23"
        },
        {
          "id": "uuid-2",
          "title": "Meeting internal",
          "scheduled_date": "2026-05-23"
        }
      ]
    }
  }
}
```

---

## 11.2 Confirm Prompt Action
```http
POST /api/v1/prompts/{promptRequestId}/confirm
```

### Request Body
```json
{
  "selected_target_id": "uuid-1",
  "confirmed": true
}
```

### Response
Executes pending prompt action and returns final result.

---

## 11.3 Get Prompt Result
```http
GET /api/v1/prompts/{promptRequestId}
```

### Response
Returns parse result, execution status, and output summary.

---

## 12. Google Calendar Integration API

## 12.1 Get Connect URL
```http
GET /api/v1/integrations/google-calendar/connect
```

## 12.2 OAuth Callback
```http
GET /api/v1/integrations/google-calendar/callback?code=...&state=...
```

## 12.3 Integration Status
```http
GET /api/v1/integrations/google-calendar/status
```

## 12.4 Disconnect Integration
```http
DELETE /api/v1/integrations/google-calendar
```

### Notes
- Calendar integration is separate from identity login.
- Two-way sync uses incremental sync tokens.
- Conflict policy is currently `remote_wins` when both local and remote changed after the last sync.

## 13. Settings API

## 12.1 Get Settings
```http
GET /api/v1/settings
```

## 12.2 Update Settings
```http
PATCH /api/v1/settings
```

### Example Request Body
```json
{
  "theme": "light",
  "default_task_time": "09:00:00",
  "timezone": "Asia/Jakarta",
  "reminder_offset_minutes": 30
}
```

---

## 14. WhatsApp Webhook API

## 13.1 Verify Webhook
Required by WhatsApp provider.

```http
GET /api/v1/webhooks/whatsapp
```

### Query Parameters
- `hub.mode`
- `hub.verify_token`
- `hub.challenge`

### Response
Return challenge when verify token is valid.

---

## 13.2 Receive Inbound WhatsApp Message
```http
POST /api/v1/webhooks/whatsapp
```

### Auth
Provider signature/verification, not user token.

### Simplified Example Payload
```json
{
  "object": "whatsapp_business_account",
  "entry": [
    {
      "changes": [
        {
          "value": {
            "messages": [
              {
                "id": "wamid.123",
                "from": "6281234567890",
                "timestamp": "1710000000",
                "text": {
                  "body": "cek agenda hari ini"
                },
                "type": "text"
              }
            ]
          }
        }
      ]
    }
  ]
}
```

### Server Workflow
1. Validate provider signature / token
2. Extract message id and sender number
3. Normalize sender number to E.164
4. Reject duplicate webhook/message id
5. Lookup verified phone in database
6. If not found:
   - store inbound log
   - send onboarding response
7. If found:
   - create prompt request
   - parse command using shared AI engine
   - execute or clarify
   - send WhatsApp reply
   - store outbound message log

### Example Internal Outcomes
#### A. Unknown sender
Bot response:
```text
Nomor kamu belum terhubung ke akun Zaid. Silakan login dulu di aplikasi, lalu verifikasi nomor HP kamu untuk mulai pakai WhatsApp assistant.
```

#### B. Known sender, read agenda
Bot response:
```text
Agenda hari ini:
1. Meeting client - 10:00
2. Follow up penjualan - 14:00
```

#### C. Known sender, create task
Bot response:
```text
Siap, task "Laporan Penjualan" berhasil dibuat untuk setiap Jumat jam 10:00.
```

---

## 14.3 Outbound WhatsApp Sender Service
This may be an internal service rather than a public endpoint.

### Internal Service Responsibility
- format outbound reply
- call WhatsApp Cloud API send endpoint
- persist message log
- handle retry and failure

---

## 15. Internal Command Execution Contract

Even if not exposed publicly, the backend should normalize prompt handling using a common internal payload.

### Example Internal Parsed Command
```json
{
  "channel": "whatsapp",
  "user_id": "uuid",
  "raw_text": "buat task laporan penjualan setiap Jumat jam 10 pagi",
  "intent": "CREATE",
  "confidence_score": 0.97,
  "entities": {
    "entity_type": "task",
    "title": "Laporan Penjualan",
    "scheduled_time": "10:00:00",
    "recurrence": {
      "type": "weekly",
      "day_of_week": "friday"
    }
  }
}
```

### Execution Result Contract
```json
{
  "status": "executed",
  "requires_confirmation": false,
  "created_entities": [
    {
      "type": "task",
      "id": "uuid"
    }
  ],
  "human_response": "Siap, task berhasil dibuat."
}
```

---

## 16. Error Codes

### Auth / Onboarding
- `INVALID_GOOGLE_TOKEN`
- `SESSION_EXPIRED`
- `PHONE_ALREADY_IN_USE`
- `PHONE_NOT_VERIFIED`
- `OTP_INVALID`
- `OTP_EXPIRED`
- `OTP_RATE_LIMITED`
- `ONBOARDING_INCOMPLETE`

### Task
- `TASK_NOT_FOUND`
- `TASK_ALREADY_DELETED`
- `INVALID_RECURRENCE`
- `INVALID_TASK_PAYLOAD`

### Prompt / AI
- `PROMPT_PARSE_FAILED`
- `PROMPT_UNSUPPORTED`
- `PROMPT_AMBIGUOUS`
- `PROMPT_CONFIRMATION_REQUIRED`
- `PROMPT_EXECUTION_FAILED`

### WhatsApp
- `WHATSAPP_UNLINKED_NUMBER`
- `WHATSAPP_DUPLICATE_MESSAGE`
- `WHATSAPP_INVALID_SIGNATURE`
- `WHATSAPP_SEND_FAILED`

### Google Calendar
- `GOOGLE_CALENDAR_NOT_CONNECTED`
- `GOOGLE_CALENDAR_TOKEN_REVOKED`
- `GOOGLE_CALENDAR_SYNC_TOKEN_INVALID`
- `GOOGLE_CALENDAR_SYNC_CONFLICT`

---

## 17. Authorization Rules

### App Endpoints
All authenticated app endpoints require a valid access token.

### Feature Gate
If `phone_verified = false`, the backend should restrict access to:
- task creation
- task editing
- prompt execution
- WhatsApp linking features

Allowed while not verified:
- get onboarding status
- submit/retry phone verification
- logout
- basic profile read if needed

### User Ownership Rule
Every task/action must be scoped to the authenticated user id or resolved verified phone owner.

---

## 18. Idempotency and Reliability

### Recommended idempotency rules
- WhatsApp webhook should deduplicate using provider message id.
- OTP resend should be rate-limited and possibly cooldown-based.
- Prompt execution should use request ids to avoid duplicate mutations from retries.
- Delete operations should be safe on already-deleted resources.

### Optional Header for client mutations
```http
Idempotency-Key: unique-key-generated-by-client
```

---

## 19. Security Recommendations

- Verify Google tokens server-side.
- Hash refresh tokens and OTP values.
- Normalize and validate phone numbers before persistence.
- Protect webhook endpoint with provider verification.
- Avoid leaking whether a phone belongs to another user in public error messages.
- Apply rate limits to OTP and prompt endpoints.

---

## 20. Suggested Backend Module Breakdown

### Auth Module
- Google token verification
- user creation/upsert
- session issuance

### Onboarding Module
- phone capture
- OTP generation
- OTP verification
- activation gate

### User Module
- current profile
- settings/profile update

### Task Module
- CRUD
- recurrence handling
- agenda queries
- outbound calendar sync dispatch

### Google Calendar Module
- OAuth connect/callback
- encrypted token storage
- Google Calendar API client
- task/event transformer
- outbound sync jobs
- inbound incremental sync jobs
- conflict resolution
- sync logging

### Prompt Module
- parser adapter
- command normalization
- confirmation flow
- execution service
- prompt logging

### WhatsApp Module
- webhook ingestion
- sender number resolution
- outbound message sender
- message deduplication

---

## 21. Example End-to-End Flows

## 20.1 App Onboarding Flow
1. `POST /auth/google`
2. `POST /onboarding/phone`
3. `POST /onboarding/phone/verify`
4. `GET /me`
5. user enters dashboard

## 20.2 App Prompt Create Task
1. `POST /prompts`
2. backend parses and executes
3. `GET /tasks?date=...` or updated UI state

## 20.3 WhatsApp Check Agenda
1. WhatsApp sends webhook to `POST /webhooks/whatsapp`
2. backend resolves verified phone
3. backend creates prompt request
4. backend queries agenda
5. backend replies through WhatsApp provider

## 20.4 App Manual Edit Task
1. `GET /tasks/{taskId}`
2. `PATCH /tasks/{taskId}`
3. UI refreshes agenda/calendar

---

## 22. Suggested Future API Extensions

- push notification registration endpoints
- reminder endpoints
- task label/category endpoints
- assistant conversation thread endpoints
- export/import endpoints
- analytics/debug endpoints for support tools

---

## 23. Final API Summary

The Zaid MVP API is built around three architectural truths:

1. **Users enter through the mobile app only** using Google Auth.
2. **Verified phone number is the security bridge** to WhatsApp interactions.
3. **App prompt and WhatsApp prompt share one execution engine**, so the same task/calendar capabilities are available across both channels.

This API structure supports:
- secure onboarding,
- dual-channel interaction,
- prompt-based task management,
- synchronized productivity data across app and WhatsApp.