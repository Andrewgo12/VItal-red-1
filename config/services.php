<?php

return [

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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Gmail Service Configuration
    |--------------------------------------------------------------------------
    */

    'gmail' => [
        'enabled' => env('GMAIL_ENABLED', false),
        'email' => env('GMAIL_EMAIL'),
        'credentials_path' => env('GMAIL_CREDENTIALS_PATH', storage_path('app/gmail-credentials.json')),
        'token_path' => env('GMAIL_TOKEN_PATH', storage_path('app/gmail-token.json')),
        'monitoring_interval' => env('GMAIL_MONITORING_INTERVAL', 60),
        'max_emails_per_batch' => env('GMAIL_MAX_EMAILS_PER_BATCH', 10),
        'scopes' => [
            'https://www.googleapis.com/auth/gmail.readonly',
            'https://www.googleapis.com/auth/gmail.modify',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Gemini AI Service Configuration
    |--------------------------------------------------------------------------
    */

    'gemini' => [
        'enabled' => env('GEMINI_ENABLED', false),
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-pro'),
        'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
        'timeout' => 30,
        'max_retries' => 3,
        'confidence_threshold' => env('AI_CONFIDENCE_THRESHOLD', 0.7),
        'auto_classification' => env('AI_AUTO_CLASSIFICATION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Python Service Configuration
    |--------------------------------------------------------------------------
    */

    'python' => [
        'enabled' => env('PYTHON_SERVICE_ENABLED', true),
        'url' => env('PYTHON_SERVICE_URL', 'http://localhost:8001'),
        'timeout' => 30,
        'max_retries' => 3,
        'endpoints' => [
            'gmail_check' => '/gmail/check',
            'analyze_medical' => '/ai/analyze-medical',
            'process_email' => '/email/process',
            'health_check' => '/health',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Services Configuration
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        'email_enabled' => env('NOTIFICATION_EMAIL_ENABLED', true),
        'urgent_notification_threshold' => env('NOTIFICATION_URGENT_THRESHOLD', 2),
        'notification_channels' => explode(',', env('NOTIFICATION_CHANNELS', 'email,internal')),
        'push_notifications' => [
            'enabled' => env('PUSH_NOTIFICATIONS_ENABLED', false),
            'vapid_public_key' => env('VAPID_PUBLIC_KEY'),
            'vapid_private_key' => env('VAPID_PRIVATE_KEY'),
            'vapid_subject' => env('VAPID_SUBJECT', 'mailto:admin@vitalred.com'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | External APIs Configuration
    |--------------------------------------------------------------------------
    */

    'external_apis' => [
        'medical_database' => [
            'enabled' => env('MEDICAL_DB_ENABLED', false),
            'url' => env('MEDICAL_DB_URL'),
            'api_key' => env('MEDICAL_DB_API_KEY'),
            'timeout' => 15,
        ],
        'sms_service' => [
            'enabled' => env('SMS_SERVICE_ENABLED', false),
            'provider' => env('SMS_PROVIDER', 'twilio'),
            'api_key' => env('SMS_API_KEY'),
            'api_secret' => env('SMS_API_SECRET'),
            'from_number' => env('SMS_FROM_NUMBER'),
        ],
    ],

];
