<?php

namespace App\Jobs;

use App\Models\SolicitudMedica;
use App\Services\GeminiAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessGmailEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $emailData;
    protected $maxTries = 3;
    protected $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(array $emailData)
    {
        $this->emailData = $emailData;
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(GeminiAIService $aiService): void
    {
        try {
            Log::info('Processing Gmail email', ['email_id' => $this->emailData['id']]);

            // Extract medical information from email
            $medicalData = $this->extractMedicalData();

            // Analyze with AI if enabled
            $aiAnalysis = null;
            if (config('services.gemini.enabled')) {
                $aiAnalysis = $aiService->analyzeMedicalCase($medicalData);
            }

            // Create medical request
            $solicitud = $this->createMedicalRequest($medicalData, $aiAnalysis);

            // Dispatch notification job if urgent
            if ($solicitud->prioridad_ia === 'Alta') {
                SendUrgentCaseNotificationJob::dispatch($solicitud);
            }

            Log::info('Gmail email processed successfully', [
                'email_id' => $this->emailData['id'],
                'solicitud_id' => $solicitud->id
            ]);

        } catch (Exception $e) {
            Log::error('Failed to process Gmail email', [
                'email_id' => $this->emailData['id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Extract medical data from email
     */
    private function extractMedicalData(): array
    {
        $subject = $this->emailData['subject'] ?? '';
        $body = $this->emailData['body'] ?? '';
        $from = $this->emailData['from'] ?? '';

        // Basic extraction logic - can be enhanced with NLP
        $data = [
            'email_id' => $this->emailData['id'],
            'fecha_recepcion_email' => now(),
            'email_remitente' => $from,
            'asunto_email' => $subject,
            'contenido_email' => $body,
        ];

        // Extract patient information
        $data = array_merge($data, $this->extractPatientInfo($body));

        // Extract medical information
        $data = array_merge($data, $this->extractMedicalInfo($body));

        // Extract institution information
        $data = array_merge($data, $this->extractInstitutionInfo($from, $body));

        return $data;
    }

    /**
     * Extract patient information from email body
     */
    private function extractPatientInfo(string $body): array
    {
        $data = [];

        // Extract patient name
        if (preg_match('/(?:paciente|nombre):\s*([^\n\r]+)/i', $body, $matches)) {
            $fullName = trim($matches[1]);
            $nameParts = explode(' ', $fullName);
            $data['paciente_nombre'] = $nameParts[0] ?? '';
            $data['paciente_apellidos'] = implode(' ', array_slice($nameParts, 1));
        }

        // Extract age
        if (preg_match('/(?:edad|años?):\s*(\d+)/i', $body, $matches)) {
            $data['paciente_edad'] = (int)$matches[1];
        }

        // Extract gender
        if (preg_match('/(?:sexo|género):\s*(masculino|femenino|m|f)/i', $body, $matches)) {
            $gender = strtolower($matches[1]);
            $data['paciente_sexo'] = in_array($gender, ['masculino', 'm']) ? 'Masculino' : 'Femenino';
        }

        // Extract identification
        if (preg_match('/(?:cédula|identificación|cc|id):\s*([^\n\r]+)/i', $body, $matches)) {
            $data['paciente_identificacion'] = trim($matches[1]);
        }

        return $data;
    }

    /**
     * Extract medical information from email body
     */
    private function extractMedicalInfo(string $body): array
    {
        $data = [];

        // Extract diagnosis
        if (preg_match('/(?:diagnóstico|dx):\s*([^\n\r]+)/i', $body, $matches)) {
            $data['diagnostico_principal'] = trim($matches[1]);
        }

        // Extract reason for consultation
        if (preg_match('/(?:motivo|consulta|mc):\s*([^\n\r]+)/i', $body, $matches)) {
            $data['motivo_consulta'] = trim($matches[1]);
        }

        // Extract medical history
        if (preg_match('/(?:antecedentes|historia):\s*([^\n\r]+)/i', $body, $matches)) {
            $data['antecedentes_medicos'] = trim($matches[1]);
        }

        // Extract current medications
        if (preg_match('/(?:medicamentos|fármacos):\s*([^\n\r]+)/i', $body, $matches)) {
            $data['medicamentos_actuales'] = trim($matches[1]);
        }

        // Extract requested specialty
        $specialties = [
            'cardiología', 'neurología', 'pediatría', 'ginecología', 'urología',
            'medicina interna', 'cirugía', 'ortopedia', 'dermatología', 'psiquiatría'
        ];

        foreach ($specialties as $specialty) {
            if (stripos($body, $specialty) !== false) {
                $data['especialidad_solicitada'] = ucfirst($specialty);
                break;
            }
        }

        // Default values
        $data['diagnostico_principal'] = $data['diagnostico_principal'] ?? 'Diagnóstico no especificado';
        $data['especialidad_solicitada'] = $data['especialidad_solicitada'] ?? 'Medicina General';

        return $data;
    }

    /**
     * Extract institution information
     */
    private function extractInstitutionInfo(string $from, string $body): array
    {
        $data = [];

        // Extract institution from email domain
        if (preg_match('/@([^.]+)/', $from, $matches)) {
            $domain = $matches[1];
            $data['institucion_remitente'] = ucfirst($domain);
        }

        // Extract referring doctor
        if (preg_match('/(?:dr|dra|doctor|doctora)\.?\s*([^\n\r]+)/i', $body, $matches)) {
            $data['medico_remitente'] = trim($matches[1]);
        }

        // Extract phone
        if (preg_match('/(?:teléfono|tel|celular):\s*([^\n\r]+)/i', $body, $matches)) {
            $data['telefono_remitente'] = trim($matches[1]);
        }

        // Default values
        $data['institucion_remitente'] = $data['institucion_remitente'] ?? 'Institución no identificada';

        return $data;
    }

    /**
     * Create medical request from extracted data
     */
    private function createMedicalRequest(array $medicalData, ?array $aiAnalysis): SolicitudMedica
    {
        $data = array_merge($medicalData, [
            'estado' => 'pendiente_evaluacion',
            'prioridad_ia' => $aiAnalysis['prioridad'] ?? 'Media',
            'score_urgencia' => $aiAnalysis['score_urgencia'] ?? 50,
            'analisis_ia' => $aiAnalysis,
        ]);

        return SolicitudMedica::create($data);
    }

    /**
     * Handle job failure
     */
    public function failed(Exception $exception): void
    {
        Log::error('ProcessGmailEmailJob failed permanently', [
            'email_id' => $this->emailData['id'],
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Could send notification to administrators about failed processing
    }
}
