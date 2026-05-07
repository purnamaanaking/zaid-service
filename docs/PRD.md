# Zaid Product Requirements Document (PRD)

## 1. Product Overview

**Product Name:** Zaid  
**Platforms:** Mobile App + WhatsApp  
**Core Idea:** Zaid is an AI-assisted personal productivity system that lets users manage tasks, reminders, and calendar items through two interaction channels:
1. **Mobile App**
2. **WhatsApp**

The **mobile app is the primary account entry point** for authentication and onboarding.  
The **mobile app and WhatsApp are both valid interaction channels** for ongoing task/calendar operations.

Users can interact in three ways:
- **Mobile App Manual Mode**: calendar and task UI
- **Mobile App Prompt Mode**: natural language commands in-app
- **WhatsApp Prompt Mode**: natural language commands via chat

All channels share the same backend, AI command processing layer, and database, enabling **two-way sync**.

---

## 2. Problem Statement

Many users manage their schedules inconsistently across chat apps, calendars, notes, and manual reminders. Existing systems often force users to:
- learn a rigid UI,
- open a specific app for simple updates,
- manually structure task input,
- re-enter data across channels.

Zaid solves this by letting users:
- authenticate once in the app,
- connect their verified phone number to WhatsApp,
- create/read/update/delete tasks and schedule items through either app UI or conversational prompts,
- always see the same data across both channels.

---

## 3. Product Vision

Build a unified assistant where:
- **identity and onboarding happen in the mobile app**,
- **task interaction can happen in both the mobile app and WhatsApp**,
- **AI interprets user intent consistently across channels**,
- **all data remains synchronized in near real time**.

---

## 4. Product Goals

### 4.1 Business Goals
- Increase onboarding completion with a simple Google-first auth flow.
- Create a sticky productivity experience by supporting both app and chat usage.
- Reduce friction for task entry through AI prompting.
- Enable future monetization through premium assistant and reminder features.

### 4.2 User Goals
- Sign in quickly without passwords.
- Link a WhatsApp identity to the account securely.
- Add and manage tasks from whichever channel is most convenient.
- Check agenda, reminders, and upcoming items in seconds.
- Trust that app data and WhatsApp data are always in sync.

### 4.3 Success Outcomes
- User can complete onboarding in under 2 minutes.
- User can create a task from app prompt or WhatsApp prompt in one message.
- Changes made in one channel appear in the other without manual syncing.
- Unverified or unlinked phone numbers cannot access account data via WhatsApp.

---

## 5. Product Principles

1. **App-first identity**  
   All users must begin from the mobile app.

2. **Single sign-on simplicity**  
   Google Auth is the only sign-in method in MVP.

3. **Phone number as WhatsApp identity**  
   The verified phone number is the key bridge between app account and WhatsApp sender identity.

4. **Dual interaction channels**  
   Both mobile app and WhatsApp are first-class interaction surfaces after onboarding.

5. **AI as accelerator, not blocker**  
   Users can always use manual UI in the app even if AI parsing fails.

6. **One source of truth**  
   All tasks, reminders, and events live in one database.

---

## 6. In Scope for MVP

### 6.1 Authentication and Onboarding
- Google Sign-In in mobile app
- Auto-detect whether user is new or existing
- Mandatory phone number input after Google auth
- OTP verification flow for phone number
- Account activation only after phone verification succeeds
- Store verified phone number as WhatsApp-linked identity

### 6.2 Mobile App Features
- Splash screen
- Onboarding screens
- Home dashboard with calendar view
- Daily agenda section
- Manual task create/edit/delete
- Task detail view
- AI prompt input in app for task/calendar commands
- Confirmation screen for AI-generated actions when needed
- Settings screen

### 6.3 WhatsApp Features
- Receive user prompts from WhatsApp bot
- Match sender number against verified phone number in database
- Allow task/calendar read and write only for linked users
- Return user-friendly replies for success, errors, and unknown commands

### 6.4 AI/NLP Features
- Parse user command into structured intent
- Support intents:
  - READ
  - CREATE
  - UPDATE
  - DELETE
- Extract entities:
  - task/event title
  - date
  - time
  - recurrence
  - notes/description
- Shared parsing engine for app prompt and WhatsApp prompt

### 6.5 Data Domain
- Users
- Phone verification records
- Sessions / auth state
- Tasks
- Task schedules
- Reminders
- Prompt logs
- WhatsApp inbound/outbound message logs

### 6.6 Sync
- Changes from app reflected in WhatsApp responses
- Changes from WhatsApp reflected in app views
- Near real-time consistency from shared backend/database

---

## 7. Out of Scope for MVP

- Password-based login
- Apple login
- Multi-device session management UI
- Team/shared workspace
- Multi-user task assignment
- Voice input
- Email reminders
- Rich media WhatsApp interactions
- Complex recurring rules beyond common patterns
- Offline-first mobile sync
- Native web application
- Billing/subscription system

---

## 8. Target Users

### 8.1 Primary User
An individual user who wants to manage personal tasks, schedules, reminders, and agenda quickly using either app UI or chat.

### 8.2 Example Personas

#### Persona A: Busy Professional
- Uses WhatsApp frequently
- Needs fast schedule checks while on the move
- Prefers typing commands like “ingatkan meeting besok jam 9”

#### Persona B: Organized Planner
- Prefers visual calendar and structured forms
- Uses app UI to review and edit tasks
- May still use prompt mode for speed

#### Persona C: Casual Productivity User
- Wants minimal setup
- Likes Google login
- Expects the system to “just work” across app and WhatsApp

---

## 9. User Journey Summary

### 9.1 Onboarding Journey
1. User installs and opens mobile app.
2. User sees splash and onboarding screens.
3. User taps **Continue with Google**.
4. Backend authenticates Google identity.
5. If no account exists, create provisional user account.
6. User is required to enter phone number.
7. System sends OTP.
8. User enters OTP.
9. On success, account becomes fully active.
10. Verified phone number becomes the user’s WhatsApp identity key.

### 9.2 Ongoing Usage Journey
After onboarding, user can:
- open mobile app and manage tasks manually,
- type natural language prompt in app,
- send natural language command to WhatsApp bot,
- see consistent data across both channels.

---

## 10. Detailed Functional Requirements

## 10.1 Authentication

### FR-AUTH-01: Google-only login
The system shall allow users to sign in only using Google Auth in the mobile app.

### FR-AUTH-02: Unified sign up / sign in
The system shall use a single entry action (“Continue with Google”) for both new and returning users.

### FR-AUTH-03: New user provisional state
The system shall create a provisional account after successful Google auth if the user does not already exist.

### FR-AUTH-04: Mandatory phone collection
The system shall require the user to enter a phone number before granting full app access.

### FR-AUTH-05: OTP verification
The system shall send an OTP to the submitted phone number and require successful verification.

### FR-AUTH-06: Activation gate
The system shall block access to primary productivity features until phone verification is complete.

### FR-AUTH-07: Verified phone uniqueness
The system shall prevent one verified phone number from being actively linked to multiple user accounts.

### FR-AUTH-08: Phone update flow
The system should support replacing a phone number through a re-verification process.

---

## 10.2 WhatsApp Identity and Access Control

### FR-WA-01: Sender validation
The backend shall validate inbound WhatsApp messages by matching the sender number with a verified phone number in the user database.

### FR-WA-02: Unlinked number handling
If no verified user is found for the sender number, the bot shall return an onboarding instruction message.

### FR-WA-03: Linked access
If a verified user exists, the bot shall process the message under that user’s account context.

### FR-WA-04: Secure isolation
The system shall never expose one user’s tasks to another phone number.

---

## 10.3 Mobile App Manual Task Management

### FR-APP-01: Calendar view
The mobile app shall display a calendar-based home screen.

### FR-APP-02: Daily agenda
The app shall display tasks/events for the selected day.

### FR-APP-03: Manual create
The user shall be able to create a task via form input.

### FR-APP-04: Manual edit
The user shall be able to edit an existing task via form input.

### FR-APP-05: Manual delete
The user shall be able to delete an existing task with confirmation.

### FR-APP-06: Task detail
The user shall be able to view task details including title, date, time, recurrence, notes, and status.

### FR-APP-07: Settings
The user shall be able to manage basic preferences in settings.

---

## 10.4 Prompt-based Interaction

### FR-AI-01: Shared prompt engine
The system shall use one shared AI/NLP command-processing service for prompts from the app and WhatsApp.

### FR-AI-02: Supported commands
The AI engine shall support reading, creating, updating, and deleting tasks/events/reminders.

### FR-AI-03: Intent extraction
The AI engine shall identify the user’s intended operation.

### FR-AI-04: Entity extraction
The AI engine shall extract structured entities such as task title, date/time, recurrence, and notes.

### FR-AI-05: Clarification handling
If a prompt is ambiguous, the system shall return a clarifying question or a review/confirmation response.

### FR-AI-06: Confirmation for risky actions
The system should request confirmation before destructive operations where confidence is low.

### FR-AI-07: App confirmation UI
The app shall be able to show a confirmation card before saving AI-generated actions when needed.

### FR-AI-08: WhatsApp confirmation flow
The bot should be able to return a confirmation text before applying uncertain updates/deletes.

---

## 10.5 Data Synchronization

### FR-SYNC-01: Shared source of truth
All user productivity data shall be stored in one centralized database, with Google Calendar synchronization represented as an external integration rather than the primary source of truth.

### FR-SYNC-02: App-to-WA consistency
Changes made in the app shall be reflected in future WhatsApp reads immediately or near real time.

### FR-SYNC-03: WA-to-App consistency
Changes made through WhatsApp shall appear in the mobile app after sync/refresh or live update.

### FR-SYNC-04: Auditability
Prompt-triggered operations should be logged for debugging and support.

### FR-SYNC-05: Google Calendar integration
The system shall support connecting a user's Google Calendar through a dedicated OAuth flow separate from base sign-in.

### FR-SYNC-06: Local-to-Google synchronization
When a connected user creates, updates, completes, restores, or deletes a scheduled task in Zaid, the system shall synchronize the corresponding Google Calendar event.

### FR-SYNC-07: Google-to-local synchronization
When a connected user's Google Calendar event changes remotely, the system shall synchronize those changes back into Zaid tasks using incremental sync.

### FR-SYNC-08: Conflict handling
If both Zaid and Google Calendar change the same linked item after the last successful sync, the system shall resolve the conflict deterministically and log the outcome.

---

## 11. Core Use Cases

### UC-01: First-time onboarding
- User signs in with Google
- User enters phone number
- User verifies OTP
- User lands on active account state

### UC-02: Returning user login
- User signs in with Google
- If phone already verified, user goes directly to dashboard

### UC-03: Add task manually in app
- User opens app
- Taps add / opens form
- Inputs task info
- Saves task
- Sees task on calendar and agenda

### UC-04: Add task via app prompt
- User enters natural language command
- AI parses command
- App shows generated task/confirmation
- User confirms
- Task saved

### UC-05: Check schedule via app prompt
- User types “cek agenda hari ini”
- AI returns agenda summary
- App displays result

### UC-06: Add task via WhatsApp
- User sends “Buat task laporan penjualan tiap Jumat jam 10 pagi”
- Backend validates sender number
- AI parses command
- Backend saves task
- Bot replies with confirmation

### UC-07: Check schedule via WhatsApp
- User sends “Ada agenda apa hari ini?”
- Backend validates sender
- System returns agenda summary

### UC-08: Edit task via WhatsApp or app prompt
- User requests a change
- AI finds the relevant task
- If confidence is sufficient, update directly
- Otherwise request clarification

### UC-09: Delete task via app/manual or prompt
- User initiates delete
- System confirms if needed
- Task deleted or soft-deleted

---

## 12. User Stories

### Authentication
- As a new user, I want to sign in with Google so I do not need to create a password.
- As a user, I want to verify my phone number so my WhatsApp identity is securely linked.
- As a returning user, I want login to be quick and familiar.

### App Interaction
- As a user, I want to add tasks manually from a form.
- As a user, I want to edit and delete tasks from the app.
- As a user, I want to view my day’s agenda in calendar format.

### Prompt Interaction
- As a user, I want to create tasks in natural language in the app.
- As a user, I want to use WhatsApp to manage tasks without opening the app.
- As a user, I want prompt results to be understandable and correct.

### Sync
- As a user, I want tasks created in WhatsApp to show up in the mobile app.
- As a user, I want tasks created in the app to be returned correctly when I ask WhatsApp.

---

## 13. Information Architecture

### 13.1 Mobile App Screens
1. Splash Screen
2. Onboarding Carousel
3. Google Sign-In Screen
4. Phone Number Input Screen
5. OTP Verification Screen
6. Home / Calendar Screen
7. AI Prompt Input Area
8. AI Confirmation Card / Result Screen
9. Task Detail Screen
10. Edit Task Screen
11. Delete Confirmation Modal
12. Settings Screen

### 13.2 WhatsApp Interaction States
1. Unknown phone number
2. Verified/linked phone number
3. AI parsing in progress
4. Successful action response
5. Clarification request
6. Error response

---

## 14. System Behavior Rules

### 14.1 Account Rules
- A user account is not fully active until phone verification succeeds.
- Google auth alone is insufficient for WhatsApp access.
- The verified phone number is the WhatsApp identity key.

### 14.2 WhatsApp Rules
- Unverified numbers cannot fetch account data.
- Unknown numbers receive a safe onboarding response only.
- The bot must behave as if each message is scoped to one authenticated user context.

### 14.3 Task Rules
- A task can have title, notes, due date, due time, recurrence, and status.
- A task may be created from manual form, app prompt, or WhatsApp prompt.
- Deleting a task may be soft-delete in the backend.

### 14.4 Prompt Rules
- Shared parser should normalize commands from both channels into one command schema.
- When date/time is incomplete, the system may request clarification.
- Low-confidence destructive actions should not auto-execute silently.

---

## 15. Edge Cases

### Authentication Edge Cases
- Google login succeeds but phone verification fails
- OTP expires
- OTP entered incorrectly too many times
- User enters a phone number already linked to another account
- User changes phone number later

### WhatsApp Edge Cases
- User messages from an unregistered number
- Sender format mismatch due to country code normalization
- User has verified phone but messages from a different WhatsApp number
- Duplicate webhook delivery from WA provider

### Prompt Edge Cases
- Ambiguous title: “hapus meeting” when multiple meetings exist
- Missing date: “ingatkan saya meeting”
- Unsupported command: “bikinin grafik penjualan”
- Invalid recurrence: “setiap tanggal 32”

### Sync Edge Cases
- App displays stale cache after WA update
- Prompt creates duplicate task because of repeated submissions
- Network retry creates repeated operations
- Google refresh token is revoked or expires
- Google incremental sync token becomes invalid and requires a reset/full resync
- Local task and remote Google event are edited concurrently

---

## 16. Non-Functional Requirements

### NFR-01: Security
- OAuth tokens must be handled securely.
- Phone verification must use expiring OTP codes.
- Access to task data must be scoped by authenticated user identity.
- Sensitive data should be encrypted in transit.

### NFR-02: Performance
- Standard CRUD actions should complete in under 2 seconds under normal conditions.
- WhatsApp bot responses should typically return within 3–5 seconds excluding external AI latency.

### NFR-03: Reliability
- Webhook endpoints must be idempotent where possible.
- OTP requests should be rate-limited.
- Prompt execution logs should support debugging failed operations.

### NFR-04: Scalability
- AI command processing should be separated logically from channel-specific adapters.
- App and WhatsApp should use the same domain services.

### NFR-05: Observability
- Track auth events, OTP events, prompt events, task mutations, and webhook errors.

### NFR-06: Localization
- MVP should assume Indonesian-language primary usage, with architecture flexible enough for multilingual prompt support later.

---

## 17. Permissions and Roles

### MVP Role Model
- Only one role in MVP: **User**
- Future roles like admin/support are out of scope but should be possible later.

---

## 18. Metrics and Analytics

### Product Metrics
- Google sign-in success rate
- Phone verification completion rate
- First task creation rate
- App prompt usage rate
- WhatsApp usage rate
- Weekly active users
- Task creation success rate
- Prompt parse failure rate
- Sync consistency incidents

### Operational Metrics
- OTP send success/failure rate
- WhatsApp webhook processing success rate
- API latency
- AI confidence / fallback rate
- Duplicate webhook rate

---

## 19. MVP Release Acceptance Criteria

The MVP is considered shippable when:
1. User can sign in with Google on mobile app.
2. User can verify phone number via OTP.
3. Verified phone number is linked to user account.
4. Unknown WhatsApp sender numbers are rejected safely.
5. Verified users can create and read tasks via WhatsApp.
6. Users can create, edit, delete, and view tasks via app manual UI.
7. Users can create and read tasks via app prompt.
8. App and WhatsApp both reflect the same underlying task data.
9. Core flows are logged and observable.

---

## 20. Suggested MVP Milestones

### Milestone 1: Identity Foundation
- Google auth
- user model
- phone number onboarding
- OTP verification
- account activation gating

### Milestone 2: Core Task Domain
- task model
- task CRUD API
- calendar/day agenda API
- app manual UI

### Milestone 3: AI Prompt Engine
- normalized command schema
- app prompt integration
- response rendering and confirmation flow

### Milestone 4: WhatsApp Integration
- WA webhook
- sender number matching
- prompt handling via shared AI engine
- response formatting

### Milestone 5: Sync and Observability
- push/pull refresh strategy
- logs and monitoring
- duplicate protection
- analytics events

---

## 21. Recommended Product Decisions for MVP

1. **Use one unified command execution service** for both app prompts and WhatsApp prompts.
2. **Treat phone verification as mandatory** before feature access.
3. **Use safe fallback text for unknown WA users** rather than partial onboarding in chat.
4. **Support manual UI and prompt UI in parallel** so AI errors never block usage.
5. **Prefer soft delete and prompt logging** for easier recovery and audit.

---

## 22. Open Questions for Later Phases

- Should reminders be separate from tasks or represented as task notifications?
- Should events and tasks be separate domain models in MVP?
- Should completed tasks appear in agenda by default?
- How should recurring tasks generate instances?
- Will WhatsApp support button-based confirmation in later phases?
- Should the app support push notifications at MVP or later?

---

## 23. Final Product Summary

Zaid MVP is a productivity assistant centered on a **mobile app for identity and onboarding**, with **two equal interaction channels after activation**:
- **Mobile App** (manual UI + prompt AI)
- **WhatsApp** (prompt AI)

The verified phone number is the trust bridge between the app account and WhatsApp identity. Both channels operate on the same task/calendar data model through a shared backend and AI command-processing engine.