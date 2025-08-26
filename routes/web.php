<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    // Redirigir automáticamente al login para aplicación médica
    return redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Redirección basada en rol desde dashboard
    Route::get('dashboard', function () {
        $user = auth()->user();

        if ($user->role === 'administrador') {
            return app(App\Http\Controllers\Admin\DashboardController::class)->index(request());
        } else {
            // Médicos van directamente a Ingresar Registro
            return redirect()->route('medico.ingresar-registro');
        }
    })->name('dashboard');

    // Rutas para Administrador
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('usuarios', [App\Http\Controllers\Admin\UsuarioController::class, 'index'])->name('usuarios');
        Route::post('usuarios', [App\Http\Controllers\Admin\UsuarioController::class, 'store'])->name('usuarios.store');
        Route::put('usuarios/{usuario}', [App\Http\Controllers\Admin\UsuarioController::class, 'update'])->name('usuarios.update');
        Route::patch('usuarios/{usuario}/toggle-status', [App\Http\Controllers\Admin\UsuarioController::class, 'toggleStatus'])->name('usuarios.toggle-status');
        Route::delete('usuarios/{usuario}', [App\Http\Controllers\Admin\UsuarioController::class, 'destroy'])->name('usuarios.destroy');

        Route::get('supervision', function () {
            return Inertia::render('admin/supervision');
        })->name('supervision');

        // Rutas para dashboard de administrador
        Route::get('buscar-registros', [App\Http\Controllers\Admin\DashboardController::class, 'buscarRegistros'])->name('buscar-registros');
        Route::get('descargar-historia/{registro}', [App\Http\Controllers\Admin\DashboardController::class, 'descargarHistoria'])->name('descargar-historia');

        // Rutas para Gmail monitoring
        Route::get('gmail-monitor', function () {
            return Inertia::render('admin/gmail-monitor');
        })->name('gmail-monitor');

        Route::get('metricas', function () {
            return Inertia::render('admin/metricas');
        })->name('metricas');

        // Rutas adicionales de administración
        Route::get('config', [App\Http\Controllers\SystemConfigController::class, 'index'])->name('config');
        Route::get('reports', [App\Http\Controllers\ReportsController::class, 'index'])->name('reports');
        Route::get('reports/medical-requests', [App\Http\Controllers\ReportsController::class, 'medicalRequests'])->name('reports.medical-requests');
        Route::get('reports/performance', [App\Http\Controllers\ReportsController::class, 'performance'])->name('reports.performance');
        Route::get('reports/audit', [App\Http\Controllers\ReportsController::class, 'audit'])->name('reports.audit');
        Route::get('trends', [App\Http\Controllers\TrendsAnalysisController::class, 'index'])->name('trends');
    });

    // Rutas para Médico
    Route::middleware('medico')->prefix('medico')->name('medico.')->group(function () {
        Route::get('ingresar-registro', [App\Http\Controllers\Medico\MedicoController::class, 'ingresarRegistro'])->name('ingresar-registro');
        Route::post('ingresar-registro', [App\Http\Controllers\Medico\MedicoController::class, 'storeRegistro'])->name('ingresar-registro.store');
        Route::get('consulta-pacientes', [App\Http\Controllers\Medico\MedicoController::class, 'consultaPacientes'])->name('consulta-pacientes');
        Route::get('buscar-pacientes', [App\Http\Controllers\Medico\MedicoController::class, 'buscarPacientes'])->name('buscar-pacientes');
        Route::get('descargar-historia/{registro}', [App\Http\Controllers\Medico\MedicoController::class, 'descargarHistoria'])->name('descargar-historia');

        // Rutas para IA
        Route::post('ai/extract-patient-data', [App\Http\Controllers\AIController::class, 'extractPatientData'])->name('ai.extract-patient-data');
        Route::post('ai/test-text-extraction', [App\Http\Controllers\AIController::class, 'testTextExtraction'])->name('ai.test-text-extraction');
        Route::post('ai/test-gemini', [App\Http\Controllers\AIController::class, 'testGeminiAPI'])->name('ai.test-gemini');

        // Rutas para gestión de solicitudes médicas
        Route::get('bandeja-casos', function () {
            return Inertia::render('medico/bandeja-casos');
        })->name('bandeja-casos');

        Route::get('evaluar-solicitud/{id}', function ($id) {
            return Inertia::render('medico/evaluar-solicitud', ['solicitudId' => $id]);
        })->name('evaluar-solicitud');

        // Rutas adicionales para evaluación médica
        Route::post('evaluar-solicitud/{id}', [App\Http\Controllers\Medico\EvaluacionController::class, 'guardarEvaluacion'])->name('guardar-evaluacion');
        Route::get('mis-evaluaciones', [App\Http\Controllers\Medico\EvaluacionController::class, 'misEvaluaciones'])->name('mis-evaluaciones');
        Route::post('cancelar-evaluacion/{id}', [App\Http\Controllers\Medico\EvaluacionController::class, 'cancelarEvaluacion'])->name('cancelar-evaluacion');

        // Dashboard médico
        Route::get('dashboard', [App\Http\Controllers\Medico\DashboardController::class, 'index'])->name('dashboard');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
