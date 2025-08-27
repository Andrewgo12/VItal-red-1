<?php

namespace App\Services;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmailTemplateService
{
    private array $templates;
    private string $templatePath;

    public function __construct()
    {
        $this->templatePath = 'emails';
        $this->loadTemplates();
    }

    /**
     * Get email template content
     */
    public function getTemplate(string $templateName, array $data = []): string
    {
        $template = $this->templates[$templateName] ?? null;
        
        if (!$template) {
            throw new \InvalidArgumentException("Email template '{$templateName}' not found");
        }

        return $this->renderTemplate($template, $data);
    }

    /**
     * Get all available templates
     */
    public function getAvailableTemplates(): array
    {
        return array_keys($this->templates);
    }

    /**
     * Render template with data
     */
    private function renderTemplate(array $template, array $data): string
    {
        // Use Laravel's view system if template exists as a view
        if (View::exists($template['view'])) {
            return View::make($template['view'], $data)->render();
        }

        // Fallback to simple string replacement
        $content = $template['content'];
        
        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $content = str_replace($placeholder, $value, $content);
        }

        return $content;
    }

    /**
     * Load email templates
     */
    private function loadTemplates(): void
    {
        $this->templates = [
            'urgent_case' => [
                'view' => 'emails.urgent-case',
                'subject' => 'Caso Médico Urgente - {{patient_name}}',
                'content' => $this->getUrgentCaseTemplate(),
                'variables' => ['patient_name', 'specialty', 'urgency_score', 'case_url', 'doctor_name']
            ],
            
            'case_assigned' => [
                'view' => 'emails.case-assigned',
                'subject' => 'Caso Asignado - {{patient_name}}',
                'content' => $this->getCaseAssignedTemplate(),
                'variables' => ['doctor_name', 'patient_name', 'specialty', 'case_url', 'priority']
            ],
            
            'case_updated' => [
                'view' => 'emails.case-updated',
                'subject' => 'Actualización de Caso - {{patient_name}}',
                'content' => $this->getCaseUpdatedTemplate(),
                'variables' => ['patient_name', 'status', 'updated_by', 'case_url', 'notes']
            ],
            
            'daily_summary' => [
                'view' => 'emails.daily-summary',
                'subject' => 'Resumen Diario - {{date}}',
                'content' => $this->getDailySummaryTemplate(),
                'variables' => ['date', 'total_cases', 'urgent_cases', 'pending_cases', 'dashboard_url']
            ],
            
            'system_alert' => [
                'view' => 'emails.system-alert',
                'subject' => 'Alerta del Sistema - {{alert_type}}',
                'content' => $this->getSystemAlertTemplate(),
                'variables' => ['alert_type', 'message', 'severity', 'timestamp', 'admin_url']
            ],
            
            'welcome_user' => [
                'view' => 'emails.welcome-user',
                'subject' => 'Bienvenido a Vital Red - {{user_name}}',
                'content' => $this->getWelcomeUserTemplate(),
                'variables' => ['user_name', 'role', 'login_url', 'support_email']
            ],
            
            'password_reset' => [
                'view' => 'emails.password-reset',
                'subject' => 'Restablecer Contraseña - Vital Red',
                'content' => $this->getPasswordResetTemplate(),
                'variables' => ['user_name', 'reset_url', 'expires_at']
            ],
            
            'case_reminder' => [
                'view' => 'emails.case-reminder',
                'subject' => 'Recordatorio: Casos Pendientes',
                'content' => $this->getCaseReminderTemplate(),
                'variables' => ['doctor_name', 'pending_count', 'urgent_count', 'cases_url']
            ]
        ];
    }

    /**
     * Urgent case email template
     */
    private function getUrgentCaseTemplate(): string
    {
        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Caso Médico Urgente</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f8f9fa; }
        .urgent-badge { background: #dc3545; color: white; padding: 5px 10px; border-radius: 3px; }
        .button { display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚨 CASO MÉDICO URGENTE</h1>
        </div>
        <div class="content">
            <p><strong>Dr. {{doctor_name}},</strong></p>
            
            <p>Se ha recibido un nuevo caso médico clasificado como <span class="urgent-badge">URGENTE</span> que requiere su atención inmediata.</p>
            
            <h3>Detalles del Caso:</h3>
            <ul>
                <li><strong>Paciente:</strong> {{patient_name}}</li>
                <li><strong>Especialidad:</strong> {{specialty}}</li>
                <li><strong>Score de Urgencia:</strong> {{urgency_score}}/100</li>
                <li><strong>Recibido:</strong> {{received_at}}</li>
            </ul>
            
            <p>Por favor, evalúe este caso lo antes posible para garantizar la atención oportuna del paciente.</p>
            
            <p style="text-align: center;">
                <a href="{{case_url}}" class="button">Evaluar Caso Ahora</a>
            </p>
        </div>
        <div class="footer">
            <p>Este es un mensaje automático del Sistema Vital Red.<br>
            No responda a este correo.</p>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Case assigned email template
     */
    private function getCaseAssignedTemplate(): string
    {
        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Caso Asignado</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #007bff; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f8f9fa; }
        .priority-high { color: #dc3545; font-weight: bold; }
        .priority-medium { color: #fd7e14; font-weight: bold; }
        .priority-low { color: #28a745; font-weight: bold; }
        .button { display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Nuevo Caso Asignado</h1>
        </div>
        <div class="content">
            <p><strong>Dr. {{doctor_name}},</strong></p>
            
            <p>Se le ha asignado un nuevo caso médico para su evaluación.</p>
            
            <h3>Información del Caso:</h3>
            <ul>
                <li><strong>Paciente:</strong> {{patient_name}}</li>
                <li><strong>Especialidad:</strong> {{specialty}}</li>
                <li><strong>Prioridad:</strong> <span class="priority-{{priority_class}}">{{priority}}</span></li>
                <li><strong>Asignado:</strong> {{assigned_at}}</li>
            </ul>
            
            <p>Por favor, revise el caso y proceda con la evaluación correspondiente.</p>
            
            <p style="text-align: center;">
                <a href="{{case_url}}" class="button">Ver Caso</a>
            </p>
        </div>
        <div class="footer">
            <p>Sistema Vital Red - Gestión Médica Inteligente</p>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Case updated email template
     */
    private function getCaseUpdatedTemplate(): string
    {
        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Actualización de Caso</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #28a745; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f8f9fa; }
        .status-badge { padding: 5px 10px; border-radius: 3px; color: white; }
        .status-accepted { background: #28a745; }
        .status-rejected { background: #dc3545; }
        .status-pending { background: #ffc107; color: #333; }
        .button { display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📝 Actualización de Caso</h1>
        </div>
        <div class="content">
            <p>El caso del paciente <strong>{{patient_name}}</strong> ha sido actualizado.</p>
            
            <h3>Detalles de la Actualización:</h3>
            <ul>
                <li><strong>Nuevo Estado:</strong> <span class="status-badge status-{{status_class}}">{{status}}</span></li>
                <li><strong>Actualizado por:</strong> {{updated_by}}</li>
                <li><strong>Fecha:</strong> {{updated_at}}</li>
            </ul>
            
            {{#if notes}}
            <h3>Observaciones:</h3>
            <p style="background: white; padding: 15px; border-left: 4px solid #007bff;">{{notes}}</p>
            {{/if}}
            
            <p style="text-align: center;">
                <a href="{{case_url}}" class="button">Ver Detalles</a>
            </p>
        </div>
        <div class="footer">
            <p>Sistema Vital Red - Gestión Médica Inteligente</p>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Daily summary email template
     */
    private function getDailySummaryTemplate(): string
    {
        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Resumen Diario</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #6f42c1; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f8f9fa; }
        .stats { display: flex; justify-content: space-around; margin: 20px 0; }
        .stat-box { text-align: center; padding: 15px; background: white; border-radius: 5px; flex: 1; margin: 0 5px; }
        .stat-number { font-size: 24px; font-weight: bold; color: #007bff; }
        .button { display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Resumen Diario</h1>
            <p>{{date}}</p>
        </div>
        <div class="content">
            <p>Resumen de la actividad del sistema durante el día de hoy:</p>
            
            <div class="stats">
                <div class="stat-box">
                    <div class="stat-number">{{total_cases}}</div>
                    <div>Casos Totales</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">{{urgent_cases}}</div>
                    <div>Casos Urgentes</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">{{pending_cases}}</div>
                    <div>Casos Pendientes</div>
                </div>
            </div>
            
            <h3>Resumen de Actividad:</h3>
            <ul>
                <li>Casos procesados: {{processed_cases}}</li>
                <li>Casos evaluados: {{evaluated_cases}}</li>
                <li>Tasa de aceptación: {{acceptance_rate}}%</li>
                <li>Tiempo promedio de respuesta: {{avg_response_time}} horas</li>
            </ul>
            
            <p style="text-align: center;">
                <a href="{{dashboard_url}}" class="button">Ver Dashboard Completo</a>
            </p>
        </div>
        <div class="footer">
            <p>Sistema Vital Red - Resumen Automático</p>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * System alert email template
     */
    private function getSystemAlertTemplate(): string
    {
        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Alerta del Sistema</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f8f9fa; }
        .alert-critical { border-left: 5px solid #dc3545; background: #f8d7da; padding: 15px; }
        .alert-warning { border-left: 5px solid #ffc107; background: #fff3cd; padding: 15px; }
        .alert-info { border-left: 5px solid #17a2b8; background: #d1ecf1; padding: 15px; }
        .button { display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Alerta del Sistema</h1>
        </div>
        <div class="content">
            <div class="alert-{{severity}}">
                <h3>{{alert_type}}</h3>
                <p>{{message}}</p>
                <p><strong>Timestamp:</strong> {{timestamp}}</p>
            </div>
            
            <p>Esta alerta requiere atención inmediata del administrador del sistema.</p>
            
            <p style="text-align: center;">
                <a href="{{admin_url}}" class="button">Ir al Panel de Administración</a>
            </p>
        </div>
        <div class="footer">
            <p>Sistema Vital Red - Alerta Automática</p>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Welcome user email template
     */
    private function getWelcomeUserTemplate(): string
    {
        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bienvenido a Vital Red</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #28a745; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f8f9fa; }
        .welcome-box { background: white; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .button { display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏥 Bienvenido a Vital Red</h1>
        </div>
        <div class="content">
            <div class="welcome-box">
                <h2>¡Hola {{user_name}}!</h2>
                
                <p>Bienvenido al Sistema de Gestión Médica Vital Red. Su cuenta ha sido creada exitosamente con el rol de <strong>{{role}}</strong>.</p>
                
                <h3>Primeros Pasos:</h3>
                <ol>
                    <li>Inicie sesión en el sistema</li>
                    <li>Complete su perfil médico</li>
                    <li>Configure sus preferencias de notificación</li>
                    <li>Explore el dashboard y las funcionalidades</li>
                </ol>
                
                <p>Si tiene alguna pregunta o necesita asistencia, no dude en contactar a nuestro equipo de soporte en <strong>{{support_email}}</strong>.</p>
            </div>
            
            <p style="text-align: center;">
                <a href="{{login_url}}" class="button">Iniciar Sesión</a>
            </p>
        </div>
        <div class="footer">
            <p>Sistema Vital Red - Gestión Médica Inteligente</p>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Password reset email template
     */
    private function getPasswordResetTemplate(): string
    {
        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Restablecer Contraseña</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #007bff; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f8f9fa; }
        .security-notice { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .button { display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Restablecer Contraseña</h1>
        </div>
        <div class="content">
            <p><strong>Hola {{user_name}},</strong></p>
            
            <p>Hemos recibido una solicitud para restablecer la contraseña de su cuenta en Vital Red.</p>
            
            <p>Si usted solicitó este cambio, haga clic en el siguiente botón para crear una nueva contraseña:</p>
            
            <p style="text-align: center;">
                <a href="{{reset_url}}" class="button">Restablecer Contraseña</a>
            </p>
            
            <div class="security-notice">
                <strong>Importante:</strong>
                <ul>
                    <li>Este enlace expira el {{expires_at}}</li>
                    <li>Si no solicitó este cambio, ignore este correo</li>
                    <li>Nunca comparta este enlace con otras personas</li>
                </ul>
            </div>
            
            <p>Si tiene problemas con el enlace, copie y pegue la siguiente URL en su navegador:</p>
            <p style="word-break: break-all; background: #e9ecef; padding: 10px; font-family: monospace;">{{reset_url}}</p>
        </div>
        <div class="footer">
            <p>Sistema Vital Red - Seguridad de Cuentas</p>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Case reminder email template
     */
    private function getCaseReminderTemplate(): string
    {
        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Recordatorio de Casos Pendientes</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #ffc107; color: #333; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f8f9fa; }
        .reminder-box { background: white; padding: 20px; border-radius: 5px; border-left: 5px solid #ffc107; }
        .urgent-notice { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .button { display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⏰ Recordatorio de Casos</h1>
        </div>
        <div class="content">
            <div class="reminder-box">
                <p><strong>Dr. {{doctor_name}},</strong></p>
                
                <p>Este es un recordatorio amigable sobre los casos médicos pendientes de evaluación en su bandeja.</p>
                
                <h3>Resumen de Casos Pendientes:</h3>
                <ul>
                    <li><strong>Total de casos pendientes:</strong> {{pending_count}}</li>
                    <li><strong>Casos urgentes:</strong> {{urgent_count}}</li>
                </ul>
                
                {{#if urgent_count}}
                <div class="urgent-notice">
                    <strong>⚠️ Atención:</strong> Tiene {{urgent_count}} caso(s) urgente(s) que requieren evaluación inmediata.
                </div>
                {{/if}}
                
                <p>Su pronta atención a estos casos es fundamental para garantizar la mejor atención médica a nuestros pacientes.</p>
            </div>
            
            <p style="text-align: center;">
                <a href="{{cases_url}}" class="button">Ver Casos Pendientes</a>
            </p>
        </div>
        <div class="footer">
            <p>Sistema Vital Red - Recordatorio Automático</p>
        </div>
    </div>
</body>
</html>';
    }
}
