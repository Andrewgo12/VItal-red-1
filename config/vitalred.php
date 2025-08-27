<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Vital Red System Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the main configuration for the Vital Red medical
    | management system. All system-wide settings are defined here.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | System Information
    |--------------------------------------------------------------------------
    */

    'system' => [
        'name' => env('VITALRED_SYSTEM_NAME', 'Vital Red'),
        'version' => '1.0.0',
        'build' => env('VITALRED_BUILD', 'production'),
        'environment' => env('APP_ENV', 'production'),
        'timezone' => env('APP_TIMEZONE', 'America/Bogota'),
        'locale' => env('APP_LOCALE', 'es'),
        'support_email' => env('VITALRED_SUPPORT_EMAIL', 'support@vitalred.com'),
        'admin_email' => env('VITALRED_ADMIN_EMAIL', 'admin@vitalred.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Medical System Configuration
    |--------------------------------------------------------------------------
    */

    'medical' => [
        'specialties' => [
            'Cardiología',
            'Neurología',
            'Pediatría',
            'Ginecología',
            'Medicina Interna',
            'Cirugía General',
            'Ortopedia',
            'Dermatología',
            'Psiquiatría',
            'Radiología',
            'Anestesiología',
            'Medicina de Emergencia',
            'Oncología',
            'Endocrinología',
            'Gastroenterología',
            'Neumología',
            'Urología',
            'Oftalmología',
            'Otorrinolaringología',
            'Reumatología',
        ],

        'priorities' => [
            'Alta' => [
                'color' => '#dc3545',
                'score_min' => 80,
                'response_time_hours' => 2,
                'notification_channels' => ['email', 'sms', 'push'],
            ],
            'Media' => [
                'color' => '#fd7e14',
                'score_min' => 50,
                'response_time_hours' => 8,
                'notification_channels' => ['email', 'push'],
            ],
            'Baja' => [
                'color' => '#28a745',
                'score_min' => 0,
                'response_time_hours' => 24,
                'notification_channels' => ['email'],
            ],
        ],

        'case_statuses' => [
            'pendiente_evaluacion' => [
                'label' => 'Pendiente Evaluación',
                'color' => '#ffc107',
                'next_statuses' => ['en_evaluacion', 'aceptada', 'rechazada'],
            ],
            'en_evaluacion' => [
                'label' => 'En Evaluación',
                'color' => '#17a2b8',
                'next_statuses' => ['aceptada', 'rechazada', 'derivada'],
            ],
            'aceptada' => [
                'label' => 'Aceptada',
                'color' => '#28a745',
                'next_statuses' => ['completada'],
            ],
            'rechazada' => [
                'label' => 'Rechazada',
                'color' => '#dc3545',
                'next_statuses' => [],
            ],
            'derivada' => [
                'label' => 'Derivada',
                'color' => '#6c757d',
                'next_statuses' => ['completada'],
            ],
            'completada' => [
                'label' => 'Completada',
                'color' => '#007bff',
                'next_statuses' => [],
            ],
        ],

        'urgency_thresholds' => [
            'critical' => 90,
            'high' => 80,
            'medium' => 50,
            'low' => 20,
        ],

        'sla_targets' => [
            'urgent_response_hours' => 2,
            'normal_response_hours' => 24,
            'acceptance_rate_percent' => 85,
            'ai_accuracy_percent' => 90,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Configuration
    |--------------------------------------------------------------------------
    */

    'ai' => [
        'enabled' => env('VITALRED_AI_ENABLED', true),
        'provider' => env('VITALRED_AI_PROVIDER', 'gemini'),
        'auto_classification' => env('VITALRED_AI_AUTO_CLASSIFICATION', true),
        'confidence_threshold' => env('VITALRED_AI_CONFIDENCE_THRESHOLD', 0.7),
        'max_retries' => env('VITALRED_AI_MAX_RETRIES', 3),
        'timeout_seconds' => env('VITALRED_AI_TIMEOUT', 30),
        
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-pro'),
            'max_tokens' => env('GEMINI_MAX_TOKENS', 2048),
            'temperature' => env('GEMINI_TEMPERATURE', 0.7),
        ],

        'prompts' => [
            'medical_analysis' => 'Analiza el siguiente caso médico y proporciona una evaluación de urgencia del 1 al 100, especialidad recomendada, y resumen del caso.',
            'urgency_classification' => 'Clasifica la urgencia de este caso médico en una escala del 1 al 100, donde 100 es extremadamente urgente.',
            'specialty_detection' => 'Identifica la especialidad médica más apropiada para este caso.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Integration Configuration
    |--------------------------------------------------------------------------
    */

    'email' => [
        'gmail' => [
            'enabled' => env('VITALRED_GMAIL_ENABLED', true),
            'client_id' => env('GMAIL_CLIENT_ID'),
            'client_secret' => env('GMAIL_CLIENT_SECRET'),
            'redirect_uri' => env('GMAIL_REDIRECT_URI'),
            'scopes' => [
                'https://www.googleapis.com/auth/gmail.readonly',
                'https://www.googleapis.com/auth/gmail.modify',
            ],
            'monitoring_interval_minutes' => env('GMAIL_MONITORING_INTERVAL', 5),
            'batch_size' => env('GMAIL_BATCH_SIZE', 10),
        ],

        'filters' => [
            'subject_keywords' => [
                'solicitud médica',
                'interconsulta',
                'caso médico',
                'evaluación médica',
                'consulta especializada',
            ],
            'sender_domains' => [
                'hospital.com',
                'clinica.com',
                'medico.com',
                'salud.gov.co',
            ],
            'exclude_keywords' => [
                'spam',
                'promoción',
                'marketing',
                'newsletter',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    */

    'security' => [
        'encryption' => [
            'enabled' => env('VITALRED_ENCRYPTION_ENABLED', true),
            'algorithm' => 'AES-256-CBC',
            'key_rotation_days' => 90,
        ],

        'audit' => [
            'enabled' => env('VITALRED_AUDIT_ENABLED', true),
            'retention_years' => 7,
            'log_channel' => 'audit',
        ],

        'session' => [
            'timeout_minutes' => env('VITALRED_SESSION_TIMEOUT', 60),
            'max_concurrent' => env('VITALRED_MAX_CONCURRENT_SESSIONS', 3),
            'ip_validation' => env('VITALRED_SESSION_IP_VALIDATION', true),
        ],

        'password' => [
            'min_length' => env('VITALRED_PASSWORD_MIN_LENGTH', 12),
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => true,
            'history_count' => 5,
            'expiry_days' => 90,
        ],

        'lockout' => [
            'enabled' => env('VITALRED_LOCKOUT_ENABLED', true),
            'max_attempts' => env('VITALRED_LOCKOUT_MAX_ATTEMPTS', 5),
            'duration_minutes' => env('VITALRED_LOCKOUT_DURATION', 30),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    */

    'performance' => [
        'cache' => [
            'default_ttl' => env('VITALRED_CACHE_TTL', 3600),
            'medical_data_ttl' => env('VITALRED_MEDICAL_CACHE_TTL', 1800),
            'ai_results_ttl' => env('VITALRED_AI_CACHE_TTL', 7200),
            'metrics_ttl' => env('VITALRED_METRICS_CACHE_TTL', 900),
        ],

        'queue' => [
            'default_queue' => env('VITALRED_DEFAULT_QUEUE', 'default'),
            'high_priority_queue' => env('VITALRED_HIGH_PRIORITY_QUEUE', 'high'),
            'email_queue' => env('VITALRED_EMAIL_QUEUE', 'emails'),
            'ai_queue' => env('VITALRED_AI_QUEUE', 'ai'),
        ],

        'limits' => [
            'max_file_size_mb' => env('VITALRED_MAX_FILE_SIZE', 10),
            'max_cases_per_page' => env('VITALRED_MAX_CASES_PER_PAGE', 50),
            'api_rate_limit_per_minute' => env('VITALRED_API_RATE_LIMIT', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    */

    'monitoring' => [
        'metrics' => [
            'enabled' => env('VITALRED_METRICS_ENABLED', true),
            'retention_days' => env('VITALRED_METRICS_RETENTION', 90),
            'collection_interval_minutes' => env('VITALRED_METRICS_INTERVAL', 5),
        ],

        'health_checks' => [
            'enabled' => env('VITALRED_HEALTH_CHECKS_ENABLED', true),
            'interval_minutes' => env('VITALRED_HEALTH_CHECK_INTERVAL', 15),
            'timeout_seconds' => env('VITALRED_HEALTH_CHECK_TIMEOUT', 30),
        ],

        'alerts' => [
            'enabled' => env('VITALRED_ALERTS_ENABLED', true),
            'channels' => ['email', 'slack'],
            'thresholds' => [
                'response_time_ms' => 5000,
                'error_rate_percent' => 5,
                'memory_usage_percent' => 90,
                'disk_usage_percent' => 85,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    */

    'backup' => [
        'enabled' => env('VITALRED_BACKUP_ENABLED', true),
        'schedule' => env('VITALRED_BACKUP_SCHEDULE', 'daily'),
        'retention_days' => env('VITALRED_BACKUP_RETENTION', 30),
        'compression' => env('VITALRED_BACKUP_COMPRESSION', true),
        'encryption' => env('VITALRED_BACKUP_ENCRYPTION', true),
        
        'components' => [
            'database' => true,
            'files' => true,
            'configuration' => true,
            'logs' => false,
        ],

        'storage' => [
            'disk' => env('VITALRED_BACKUP_DISK', 'local'),
            'path' => env('VITALRED_BACKUP_PATH', 'backups'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */

    'features' => [
        'gmail_integration' => env('FEATURE_GMAIL', true),
        'ai_analysis' => env('FEATURE_AI', true),
        'push_notifications' => env('FEATURE_PUSH_NOTIFICATIONS', false),
        'sms_notifications' => env('FEATURE_SMS', false),
        'advanced_reporting' => env('FEATURE_ADVANCED_REPORTS', true),
        'api_access' => env('FEATURE_API', true),
        'backup_system' => env('FEATURE_BACKUPS', true),
        'audit_logging' => env('FEATURE_AUDIT', true),
        'metrics_collection' => env('FEATURE_METRICS', true),
        'two_factor_auth' => env('FEATURE_2FA', false),
        'real_time_updates' => env('FEATURE_REAL_TIME', true),
        'mobile_app' => env('FEATURE_MOBILE', false),
        'telemedicine' => env('FEATURE_TELEMEDICINE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Configuration
    |--------------------------------------------------------------------------
    */

    'integrations' => [
        'slack' => [
            'enabled' => env('SLACK_ENABLED', false),
            'webhook_url' => env('SLACK_WEBHOOK_URL'),
            'channel' => env('SLACK_CHANNEL', '#medical-alerts'),
            'username' => env('SLACK_USERNAME', 'Vital Red'),
        ],

        'sms' => [
            'enabled' => env('SMS_ENABLED', false),
            'provider' => env('SMS_PROVIDER', 'twilio'),
            'api_key' => env('SMS_API_KEY'),
            'from_number' => env('SMS_FROM_NUMBER'),
        ],

        'external_apis' => [
            'enabled' => env('EXTERNAL_APIS_ENABLED', false),
            'timeout_seconds' => env('EXTERNAL_API_TIMEOUT', 30),
            'retry_attempts' => env('EXTERNAL_API_RETRIES', 3),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Configuration
    |--------------------------------------------------------------------------
    */

    'development' => [
        'debug_mode' => env('VITALRED_DEBUG', false),
        'mock_ai' => env('VITALRED_MOCK_AI', false),
        'mock_gmail' => env('VITALRED_MOCK_GMAIL', false),
        'demo_data' => env('VITALRED_DEMO_DATA', false),
        'profiling' => env('VITALRED_PROFILING', false),
    ],

];
