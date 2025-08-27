<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Version Information
    |--------------------------------------------------------------------------
    |
    | This file contains version and build information for the Vital Red
    | medical management system.
    |
    */

    'version' => '1.0.0',
    'build' => env('APP_BUILD', 'dev'),
    'release_date' => '2024-01-15',
    'codename' => 'Genesis',

    /*
    |--------------------------------------------------------------------------
    | Version History
    |--------------------------------------------------------------------------
    |
    | Major version releases and their key features.
    |
    */

    'history' => [
        '1.0.0' => [
            'release_date' => '2024-01-15',
            'codename' => 'Genesis',
            'features' => [
                'Sistema completo de gestión médica',
                'Integración con Gmail API',
                'Análisis inteligente con Gemini AI',
                'Dashboard con métricas en tiempo real',
                'Sistema de notificaciones avanzado',
                'API REST completa',
                'Sistema de respaldos automáticos',
                'Gestión de usuarios y roles',
                'Reportes y análisis de tendencias',
                'Configuración del sistema',
            ],
            'improvements' => [
                'Arquitectura escalable con Laravel 11',
                'Frontend moderno con Vue.js 3 e Inertia.js',
                'Autenticación segura con Sanctum',
                'Sistema de colas con Redis',
                'Logging y auditoría completos',
                'Tests automatizados',
                'Documentación exhaustiva',
            ],
            'fixes' => [
                'Implementación inicial estable',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | System Requirements
    |--------------------------------------------------------------------------
    |
    | Minimum system requirements for this version.
    |
    */

    'requirements' => [
        'php' => '8.2',
        'laravel' => '11.0',
        'mysql' => '8.0',
        'redis' => '6.0',
        'node' => '18.0',
        'memory' => '2GB',
        'storage' => '5GB',
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Feature flags for enabling/disabling functionality.
    |
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
    ],

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | API versioning information.
    |
    */

    'api' => [
        'version' => 'v1',
        'supported_versions' => ['v1'],
        'deprecated_versions' => [],
        'sunset_date' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Schema Version
    |--------------------------------------------------------------------------
    |
    | Database schema version for migration tracking.
    |
    */

    'database' => [
        'schema_version' => '1.0.0',
        'migration_count' => 8,
        'last_migration' => '2024_01_15_000008_create_metricas_sistema_table',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dependencies
    |--------------------------------------------------------------------------
    |
    | Key dependency versions.
    |
    */

    'dependencies' => [
        'laravel/framework' => '^11.0',
        'inertiajs/inertia-laravel' => '^2.0',
        'laravel/sanctum' => '^4.0',
        'vue' => '^3.3',
        'bootstrap' => '^5.3',
        'chart.js' => '^4.4',
    ],

    /*
    |--------------------------------------------------------------------------
    | Build Information
    |--------------------------------------------------------------------------
    |
    | Build and deployment information.
    |
    */

    'build_info' => [
        'build_number' => env('BUILD_NUMBER', '1'),
        'build_date' => env('BUILD_DATE', '2024-01-15'),
        'git_commit' => env('GIT_COMMIT', 'unknown'),
        'git_branch' => env('GIT_BRANCH', 'main'),
        'environment' => env('APP_ENV', 'production'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Support Information
    |--------------------------------------------------------------------------
    |
    | Support and contact information.
    |
    */

    'support' => [
        'email' => 'support@vitalred.com',
        'documentation' => 'https://docs.vitalred.com',
        'github' => 'https://github.com/vital-red/medical-system',
        'website' => 'https://vitalred.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | License Information
    |--------------------------------------------------------------------------
    |
    | License and copyright information.
    |
    */

    'license' => [
        'type' => 'Proprietary',
        'copyright' => '© 2024 Vital Red. Todos los derechos reservados.',
        'terms_url' => 'https://vitalred.com/terms',
        'privacy_url' => 'https://vitalred.com/privacy',
    ],

];
