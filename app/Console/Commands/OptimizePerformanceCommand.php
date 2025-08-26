<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use App\Models\SolicitudMedica;
use App\Models\User;

class OptimizePerformanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'system:optimize-performance 
                            {--analyze : Analyze current performance}
                            {--optimize : Apply optimizations}
                            {--cache : Optimize cache}
                            {--database : Optimize database}
                            {--all : Apply all optimizations}';

    /**
     * The console command description.
     */
    protected $description = 'Optimize system performance for production environment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando optimización de rendimiento del Sistema Vital Red...');
        
        if ($this->option('analyze') || $this->option('all')) {
            $this->analyzePerformance();
        }
        
        if ($this->option('cache') || $this->option('all')) {
            $this->optimizeCache();
        }
        
        if ($this->option('database') || $this->option('all')) {
            $this->optimizeDatabase();
        }
        
        if ($this->option('optimize') || $this->option('all')) {
            $this->applyGeneralOptimizations();
        }
        
        $this->info('✅ Optimización de rendimiento completada');
        
        return 0;
    }
    
    /**
     * Analyze current system performance
     */
    private function analyzePerformance(): void
    {
        $this->info('📊 Analizando rendimiento actual del sistema...');
        
        // Database performance analysis
        $this->analyzeDatabase();
        
        // Cache performance analysis
        $this->analyzeCache();
        
        // Query performance analysis
        $this->analyzeQueries();
        
        // Memory usage analysis
        $this->analyzeMemoryUsage();
        
        $this->info('✅ Análisis de rendimiento completado');
    }
    
    /**
     * Analyze database performance
     */
    private function analyzeDatabase(): void
    {
        $this->line('🗄️ Análisis de Base de Datos:');
        
        // Check slow queries
        $slowQueries = DB::select("
            SELECT query_time, sql_text 
            FROM mysql.slow_log 
            WHERE start_time >= DATE_SUB(NOW(), INTERVAL 1 DAY)
            ORDER BY query_time DESC 
            LIMIT 10
        ");
        
        if (count($slowQueries) > 0) {
            $this->warn("  ⚠️ Se encontraron " . count($slowQueries) . " consultas lentas");
            foreach ($slowQueries as $query) {
                $this->line("    - Tiempo: {$query->query_time}s");
            }
        } else {
            $this->info("  ✅ No se encontraron consultas lentas");
        }
        
        // Check table sizes
        $tableSizes = DB::select("
            SELECT 
                table_name,
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                table_rows
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
            ORDER BY (data_length + index_length) DESC
        ");
        
        $this->line("  📋 Tamaños de tablas principales:");
        foreach (array_slice($tableSizes, 0, 5) as $table) {
            $this->line("    - {$table->table_name}: {$table->size_mb} MB ({$table->table_rows} filas)");
        }
        
        // Check index usage
        $this->checkIndexUsage();
    }
    
    /**
     * Check index usage
     */
    private function checkIndexUsage(): void
    {
        $unusedIndexes = DB::select("
            SELECT 
                t.table_schema,
                t.table_name,
                s.index_name,
                s.cardinality
            FROM information_schema.tables t
            LEFT JOIN information_schema.statistics s ON t.table_name = s.table_name
            WHERE t.table_schema = DATABASE()
            AND s.index_name IS NOT NULL
            AND s.index_name != 'PRIMARY'
            AND s.cardinality < 100
        ");
        
        if (count($unusedIndexes) > 0) {
            $this->warn("  ⚠️ Índices con baja cardinalidad encontrados:");
            foreach ($unusedIndexes as $index) {
                $this->line("    - {$index->table_name}.{$index->index_name} (cardinalidad: {$index->cardinality})");
            }
        }
    }
    
    /**
     * Analyze cache performance
     */
    private function analyzeCache(): void
    {
        $this->line('💾 Análisis de Cache:');
        
        try {
            // Test cache write performance
            $startTime = microtime(true);
            Cache::put('performance_test', 'test_data', 60);
            $writeTime = (microtime(true) - $startTime) * 1000;
            
            // Test cache read performance
            $startTime = microtime(true);
            $data = Cache::get('performance_test');
            $readTime = (microtime(true) - $startTime) * 1000;
            
            Cache::forget('performance_test');
            
            $this->info("  ✅ Cache Write: {$writeTime}ms");
            $this->info("  ✅ Cache Read: {$readTime}ms");
            
            if ($writeTime > 50 || $readTime > 10) {
                $this->warn("  ⚠️ Rendimiento de cache subóptimo");
            }
            
        } catch (\Exception $e) {
            $this->error("  ❌ Error en cache: " . $e->getMessage());
        }
    }
    
    /**
     * Analyze query performance
     */
    private function analyzeQueries(): void
    {
        $this->line('🔍 Análisis de Consultas Críticas:');
        
        $criticalQueries = [
            'Solicitudes pendientes' => function() {
                return SolicitudMedica::where('estado', 'pendiente_evaluacion')->count();
            },
            'Casos urgentes' => function() {
                return SolicitudMedica::where('prioridad_ia', 'Alta')
                    ->where('estado', 'pendiente_evaluacion')->count();
            },
            'Usuarios activos' => function() {
                return User::where('is_active', true)->count();
            }
        ];
        
        foreach ($criticalQueries as $name => $query) {
            $startTime = microtime(true);
            $result = $query();
            $queryTime = (microtime(true) - $startTime) * 1000;
            
            $status = $queryTime < 100 ? '✅' : ($queryTime < 500 ? '⚠️' : '❌');
            $this->line("  {$status} {$name}: {$queryTime}ms (resultado: {$result})");
        }
    }
    
    /**
     * Analyze memory usage
     */
    private function analyzeMemoryUsage(): void
    {
        $this->line('🧠 Análisis de Memoria:');
        
        $memoryUsage = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        $memoryLimit = ini_get('memory_limit');
        
        $this->info("  📊 Uso actual: " . $this->formatBytes($memoryUsage));
        $this->info("  📈 Pico de uso: " . $this->formatBytes($peakMemory));
        $this->info("  🔒 Límite configurado: {$memoryLimit}");
        
        $usagePercent = ($peakMemory / $this->parseMemoryLimit($memoryLimit)) * 100;
        if ($usagePercent > 80) {
            $this->warn("  ⚠️ Alto uso de memoria: {$usagePercent}%");
        }
    }
    
    /**
     * Optimize cache configuration
     */
    private function optimizeCache(): void
    {
        $this->info('💾 Optimizando configuración de cache...');
        
        // Clear all caches
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        
        $this->line('  ✅ Cache limpiado');
        
        // Warm up critical caches
        $this->warmUpCache();
        
        // Optimize cache configuration
        $this->optimizeCacheConfig();
        
        $this->info('✅ Optimización de cache completada');
    }
    
    /**
     * Warm up critical caches
     */
    private function warmUpCache(): void
    {
        $this->line('🔥 Precalentando caches críticos...');
        
        // Cache user counts
        Cache::remember('users_count', 3600, function() {
            return User::count();
        });
        
        // Cache active users
        Cache::remember('active_users_count', 3600, function() {
            return User::where('is_active', true)->count();
        });
        
        // Cache pending requests
        Cache::remember('pending_requests_count', 300, function() {
            return SolicitudMedica::where('estado', 'pendiente_evaluacion')->count();
        });
        
        // Cache urgent cases
        Cache::remember('urgent_cases_count', 300, function() {
            return SolicitudMedica::where('prioridad_ia', 'Alta')
                ->where('estado', 'pendiente_evaluacion')->count();
        });
        
        // Cache specialties
        Cache::remember('specialties_list', 3600, function() {
            return SolicitudMedica::distinct()->pluck('especialidad_solicitada')->filter();
        });
        
        $this->line('  ✅ Caches críticos precalentados');
    }
    
    /**
     * Optimize cache configuration
     */
    private function optimizeCacheConfig(): void
    {
        $this->line('⚙️ Optimizando configuración de cache...');
        
        // Set optimal cache tags
        $cacheConfig = [
            'dashboard_metrics' => 300,      // 5 minutes
            'user_permissions' => 3600,     // 1 hour
            'system_config' => 7200,        // 2 hours
            'medical_specialties' => 86400, // 24 hours
        ];
        
        foreach ($cacheConfig as $key => $ttl) {
            Cache::put("config_{$key}_ttl", $ttl, 86400);
        }
        
        $this->line('  ✅ Configuración de cache optimizada');
    }
    
    /**
     * Optimize database performance
     */
    private function optimizeDatabase(): void
    {
        $this->info('🗄️ Optimizando base de datos...');
        
        // Optimize tables
        $this->optimizeTables();
        
        // Update table statistics
        $this->updateTableStatistics();
        
        // Create missing indexes
        $this->createOptimalIndexes();
        
        // Clean old data
        $this->cleanOldData();
        
        $this->info('✅ Optimización de base de datos completada');
    }
    
    /**
     * Optimize database tables
     */
    private function optimizeTables(): void
    {
        $this->line('🔧 Optimizando tablas...');
        
        $tables = [
            'solicitudes_medicas',
            'users',
            'audit_logs',
            'notificaciones_internas'
        ];
        
        foreach ($tables as $table) {
            try {
                DB::statement("OPTIMIZE TABLE {$table}");
                $this->line("  ✅ Tabla {$table} optimizada");
            } catch (\Exception $e) {
                $this->warn("  ⚠️ Error optimizando {$table}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Update table statistics
     */
    private function updateTableStatistics(): void
    {
        $this->line('📊 Actualizando estadísticas de tablas...');
        
        try {
            DB::statement("ANALYZE TABLE solicitudes_medicas, users, audit_logs, notificaciones_internas");
            $this->line('  ✅ Estadísticas actualizadas');
        } catch (\Exception $e) {
            $this->warn('  ⚠️ Error actualizando estadísticas: ' . $e->getMessage());
        }
    }
    
    /**
     * Create optimal indexes
     */
    private function createOptimalIndexes(): void
    {
        $this->line('📇 Creando índices optimizados...');
        
        $indexes = [
            'solicitudes_medicas' => [
                'idx_estado_prioridad_fecha' => 'estado, prioridad_ia, fecha_recepcion_email',
                'idx_especialidad_estado' => 'especialidad_solicitada, estado',
                'idx_medico_fecha_eval' => 'medico_evaluador_id, fecha_evaluacion'
            ],
            'audit_logs' => [
                'idx_user_action_timestamp' => 'user_id, action, timestamp',
                'idx_resource_timestamp' => 'resource_type, resource_id, timestamp'
            ],
            'notificaciones_internas' => [
                'idx_estado_tipo_fecha' => 'estado, tipo, created_at'
            ]
        ];
        
        foreach ($indexes as $table => $tableIndexes) {
            foreach ($tableIndexes as $indexName => $columns) {
                try {
                    // Check if index exists
                    $exists = DB::select("
                        SELECT COUNT(*) as count 
                        FROM information_schema.statistics 
                        WHERE table_schema = DATABASE() 
                        AND table_name = ? 
                        AND index_name = ?
                    ", [$table, $indexName]);
                    
                    if ($exists[0]->count == 0) {
                        DB::statement("CREATE INDEX {$indexName} ON {$table} ({$columns})");
                        $this->line("  ✅ Índice {$indexName} creado en {$table}");
                    } else {
                        $this->line("  ℹ️ Índice {$indexName} ya existe en {$table}");
                    }
                } catch (\Exception $e) {
                    $this->warn("  ⚠️ Error creando índice {$indexName}: " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Clean old data
     */
    private function cleanOldData(): void
    {
        $this->line('🧹 Limpiando datos antiguos...');
        
        // Clean old audit logs (older than 6 months)
        $deletedAuditLogs = DB::table('audit_logs')
            ->where('timestamp', '<', now()->subMonths(6))
            ->delete();
        
        if ($deletedAuditLogs > 0) {
            $this->line("  ✅ Eliminados {$deletedAuditLogs} logs de auditoría antiguos");
        }
        
        // Clean old notifications (older than 3 months)
        $deletedNotifications = DB::table('notificaciones_internas')
            ->where('created_at', '<', now()->subMonths(3))
            ->where('estado', 'enviada')
            ->delete();
        
        if ($deletedNotifications > 0) {
            $this->line("  ✅ Eliminadas {$deletedNotifications} notificaciones antiguas");
        }
        
        // Clean temporary files
        $this->cleanTemporaryFiles();
    }
    
    /**
     * Clean temporary files
     */
    private function cleanTemporaryFiles(): void
    {
        $tempDirs = [
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs')
        ];
        
        foreach ($tempDirs as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '/*');
                $cleaned = 0;
                
                foreach ($files as $file) {
                    if (is_file($file) && filemtime($file) < strtotime('-7 days')) {
                        unlink($file);
                        $cleaned++;
                    }
                }
                
                if ($cleaned > 0) {
                    $this->line("  ✅ Limpiados {$cleaned} archivos temporales de " . basename($dir));
                }
            }
        }
    }
    
    /**
     * Apply general optimizations
     */
    private function applyGeneralOptimizations(): void
    {
        $this->info('⚡ Aplicando optimizaciones generales...');
        
        // Optimize Laravel configuration
        $this->optimizeLaravelConfig();
        
        // Optimize Composer autoloader
        $this->optimizeComposer();
        
        // Generate optimized class map
        $this->generateOptimizedClassMap();
        
        $this->info('✅ Optimizaciones generales aplicadas');
    }
    
    /**
     * Optimize Laravel configuration
     */
    private function optimizeLaravelConfig(): void
    {
        $this->line('⚙️ Optimizando configuración de Laravel...');
        
        // Cache configuration
        Artisan::call('config:cache');
        $this->line('  ✅ Configuración cacheada');
        
        // Cache routes
        Artisan::call('route:cache');
        $this->line('  ✅ Rutas cacheadas');
        
        // Cache views
        Artisan::call('view:cache');
        $this->line('  ✅ Vistas cacheadas');
        
        // Cache events
        Artisan::call('event:cache');
        $this->line('  ✅ Eventos cacheados');
    }
    
    /**
     * Optimize Composer autoloader
     */
    private function optimizeComposer(): void
    {
        $this->line('📦 Optimizando autoloader de Composer...');
        
        try {
            exec('composer dump-autoload --optimize --no-dev', $output, $returnCode);
            
            if ($returnCode === 0) {
                $this->line('  ✅ Autoloader optimizado');
            } else {
                $this->warn('  ⚠️ Error optimizando autoloader');
            }
        } catch (\Exception $e) {
            $this->warn('  ⚠️ Error ejecutando composer: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate optimized class map
     */
    private function generateOptimizedClassMap(): void
    {
        $this->line('🗺️ Generando mapa de clases optimizado...');
        
        try {
            Artisan::call('optimize');
            $this->line('  ✅ Mapa de clases generado');
        } catch (\Exception $e) {
            $this->warn('  ⚠️ Error generando mapa de clases: ' . $e->getMessage());
        }
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit(string $memoryLimit): int
    {
        $unit = strtolower(substr($memoryLimit, -1));
        $value = (int) substr($memoryLimit, 0, -1);
        
        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return (int) $memoryLimit;
        }
    }
}
