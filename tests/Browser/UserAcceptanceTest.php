<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use App\Models\User;
use App\Models\SolicitudMedica;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class UserAcceptanceTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $admin;
    protected $medico;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'role' => 'administrador'
        ]);
        
        $this->medico = User::factory()->create([
            'name' => 'Dr. Test',
            'email' => 'medico@test.com',
            'role' => 'medico'
        ]);
    }

    /**
     * Test complete user workflow for medical evaluation
     */
    public function test_medical_user_can_complete_evaluation_workflow()
    {
        // Create test medical request
        $solicitud = SolicitudMedica::factory()->create([
            'paciente_nombre' => 'Juan Pérez',
            'especialidad_solicitada' => 'Cardiología',
            'prioridad_ia' => 'Alta',
            'estado' => 'pendiente_evaluacion',
            'diagnostico_principal' => 'Dolor torácico agudo'
        ]);

        $this->browse(function (Browser $browser) use ($solicitud) {
            $browser->loginAs($this->medico)
                    ->visit('/medico/bandeja-casos')
                    ->assertSee('Bandeja de Casos Médicos')
                    ->assertSee('Juan Pérez')
                    ->assertSee('Cardiología')
                    ->assertSee('Alta')
                    
                    // Click to evaluate case
                    ->click("button[onclick='evaluateCase({$solicitud->id})']")
                    ->waitForLocation("/medico/evaluar-solicitud/{$solicitud->id}")
                    ->assertSee('Evaluación Médica')
                    ->assertSee('Juan Pérez')
                    ->assertSee('Dolor torácico agudo')
                    
                    // Fill evaluation form
                    ->select('decision_medica', 'aceptar')
                    ->waitFor('#acceptance-fields')
                    ->type('observaciones_medico', 'Caso aceptado para traslado urgente. Requiere atención inmediata.')
                    ->select('prioridad_medica', 'Alta')
                    ->type('fecha_programada', now()->addHours(2)->format('Y-m-d\TH:i'))
                    ->select('servicio_destino', 'urgencias')
                    
                    // Submit evaluation
                    ->click('#submit-btn')
                    ->waitForText('Evaluación guardada exitosamente')
                    ->assertPathIs('/medico/bandeja-casos');
        });

        // Verify evaluation was saved
        $solicitud->refresh();
        $this->assertEquals('evaluada', $solicitud->estado);
        $this->assertEquals('aceptar', $solicitud->decision_medica);
        $this->assertEquals($this->medico->id, $solicitud->medico_evaluador_id);
    }

    /**
     * Test admin dashboard functionality
     */
    public function test_admin_can_access_dashboard_and_view_metrics()
    {
        // Create test data
        SolicitudMedica::factory()->count(5)->create(['prioridad_ia' => 'Alta']);
        SolicitudMedica::factory()->count(10)->create(['prioridad_ia' => 'Media']);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/dashboard')
                    ->assertSee('Dashboard Administrativo')
                    ->assertSee('Total Solicitudes')
                    ->assertSee('Casos Urgentes Pendientes')
                    ->assertSee('Tasa de Aceptación')
                    ->assertSee('Estado del Sistema')
                    
                    // Check metrics cards are visible
                    ->assertVisible('.card.border-left-primary')
                    ->assertVisible('.card.border-left-danger')
                    ->assertVisible('.card.border-left-success')
                    ->assertVisible('.card.border-left-info')
                    
                    // Check charts are loaded
                    ->assertVisible('#dailyActivityChart')
                    ->assertVisible('#priorityChart')
                    
                    // Test refresh functionality
                    ->click('button[onclick="refreshDashboard()"]')
                    ->pause(1000)
                    
                    // Test navigation to reports
                    ->click('a[href*="reports"]')
                    ->waitForLocation('/admin/reports')
                    ->assertSee('Reportes del Sistema');
        });
    }

    /**
     * Test medical case filtering and search
     */
    public function test_medical_user_can_filter_and_search_cases()
    {
        // Create diverse test data
        SolicitudMedica::factory()->create([
            'paciente_nombre' => 'María García',
            'especialidad_solicitada' => 'Neurología',
            'prioridad_ia' => 'Alta',
            'estado' => 'pendiente_evaluacion'
        ]);

        SolicitudMedica::factory()->create([
            'paciente_nombre' => 'Carlos López',
            'especialidad_solicitada' => 'Cardiología',
            'prioridad_ia' => 'Media',
            'estado' => 'evaluada'
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->medico)
                    ->visit('/medico/bandeja-casos')
                    ->assertSee('Bandeja de Casos Médicos')
                    
                    // Test filter panel
                    ->click('button[onclick="showFilters()"]')
                    ->waitFor('#filters-panel')
                    ->assertVisible('#filters-panel')
                    
                    // Filter by priority
                    ->select('#filter-priority', 'Alta')
                    ->click('button[onclick="applyFilters()"]')
                    ->waitForReload()
                    ->assertSee('María García')
                    ->assertDontSee('Carlos López')
                    
                    // Clear filters
                    ->click('button[onclick="showFilters()"]')
                    ->waitFor('#filters-panel')
                    ->click('button[onclick="clearFilters()"]')
                    ->waitForReload()
                    ->assertSee('María García')
                    ->assertSee('Carlos López')
                    
                    // Test specialty filter
                    ->click('button[onclick="showFilters()"]')
                    ->waitFor('#filters-panel')
                    ->select('#filter-specialty', 'Neurología')
                    ->click('button[onclick="applyFilters()"]')
                    ->waitForReload()
                    ->assertSee('María García')
                    ->assertDontSee('Carlos López');
        });
    }

    /**
     * Test case details modal
     */
    public function test_user_can_view_case_details_in_modal()
    {
        $solicitud = SolicitudMedica::factory()->create([
            'paciente_nombre' => 'Ana Martínez',
            'paciente_apellidos' => 'Rodríguez',
            'paciente_edad' => 45,
            'especialidad_solicitada' => 'Cardiología',
            'diagnostico_principal' => 'Hipertensión arterial',
            'motivo_consulta' => 'Control de presión arterial'
        ]);

        $this->browse(function (Browser $browser) use ($solicitud) {
            $browser->loginAs($this->medico)
                    ->visit('/medico/bandeja-casos')
                    ->assertSee('Ana Martínez')
                    
                    // Click view details button
                    ->click("button[onclick='viewCase({$solicitud->id})']")
                    ->waitFor('#caseDetailsModal')
                    ->assertVisible('#caseDetailsModal')
                    ->assertSee('Detalles del Caso')
                    ->assertSee('Ana Martínez Rodríguez')
                    ->assertSee('45')
                    ->assertSee('Cardiología')
                    ->assertSee('Hipertensión arterial')
                    ->assertSee('Control de presión arterial')
                    
                    // Close modal
                    ->click('.modal-header .close')
                    ->waitUntilMissing('#caseDetailsModal');
        });
    }

    /**
     * Test urgent case notifications
     */
    public function test_urgent_case_notifications_appear()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->medico)
                    ->visit('/medico/bandeja-casos')
                    ->assertSee('Bandeja de Casos Médicos');

            // Simulate urgent case creation via JavaScript
            $browser->script([
                'showUrgentAlert({
                    patient_name: "Paciente Urgente",
                    specialty: "Cardiología",
                    institution: "Hospital Test"
                });'
            ]);

            $browser->waitFor('.urgent-alert')
                    ->assertSee('Caso Urgente Detectado!')
                    ->assertSee('Paciente Urgente')
                    ->assertSee('Cardiología');
        });
    }

    /**
     * Test user authentication and authorization
     */
    public function test_authentication_and_authorization_flow()
    {
        $this->browse(function (Browser $browser) {
            // Test unauthenticated access
            $browser->visit('/medico/bandeja-casos')
                    ->assertPathIs('/login')
                    ->assertSee('Iniciar Sesión');

            // Test medical user login
            $browser->type('email', $this->medico->email)
                    ->type('password', 'password')
                    ->click('button[type="submit"]')
                    ->waitForLocation('/medico/bandeja-casos')
                    ->assertSee('Bandeja de Casos Médicos');

            // Test access to admin area (should be denied)
            $browser->visit('/admin/dashboard')
                    ->assertSee('403')
                    ->orWhere(function ($browser) {
                        $browser->assertPathIs('/medico/bandeja-casos');
                    });

            // Logout
            $browser->click('.nav-link[onclick*="logout"]')
                    ->waitForLocation('/login');

            // Test admin login
            $browser->type('email', $this->admin->email)
                    ->type('password', 'password')
                    ->click('button[type="submit"]')
                    ->waitForLocation('/admin/dashboard')
                    ->assertSee('Dashboard Administrativo');
        });
    }

    /**
     * Test responsive design on mobile
     */
    public function test_responsive_design_mobile()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667) // iPhone 6/7/8 size
                    ->loginAs($this->medico)
                    ->visit('/medico/bandeja-casos')
                    ->assertSee('Bandeja de Casos Médicos')
                    
                    // Check mobile navigation
                    ->assertVisible('.navbar-toggler')
                    ->click('.navbar-toggler')
                    ->waitFor('.navbar-collapse.show')
                    
                    // Check table responsiveness
                    ->assertVisible('.table-responsive')
                    
                    // Check cards stack properly
                    ->visit('/admin/dashboard')
                    ->assertVisible('.card');
        });
    }

    /**
     * Test system configuration interface
     */
    public function test_admin_can_configure_system_settings()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/config')
                    ->assertSee('Configuración del Sistema')
                    
                    // Test Gmail configuration
                    ->click('a[href="#gmail-config"]')
                    ->waitFor('#gmail-config')
                    ->type('input[name="email"]', 'test@gmail.com')
                    ->type('input[name="app_password"]', 'test_password_1234567890')
                    ->click('button[onclick="updateGmailConfig()"]')
                    ->waitForText('Configuración actualizada')
                    
                    // Test AI configuration
                    ->click('a[href="#ai-config"]')
                    ->waitFor('#ai-config')
                    ->type('textarea[name="gemini_api_keys"]', 'test_key_1234567890123456789012345678901234567890')
                    ->click('button[onclick="updateAIConfig()"]')
                    ->waitForText('Configuración actualizada');
        });
    }

    /**
     * Test report generation and export
     */
    public function test_admin_can_generate_and_export_reports()
    {
        // Create test data
        SolicitudMedica::factory()->count(10)->create();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/reports')
                    ->assertSee('Reportes del Sistema')
                    
                    // Test medical requests report
                    ->click('a[href*="medical-requests"]')
                    ->waitForLocation('/admin/reports/medical-requests')
                    ->assertSee('Reporte de Solicitudes Médicas')
                    ->assertSee('Estadísticas')
                    ->assertSee('Por Especialidad')
                    
                    // Test export functionality
                    ->click('button[onclick="exportReport(\'pdf\')"]')
                    ->pause(2000) // Wait for download
                    
                    // Test performance report
                    ->visit('/admin/reports/performance')
                    ->assertSee('Reporte de Rendimiento')
                    ->assertSee('Tiempos de Procesamiento')
                    ->assertSee('Cumplimiento SLA');
        });
    }

    /**
     * Test error handling and user feedback
     */
    public function test_error_handling_and_user_feedback()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->medico)
                    ->visit('/medico/bandeja-casos')
                    
                    // Test invalid case access
                    ->visit('/medico/evaluar-solicitud/99999')
                    ->assertSee('404')
                    ->orWhere(function ($browser) {
                        $browser->assertSee('No encontrado');
                    });

            // Test form validation
            $solicitud = SolicitudMedica::factory()->create(['estado' => 'pendiente_evaluacion']);
            
            $browser->visit("/medico/evaluar-solicitud/{$solicitud->id}")
                    ->assertSee('Evaluación Médica')
                    
                    // Submit empty form
                    ->click('#submit-btn')
                    ->waitForText('Por favor seleccione una decisión médica')
                    
                    // Fill partial form
                    ->select('decision_medica', 'aceptar')
                    ->click('#submit-btn')
                    ->waitForText('Por favor ingrese sus observaciones médicas');
        });
    }

    /**
     * Test accessibility features
     */
    public function test_accessibility_features()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->medico)
                    ->visit('/medico/bandeja-casos')
                    
                    // Check for proper ARIA labels
                    ->assertAttribute('button[onclick*="viewCase"]', 'title', 'Ver Detalles')
                    ->assertAttribute('button[onclick*="evaluateCase"]', 'title', 'Evaluar')
                    
                    // Check for proper form labels
                    ->visit('/medico/evaluar-solicitud/' . SolicitudMedica::factory()->create(['estado' => 'pendiente_evaluacion'])->id)
                    ->assertVisible('label[for="decision_medica"]')
                    ->assertVisible('label[for="observaciones_medico"]')
                    
                    // Check keyboard navigation
                    ->keys('body', '{tab}')
                    ->assertFocused('input, button, select, textarea');
        });
    }

    /**
     * Test data persistence and consistency
     */
    public function test_data_persistence_and_consistency()
    {
        $solicitud = SolicitudMedica::factory()->create([
            'estado' => 'pendiente_evaluacion',
            'paciente_nombre' => 'Test Persistence'
        ]);

        $this->browse(function (Browser $browser) use ($solicitud) {
            $browser->loginAs($this->medico)
                    ->visit("/medico/evaluar-solicitud/{$solicitud->id}")
                    ->select('decision_medica', 'aceptar')
                    ->type('observaciones_medico', 'Test evaluation for persistence')
                    ->click('#submit-btn')
                    ->waitForLocation('/medico/bandeja-casos');

            // Verify data was saved by checking in another session
            $browser->visit('/logout')
                    ->waitForLocation('/login')
                    ->loginAs($this->admin)
                    ->visit('/admin/dashboard')
                    ->assertSee('Dashboard Administrativo');
        });

        // Verify in database
        $solicitud->refresh();
        $this->assertEquals('evaluada', $solicitud->estado);
        $this->assertEquals('aceptar', $solicitud->decision_medica);
        $this->assertEquals('Test evaluation for persistence', $solicitud->observaciones_medico);
    }
}
