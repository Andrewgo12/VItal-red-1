<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Metrics Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the metrics and monitoring
    | system in the Vital Red medical management application.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Metrics Collection
    |--------------------------------------------------------------------------
    |
    | Configuration for metrics collection and storage.
    |
    */

    'collection' => [
        'enabled' => env('METRICS_ENABLED', true),
        'retention_days' => env('METRICS_RETENTION_DAYS', 90),
        'batch_size' => env('METRICS_BATCH_SIZE', 100),
        'flush_interval' => env('METRICS_FLUSH_INTERVAL', 60), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | System Metrics
    |--------------------------------------------------------------------------
    |
    | Configuration for system-level metrics collection.
    |
    */

    'system' => [
        'enabled' => true,
        'interval' => env('SYSTEM_METRICS_INTERVAL', 300), // 5 minutes
        
        'metrics' => [
            'cpu_usage' => true,
            'memory_usage' => true,
            'disk_usage' => true,
            'database_connections' => true,
            'queue_size' => true,
            'cache_hit_ratio' => true,
            'response_time' => true,
            'error_rate' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Medical Metrics
    |--------------------------------------------------------------------------
    |
    | Configuration for medical-specific metrics.
    |
    */

    'medical' => [
        'enabled' => true,
        
        'metrics' => [
            // Case metrics
            'total_cases' => [
                'type' => 'counter',
                'description' => 'Total number of medical cases',
                'labels' => ['specialty', 'priority', 'institution'],
            ],
            
            'pending_cases' => [
                'type' => 'gauge',
                'description' => 'Number of pending medical cases',
                'labels' => ['specialty', 'priority'],
            ],
            
            'urgent_cases' => [
                'type' => 'gauge',
                'description' => 'Number of urgent medical cases',
                'labels' => ['specialty'],
            ],
            
            'response_time' => [
                'type' => 'histogram',
                'description' => 'Time to respond to medical cases (hours)',
                'labels' => ['specialty', 'priority', 'doctor_id'],
                'buckets' => [0.5, 1, 2, 4, 8, 24, 48, 72],
            ],
            
            'acceptance_rate' => [
                'type' => 'gauge',
                'description' => 'Case acceptance rate percentage',
                'labels' => ['specialty', 'doctor_id'],
            ],
            
            // Doctor metrics
            'doctor_workload' => [
                'type' => 'gauge',
                'description' => 'Number of cases assigned to each doctor',
                'labels' => ['doctor_id', 'specialty'],
            ],
            
            'doctor_performance' => [
                'type' => 'gauge',
                'description' => 'Doctor performance score',
                'labels' => ['doctor_id', 'metric_type'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Metrics
    |--------------------------------------------------------------------------
    |
    | Configuration for AI-related metrics.
    |
    */

    'ai' => [
        'enabled' => env('AI_METRICS_ENABLED', true),
        
        'metrics' => [
            'ai_requests' => [
                'type' => 'counter',
                'description' => 'Number of AI analysis requests',
                'labels' => ['model', 'status'],
            ],
            
            'ai_response_time' => [
                'type' => 'histogram',
                'description' => 'AI response time in seconds',
                'labels' => ['model'],
                'buckets' => [0.1, 0.5, 1, 2, 5, 10, 30],
            ],
            
            'ai_accuracy' => [
                'type' => 'gauge',
                'description' => 'AI prediction accuracy percentage',
                'labels' => ['model', 'metric_type'],
            ],
            
            'ai_confidence' => [
                'type' => 'histogram',
                'description' => 'AI confidence scores',
                'labels' => ['model'],
                'buckets' => [0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1.0],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Processing Metrics
    |--------------------------------------------------------------------------
    |
    | Configuration for email processing metrics.
    |
    */

    'email' => [
        'enabled' => env('EMAIL_METRICS_ENABLED', true),
        
        'metrics' => [
            'emails_processed' => [
                'type' => 'counter',
                'description' => 'Number of emails processed',
                'labels' => ['status', 'source'],
            ],
            
            'email_processing_time' => [
                'type' => 'histogram',
                'description' => 'Email processing time in seconds',
                'labels' => ['source'],
                'buckets' => [1, 5, 10, 30, 60, 120, 300],
            ],
            
            'email_extraction_accuracy' => [
                'type' => 'gauge',
                'description' => 'Email data extraction accuracy',
                'labels' => ['field_type'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Metrics
    |--------------------------------------------------------------------------
    |
    | Configuration for application performance metrics.
    |
    */

    'performance' => [
        'enabled' => true,
        
        'metrics' => [
            'http_requests' => [
                'type' => 'counter',
                'description' => 'HTTP requests count',
                'labels' => ['method', 'route', 'status_code'],
            ],
            
            'http_request_duration' => [
                'type' => 'histogram',
                'description' => 'HTTP request duration in seconds',
                'labels' => ['method', 'route'],
                'buckets' => [0.01, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10],
            ],
            
            'database_queries' => [
                'type' => 'counter',
                'description' => 'Database queries count',
                'labels' => ['connection', 'type'],
            ],
            
            'database_query_duration' => [
                'type' => 'histogram',
                'description' => 'Database query duration in seconds',
                'labels' => ['connection'],
                'buckets' => [0.001, 0.005, 0.01, 0.05, 0.1, 0.5, 1, 5],
            ],
            
            'cache_operations' => [
                'type' => 'counter',
                'description' => 'Cache operations count',
                'labels' => ['operation', 'store'],
            ],
            
            'queue_jobs' => [
                'type' => 'counter',
                'description' => 'Queue jobs count',
                'labels' => ['queue', 'status'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Metrics
    |--------------------------------------------------------------------------
    |
    | Configuration for business-level metrics and KPIs.
    |
    */

    'business' => [
        'enabled' => true,
        
        'kpis' => [
            // Efficiency KPIs
            'avg_response_time' => [
                'target' => 4, // hours
                'warning_threshold' => 6,
                'critical_threshold' => 12,
            ],
            
            'case_acceptance_rate' => [
                'target' => 85, // percentage
                'warning_threshold' => 75,
                'critical_threshold' => 65,
            ],
            
            'urgent_case_response_time' => [
                'target' => 1, // hours
                'warning_threshold' => 2,
                'critical_threshold' => 4,
            ],
            
            // Quality KPIs
            'ai_accuracy' => [
                'target' => 90, // percentage
                'warning_threshold' => 80,
                'critical_threshold' => 70,
            ],
            
            'doctor_satisfaction' => [
                'target' => 4.5, // out of 5
                'warning_threshold' => 4.0,
                'critical_threshold' => 3.5,
            ],
            
            // Volume KPIs
            'daily_case_volume' => [
                'target' => 100,
                'warning_threshold' => 150,
                'critical_threshold' => 200,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alerting
    |--------------------------------------------------------------------------
    |
    | Configuration for metric-based alerting.
    |
    */

    'alerting' => [
        'enabled' => env('METRICS_ALERTING_ENABLED', true),
        
        'channels' => [
            'email' => env('METRICS_ALERT_EMAIL', 'admin@vitalred.com'),
            'slack' => env('METRICS_ALERT_SLACK_WEBHOOK'),
        ],
        
        'rules' => [
            'high_response_time' => [
                'metric' => 'response_time',
                'condition' => 'avg > 8', // hours
                'duration' => '5m',
                'severity' => 'warning',
            ],
            
            'urgent_cases_backlog' => [
                'metric' => 'urgent_cases',
                'condition' => 'count > 10',
                'duration' => '1m',
                'severity' => 'critical',
            ],
            
            'ai_service_down' => [
                'metric' => 'ai_requests',
                'condition' => 'rate < 0.1',
                'duration' => '2m',
                'severity' => 'critical',
            ],
            
            'high_error_rate' => [
                'metric' => 'http_requests',
                'condition' => 'error_rate > 5', // percentage
                'duration' => '5m',
                'severity' => 'warning',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for exporting metrics to external systems.
    |
    */

    'export' => [
        'enabled' => env('METRICS_EXPORT_ENABLED', false),
        
        'prometheus' => [
            'enabled' => env('PROMETHEUS_ENABLED', false),
            'endpoint' => '/metrics',
            'namespace' => 'vitalred',
        ],
        
        'influxdb' => [
            'enabled' => env('INFLUXDB_ENABLED', false),
            'host' => env('INFLUXDB_HOST', 'localhost'),
            'port' => env('INFLUXDB_PORT', 8086),
            'database' => env('INFLUXDB_DATABASE', 'vitalred'),
            'username' => env('INFLUXDB_USERNAME'),
            'password' => env('INFLUXDB_PASSWORD'),
        ],
        
        'datadog' => [
            'enabled' => env('DATADOG_ENABLED', false),
            'api_key' => env('DATADOG_API_KEY'),
            'app_key' => env('DATADOG_APP_KEY'),
        ],
    ],

];
