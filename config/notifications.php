<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the notification system
    | in the Vital Red medical management system.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Email Notifications
    |--------------------------------------------------------------------------
    |
    | Configuration for email notifications sent by the system.
    |
    */

    'email' => [
        'enabled' => env('NOTIFICATION_EMAIL_ENABLED', true),
        'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@vitalred.com'),
        'from_name' => env('MAIL_FROM_NAME', 'Vital Red'),
        'reply_to' => env('NOTIFICATION_REPLY_TO', 'support@vitalred.com'),
        
        // Email templates
        'templates' => [
            'urgent_case' => 'emails.urgent-case',
            'case_assigned' => 'emails.case-assigned',
            'case_updated' => 'emails.case-updated',
            'daily_summary' => 'emails.daily-summary',
            'system_alert' => 'emails.system-alert',
        ],
        
        // Throttling
        'throttle' => [
            'urgent_cases' => 5, // Max urgent notifications per hour per user
            'general' => 20, // Max general notifications per hour per user
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Internal Notifications
    |--------------------------------------------------------------------------
    |
    | Configuration for internal system notifications (in-app).
    |
    */

    'internal' => [
        'enabled' => true,
        'retention_days' => env('NOTIFICATION_RETENTION_DAYS', 30),
        'max_per_user' => env('NOTIFICATION_MAX_PER_USER', 100),
        
        // Auto-mark as read after
        'auto_read_after_days' => env('NOTIFICATION_AUTO_READ_DAYS', 7),
        
        // Notification types
        'types' => [
            'urgent_case' => [
                'icon' => 'fas fa-exclamation-triangle',
                'color' => 'danger',
                'sound' => true,
            ],
            'new_case' => [
                'icon' => 'fas fa-file-medical',
                'color' => 'info',
                'sound' => false,
            ],
            'case_assigned' => [
                'icon' => 'fas fa-user-md',
                'color' => 'primary',
                'sound' => true,
            ],
            'case_updated' => [
                'icon' => 'fas fa-edit',
                'color' => 'warning',
                'sound' => false,
            ],
            'system_alert' => [
                'icon' => 'fas fa-bell',
                'color' => 'danger',
                'sound' => true,
            ],
            'reminder' => [
                'icon' => 'fas fa-clock',
                'color' => 'secondary',
                'sound' => false,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Push Notifications
    |--------------------------------------------------------------------------
    |
    | Configuration for browser push notifications.
    |
    */

    'push' => [
        'enabled' => env('PUSH_NOTIFICATIONS_ENABLED', false),
        'vapid_public_key' => env('VAPID_PUBLIC_KEY'),
        'vapid_private_key' => env('VAPID_PRIVATE_KEY'),
        'vapid_subject' => env('VAPID_SUBJECT', 'mailto:admin@vitalred.com'),
        
        // Push notification settings
        'icon' => '/favicon.ico',
        'badge' => '/favicon.ico',
        'ttl' => 3600, // Time to live in seconds
        
        // Auto-subscribe new users
        'auto_subscribe' => env('PUSH_AUTO_SUBSCRIBE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Notifications
    |--------------------------------------------------------------------------
    |
    | Configuration for SMS notifications (optional).
    |
    */

    'sms' => [
        'enabled' => env('SMS_NOTIFICATIONS_ENABLED', false),
        'provider' => env('SMS_PROVIDER', 'twilio'),
        'from_number' => env('SMS_FROM_NUMBER'),
        
        // SMS templates
        'templates' => [
            'urgent_case' => 'Caso urgente: {patient_name} - {specialty}. Revisar sistema.',
            'case_assigned' => 'Nuevo caso asignado: {patient_name}. Revisar sistema.',
        ],
        
        // Only for urgent cases by default
        'urgent_only' => env('SMS_URGENT_ONLY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Slack Notifications
    |--------------------------------------------------------------------------
    |
    | Configuration for Slack notifications (optional).
    |
    */

    'slack' => [
        'enabled' => env('SLACK_NOTIFICATIONS_ENABLED', false),
        'webhook_url' => env('SLACK_WEBHOOK_URL'),
        'channel' => env('SLACK_CHANNEL', '#medical-alerts'),
        'username' => env('SLACK_USERNAME', 'Vital Red'),
        'icon_emoji' => env('SLACK_ICON', ':hospital:'),
        
        // Message templates
        'templates' => [
            'urgent_case' => ':warning: *Caso Urgente* - {patient_name} ({specialty})',
            'system_alert' => ':rotating_light: *Alerta del Sistema* - {message}',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | Define which channels are used for different types of notifications.
    |
    */

    'channels' => [
        'urgent_case' => ['internal', 'email', 'push', 'sms'],
        'new_case' => ['internal'],
        'case_assigned' => ['internal', 'email', 'push'],
        'case_updated' => ['internal'],
        'system_alert' => ['internal', 'email', 'slack'],
        'daily_summary' => ['email'],
        'reminder' => ['internal', 'push'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Urgency Thresholds
    |--------------------------------------------------------------------------
    |
    | Configuration for urgency-based notification behavior.
    |
    */

    'urgency' => [
        // Hours after which urgent cases trigger escalation
        'escalation_threshold' => env('NOTIFICATION_URGENT_THRESHOLD', 2),
        
        // Score thresholds for different urgency levels
        'score_thresholds' => [
            'high' => env('URGENCY_HIGH_THRESHOLD', 80),
            'medium' => env('URGENCY_MEDIUM_THRESHOLD', 50),
            'low' => env('URGENCY_LOW_THRESHOLD', 20),
        ],
        
        // Escalation intervals (in minutes)
        'escalation_intervals' => [
            'first' => 30,  // 30 minutes
            'second' => 60, // 1 hour
            'third' => 120, // 2 hours
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Preferences
    |--------------------------------------------------------------------------
    |
    | Default notification preferences for new users.
    |
    */

    'default_preferences' => [
        'email_notifications' => true,
        'urgent_cases' => true,
        'case_assignments' => true,
        'case_updates' => false,
        'daily_summary' => true,
        'system_updates' => true,
        'push_notifications' => false,
        'sms_notifications' => false,
        
        // Quiet hours (24-hour format)
        'quiet_hours' => [
            'enabled' => false,
            'start' => '22:00',
            'end' => '07:00',
        ],
        
        // Weekend notifications
        'weekend_notifications' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configuration for notification rate limiting to prevent spam.
    |
    */

    'rate_limiting' => [
        'enabled' => true,
        
        // Max notifications per time period
        'limits' => [
            'email' => [
                'urgent' => ['count' => 5, 'period' => 3600], // 5 per hour
                'normal' => ['count' => 20, 'period' => 3600], // 20 per hour
            ],
            'sms' => [
                'urgent' => ['count' => 3, 'period' => 3600], // 3 per hour
                'normal' => ['count' => 5, 'period' => 3600], // 5 per hour
            ],
            'push' => [
                'urgent' => ['count' => 10, 'period' => 3600], // 10 per hour
                'normal' => ['count' => 50, 'period' => 3600], // 50 per hour
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Delivery Tracking
    |--------------------------------------------------------------------------
    |
    | Configuration for tracking notification delivery and engagement.
    |
    */

    'tracking' => [
        'enabled' => env('NOTIFICATION_TRACKING_ENABLED', true),
        'retention_days' => env('NOTIFICATION_TRACKING_RETENTION', 90),
        
        // Track delivery status
        'track_delivery' => true,
        
        // Track user engagement (opens, clicks)
        'track_engagement' => true,
        
        // Store delivery logs
        'store_logs' => true,
    ],

];
