<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GmailMonitorController;
use App\Http\Controllers\SolicitudMedicaController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Gmail monitoring API routes
Route::prefix('gmail-monitor')->group(function () {
    Route::post('start', [GmailMonitorController::class, 'startMonitoring']);
    Route::post('stop', [GmailMonitorController::class, 'stopMonitoring']);
    Route::get('status', [GmailMonitorController::class, 'getStatus']);
    Route::post('process-email', [GmailMonitorController::class, 'processSingleEmail']);
    Route::get('statistics', [GmailMonitorController::class, 'getStatistics']);
    Route::get('test-environment', [GmailMonitorController::class, 'testEnvironment']);

    // Endpoints called by Python service
    Route::post('receive-medical-case', [GmailMonitorController::class, 'receiveMedicalCase']);
    Route::post('receive-urgent-notification', [GmailMonitorController::class, 'receiveUrgentNotification']);
});

// Authentication routes
Route::post('/auth/login', [App\Http\Controllers\AuthController::class, 'login']);
Route::post('/auth/logout', [App\Http\Controllers\AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/auth/refresh', [App\Http\Controllers\AuthController::class, 'refresh'])->middleware('auth:sanctum');

// Medical requests API routes
Route::prefix('solicitudes-medicas')->group(function () {
    Route::get('/', [SolicitudMedicaController::class, 'index']);
    Route::get('/{id}', [SolicitudMedicaController::class, 'show']);
    Route::post('/', [SolicitudMedicaController::class, 'store']);
    Route::put('/{id}', [SolicitudMedicaController::class, 'update']);
    Route::patch('/{id}/evaluar', [SolicitudMedicaController::class, 'evaluar']);
    Route::get('/pendientes/evaluacion', [SolicitudMedicaController::class, 'pendientesEvaluacion']);
    Route::get('/urgentes/lista', [SolicitudMedicaController::class, 'urgentes']);
    Route::get('/estadisticas', [SolicitudMedicaController::class, 'estadisticas']);
    Route::get('/especialidad/{especialidad}', [SolicitudMedicaController::class, 'porEspecialidad']);
});

// Protected API routes
Route::middleware('auth:sanctum')->group(function () {
    // Users management
    Route::apiResource('users', App\Http\Controllers\UserManagementController::class);

    // Metrics and reports
    Route::get('metrics/dashboard', [App\Http\Controllers\MetricsController::class, 'dashboard']);
    Route::get('metrics/detailed', [App\Http\Controllers\MetricsController::class, 'detailed']);
    Route::get('reports/medical-requests', [App\Http\Controllers\ReportsController::class, 'medicalRequestsApi']);
    Route::get('reports/performance', [App\Http\Controllers\ReportsController::class, 'performanceApi']);
    Route::get('reports/audit', [App\Http\Controllers\ReportsController::class, 'auditApi']);

    // Trends analysis
    Route::prefix('trends')->group(function () {
        Route::get('temporal', [App\Http\Controllers\TrendsAnalysisController::class, 'getTemporalTrends']);
        Route::get('specialty', [App\Http\Controllers\TrendsAnalysisController::class, 'getSpecialtyTrends']);
        Route::get('institution', [App\Http\Controllers\TrendsAnalysisController::class, 'getInstitutionTrends']);
        Route::get('priority', [App\Http\Controllers\TrendsAnalysisController::class, 'getPriorityTrends']);
        Route::get('performance', [App\Http\Controllers\TrendsAnalysisController::class, 'getPerformanceTrends']);
        Route::get('seasonal', [App\Http\Controllers\TrendsAnalysisController::class, 'getSeasonalPatterns']);
        Route::get('predictive', [App\Http\Controllers\TrendsAnalysisController::class, 'getPredictiveAnalysis']);
    });

    // System configuration
    Route::prefix('config')->group(function () {
        Route::get('status', [App\Http\Controllers\SystemConfigController::class, 'status']);
        Route::post('gmail', [App\Http\Controllers\SystemConfigController::class, 'updateGmailConfig']);
        Route::post('ai', [App\Http\Controllers\SystemConfigController::class, 'updateAIConfig']);
        Route::post('notifications', [App\Http\Controllers\SystemConfigController::class, 'updateNotificationsConfig']);
        Route::post('test-connections', [App\Http\Controllers\SystemConfigController::class, 'testConnections']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [App\Http\Controllers\NotificationController::class, 'index']);
        Route::put('{notification}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead']);
        Route::post('send', [App\Http\Controllers\NotificationController::class, 'send']);
    });

    // System backup
    Route::prefix('system')->group(function () {
        Route::post('backup', [App\Http\Controllers\BackupController::class, 'create']);
        Route::get('backups', [App\Http\Controllers\BackupController::class, 'list']);
        Route::delete('backups/{backup}', [App\Http\Controllers\BackupController::class, 'delete']);
    });
});
