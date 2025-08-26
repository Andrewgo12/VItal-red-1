<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class GeminiAIService
{
    private array $apiKeys;
    private int $currentKeyIndex = 0;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent';

    public function __construct()
    {
        $this->apiKeys = [
            config('app.gemini_api_key_1', env('GEMINI_API_KEY_1')),
            config('app.gemini_api_key_2', env('GEMINI_API_KEY_2')),
            config('app.gemini_api_key_3', env('GEMINI_API_KEY_3')),
            config('app.gemini_api_key_4', env('GEMINI_API_KEY_4')),
        ];
    }

    /**
     * Rotar entre las API keys disponibles
     */
    private function getNextApiKey(): string
    {
        $apiKey = $this->apiKeys[$this->currentKeyIndex];
        $this->currentKeyIndex = ($this->currentKeyIndex + 1) % count($this->apiKeys);
        return $apiKey;
    }

    /**
     * Extraer texto de un archivo (PDF, imagen, etc.)
     */
    public function extractTextFromFile(string $filePath): string
    {
        $fullPath = storage_path('app/public/' . $filePath);
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        Log::info("Intentando extraer texto de archivo: {$fullPath}, extensión: {$extension}");

        try {
            switch ($extension) {
                case 'pdf':
                    return $this->extractTextFromPdf($fullPath);

                case 'jpg':
                case 'jpeg':
                case 'png':
                case 'gif':
                case 'bmp':
                case 'tiff':
                    return $this->extractTextFromImage($fullPath);

                case 'doc':
                case 'docx':
                    return $this->extractTextFromWord($fullPath);

                default:
                    throw new \Exception("Tipo de archivo no soportado: {$extension}");
            }
        } catch (\Exception $e) {
            Log::error("Error extrayendo texto del archivo: " . $e->getMessage());
            throw new \Exception("No se pudo extraer el texto del archivo: " . $e->getMessage());
        }
    }

    /**
     * Extraer texto de PDF
     */
    private function extractTextFromPdf(string $filePath): string
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();

            if (empty(trim($text))) {
                throw new \Exception("El PDF no contiene texto extraíble o está protegido");
            }

            return $text;
        } catch (\Exception $e) {
            Log::error("Error extrayendo texto de PDF: " . $e->getMessage());
            throw new \Exception("No se pudo extraer el texto del PDF: " . $e->getMessage());
        }
    }



    /**
     * Extraer texto de imagen usando OCR (API online)
     */
    private function extractTextFromImage(string $filePath): string
    {
        try {
            // Usar OCR.space API (gratuita) para extraer texto de imágenes
            $response = Http::attach(
                'file', file_get_contents($filePath), basename($filePath)
            )->post('https://api.ocr.space/parse/image', [
                'apikey' => 'helloworld', // API key gratuita
                'language' => 'spa', // Español
                'isOverlayRequired' => false,
                'detectOrientation' => true,
                'scale' => true,
                'OCREngine' => 2
            ]);

            if ($response->successful()) {
                $result = $response->json();

                if (isset($result['ParsedResults'][0]['ParsedText'])) {
                    $text = $result['ParsedResults'][0]['ParsedText'];

                    if (empty(trim($text))) {
                        throw new \Exception("No se pudo extraer texto de la imagen");
                    }

                    return $text;
                } else {
                    throw new \Exception("Error en la respuesta del OCR: " . ($result['ErrorMessage'] ?? 'Error desconocido'));
                }
            } else {
                throw new \Exception("Error en la API de OCR: " . $response->status());
            }
        } catch (\Exception $e) {
            Log::error("Error en OCR de imagen: " . $e->getMessage());
            throw new \Exception("No se pudo extraer el texto de la imagen: " . $e->getMessage());
        }
    }

    /**
     * Extraer texto de documento Word (básico)
     */
    private function extractTextFromWord(string $filePath): string
    {
        // Para documentos Word necesitaríamos una librería adicional
        // Por ahora retornamos un mensaje indicativo
        throw new \Exception("Extracción de documentos Word no implementada aún. Use PDF o imágenes.");
    }

    /**
     * Analizar texto con Gemini AI y extraer datos del paciente
     */
    public function analyzePatientDocument(string $text): array
    {
        $prompt = $this->buildAnalysisPrompt($text);

        $maxRetries = count($this->apiKeys);
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                $apiKey = $this->getNextApiKey();
                $response = $this->callGeminiAPI($prompt, $apiKey);

                if ($response) {
                    return $this->parseGeminiResponse($response);
                }
            } catch (\Exception $e) {
                Log::warning("Error con API key {$attempt}: " . $e->getMessage());
                $attempt++;

                if ($attempt >= $maxRetries) {
                    throw new \Exception("Todas las API keys fallaron. Último error: " . $e->getMessage());
                }
            }
        }

        throw new \Exception("No se pudo analizar el documento con ninguna API key");
    }

    /**
     * Construir el prompt para análisis de documento médico
     */
    private function buildAnalysisPrompt(string $text): string
    {
        return "Analiza el siguiente documento médico y extrae ÚNICAMENTE la información del paciente que se solicita.
        Responde SOLO con un JSON válido sin texto adicional, comentarios o explicaciones.

        Texto del documento:
        {$text}

        Extrae y devuelve un JSON con esta estructura exacta:
        {
            \"tipo_identificacion\": \"cc|ti|ce|pp|rc\",
            \"numero_identificacion\": \"número sin puntos ni comas\",
            \"nombre\": \"solo nombres\",
            \"apellidos\": \"solo apellidos\",
            \"fecha_nacimiento\": \"YYYY-MM-DD\",
            \"edad\": \"número entero de años\",
            \"sexo\": \"masculino|femenino|otro\"
        }

        Reglas importantes:
        - Si no encuentras un dato, usa null
        - Para tipo_identificacion: cc=Cédula, ti=Tarjeta Identidad, ce=Cédula Extranjería, pp=Pasaporte, rc=Registro Civil
        - Para sexo: usa exactamente \"masculino\", \"femenino\" o \"otro\"
        - Para fecha_nacimiento: OBLIGATORIO formato YYYY-MM-DD (ejemplo: 1985-03-15)
        - Para edad: solo el número entero de años (ejemplo: 38)
        - Para numero_identificacion: solo números, sin puntos, comas o espacios
        - Busca fechas en formatos como: 15/03/1985, 15-03-1985, 15 de marzo de 1985, etc. y conviértelas a YYYY-MM-DD
        - Busca edad en formatos como: 38 años, 38 años de edad, edad: 38, etc.
        - Si encuentras fecha de nacimiento Y edad, usa ambos
        - Si solo encuentras uno de los dos, extrae el que encuentres
        - NO agregues texto explicativo, SOLO el JSON";
    }

    /**
     * Llamar a la API de Gemini
     */
    private function callGeminiAPI(string $prompt, string $apiKey): ?string
    {
        $response = Http::timeout(30)->post($this->baseUrl . '?key=' . $apiKey, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'topK' => 1,
                'topP' => 0.8,
                'maxOutputTokens' => 1024,
            ]
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        }

        throw new \Exception("Error en API de Gemini: " . $response->body());
    }

    /**
     * Parsear respuesta de Gemini y extraer JSON
     */
    private function parseGeminiResponse(string $response): array
    {
        // Limpiar la respuesta de posibles caracteres extra
        $response = trim($response);

        // Buscar JSON en la respuesta
        if (preg_match('/\{.*\}/s', $response, $matches)) {
            $jsonString = $matches[0];
            $data = json_decode($jsonString, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->validateAndCleanData($data);
            }
        }

        throw new \Exception("No se pudo parsear la respuesta de la IA como JSON válido");
    }

    /**
     * Validar y limpiar los datos extraídos
     */
    private function validateAndCleanData(array $data): array
    {
        $cleanData = [];

        // Validar tipo_identificacion
        $validTypes = ['cc', 'ti', 'ce', 'pp', 'rc'];
        $cleanData['tipo_identificacion'] = in_array($data['tipo_identificacion'] ?? '', $validTypes)
            ? $data['tipo_identificacion']
            : null;

        // Limpiar número de identificación
        $cleanData['numero_identificacion'] = isset($data['numero_identificacion'])
            ? preg_replace('/[^0-9]/', '', $data['numero_identificacion'])
            : null;

        // Limpiar nombres y apellidos
        $cleanData['nombre'] = isset($data['nombre']) ? trim($data['nombre']) : null;
        $cleanData['apellidos'] = isset($data['apellidos']) ? trim($data['apellidos']) : null;

        // Validar edad
        $cleanData['edad'] = null;
        if (isset($data['edad']) && is_numeric($data['edad'])) {
            $cleanData['edad'] = (int) $data['edad'];
            Log::info("Edad procesada: " . $cleanData['edad']);
        }

        // Validar fecha de nacimiento
        $cleanData['fecha_nacimiento'] = null;
        if (isset($data['fecha_nacimiento']) && $data['fecha_nacimiento']) {
            try {
                $date = new \DateTime($data['fecha_nacimiento']);
                $cleanData['fecha_nacimiento'] = $date->format('Y-m-d');
                Log::info("Fecha de nacimiento procesada: " . $data['fecha_nacimiento'] . " -> " . $cleanData['fecha_nacimiento']);
            } catch (\Exception $e) {
                Log::warning("Fecha de nacimiento inválida: " . $data['fecha_nacimiento'] . " - " . $e->getMessage());
                // Fecha inválida, mantener null
            }
        }

        // Cálculo inverso: si no hay fecha pero sí edad, calcular fecha aproximada
        if (!$cleanData['fecha_nacimiento'] && $cleanData['edad']) {
            $currentYear = date('Y');
            $birthYear = $currentYear - $cleanData['edad'];
            // Usar 1 de enero como fecha aproximada
            $cleanData['fecha_nacimiento'] = $birthYear . '-01-01';
            Log::info("Fecha de nacimiento calculada desde edad {$cleanData['edad']}: {$cleanData['fecha_nacimiento']}");
        }

        if (!$cleanData['fecha_nacimiento'] && !$cleanData['edad']) {
            Log::info("No se encontró fecha_nacimiento ni edad en los datos de IA");
        }

        // Validar sexo
        $validSexes = ['masculino', 'femenino', 'otro'];
        $cleanData['sexo'] = in_array($data['sexo'] ?? '', $validSexes)
            ? $data['sexo']
            : null;

        return $cleanData;
    }

    /**
     * Enhanced medical text analysis with Gemini AI
     */
    public function analyzeMedicalText(string $text): array
    {
        try {
            $prompt = $this->buildMedicalAnalysisPrompt($text);

            foreach ($this->apiKeys as $apiKey) {
                if (empty($apiKey)) continue;

                try {
                    $response = $this->callGeminiAPI($prompt, $apiKey);
                    if ($response) {
                        return $this->parseMedicalAnalysisResponse($response);
                    }
                } catch (\Exception $e) {
                    Log::warning("Error con API key, intentando siguiente: " . $e->getMessage());
                    continue;
                }
            }

            throw new \Exception("Todas las API keys fallaron");

        } catch (\Exception $e) {
            Log::error('Error en analyzeMedicalText: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Build comprehensive medical analysis prompt
     */
    private function buildMedicalAnalysisPrompt(string $text): string
    {
        return "Analiza el siguiente texto médico y extrae información clínica detallada en formato JSON.

INSTRUCCIONES ESPECÍFICAS:
- Extrae TODA la información médica disponible
- Si no encuentras un dato, usa null
- Sé preciso con los valores numéricos
- Identifica el nivel de urgencia del caso

FORMATO JSON REQUERIDO:
{
    \"patient_info\": {
        \"name\": \"string\",
        \"age\": number,
        \"gender\": \"masculino|femenino|otro\",
        \"identification\": \"string\",
        \"phone\": \"string\"
    },
    \"clinical_data\": {
        \"chief_complaint\": \"string\",
        \"current_illness\": \"string\",
        \"medical_history\": \"string\",
        \"primary_diagnosis\": \"string\",
        \"secondary_diagnoses\": [\"string\"],
        \"medications\": [\"string\"],
        \"allergies\": [\"string\"]
    },
    \"vital_signs\": {
        \"heart_rate\": number,
        \"respiratory_rate\": number,
        \"blood_pressure_systolic\": number,
        \"blood_pressure_diastolic\": number,
        \"temperature\": number,
        \"oxygen_saturation\": number,
        \"glasgow_scale\": \"string\"
    },
    \"referral_info\": {
        \"specialty_requested\": \"string\",
        \"referral_type\": \"consulta|hospitalizacion|cirugia|urgencia|otro\",
        \"reason_for_referral\": \"string\",
        \"urgency_level\": \"Alta|Media|Baja\",
        \"oxygen_requirement\": \"SI|NO\"
    },
    \"institution_info\": {
        \"referring_institution\": \"string\",
        \"referring_doctor\": \"string\",
        \"contact_email\": \"string\",
        \"contact_phone\": \"string\"
    },
    \"temporal_info\": {
        \"symptom_duration\": \"string\",
        \"onset_date\": \"string\",
        \"evolution\": \"string\"
    },
    \"severity_indicators\": {
        \"critical_keywords\": [\"string\"],
        \"urgency_score\": number,
        \"priority_justification\": \"string\"
    }
}

TEXTO MÉDICO A ANALIZAR:
" . $text . "

RESPONDE ÚNICAMENTE CON EL JSON, SIN TEXTO ADICIONAL.";
    }

    /**
     * Parse medical analysis response from Gemini
     */
    private function parseMedicalAnalysisResponse(string $response): array
    {
        try {
            $response = trim($response);

            // Extract JSON from response
            if (preg_match('/\{.*\}/s', $response, $matches)) {
                $jsonString = $matches[0];
                $data = json_decode($jsonString, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    return [
                        'success' => true,
                        'data' => $this->validateMedicalAnalysisData($data),
                        'raw_response' => $response
                    ];
                }
            }

            return [
                'success' => false,
                'error' => 'No se pudo parsear la respuesta como JSON válido',
                'raw_response' => $response
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'raw_response' => $response
            ];
        }
    }

    /**
     * Validate and clean medical analysis data
     */
    private function validateMedicalAnalysisData(array $data): array
    {
        $validated = [
            'patient_info' => [
                'name' => $this->cleanString($data['patient_info']['name'] ?? null),
                'age' => $this->validateAge($data['patient_info']['age'] ?? null),
                'gender' => $this->validateGender($data['patient_info']['gender'] ?? null),
                'identification' => $this->cleanString($data['patient_info']['identification'] ?? null),
                'phone' => $this->cleanString($data['patient_info']['phone'] ?? null)
            ],
            'clinical_data' => [
                'chief_complaint' => $this->cleanString($data['clinical_data']['chief_complaint'] ?? null),
                'current_illness' => $this->cleanString($data['clinical_data']['current_illness'] ?? null),
                'medical_history' => $this->cleanString($data['clinical_data']['medical_history'] ?? null),
                'primary_diagnosis' => $this->cleanString($data['clinical_data']['primary_diagnosis'] ?? null),
                'secondary_diagnoses' => $this->validateArray($data['clinical_data']['secondary_diagnoses'] ?? []),
                'medications' => $this->validateArray($data['clinical_data']['medications'] ?? []),
                'allergies' => $this->validateArray($data['clinical_data']['allergies'] ?? [])
            ],
            'vital_signs' => [
                'heart_rate' => $this->validateVitalSign($data['vital_signs']['heart_rate'] ?? null, 30, 250),
                'respiratory_rate' => $this->validateVitalSign($data['vital_signs']['respiratory_rate'] ?? null, 5, 60),
                'blood_pressure_systolic' => $this->validateVitalSign($data['vital_signs']['blood_pressure_systolic'] ?? null, 60, 300),
                'blood_pressure_diastolic' => $this->validateVitalSign($data['vital_signs']['blood_pressure_diastolic'] ?? null, 30, 200),
                'temperature' => $this->validateTemperature($data['vital_signs']['temperature'] ?? null),
                'oxygen_saturation' => $this->validateVitalSign($data['vital_signs']['oxygen_saturation'] ?? null, 50, 100),
                'glasgow_scale' => $this->cleanString($data['vital_signs']['glasgow_scale'] ?? null)
            ],
            'referral_info' => [
                'specialty_requested' => $this->cleanString($data['referral_info']['specialty_requested'] ?? null),
                'referral_type' => $this->validateReferralType($data['referral_info']['referral_type'] ?? null),
                'reason_for_referral' => $this->cleanString($data['referral_info']['reason_for_referral'] ?? null),
                'urgency_level' => $this->validateUrgencyLevel($data['referral_info']['urgency_level'] ?? null),
                'oxygen_requirement' => $this->validateYesNo($data['referral_info']['oxygen_requirement'] ?? null)
            ],
            'institution_info' => [
                'referring_institution' => $this->cleanString($data['institution_info']['referring_institution'] ?? null),
                'referring_doctor' => $this->cleanString($data['institution_info']['referring_doctor'] ?? null),
                'contact_email' => $this->validateEmail($data['institution_info']['contact_email'] ?? null),
                'contact_phone' => $this->cleanString($data['institution_info']['contact_phone'] ?? null)
            ],
            'temporal_info' => [
                'symptom_duration' => $this->cleanString($data['temporal_info']['symptom_duration'] ?? null),
                'onset_date' => $this->cleanString($data['temporal_info']['onset_date'] ?? null),
                'evolution' => $this->cleanString($data['temporal_info']['evolution'] ?? null)
            ],
            'severity_indicators' => [
                'critical_keywords' => $this->validateArray($data['severity_indicators']['critical_keywords'] ?? []),
                'urgency_score' => $this->validateScore($data['severity_indicators']['urgency_score'] ?? null),
                'priority_justification' => $this->cleanString($data['severity_indicators']['priority_justification'] ?? null)
            ]
        ];

        return $validated;
    }

    /**
     * Helper validation methods
     */
    private function cleanString($value): ?string
    {
        if (is_string($value)) {
            $cleaned = trim($value);
            return empty($cleaned) ? null : $cleaned;
        }
        return null;
    }

    private function validateAge($value): ?int
    {
        if (is_numeric($value)) {
            $age = (int) $value;
            return ($age >= 0 && $age <= 120) ? $age : null;
        }
        return null;
    }

    private function validateGender($value): ?string
    {
        $validGenders = ['masculino', 'femenino', 'otro'];
        return in_array($value, $validGenders) ? $value : null;
    }

    private function validateArray($value): array
    {
        return is_array($value) ? array_filter($value, 'is_string') : [];
    }

    private function validateVitalSign($value, $min, $max): ?int
    {
        if (is_numeric($value)) {
            $vital = (int) $value;
            return ($vital >= $min && $vital <= $max) ? $vital : null;
        }
        return null;
    }

    private function validateTemperature($value): ?float
    {
        if (is_numeric($value)) {
            $temp = (float) $value;
            return ($temp >= 30.0 && $temp <= 45.0) ? $temp : null;
        }
        return null;
    }

    private function validateReferralType($value): string
    {
        $validTypes = ['consulta', 'hospitalizacion', 'cirugia', 'urgencia', 'otro'];
        return in_array($value, $validTypes) ? $value : 'consulta';
    }

    private function validateUrgencyLevel($value): string
    {
        $validLevels = ['Alta', 'Media', 'Baja'];
        return in_array($value, $validLevels) ? $value : 'Media';
    }

    private function validateYesNo($value): string
    {
        return in_array($value, ['SI', 'NO']) ? $value : 'NO';
    }

    private function validateEmail($value): ?string
    {
        if (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $value;
        }
        return null;
    }

    private function validateScore($value): ?float
    {
        if (is_numeric($value)) {
            $score = (float) $value;
            return ($score >= 0 && $score <= 100) ? $score : null;
        }
        return null;
    }

    /**
     * Analyze medical document with enhanced AI
     */
    public function analyzeMedicalDocument(string $filePath): array
    {
        try {
            // Extract text from document
            $extractedText = $this->extractTextFromFile($filePath);

            if (empty($extractedText)) {
                return [
                    'success' => false,
                    'error' => 'No se pudo extraer texto del documento'
                ];
            }

            // Analyze with enhanced medical AI
            $analysis = $this->analyzeMedicalText($extractedText);

            if ($analysis['success']) {
                $analysis['extracted_text'] = $extractedText;
                $analysis['file_path'] = $filePath;
                $analysis['analysis_timestamp'] = now()->toISOString();
            }

            return $analysis;

        } catch (\Exception $e) {
            Log::error('Error en analyzeMedicalDocument: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
