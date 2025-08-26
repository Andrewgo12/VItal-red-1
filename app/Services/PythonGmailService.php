<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use App\Models\SolicitudMedica;
use App\Models\AuditoriaSolicitud;
use Exception;

class PythonGmailService
{
    private string $pythonPath;
    private string $iaPath;
    private string $venvPath;
    
    public function __construct()
    {
        $this->pythonPath = config('app.python_path', 'python');
        $this->iaPath = base_path('ia');
        $this->venvPath = config('app.python_venv_path');
        
        // Use virtual environment if configured
        if ($this->venvPath && file_exists($this->venvPath)) {
            $this->pythonPath = $this->venvPath . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'python.exe';
        }
    }
    
    /**
     * Start continuous Gmail monitoring
     */
    public function startGmailMonitoring(): array
    {
        try {
            Log::info('Starting Gmail monitoring service');
            
            $command = [
                $this->pythonPath,
                $this->iaPath . DIRECTORY_SEPARATOR . 'gmail_monitor_service.py',
                'start'
            ];
            
            $result = Process::path($this->iaPath)
                ->timeout(60)
                ->run($command);
            
            if ($result->successful()) {
                Log::info('Gmail monitoring service started successfully');
                return [
                    'success' => true,
                    'message' => 'Gmail monitoring started',
                    'output' => $result->output()
                ];
            } else {
                Log::error('Failed to start Gmail monitoring: ' . $result->errorOutput());
                return [
                    'success' => false,
                    'message' => 'Failed to start Gmail monitoring',
                    'error' => $result->errorOutput()
                ];
            }
            
        } catch (Exception $e) {
            Log::error('Error starting Gmail monitoring: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error starting Gmail monitoring',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Stop Gmail monitoring
     */
    public function stopGmailMonitoring(): array
    {
        try {
            Log::info('Stopping Gmail monitoring service');
            
            $command = [
                $this->pythonPath,
                $this->iaPath . DIRECTORY_SEPARATOR . 'gmail_monitor_service.py',
                'stop'
            ];
            
            $result = Process::path($this->iaPath)
                ->timeout(30)
                ->run($command);
            
            return [
                'success' => true,
                'message' => 'Gmail monitoring stop signal sent',
                'output' => $result->output()
            ];
            
        } catch (Exception $e) {
            Log::error('Error stopping Gmail monitoring: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error stopping Gmail monitoring',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get Gmail monitoring status
     */
    public function getMonitoringStatus(): array
    {
        try {
            $command = [
                $this->pythonPath,
                $this->iaPath . DIRECTORY_SEPARATOR . 'gmail_monitor_service.py',
                'status'
            ];
            
            $result = Process::path($this->iaPath)
                ->timeout(10)
                ->run($command);
            
            if ($result->successful()) {
                $output = $result->output();
                
                // Try to parse JSON output
                if (str_contains($output, '{')) {
                    $jsonStart = strpos($output, '{');
                    $jsonOutput = substr($output, $jsonStart);
                    $status = json_decode($jsonOutput, true);
                    
                    if ($status) {
                        return [
                            'success' => true,
                            'status' => $status
                        ];
                    }
                }
                
                return [
                    'success' => true,
                    'status' => ['raw_output' => $output]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Service not running',
                    'error' => $result->errorOutput()
                ];
            }
            
        } catch (Exception $e) {
            Log::error('Error getting monitoring status: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error getting status',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process a single email manually
     */
    public function processSingleEmail(string $emailId): array
    {
        try {
            Log::info("Processing single email: {$emailId}");
            
            $command = [
                $this->pythonPath,
                $this->iaPath . DIRECTORY_SEPARATOR . 'process_single_email.py',
                '--email-id', $emailId
            ];
            
            $result = Process::path($this->iaPath)
                ->timeout(300) // 5 minutes timeout
                ->run($command);
            
            if ($result->successful()) {
                $output = $result->output();
                
                // Try to parse JSON output
                if (str_contains($output, '{')) {
                    $jsonStart = strpos($output, '{');
                    $jsonOutput = substr($output, $jsonStart);
                    $processedData = json_decode($jsonOutput, true);
                    
                    if ($processedData && isset($processedData['medical_case'])) {
                        // Create solicitud médica from processed data
                        $solicitud = $this->createSolicitudFromProcessedData($processedData['medical_case']);
                        
                        return [
                            'success' => true,
                            'message' => 'Email processed successfully',
                            'solicitud_id' => $solicitud->id,
                            'processed_data' => $processedData
                        ];
                    }
                }
                
                return [
                    'success' => true,
                    'message' => 'Email processed but no medical case detected',
                    'output' => $output
                ];
            } else {
                Log::error("Failed to process email {$emailId}: " . $result->errorOutput());
                return [
                    'success' => false,
                    'message' => 'Failed to process email',
                    'error' => $result->errorOutput()
                ];
            }
            
        } catch (Exception $e) {
            Log::error("Error processing email {$emailId}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error processing email',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Receive processed medical case from Python service
     */
    public function receiveMedicalCase(array $medicalCaseData): SolicitudMedica
    {
        try {
            Log::info('Receiving medical case from Python service');
            
            $solicitud = $this->createSolicitudFromProcessedData($medicalCaseData);
            
            // Register audit entry
            AuditoriaSolicitud::registrarAccion(
                $solicitud->id,
                'solicitud_recibida',
                'Solicitud médica recibida desde procesamiento automático de Gmail',
                null,
                $medicalCaseData
            );
            
            Log::info("Medical case created with ID: {$solicitud->id}");
            
            return $solicitud;
            
        } catch (Exception $e) {
            Log::error('Error receiving medical case: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create SolicitudMedica from processed Python data
     */
    private function createSolicitudFromProcessedData(array $data): SolicitudMedica
    {
        // Map Python data to Laravel model fields
        $solicitudData = [
            'email_unique_id' => $data['email_unique_id'] ?? uniqid('email_'),
            'email_message_id' => $data['email_message_id'] ?? null,
            
            // Información del remitente
            'institucion_remitente' => $data['institucion_remitente'] ?? 'No especificada',
            'medico_remitente' => $data['medico_remitente'] ?? null,
            'email_remitente' => $data['email_remitente'] ?? '',
            'telefono_remitente' => $data['telefono_remitente'] ?? null,
            
            // Información del paciente
            'paciente_nombre' => $data['paciente_nombre'] ?? 'Paciente no identificado',
            'paciente_apellidos' => $data['paciente_apellidos'] ?? null,
            'paciente_identificacion' => $data['paciente_identificacion'] ?? null,
            'paciente_tipo_id' => $data['paciente_tipo_id'] ?? null,
            'paciente_edad' => $data['paciente_edad'] ?? null,
            'paciente_sexo' => $data['paciente_sexo'] ?? null,
            'paciente_telefono' => $data['paciente_telefono'] ?? null,
            
            // Información clínica
            'diagnostico_principal' => $data['diagnostico_principal'] ?? 'Diagnóstico pendiente',
            'diagnosticos_secundarios' => $data['diagnosticos_secundarios'] ?? null,
            'motivo_consulta' => $data['motivo_consulta'] ?? 'No especificado',
            'enfermedad_actual' => $data['enfermedad_actual'] ?? null,
            'antecedentes_medicos' => $data['antecedentes_medicos'] ?? null,
            'medicamentos_actuales' => $data['medicamentos_actuales'] ?? null,
            
            // Signos vitales
            'frecuencia_cardiaca' => $data['frecuencia_cardiaca'] ?? null,
            'frecuencia_respiratoria' => $data['frecuencia_respiratoria'] ?? null,
            'temperatura' => $data['temperatura'] ?? null,
            'tension_sistolica' => $data['tension_sistolica'] ?? null,
            'tension_diastolica' => $data['tension_diastolica'] ?? null,
            'saturacion_oxigeno' => $data['saturacion_oxigeno'] ?? null,
            'escala_glasgow' => $data['escala_glasgow'] ?? null,
            
            // Información de la solicitud
            'especialidad_solicitada' => $data['especialidad_solicitada'] ?? 'No especificada',
            'tipo_solicitud' => $data['tipo_solicitud'] ?? 'consulta',
            'motivo_remision' => $data['motivo_remision'] ?? 'No especificado',
            'requerimiento_oxigeno' => $data['requerimiento_oxigeno'] ?? 'NO',
            'tipo_servicio' => $data['tipo_servicio'] ?? null,
            'observaciones_adicionales' => $data['observaciones_adicionales'] ?? null,
            
            // Clasificación automática por IA
            'prioridad_ia' => $data['prioridad'] ?? 'Media',
            'score_urgencia' => $data['score_urgencia'] ?? null,
            'criterios_priorizacion' => $data['criterios_priorizacion'] ?? null,
            
            // Estado inicial
            'estado' => 'recibida',
            
            // Archivos adjuntos
            'archivos_adjuntos' => $data['archivos_adjuntos'] ?? null,
            'texto_extraido' => $data['texto_extraido'] ?? null,
            
            // Metadatos del procesamiento
            'fecha_recepcion_email' => $data['fecha_recepcion_email'] ?? now(),
            'fecha_procesamiento_ia' => now(),
            'metadatos_procesamiento' => $data['metadatos_procesamiento'] ?? null,
        ];
        
        return SolicitudMedica::create($solicitudData);
    }
    
    /**
     * Test Python environment and dependencies
     */
    public function testPythonEnvironment(): array
    {
        try {
            $command = [
                $this->pythonPath,
                $this->iaPath . DIRECTORY_SEPARATOR . 'setup.py',
                'test'
            ];
            
            $result = Process::path($this->iaPath)
                ->timeout(60)
                ->run($command);
            
            return [
                'success' => $result->successful(),
                'output' => $result->output(),
                'error' => $result->errorOutput(),
                'python_path' => $this->pythonPath,
                'ia_path' => $this->iaPath
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'python_path' => $this->pythonPath,
                'ia_path' => $this->iaPath
            ];
        }
    }
    
    /**
     * Get processing statistics from Python service
     */
    public function getProcessingStatistics(): array
    {
        try {
            $command = [
                $this->pythonPath,
                $this->iaPath . DIRECTORY_SEPARATOR . 'admin_tools.py',
                'report',
                '--report-type', 'statistics'
            ];
            
            $result = Process::path($this->iaPath)
                ->timeout(30)
                ->run($command);
            
            if ($result->successful()) {
                $output = $result->output();
                
                // Try to parse JSON output
                if (str_contains($output, '{')) {
                    $jsonStart = strpos($output, '{');
                    $jsonOutput = substr($output, $jsonStart);
                    $statistics = json_decode($jsonOutput, true);
                    
                    if ($statistics) {
                        return [
                            'success' => true,
                            'statistics' => $statistics
                        ];
                    }
                }
                
                return [
                    'success' => true,
                    'raw_output' => $output
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $result->errorOutput()
                ];
            }
            
        } catch (Exception $e) {
            Log::error('Error getting processing statistics: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
