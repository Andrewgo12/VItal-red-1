<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\ConfiguracionSistema;

class VitalRedSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'vitalred:setup 
                            {--force : Force setup even if already configured}
                            {--admin-email= : Admin email address}
                            {--admin-password= : Admin password}
                            {--skip-demo : Skip demo data creation}';

    /**
     * The console command description.
     */
    protected $description = 'Setup Vital Red medical management system';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🏥 Vital Red - Medical Management System Setup');
        $this->info('================================================');

        // Check if system is already configured
        if ($this->isSystemConfigured() && !$this->option('force')) {
            $this->warn('System appears to be already configured.');
            if (!$this->confirm('Do you want to continue anyway?')) {
                return self::SUCCESS;
            }
        }

        try {
            $this->info('Starting system setup...');

            // Step 1: Check requirements
            $this->checkSystemRequirements();

            // Step 2: Setup database
            $this->setupDatabase();

            // Step 3: Create admin user
            $this->createAdminUser();

            // Step 4: Configure system settings
            $this->configureSystemSettings();

            // Step 5: Setup storage
            $this->setupStorage();

            // Step 6: Create demo data (optional)
            if (!$this->option('skip-demo')) {
                $this->createDemoData();
            }

            // Step 7: Optimize application
            $this->optimizeApplication();

            // Step 8: Final verification
            $this->verifySetup();

            $this->info('✅ Setup completed successfully!');
            $this->displayPostSetupInstructions();

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Setup failed: ' . $e->getMessage());
            $this->error('Please check the logs for more details.');
            return self::FAILURE;
        }
    }

    /**
     * Check if system is already configured
     */
    private function isSystemConfigured(): bool
    {
        try {
            // Check if admin user exists
            $adminExists = User::where('role', 'administrador')->exists();
            
            // Check if system configuration exists
            $configExists = ConfiguracionSistema::where('clave', 'system_configured')->exists();
            
            return $adminExists && $configExists;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check system requirements
     */
    private function checkSystemRequirements(): void
    {
        $this->info('🔍 Checking system requirements...');

        $requirements = [
            'PHP Version' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'PDO Extension' => extension_loaded('pdo'),
            'OpenSSL Extension' => extension_loaded('openssl'),
            'Tokenizer Extension' => extension_loaded('tokenizer'),
            'XML Extension' => extension_loaded('xml'),
            'Ctype Extension' => extension_loaded('ctype'),
            'JSON Extension' => extension_loaded('json'),
            'BCMath Extension' => extension_loaded('bcmath'),
            'Fileinfo Extension' => extension_loaded('fileinfo'),
            'Storage Writable' => is_writable(storage_path()),
            'Bootstrap Cache Writable' => is_writable(bootstrap_path('cache')),
        ];

        $failed = [];
        foreach ($requirements as $requirement => $passed) {
            if ($passed) {
                $this->line("  ✅ {$requirement}");
            } else {
                $this->line("  ❌ {$requirement}");
                $failed[] = $requirement;
            }
        }

        if (!empty($failed)) {
            throw new \Exception('System requirements not met: ' . implode(', ', $failed));
        }

        $this->info('✅ All system requirements met');
    }

    /**
     * Setup database
     */
    private function setupDatabase(): void
    {
        $this->info('🗄️ Setting up database...');

        try {
            // Test database connection
            DB::connection()->getPdo();
            $this->line('  ✅ Database connection successful');

            // Run migrations
            $this->line('  📦 Running migrations...');
            Artisan::call('migrate', ['--force' => true]);
            $this->line('  ✅ Migrations completed');

        } catch (\Exception $e) {
            throw new \Exception('Database setup failed: ' . $e->getMessage());
        }
    }

    /**
     * Create admin user
     */
    private function createAdminUser(): void
    {
        $this->info('👤 Creating admin user...');

        $email = $this->option('admin-email') ?: $this->ask('Admin email address', 'admin@vitalred.com');
        $password = $this->option('admin-password') ?: $this->secret('Admin password (leave empty to generate)');

        if (empty($password)) {
            $password = $this->generateSecurePassword();
            $this->warn("Generated admin password: {$password}");
            $this->warn('Please save this password securely!');
        }

        // Check if admin already exists
        $existingAdmin = User::where('email', $email)->first();
        if ($existingAdmin) {
            if ($this->confirm("Admin user with email {$email} already exists. Update password?")) {
                $existingAdmin->update([
                    'password' => Hash::make($password),
                    'is_active' => true,
                ]);
                $this->line('  ✅ Admin user password updated');
            }
        } else {
            User::create([
                'name' => 'Administrador del Sistema',
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'administrador',
                'department' => 'Administración',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $this->line('  ✅ Admin user created successfully');
        }
    }

    /**
     * Configure system settings
     */
    private function configureSystemSettings(): void
    {
        $this->info('⚙️ Configuring system settings...');

        $defaultSettings = [
            'system_configured' => 'true',
            'system_name' => 'Vital Red',
            'system_version' => config('version.version', '1.0.0'),
            'setup_date' => now()->toISOString(),
            'timezone' => config('app.timezone', 'America/Bogota'),
            'locale' => config('app.locale', 'es'),
            
            // Email settings
            'email_notifications_enabled' => 'true',
            'urgent_case_notification_enabled' => 'true',
            'daily_summary_enabled' => 'true',
            
            // AI settings
            'ai_processing_enabled' => 'true',
            'ai_confidence_threshold' => '0.7',
            'ai_auto_classification' => 'true',
            
            // Security settings
            'session_timeout' => '3600',
            'max_login_attempts' => '5',
            'password_min_length' => '12',
            'require_password_change' => 'false',
            
            // Backup settings
            'auto_backup_enabled' => 'true',
            'backup_frequency' => 'daily',
            'backup_retention_days' => '30',
            
            // Monitoring settings
            'metrics_collection_enabled' => 'true',
            'performance_monitoring_enabled' => 'true',
            'error_reporting_enabled' => 'true',
        ];

        foreach ($defaultSettings as $key => $value) {
            ConfiguracionSistema::updateOrCreate(
                ['clave' => $key],
                [
                    'valor' => $value,
                    'descripcion' => $this->getSettingDescription($key),
                    'tipo' => $this->getSettingType($value),
                ]
            );
        }

        $this->line('  ✅ System settings configured');
    }

    /**
     * Setup storage directories
     */
    private function setupStorage(): void
    {
        $this->info('📁 Setting up storage...');

        $directories = [
            'app/public',
            'app/backups',
            'app/reports',
            'app/uploads',
            'app/temp',
            'logs',
            'framework/cache',
            'framework/sessions',
            'framework/views',
        ];

        foreach ($directories as $directory) {
            $path = storage_path($directory);
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
                $this->line("  ✅ Created directory: {$directory}");
            }
        }

        // Create storage link
        if (!file_exists(public_path('storage'))) {
            Artisan::call('storage:link');
            $this->line('  ✅ Storage link created');
        }

        $this->line('  ✅ Storage setup completed');
    }

    /**
     * Create demo data
     */
    private function createDemoData(): void
    {
        if (!$this->confirm('Create demo data for testing?', true)) {
            return;
        }

        $this->info('🎭 Creating demo data...');

        try {
            // Create demo doctor
            $demoDoctor = User::firstOrCreate(
                ['email' => 'doctor@vitalred.com'],
                [
                    'name' => 'Dr. Juan Pérez',
                    'password' => Hash::make('password123'),
                    'role' => 'medico',
                    'department' => 'Cardiología',
                    'medical_license' => 'MP-12345',
                    'specialties' => ['Cardiología', 'Medicina Interna'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $this->line('  ✅ Demo doctor created');

            // Seed demo medical cases
            Artisan::call('db:seed', [
                '--class' => 'DemoSolicitudesMedicasSeeder',
                '--force' => true
            ]);

            $this->line('  ✅ Demo medical cases created');

        } catch (\Exception $e) {
            $this->warn('  ⚠️ Demo data creation failed: ' . $e->getMessage());
        }
    }

    /**
     * Optimize application
     */
    private function optimizeApplication(): void
    {
        $this->info('🚀 Optimizing application...');

        try {
            // Clear all caches
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            // Cache configuration for production
            if (app()->environment('production')) {
                Artisan::call('config:cache');
                Artisan::call('route:cache');
                Artisan::call('view:cache');
            }

            $this->line('  ✅ Application optimized');

        } catch (\Exception $e) {
            $this->warn('  ⚠️ Optimization failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify setup
     */
    private function verifySetup(): void
    {
        $this->info('🔍 Verifying setup...');

        $checks = [
            'Database connection' => $this->verifyDatabase(),
            'Admin user exists' => $this->verifyAdminUser(),
            'System configuration' => $this->verifySystemConfig(),
            'Storage permissions' => $this->verifyStoragePermissions(),
        ];

        foreach ($checks as $check => $passed) {
            if ($passed) {
                $this->line("  ✅ {$check}");
            } else {
                $this->line("  ❌ {$check}");
                throw new \Exception("Verification failed: {$check}");
            }
        }

        $this->line('  ✅ All verifications passed');
    }

    /**
     * Display post-setup instructions
     */
    private function displayPostSetupInstructions(): void
    {
        $this->info('');
        $this->info('🎉 Vital Red setup completed successfully!');
        $this->info('');
        $this->info('Next steps:');
        $this->info('1. Configure Gmail API credentials in .env file');
        $this->info('2. Configure Google Gemini AI API key in .env file');
        $this->info('3. Set up email SMTP configuration');
        $this->info('4. Configure backup storage (AWS S3, etc.)');
        $this->info('5. Set up SSL certificate for production');
        $this->info('6. Configure cron jobs for scheduled tasks');
        $this->info('7. Set up monitoring and alerting');
        $this->info('');
        $this->info('Start the application with: php artisan serve');
        $this->info('Access the admin panel at: ' . config('app.url') . '/admin');
        $this->info('');
        $this->info('Documentation: docs/');
        $this->info('Support: support@vitalred.com');
    }

    /**
     * Generate secure password
     */
    private function generateSecurePassword(int $length = 16): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $password;
    }

    /**
     * Get setting description
     */
    private function getSettingDescription(string $key): string
    {
        $descriptions = [
            'system_configured' => 'Indicates if the system has been configured',
            'system_name' => 'Name of the medical system',
            'system_version' => 'Current version of the system',
            'setup_date' => 'Date when the system was set up',
            'timezone' => 'System timezone',
            'locale' => 'System locale/language',
            'email_notifications_enabled' => 'Enable email notifications',
            'urgent_case_notification_enabled' => 'Enable urgent case notifications',
            'daily_summary_enabled' => 'Enable daily summary emails',
            'ai_processing_enabled' => 'Enable AI processing of medical cases',
            'ai_confidence_threshold' => 'Minimum confidence threshold for AI decisions',
            'ai_auto_classification' => 'Enable automatic case classification',
            'session_timeout' => 'Session timeout in seconds',
            'max_login_attempts' => 'Maximum login attempts before lockout',
            'password_min_length' => 'Minimum password length',
            'require_password_change' => 'Require password change on first login',
            'auto_backup_enabled' => 'Enable automatic backups',
            'backup_frequency' => 'Backup frequency (daily, weekly, monthly)',
            'backup_retention_days' => 'Number of days to retain backups',
            'metrics_collection_enabled' => 'Enable metrics collection',
            'performance_monitoring_enabled' => 'Enable performance monitoring',
            'error_reporting_enabled' => 'Enable error reporting',
        ];

        return $descriptions[$key] ?? 'System configuration setting';
    }

    /**
     * Get setting type
     */
    private function getSettingType(string $value): string
    {
        if (in_array(strtolower($value), ['true', 'false'])) {
            return 'boolean';
        }
        
        if (is_numeric($value)) {
            return 'number';
        }
        
        return 'string';
    }

    /**
     * Verify database connection
     */
    private function verifyDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Verify admin user exists
     */
    private function verifyAdminUser(): bool
    {
        return User::where('role', 'administrador')->where('is_active', true)->exists();
    }

    /**
     * Verify system configuration
     */
    private function verifySystemConfig(): bool
    {
        return ConfiguracionSistema::where('clave', 'system_configured')->where('valor', 'true')->exists();
    }

    /**
     * Verify storage permissions
     */
    private function verifyStoragePermissions(): bool
    {
        return is_writable(storage_path()) && is_writable(bootstrap_path('cache'));
    }
}
