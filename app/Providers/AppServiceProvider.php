<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\Paginator;
use App\Services\GeminiAIService;
use App\Services\NotificationService;
use App\Services\ReportService;
use App\Services\MetricsService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register services as singletons
        $this->app->singleton(GeminiAIService::class, function ($app) {
            return new GeminiAIService();
        });

        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService();
        });

        $this->app->singleton(ReportService::class, function ($app) {
            return new ReportService();
        });

        $this->app->singleton(MetricsService::class, function ($app) {
            return new MetricsService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set default string length for MySQL
        Schema::defaultStringLength(191);

        // Force HTTPS in production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Use Bootstrap for pagination
        Paginator::useBootstrapFive();

        // Define Gates for authorization
        $this->defineGates();

        // Register custom validation rules
        $this->registerValidationRules();

        // Register model observers
        $this->registerObservers();
    }

    /**
     * Define authorization gates
     */
    private function defineGates(): void
    {
        // Admin gates
        Gate::define('manage-users', function ($user) {
            return $user->role === 'administrador';
        });

        Gate::define('view-admin-panel', function ($user) {
            return $user->role === 'administrador';
        });

        Gate::define('configure-system', function ($user) {
            return $user->role === 'administrador';
        });

        Gate::define('view-reports', function ($user) {
            return $user->role === 'administrador';
        });

        Gate::define('manage-system-config', function ($user) {
            return $user->role === 'administrador';
        });

        // Medical gates
        Gate::define('evaluate-cases', function ($user) {
            return in_array($user->role, ['medico', 'administrador']);
        });

        Gate::define('view-medical-cases', function ($user) {
            return in_array($user->role, ['medico', 'administrador']);
        });

        Gate::define('access-medical-area', function ($user) {
            return in_array($user->role, ['medico', 'administrador']);
        });
    }

    /**
     * Register custom validation rules
     */
    private function registerValidationRules(): void
    {
        \Validator::extend('medical_license', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^MP-\d{4,6}$/', $value);
        });

        \Validator::extend('colombian_phone', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^(\+57|57)?[1-9]\d{9}$/', $value);
        });

        \Validator::extend('medical_specialty', function ($attribute, $value, $parameters, $validator) {
            $validSpecialties = [
                'Medicina General', 'Cardiología', 'Neurología', 'Ortopedia',
                'Pediatría', 'Ginecología', 'Urología', 'Oftalmología',
                'Dermatología', 'Psiquiatría', 'Medicina Interna', 'Cirugía General'
            ];
            return in_array($value, $validSpecialties);
        });

        \Validator::extend('priority_level', function ($attribute, $value, $parameters, $validator) {
            return in_array($value, ['Alta', 'Media', 'Baja']);
        });

        \Validator::extend('case_status', function ($attribute, $value, $parameters, $validator) {
            $validStatuses = [
                'pendiente_evaluacion', 'en_evaluacion', 'aceptada',
                'rechazada', 'derivada', 'completada'
            ];
            return in_array($value, $validStatuses);
        });
    }

    /**
     * Register model observers
     */
    private function registerObservers(): void
    {
        \App\Models\SolicitudMedica::observe(\App\Observers\SolicitudMedicaObserver::class);
    }
}
