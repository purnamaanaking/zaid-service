# Zaid Service

Laravel backend service for the Zaid mobile app and WhatsApp assistant.

## Stack
- Laravel 13
- PostgreSQL
- Laravel Sanctum
- Laravel Socialite

## Current status
This repository has been scaffolded and is ready for backend domain implementation.

## Quick start
1. Copy environment file:
   - `cp .env.example .env`
2. Update PostgreSQL credentials in `.env`
3. Generate app key:
   - `php artisan key:generate`
4. Run migrations:
   - `php artisan migrate`
5. Start local server:
   - `php artisan serve`

## Notes
- `.env.example` is preconfigured for PostgreSQL.
- Sanctum is installed as the token auth foundation for the mobile app API.
- Socialite is installed as the Google auth foundation.
- Additional domain implementation for onboarding, tasks, prompts, and WhatsApp webhook is not created yet.

## Local environment reminder
The current machine does not have `pdo_pgsql` installed yet, so PostgreSQL connections and migrations will need that PHP extension enabled before database work can run locally.
