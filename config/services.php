<?php

return [

    'openai' => [
        'api_key' => env('OPENAI_API_KEY', ''),
        'api_base' => env('OPENAI_API_BASE', 'https://api.openai.com/v1'),
        'model_text' => env('OPENAI_MODEL_TEXT', 'gpt-4.1-mini'),
        'model_multimodal' => env('OPENAI_MODEL_MULTIMODAL', 'gpt-4.1-mini'),
        'model_fallback' => env('OPENAI_MODEL_FALLBACK', 'gpt-4.1-mini'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        'calendar_redirect' => env('GOOGLE_CALENDAR_REDIRECT_URI', env('GOOGLE_REDIRECT_URI')),
        'calendar_scopes' => array_values(array_filter(array_map('trim', explode(',', (string) env('GOOGLE_CALENDAR_SCOPES', 'https://www.googleapis.com/auth/calendar,https://www.googleapis.com/auth/tasks'))))),
        'calendar_primary_id' => env('GOOGLE_CALENDAR_PRIMARY_ID', 'primary'),
        'calendar_sync_interval_minutes' => (int) env('GOOGLE_CALENDAR_SYNC_INTERVAL_MINUTES', 5),
    ],

    'whatsapp' => [
        'driver' => env('WHATSAPP_DRIVER', 'waha'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
    ],

    'waha' => [
        'base_url' => env('WAHA_BASE_URL'),
        'api_key' => env('WAHA_API_KEY'),
        'session' => env('WAHA_SESSION', 'default'),
        'webhook_secret' => env('WAHA_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
