<?php

use App\Http\Controllers\Api\Agenda\DayAgendaController;
use App\Http\Controllers\Api\Auth\GoogleAuthController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RefreshTokenController;
use App\Http\Controllers\Api\Calendar\MonthCalendarController;
use App\Http\Controllers\Api\Events\EventController;
use App\Http\Controllers\Api\Integrations\GoogleCalendarCallbackController;
use App\Http\Controllers\Api\Integrations\GoogleCalendarConnectController;
use App\Http\Controllers\Api\Integrations\GoogleCalendarDisconnectController;
use App\Http\Controllers\Api\Integrations\GoogleCalendarStatusController;
use App\Http\Controllers\Api\Onboarding\OnboardingStatusController;
use App\Http\Controllers\Api\Onboarding\PhoneOnboardingController;
use App\Http\Controllers\Api\Onboarding\ResendOtpController;
use App\Http\Controllers\Api\Onboarding\VerifyPhoneOtpController;
use App\Http\Controllers\Api\Prompts\PromptController;
use App\Http\Controllers\Api\Settings\SettingsController;
use App\Http\Controllers\Api\TaskLists\TaskListController;
use App\Http\Controllers\Api\Tasks\TaskController;
use App\Http\Controllers\Api\Upload\FileUploadController;
use App\Http\Controllers\Api\User\ChangePhoneController;
use App\Http\Controllers\Api\User\MeController;
use App\Http\Controllers\Api\User\UpdateProfileController;
use App\Http\Controllers\Api\Webhooks\GoogleCalendarWebhookController;
use App\Http\Controllers\Api\Webhooks\WhatsappWebhookController;
use App\Http\Controllers\Api\Whatsapp\WahaSessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // Health
    Route::get('/health', fn () => response()->json([
        'success' => true,
        'message' => 'Zaid service is healthy',
    ]));

    // Auth (public)
    Route::middleware('throttle:auth')->group(function (): void {
        Route::post('/auth/google', GoogleAuthController::class);
    });

    // WhatsApp webhook (public, provider-verified)
    Route::get('/webhooks/whatsapp', [WhatsappWebhookController::class, 'verify']);
    Route::post('/webhooks/whatsapp', [WhatsappWebhookController::class, 'handle']);

    // Google Calendar push notification webhook (public, Google-verified via channel ID)
    Route::post('/webhooks/google-calendar', GoogleCalendarWebhookController::class);

    Route::get('/integrations/google-calendar/callback', GoogleCalendarCallbackController::class);

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function (): void {
        // Auth management
        Route::post('/auth/refresh', RefreshTokenController::class);
        Route::post('/auth/logout', LogoutController::class);

        // Onboarding (authenticated but phone may not be verified yet)
        Route::get('/onboarding/status', OnboardingStatusController::class);
        Route::middleware('throttle:otp')->group(function (): void {
            Route::post('/onboarding/phone', PhoneOnboardingController::class);
            Route::post('/onboarding/phone/verify', VerifyPhoneOtpController::class);
            Route::post('/onboarding/phone/resend-otp', ResendOtpController::class);
        });

        // User profile
        Route::get('/me', MeController::class);
        Route::patch('/me', UpdateProfileController::class);

        // Google Calendar integration
        Route::get('/integrations/google-calendar/connect', GoogleCalendarConnectController::class);
        Route::get('/integrations/google-calendar/status', GoogleCalendarStatusController::class);
        Route::delete('/integrations/google-calendar', GoogleCalendarDisconnectController::class);

        // Protected routes (require phone verified)
        Route::middleware('phone.verified')->group(function (): void {
            // Change phone (re-verify flow)
            Route::middleware('throttle:otp')->group(function (): void {
                Route::post('/me/phone/change', ChangePhoneController::class);
            });

            // Tasks
            Route::apiResource('tasks', TaskController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
            Route::post('/tasks/{task}/complete', [TaskController::class, 'complete']);
            Route::post('/tasks/{task}/restore', [TaskController::class, 'restore']);

            // Agenda & Calendar
            Route::get('/agenda/day', DayAgendaController::class);
            Route::get('/calendar/month', MonthCalendarController::class);
            Route::get('/events', [EventController::class, 'index']);
            Route::post('/events', [EventController::class, 'store']);
            Route::patch('/events/{event}', [EventController::class, 'update']);
            Route::delete('/events/{event}', [EventController::class, 'destroy']);

            // Task lists
            Route::apiResource('task-lists', TaskListController::class)->only(['index', 'store', 'update', 'destroy']);

            // Prompts
            Route::middleware('throttle:prompt')->group(function (): void {
                Route::get('/prompts', [PromptController::class, 'index']);
                Route::delete('/prompts', [PromptController::class, 'destroyAll']);
                Route::post('/prompts', [PromptController::class, 'store']);
                Route::get('/prompts/{promptRequest}', [PromptController::class, 'show']);
                Route::post('/prompts/{promptRequest}/confirm', [PromptController::class, 'confirm']);
            });

            // File upload
            Route::middleware('throttle:upload')->group(function (): void {
                Route::post('/upload', FileUploadController::class);
            });

            // WAHA helper endpoints for testing
            Route::get('/whatsapp/waha/status', [WahaSessionController::class, 'status']);
            Route::post('/whatsapp/waha/start', [WahaSessionController::class, 'start']);
            Route::get('/whatsapp/waha/qr', [WahaSessionController::class, 'qr']);

            // Settings
            Route::get('/settings', [SettingsController::class, 'show']);
            Route::patch('/settings', [SettingsController::class, 'update']);
        });
    });
});
