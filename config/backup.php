<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the backup system.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Backup Retention
    |--------------------------------------------------------------------------
    |
    | Number of days to keep backup files before they are automatically deleted.
    |
    */

    'retention_days' => env('BACKUP_RETENTION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Backup Storage
    |--------------------------------------------------------------------------
    |
    | Configuration for backup storage locations.
    |
    */

    'storage' => [
        'local' => [
            'enabled' => true,
            'path' => storage_path('app/backups'),
        ],
        
        's3' => [
            'enabled' => env('BACKUP_S3_ENABLED', false),
            'bucket' => env('BACKUP_S3_BUCKET'),
            'region' => env('BACKUP_S3_REGION', 'us-east-1'),
            'key' => env('BACKUP_S3_KEY'),
            'secret' => env('BACKUP_S3_SECRET'),
            'path' => env('BACKUP_S3_PATH', 'backups'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Backup
    |--------------------------------------------------------------------------
    |
    | Configuration for database backups.
    |
    */

    'database' => [
        'enabled' => true,
        'connections' => [
            config('database.default'),
        ],
        'exclude_tables' => [
            'sessions',
            'cache',
            'jobs',
            'failed_jobs',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Backup
    |--------------------------------------------------------------------------
    |
    | Configuration for file backups.
    |
    */

    'files' => [
        'enabled' => true,
        'include' => [
            base_path('.env'),
            storage_path('app'),
            public_path('uploads'),
        ],
        'exclude' => [
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('app/backups'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Notifications
    |--------------------------------------------------------------------------
    |
    | Configuration for backup notifications.
    |
    */

    'notifications' => [
        'enabled' => env('BACKUP_NOTIFICATIONS_ENABLED', true),
        'channels' => ['mail'],
        'recipients' => [
            env('BACKUP_NOTIFICATION_EMAIL', 'admin@vitalred.com'),
        ],
        'notify_on' => [
            'success' => false,
            'failure' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Schedule
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic backup scheduling.
    |
    */

    'schedule' => [
        'database' => [
            'enabled' => true,
            'frequency' => 'daily',
            'time' => '02:00',
        ],
        'files' => [
            'enabled' => true,
            'frequency' => 'weekly',
            'day' => 'sunday',
            'time' => '03:00',
        ],
        'full' => [
            'enabled' => true,
            'frequency' => 'monthly',
            'day' => 1,
            'time' => '04:00',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Compression
    |--------------------------------------------------------------------------
    |
    | Configuration for backup compression.
    |
    */

    'compression' => [
        'enabled' => true,
        'method' => 'zip', // zip, gzip, tar
        'level' => 6, // 1-9 for compression level
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Encryption
    |--------------------------------------------------------------------------
    |
    | Configuration for backup encryption.
    |
    */

    'encryption' => [
        'enabled' => env('BACKUP_ENCRYPTION_ENABLED', false),
        'method' => 'aes-256-cbc',
        'key' => env('BACKUP_ENCRYPTION_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for backup monitoring and health checks.
    |
    */

    'monitoring' => [
        'enabled' => true,
        'max_backup_age_days' => 2,
        'min_backup_size_mb' => 1,
        'check_frequency' => 'daily',
    ],

];
