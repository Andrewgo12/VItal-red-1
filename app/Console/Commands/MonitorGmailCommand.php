<?php

namespace App\Console\Commands;

use App\Jobs\ProcessGmailEmailJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;

class MonitorGmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gmail:monitor {--once : Run once instead of continuously}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor Gmail for new medical requests';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!config('services.gmail.enabled', false)) {
            $this->error('Gmail monitoring is disabled. Enable it in configuration.');
            return 1;
        }

        $this->info('Starting Gmail monitoring...');

        $runOnce = $this->option('once');
        $interval = config('services.gmail.monitoring_interval', 60);
        $maxEmails = config('services.gmail.max_emails_per_batch', 10);

        do {
            try {
                $this->info('Checking for new emails...');
                
                $newEmails = $this->fetchNewEmails($maxEmails);
                
                if (empty($newEmails)) {
                    $this->info('No new emails found.');
                } else {
                    $this->info('Found ' . count($newEmails) . ' new emails. Processing...');
                    
                    foreach ($newEmails as $email) {
                        ProcessGmailEmailJob::dispatch($email);
                    }
                    
                    $this->info('Dispatched ' . count($newEmails) . ' email processing jobs.');
                }

                if (!$runOnce) {
                    $this->info("Waiting {$interval} seconds before next check...");
                    sleep($interval);
                }

            } catch (Exception $e) {
                $this->error('Error during Gmail monitoring: ' . $e->getMessage());
                Log::error('Gmail monitoring error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                if (!$runOnce) {
                    $this->info('Waiting 60 seconds before retry...');
                    sleep(60);
                }
            }

        } while (!$runOnce);

        $this->info('Gmail monitoring stopped.');
        return 0;
    }

    /**
     * Fetch new emails from Gmail
     */
    private function fetchNewEmails(int $maxEmails): array
    {
        // This would integrate with the Python Gmail service
        $pythonServiceUrl = config('services.python.url', 'http://localhost:8001');
        
        try {
            $response = $this->callPythonService($pythonServiceUrl . '/gmail/check', [
                'max_emails' => $maxEmails
            ]);

            if ($response && isset($response['emails'])) {
                return $response['emails'];
            }

            return [];

        } catch (Exception $e) {
            Log::error('Failed to fetch emails from Python service', [
                'error' => $e->getMessage(),
                'url' => $pythonServiceUrl
            ]);

            // Fallback to mock data for development
            if (app()->environment('local')) {
                return $this->getMockEmails();
            }

            throw $e;
        }
    }

    /**
     * Call Python service
     */
    private function callPythonService(string $url, array $data = []): ?array
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL error: {$error}");
        }

        if ($httpCode !== 200) {
            throw new Exception("HTTP error: {$httpCode}");
        }

        $decoded = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON decode error: " . json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * Get mock emails for development
     */
    private function getMockEmails(): array
    {
        // Return empty array most of the time to avoid spam
        if (rand(1, 10) > 2) {
            return [];
        }

        return [
            [
                'id' => 'mock_' . uniqid(),
                'subject' => 'Solicitud médica urgente - Paciente Juan Pérez',
                'from' => 'doctor@hospital.com',
                'body' => "Paciente: Juan Pérez García\nEdad: 45 años\nSexo: Masculino\nDiagnóstico: Dolor torácico agudo\nMotivo: Evaluación cardiológica urgente\nEspecialidad: Cardiología\nInstitución: Hospital General",
                'received_at' => now()->toISOString()
            ]
        ];
    }
}
