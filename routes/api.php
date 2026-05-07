<?php

use App\Http\Controllers\Api\Agenda\DayAgendaController;
use App\Http\Controllers\Api\Auth\GoogleAuthController;
use App\Http\Controllers\Api\Calendar\MonthCalendarController;
use App\Http\Controllers\Api\Onboarding\OnboardingStatusController;
use App\Http\Controllers\Api\Onboarding\PhoneOnboardingController;
use App\Http\Controllers\Api\Onboarding\ResendOtpController;
use App\Http\Controllers\Api\Onboarding\VerifyPhoneOtpController;
use App\Http\Controllers\Api\Prompts\PromptController;
use App\Http\Controllers\Api\Settings\SettingsController;
use App\Http\Controllers\Api\Tasks\TaskController;
use App\Http\Controllers\Api\User\MeController;
use App\Http\Controllers\Api\Webhooks\WhatsappWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // Health
    Route::get('/health', fn () => response()->json([
        'success' => true,
        'message' => 'Zaid service is healthy',
    ]));

    // Auth (public)
    Route::post('/auth/google', GoogleAuthController::class);

    // WhatsApp webhook (public, provider-verified)
    Route::get('/webhooks/whatsapp', [WhatsappWebhookController::class, 'verify']);
    Route::post('/webhooks/whatsapp', [WhatsappWebhookController::class, 'handle']);

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function (): void {
        // Onboarding (authenticated but phone may not be verified yet)
        Route::get('/onboarding/status', OnboardingStatusController::class);
        Route::post('/onboarding/phone', PhoneOnboardingController::class);
        Route::post('/onboarding/phone/verify', VerifyPhoneOtpController::class);
        Route::post('/onboarding/phone/resend-otp', ResendOtpController::class);
        Route::get('/me', MeController::class);

        // Protected routes (require phone verified)
        Route::middleware('phone.verified')->group(function (): void {
            // Tasks
            Route::apiResource('tasks', TaskController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
            Route::post('/tasks/{task}/complete', [TaskController::class, 'complete']);
            Route::post('/tasks/{task}/restore', [TaskController::class, 'restore']);

            // Agenda & Calendar
            Route::get('/agenda/day', DayAgendaController::class);
            Route::get('/calendar/month', MonthCalendarController::class);

            // Prompts
            Route::post('/prompts', [PromptController::class, 'store']);
            Route::get('/prompts/{promptRequest}', [PromptController::class, 'show']);
            Route::post('/prompts/{promptRequest}/confirm', [PromptController::class, 'confirm']);

            // Settings
            Route::get('/settings', [SettingsController::class, 'show']);
            Route::patch('/settings', [SettingsController::class, 'update']);
        });
    });
});
