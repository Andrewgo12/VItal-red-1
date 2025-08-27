<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SetupVitalRed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vitalred:setup {--force : Force setup even if database exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup Vital Red system with initial configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🏥 Configurando Sistema Vital Red...');
        $this->newLine();

        // Check if database is already setup
        if (!$this->option('force') && $this->isDatabaseSetup()) {
            if (!$this->confirm('La base de datos ya parece estar configurada. ¿Desea continuar?')) {
                $this->info('Configuración cancelada.');
                return 0;
            }
        }

        try {
            // Step 1: Generate application key
            $this->step('Generando clave de aplicación', function () {
                Artisan::call('key:generate', ['--force' => true]);
            });

            // Step 2: Run migrations
            $this->step('Ejecutando migraciones de base de datos', function () {
                Artisan::call('migrate', ['--force' => true]);
            });

            // Step 3: Run seeders
            $this->step('Creando usuarios iniciales', function () {
                Artisan::call('db:seed', ['--force' => true]);
            });

            // Step 4: Create storage link
            $this->step('Creando enlace de almacenamiento', function () {
                Artisan::call('storage:link');
            });

            // Step 5: Clear and cache config
            $this->step('Optimizando configuración', function () {
                Artisan::call('config:clear');
                Artisan::call('cache:clear');
                Artisan::call('route:clear');
                Artisan::call('view:clear');
            });

            // Step 6: Setup directories
            $this->step('Creando directorios necesarios', function () {
                $this->createDirectories();
            });

            // Step 7: Set permissions (Linux/Mac only)
            if (PHP_OS_FAMILY !== 'Windows') {
                $this->step('Configurando permisos', function () {
                    $this->setPermissions();
                });
            }

            $this->newLine();
            $this->info('✅ ¡Sistema Vital Red configurado exitosamente!');
            $this->newLine();

            // Display login information
            $this->displayLoginInfo();

            // Display next steps
            $this->displayNextSteps();

        } catch (\Exception $e) {
            $this->error('❌ Error durante la configuración: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Execute a setup step with progress indication
     */
    private function step(string $description, callable $callback): void
    {
        $this->info("📋 {$description}...");
        
        try {
            $callback();
            $this->info("   ✅ Completado");
        } catch (\Exception $e) {
            $this->error("   ❌ Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if database is already setup
     */
    private function isDatabaseSetup(): bool
    {
        try {
            return Schema::hasTable('users') && 
                   Schema::hasTable('solicitudes_medicas') && 
                   DB::table('users')->count() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create necessary directories
     */
    private function createDirectories(): void
    {
        $directories = [
            storage_path('app/backups'),
            storage_path('app/exports'),
            storage_path('app/imports'),
            storage_path('app/temp'),
            storage_path('logs'),
            public_path('uploads'),
            public_path('exports'),
        ];

        foreach ($directories as $directory) {
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
        }
    }

    /**
     * Set proper permissions (Linux/Mac only)
     */
    private function setPermissions(): void
    {
        $paths = [
            storage_path(),
            bootstrap_path('cache'),
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                chmod($path, 0755);
                // Recursively set permissions for subdirectories
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path)
                );
                
                foreach ($iterator as $file) {
                    if ($file->isDir()) {
                        chmod($file->getPathname(), 0755);
                    } else {
                        chmod($file->getPathname(), 0644);
                    }
                }
            }
        }
    }

    /**
     * Display login information
     */
    private function displayLoginInfo(): void
    {
        $this->info('🔐 Información de Acceso:');
        $this->table(
            ['Rol', 'Email', 'Contraseña'],
            [
                ['Administrador', 'admin@vitalred.com', 'admin123'],
                ['Médico Demo', 'medico@vitalred.com', 'medico123'],
            ]
        );
        
        $this->warn('⚠️  IMPORTANTE: Cambie estas contraseñas por defecto antes de usar en producción.');
    }

    /**
     * Display next steps
     */
    private function displayNextSteps(): void
    {
        $this->info('📝 Próximos Pasos:');
        $this->line('');
        $this->line('1. 🌐 Inicie el servidor de desarrollo:');
        $this->line('   php artisan serve');
        $this->line('');
        $this->line('2. 🔧 Configure las variables de entorno en .env:');
        $this->line('   - Configuración de Gmail (GMAIL_*)');
        $this->line('   - Configuración de IA (GEMINI_*)');
        $this->line('   - Configuración de email (MAIL_*)');
        $this->line('');
        $this->line('3. 🐍 Configure el servicio Python (opcional):');
        $this->line('   cd ia && python -m venv venv && source venv/bin/activate');
        $this->line('   pip install -r requirements.txt');
        $this->line('');
        $this->line('4. 🚀 Inicie los workers de cola (producción):');
        $this->line('   php artisan queue:work');
        $this->line('');
        $this->line('5. 📊 Acceda al sistema:');
        $this->line('   http://localhost:8000');
        $this->line('');
        $this->info('📚 Documentación completa disponible en: docs/README.md');
    }
}
