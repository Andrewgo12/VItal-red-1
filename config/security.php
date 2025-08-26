<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Configuration for Vital Red System
    |--------------------------------------------------------------------------
    |
    | This file contains security settings for the medical system including
    | access controls, audit settings, and security policies.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Role-Based Access Control
    |--------------------------------------------------------------------------
    */
    'roles' => [
        'administrador' => [
            'name' => 'Administrador',
            'permissions' => [
                'admin.*',
                'medico.*',
                'gmail-monitor.*',
                'audit.*',
                'users.*',
                'reports.*',
                'system.*'
            ],
            'description' => 'Acceso completo al sistema'
        ],
        'medico' => [
            'name' => 'Médico Evaluador',
            'permissions' => [
                'medico.bandeja-casos',
                'medico.evaluar-solicitud',
                'medico.consulta-pacientes',
                'medico.ingresar-registro',
                'solicitudes-medicas.view',
                'solicitudes-medicas.evaluate',
                'solicitudes-medicas.update'
            ],
            'description' => 'Acceso a funciones médicas y evaluación de casos'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Protected Routes
    |--------------------------------------------------------------------------
    */
    'protected_routes' => [
        'admin/*' => ['administrador'],
        'medico/*' => ['medico', 'administrador'],
        'api/gmail-monitor/*' => ['administrador'],
        'api/solicitudes-medicas/*' => ['medico', 'administrador'],
        'api/admin/*' => ['administrador']
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Configuration
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'enabled' => env('AUDIT_ENABLED', true),
        'retention_days' => env('AUDIT_RETENTION_DAYS', 90),
        'log_channel' => env('AUDIT_LOG_CHANNEL', 'audit'),
        'log_failed_requests' => env('AUDIT_LOG_FAILED_REQUESTS', true),
        'log_successful_requests' => env('AUDIT_LOG_SUCCESSFUL_REQUESTS', true),
        
        // Routes to audit
        'auditable_routes' => [
            'admin/*',
            'medico/*',
            'api/gmail-monitor/*',
            'api/solicitudes-medicas/*'
        ],
        
        // Actions that require special attention
        'high_risk_actions' => [
            'delete_medical_request',
            'start_gmail_monitoring',
            'stop_gmail_monitoring',
            'create_user',
            'delete_user',
            'change_user_role'
        ],
        
        // Sensitive data fields to redact in logs
        'sensitive_fields' => [
            'password',
            'password_confirmation',
            'token',
            'api_key',
            '_token',
            'csrf_token',
            'paciente_identificacion' // Protect patient ID
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    */
    'session' => [
        'timeout_minutes' => env('SESSION_TIMEOUT_MINUTES', 480), // 8 hours
        'concurrent_sessions' => env('ALLOW_CONCURRENT_SESSIONS', false),
        'secure_cookies' => env('SESSION_SECURE_COOKIES', true),
        'same_site_cookies' => env('SESSION_SAME_SITE', 'strict')
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limiting' => [
        'api' => [
            'max_attempts' => env('API_RATE_LIMIT_ATTEMPTS', 60),
            'decay_minutes' => env('API_RATE_LIMIT_DECAY', 1)
        ],
        'login' => [
            'max_attempts' => env('LOGIN_RATE_LIMIT_ATTEMPTS', 5),
            'decay_minutes' => env('LOGIN_RATE_LIMIT_DECAY', 15)
        ],
        'gmail_monitor' => [
            'max_attempts' => env('GMAIL_MONITOR_RATE_LIMIT', 10),
            'decay_minutes' => env('GMAIL_MONITOR_RATE_DECAY', 60)
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Restrictions
    |--------------------------------------------------------------------------
    */
    'ip_restrictions' => [
        'enabled' => env('IP_RESTRICTIONS_ENABLED', false),
        'allowed_ips' => env('ALLOWED_IPS', ''),
        'admin_only_ips' => env('ADMIN_ONLY_IPS', ''),
        'blocked_ips' => env('BLOCKED_IPS', '')
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Protection
    |--------------------------------------------------------------------------
    */
    'data_protection' => [
        'encrypt_sensitive_data' => env('ENCRYPT_SENSITIVE_DATA', true),
        'mask_patient_data_in_logs' => env('MASK_PATIENT_DATA', true),
        'require_https' => env('REQUIRE_HTTPS', true),
        'data_retention_days' => env('DATA_RETENTION_DAYS', 2555), // 7 years
        
        // Fields that should be encrypted in database
        'encrypted_fields' => [
            'paciente_identificacion',
            'paciente_telefono',
            'telefono_remitente'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Policy
    |--------------------------------------------------------------------------
    */
    'password_policy' => [
        'min_length' => env('PASSWORD_MIN_LENGTH', 8),
        'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
        'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
        'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
        'require_symbols' => env('PASSWORD_REQUIRE_SYMBOLS', true),
        'max_age_days' => env('PASSWORD_MAX_AGE_DAYS', 90),
        'history_count' => env('PASSWORD_HISTORY_COUNT', 5)
    ],

    /*
    |--------------------------------------------------------------------------
    | Medical Data Security
    |--------------------------------------------------------------------------
    */
    'medical_data' => [
        'access_logging' => env('MEDICAL_DATA_ACCESS_LOGGING', true),
        'require_justification' => env('MEDICAL_DATA_REQUIRE_JUSTIFICATION', false),
        'auto_logout_minutes' => env('MEDICAL_DATA_AUTO_LOGOUT', 30),
        'watermark_documents' => env('MEDICAL_DATA_WATERMARK', true),
        
        // Patient data access controls
        'patient_data_access' => [
            'log_all_access' => true,
            'require_reason' => false,
            'notify_patient' => false,
            'retention_days' => 2555 // 7 years
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | System Monitoring
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'failed_login_threshold' => env('FAILED_LOGIN_THRESHOLD', 5),
        'suspicious_activity_threshold' => env('SUSPICIOUS_ACTIVITY_THRESHOLD', 10),
        'alert_email' => env('SECURITY_ALERT_EMAIL', ''),
        'monitor_file_changes' => env('MONITOR_FILE_CHANGES', true),
        'monitor_database_changes' => env('MONITOR_DATABASE_CHANGES', true)
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup and Recovery
    |--------------------------------------------------------------------------
    */
    'backup' => [
        'enabled' => env('BACKUP_ENABLED', true),
        'frequency' => env('BACKUP_FREQUENCY', 'daily'),
        'retention_days' => env('BACKUP_RETENTION_DAYS', 30),
        'encrypt_backups' => env('ENCRYPT_BACKUPS', true),
        'verify_backups' => env('VERIFY_BACKUPS', true)
    ],

    /*
    |--------------------------------------------------------------------------
    | Compliance Settings
    |--------------------------------------------------------------------------
    */
    'compliance' => [
        'hipaa_compliant' => env('HIPAA_COMPLIANT', true),
        'gdpr_compliant' => env('GDPR_COMPLIANT', true),
        'data_processing_agreement' => env('DATA_PROCESSING_AGREEMENT', true),
        'patient_consent_required' => env('PATIENT_CONSENT_REQUIRED', true),
        'audit_trail_immutable' => env('AUDIT_TRAIL_IMMUTABLE', true)
    ],

    /*
    |--------------------------------------------------------------------------
    | Emergency Access
    |--------------------------------------------------------------------------
    */
    'emergency_access' => [
        'enabled' => env('EMERGENCY_ACCESS_ENABLED', true),
        'emergency_role' => 'emergency_admin',
        'require_justification' => env('EMERGENCY_REQUIRE_JUSTIFICATION', true),
        'auto_expire_hours' => env('EMERGENCY_AUTO_EXPIRE_HOURS', 24),
        'notify_admin' => env('EMERGENCY_NOTIFY_ADMIN', true)
    ]
];
