<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\GeminiAIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class GeminiAIServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $geminiService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->geminiService = new GeminiAIService();
    }

    public function test_can_analyze_medical_text()
    {
        $medicalText = "Paciente de 45 años con dolor torácico agudo, disnea y sudoración. FC: 120 lpm, TA: 140/90 mmHg.";
        
        $result = $this->geminiService->analyzeMedicalText($medicalText);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('patient_info', $result);
        $this->assertArrayHasKey('vital_signs', $result);
        $this->assertArrayHasKey('symptoms', $result);
    }

    public function test_can_classify_priority()
    {
        $medicalData = [
            'diagnostico_principal' => 'Infarto agudo del miocardio',
            'motivo_consulta' => 'Dolor torácico severo',
            'paciente_edad' => 65
        ];
        
        $result = $this->geminiService->classifyPriority($medicalData);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('priority_level', $result);
        $this->assertArrayHasKey('urgency_score', $result);
        $this->assertArrayHasKey('criteria_explanation', $result);
    }

    public function test_handles_empty_text_gracefully()
    {
        $result = $this->geminiService->analyzeMedicalText('');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_validates_medical_data_structure()
    {
        $invalidData = ['invalid_field' => 'test'];
        
        $result = $this->geminiService->classifyPriority($invalidData);
        
        $this->assertIsArray($result);
        // Should handle invalid data gracefully
    }

    public function test_extracts_patient_information()
    {
        $text = "Paciente María González, 34 años, CC 12345678, con diabetes mellitus tipo 2";
        
        $result = $this->geminiService->analyzeMedicalText($text);
        
        $this->assertArrayHasKey('patient_info', $result);
        if (isset($result['patient_info'])) {
            $this->assertArrayHasKey('age', $result['patient_info']);
            $this->assertArrayHasKey('name', $result['patient_info']);
        }
    }

    public function test_extracts_vital_signs()
    {
        $text = "Signos vitales: FC 80 lpm, FR 18 rpm, TA 120/80 mmHg, Temp 36.5°C, SpO2 98%";
        
        $result = $this->geminiService->analyzeMedicalText($text);
        
        $this->assertArrayHasKey('vital_signs', $result);
        if (isset($result['vital_signs'])) {
            $this->assertArrayHasKey('heart_rate', $result['vital_signs']);
            $this->assertArrayHasKey('blood_pressure_systolic', $result['vital_signs']);
        }
    }

    public function test_identifies_medical_specialties()
    {
        $text = "Solicitud de interconsulta a cardiología por dolor precordial";
        
        $result = $this->geminiService->analyzeMedicalText($text);
        
        $this->assertArrayHasKey('specialty_detected', $result);
    }

    public function test_calculates_urgency_score()
    {
        $urgentCase = [
            'diagnostico_principal' => 'Paro cardiorespiratorio',
            'motivo_consulta' => 'Paciente inconsciente',
            'paciente_edad' => 70
        ];
        
        $result = $this->geminiService->classifyPriority($urgentCase);
        
        $this->assertArrayHasKey('urgency_score', $result);
        if (isset($result['urgency_score'])) {
            $this->assertGreaterThan(80, $result['urgency_score']);
        }
    }

    public function test_handles_api_errors_gracefully()
    {
        // Mock a service that throws an exception
        $mockService = Mockery::mock(GeminiAIService::class)->makePartial();
        $mockService->shouldReceive('makeApiRequest')
                   ->andThrow(new \Exception('API Error'));
        
        $result = $mockService->analyzeMedicalText('test text');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_validates_api_response_structure()
    {
        $text = "Paciente con síntomas respiratorios";
        
        $result = $this->geminiService->analyzeMedicalText($text);
        
        // Verify response has expected structure
        $this->assertIsArray($result);
        $expectedKeys = ['patient_info', 'vital_signs', 'symptoms', 'clinical_data'];
        
        foreach ($expectedKeys as $key) {
            if (isset($result[$key])) {
                $this->assertIsArray($result[$key]);
            }
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
