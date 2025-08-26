<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Models\User;
use App\Models\SolicitudMedica;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class LoadTestSuite extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $medicos;
    protected $testStartTime;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testStartTime = microtime(true);
        
        // Create test users
        $this->admin = User::factory()->create(['role' => 'administrador']);
        $this->medicos = User::factory()->count(10)->create(['role' => 'medico']);
        
        // Seed test data
        $this->seedTestData();
    }

    protected function seedTestData(): void
    {
        // Create a large dataset for performance testing
        SolicitudMedica::factory()->count(1000)->create([
            'fecha_recepcion_email' => Carbon::now()->subDays(rand(1, 30))
        ]);
    }

    public function test_dashboard_load_performance()
    {
        $this->actingAs($this->admin);

        $startTime = microtime(true);
        
        // Test dashboard loading with large dataset
        $response = $this->get('/admin/dashboard');
        
        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        
        // Dashboard should load within 2 seconds
        $this->assertLessThan(2000, $loadTime, "Dashboard load time exceeded 2 seconds: {$loadTime}ms");
        
        $this->logPerformanceMetric('dashboard_load', $loadTime);
    }

    public function test_medical_requests_list_performance()
    {
        $this->actingAs($this->medicos->first());

        $startTime = microtime(true);
        
        // Test medical requests list with pagination
        $response = $this->get('/medico/bandeja-casos');
        
        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        
        // List should load within 1.5 seconds
        $this->assertLessThan(1500, $loadTime, "Medical requests list load time exceeded 1.5 seconds: {$loadTime}ms");
        
        $this->logPerformanceMetric('medical_requests_list', $loadTime);
    }

    public function test_api_endpoints_performance()
    {
        $this->actingAs($this->admin);

        $endpoints = [
            '/api/solicitudes-medicas' => 1000, // 1 second max
            '/api/metrics/dashboard' => 1500,   // 1.5 seconds max
            '/api/users' => 800,                // 0.8 seconds max
        ];

        foreach ($endpoints as $endpoint => $maxTime) {
            $startTime = microtime(true);
            
            $response = $this->getJson($endpoint);
            
            $endTime = microtime(true);
            $loadTime = ($endTime - $startTime) * 1000;

            $response->assertStatus(200);
            
            $this->assertLessThan($maxTime, $loadTime, 
                "API endpoint {$endpoint} exceeded {$maxTime}ms: {$loadTime}ms");
            
            $this->logPerformanceMetric("api_{$endpoint}", $loadTime);
        }
    }

    public function test_database_query_performance()
    {
        // Test complex queries performance
        $queries = [
            'urgent_cases_count' => function() {
                return SolicitudMedica::where('prioridad_ia', 'Alta')
                    ->where('estado', 'pendiente_evaluacion')
                    ->count();
            },
            'monthly_statistics' => function() {
                return SolicitudMedica::selectRaw('
                    DATE_FORMAT(fecha_recepcion_email, "%Y-%m") as month,
                    COUNT(*) as total,
                    SUM(CASE WHEN prioridad_ia = "Alta" THEN 1 ELSE 0 END) as urgent,
                    AVG(CASE WHEN fecha_evaluacion IS NOT NULL THEN 
                        TIMESTAMPDIFF(HOUR, fecha_recepcion_email, fecha_evaluacion) 
                        ELSE NULL END) as avg_response_time
                ')
                ->where('fecha_recepcion_email', '>=', Carbon::now()->subMonths(6))
                ->groupBy('month')
                ->get();
            },
            'specialty_analysis' => function() {
                return SolicitudMedica::select('especialidad_solicitada')
                    ->selectRaw('COUNT(*) as total')
                    ->selectRaw('AVG(CASE WHEN decision_medica = "aceptar" THEN 1 ELSE 0 END) * 100 as acceptance_rate')
                    ->groupBy('especialidad_solicitada')
                    ->orderByDesc('total')
                    ->get();
            }
        ];

        foreach ($queries as $queryName => $queryFunction) {
            $startTime = microtime(true);
            
            $result = $queryFunction();
            
            $endTime = microtime(true);
            $queryTime = ($endTime - $startTime) * 1000;

            // Database queries should complete within 500ms
            $this->assertLessThan(500, $queryTime, 
                "Database query {$queryName} exceeded 500ms: {$queryTime}ms");
            
            $this->logPerformanceMetric("db_query_{$queryName}", $queryTime);
        }
    }

    public function test_concurrent_user_simulation()
    {
        $concurrentUsers = 20;
        $requestsPerUser = 5;
        $totalRequests = $concurrentUsers * $requestsPerUser;
        
        $startTime = microtime(true);
        
        // Simulate concurrent requests
        $responses = [];
        for ($i = 0; $i < $totalRequests; $i++) {
            $user = $this->medicos->random();
            $this->actingAs($user);
            
            $requestStart = microtime(true);
            $response = $this->getJson('/api/solicitudes-medicas?limit=10');
            $requestEnd = microtime(true);
            
            $responses[] = [
                'status' => $response->getStatusCode(),
                'time' => ($requestEnd - $requestStart) * 1000
            ];
        }
        
        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        
        // Calculate statistics
        $successfulRequests = count(array_filter($responses, fn($r) => $r['status'] === 200));
        $averageResponseTime = array_sum(array_column($responses, 'time')) / count($responses);
        $maxResponseTime = max(array_column($responses, 'time'));
        
        // Assertions
        $this->assertEquals($totalRequests, $successfulRequests, 
            "Not all concurrent requests were successful");
        
        $this->assertLessThan(2000, $averageResponseTime, 
            "Average response time under load exceeded 2 seconds: {$averageResponseTime}ms");
        
        $this->assertLessThan(5000, $maxResponseTime, 
            "Maximum response time under load exceeded 5 seconds: {$maxResponseTime}ms");
        
        $this->logPerformanceMetric('concurrent_users_test', [
            'total_time' => $totalTime,
            'total_requests' => $totalRequests,
            'successful_requests' => $successfulRequests,
            'average_response_time' => $averageResponseTime,
            'max_response_time' => $maxResponseTime
        ]);
    }

    public function test_memory_usage_under_load()
    {
        $initialMemory = memory_get_usage(true);
        
        // Process large dataset
        $largeBatch = SolicitudMedica::with(['medicoEvaluador'])
            ->limit(500)
            ->get();
        
        // Perform operations on the dataset
        $processed = $largeBatch->map(function ($solicitud) {
            return [
                'id' => $solicitud->id,
                'patient' => $solicitud->paciente_nombre,
                'priority' => $solicitud->prioridad_ia,
                'waiting_time' => $solicitud->getWaitingTimeInHours(),
                'evaluator' => $solicitud->medicoEvaluador?->name
            ];
        });
        
        $peakMemory = memory_get_peak_usage(true);
        $memoryUsed = $peakMemory - $initialMemory;
        
        // Memory usage should not exceed 50MB for this operation
        $maxMemoryMB = 50 * 1024 * 1024; // 50MB in bytes
        $this->assertLessThan($maxMemoryMB, $memoryUsed, 
            "Memory usage exceeded 50MB: " . ($memoryUsed / 1024 / 1024) . "MB");
        
        $this->logPerformanceMetric('memory_usage_test', [
            'initial_memory_mb' => $initialMemory / 1024 / 1024,
            'peak_memory_mb' => $peakMemory / 1024 / 1024,
            'memory_used_mb' => $memoryUsed / 1024 / 1024,
            'records_processed' => $largeBatch->count()
        ]);
    }

    public function test_cache_performance()
    {
        $cacheKey = 'test_performance_cache';
        $largeData = range(1, 10000);
        
        // Test cache write performance
        $startTime = microtime(true);
        Cache::put($cacheKey, $largeData, 3600);
        $writeTime = (microtime(true) - $startTime) * 1000;
        
        // Test cache read performance
        $startTime = microtime(true);
        $cachedData = Cache::get($cacheKey);
        $readTime = (microtime(true) - $startTime) * 1000;
        
        // Cache operations should be fast
        $this->assertLessThan(100, $writeTime, "Cache write took too long: {$writeTime}ms");
        $this->assertLessThan(50, $readTime, "Cache read took too long: {$readTime}ms");
        $this->assertEquals($largeData, $cachedData, "Cached data integrity check failed");
        
        $this->logPerformanceMetric('cache_performance', [
            'write_time_ms' => $writeTime,
            'read_time_ms' => $readTime,
            'data_size' => count($largeData)
        ]);
        
        Cache::forget($cacheKey);
    }

    public function test_search_performance()
    {
        $this->actingAs($this->admin);
        
        $searchTerms = [
            'Juan',
            'Cardiología',
            'Alta',
            'pendiente'
        ];
        
        foreach ($searchTerms as $term) {
            $startTime = microtime(true);
            
            $response = $this->getJson("/api/solicitudes-medicas?search={$term}");
            
            $endTime = microtime(true);
            $searchTime = ($endTime - $startTime) * 1000;
            
            $response->assertStatus(200);
            
            // Search should complete within 800ms
            $this->assertLessThan(800, $searchTime, 
                "Search for '{$term}' exceeded 800ms: {$searchTime}ms");
            
            $this->logPerformanceMetric("search_performance_{$term}", $searchTime);
        }
    }

    public function test_report_generation_performance()
    {
        $this->actingAs($this->admin);
        
        $reportTypes = [
            'medical_requests' => '/api/reports/medical-requests',
            'performance' => '/api/reports/performance',
            'audit' => '/api/reports/audit'
        ];
        
        foreach ($reportTypes as $type => $endpoint) {
            $startTime = microtime(true);
            
            $response = $this->getJson($endpoint . '?period=1month&format=json');
            
            $endTime = microtime(true);
            $generationTime = ($endTime - $startTime) * 1000;
            
            $response->assertStatus(200);
            
            // Report generation should complete within 3 seconds
            $this->assertLessThan(3000, $generationTime, 
                "Report generation for {$type} exceeded 3 seconds: {$generationTime}ms");
            
            $this->logPerformanceMetric("report_generation_{$type}", $generationTime);
        }
    }

    public function test_ai_service_performance()
    {
        $medicalTexts = [
            "Paciente de 45 años con dolor torácico agudo",
            "Mujer de 30 años con cefalea intensa y náuseas",
            "Hombre de 60 años con disnea y edema en miembros inferiores",
            "Niño de 8 años con fiebre alta y dolor abdominal"
        ];
        
        foreach ($medicalTexts as $index => $text) {
            $startTime = microtime(true);
            
            // Simulate AI analysis (mock implementation)
            $result = $this->simulateAIAnalysis($text);
            
            $endTime = microtime(true);
            $analysisTime = ($endTime - $startTime) * 1000;
            
            // AI analysis should complete within 2 seconds
            $this->assertLessThan(2000, $analysisTime, 
                "AI analysis {$index} exceeded 2 seconds: {$analysisTime}ms");
            
            $this->assertIsArray($result);
            
            $this->logPerformanceMetric("ai_analysis_{$index}", $analysisTime);
        }
    }

    protected function simulateAIAnalysis(string $text): array
    {
        // Simulate processing time
        usleep(rand(100000, 500000)); // 0.1 to 0.5 seconds
        
        return [
            'patient_info' => ['age' => 45, 'gender' => 'M'],
            'symptoms' => ['dolor torácico'],
            'priority' => 'Alta',
            'confidence' => 0.85
        ];
    }

    protected function logPerformanceMetric(string $testName, $metric): void
    {
        $logData = [
            'test' => $testName,
            'timestamp' => now()->toISOString(),
            'metric' => $metric,
            'environment' => config('app.env'),
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit')
        ];
        
        // Log to file for analysis
        file_put_contents(
            storage_path('logs/performance_tests.log'),
            json_encode($logData) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }

    public function test_stress_test_evaluation_workflow()
    {
        $iterations = 50;
        $times = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            $medico = $this->medicos->random();
            $this->actingAs($medico);
            
            $solicitud = SolicitudMedica::factory()->create([
                'estado' => 'pendiente_evaluacion'
            ]);
            
            $startTime = microtime(true);
            
            $response = $this->post(route('medico.guardar-evaluacion', $solicitud->id), [
                'decision_medica' => 'aceptar',
                'observaciones_medico' => "Evaluation iteration {$i}",
                'prioridad_medica' => 'Media'
            ]);
            
            $endTime = microtime(true);
            $times[] = ($endTime - $startTime) * 1000;
            
            $response->assertRedirect();
        }
        
        $averageTime = array_sum($times) / count($times);
        $maxTime = max($times);
        $minTime = min($times);
        
        // Performance should be consistent
        $this->assertLessThan(1000, $averageTime, 
            "Average evaluation time exceeded 1 second: {$averageTime}ms");
        
        $this->assertLessThan(2000, $maxTime, 
            "Maximum evaluation time exceeded 2 seconds: {$maxTime}ms");
        
        $this->logPerformanceMetric('stress_test_evaluation', [
            'iterations' => $iterations,
            'average_time_ms' => $averageTime,
            'max_time_ms' => $maxTime,
            'min_time_ms' => $minTime,
            'standard_deviation' => $this->calculateStandardDeviation($times)
        ]);
    }

    protected function calculateStandardDeviation(array $values): float
    {
        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / count($values);
        
        return sqrt($variance);
    }

    protected function tearDown(): void
    {
        $testEndTime = microtime(true);
        $totalTestTime = ($testEndTime - $this->testStartTime) * 1000;
        
        $this->logPerformanceMetric('total_test_suite_time', $totalTestTime);
        
        parent::tearDown();
    }
}
