<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $notification->titulo }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        .priority-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        .priority-critica {
            background-color: #dc2626;
            color: white;
        }
        .priority-alta {
            background-color: #ea580c;
            color: white;
        }
        .priority-media {
            background-color: #ca8a04;
            color: white;
        }
        .priority-baja {
            background-color: #16a34a;
            color: white;
        }
        .notification-title {
            font-size: 20px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 20px;
        }
        .notification-content {
            background-color: #f8fafc;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #2563eb;
        }
        .patient-info {
            background-color: #fef3c7;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            border-left: 4px solid #f59e0b;
        }
        .patient-info h3 {
            margin-top: 0;
            color: #92400e;
        }
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            min-width: 120px;
            color: #374151;
        }
        .info-value {
            color: #1f2937;
        }
        .action-buttons {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 0 10px;
        }
        .btn:hover {
            background-color: #1d4ed8;
        }
        .btn-urgent {
            background-color: #dc2626;
        }
        .btn-urgent:hover {
            background-color: #b91c1c;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #6b7280;
        }
        .timestamp {
            font-size: 12px;
            color: #6b7280;
            margin-top: 20px;
        }
        .urgent-banner {
            background-color: #fef2f2;
            border: 2px solid #dc2626;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
        .urgent-banner h2 {
            color: #dc2626;
            margin: 0;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">VITAL RED</div>
            <div>Sistema de Gestión de Referencias Médicas</div>
        </div>

        @if($notification->prioridad === 'critica')
        <div class="urgent-banner">
            <h2>🚨 NOTIFICACIÓN URGENTE 🚨</h2>
            <p>Esta notificación requiere atención inmediata</p>
        </div>
        @endif

        <div class="priority-badge priority-{{ $notification->prioridad }}">
            Prioridad: {{ ucfirst($notification->prioridad) }}
        </div>

        <h1 class="notification-title">{{ $notification->titulo }}</h1>

        <div class="notification-content">
            {!! nl2br(e($notification->mensaje)) !!}
        </div>

        @if($solicitud)
        <div class="patient-info">
            <h3>📋 Información del Caso</h3>
            
            <div class="info-row">
                <span class="info-label">Paciente:</span>
                <span class="info-value">{{ $solicitud->paciente_nombre }} {{ $solicitud->paciente_apellidos }}</span>
            </div>
            
            @if($solicitud->paciente_identificacion)
            <div class="info-row">
                <span class="info-label">Identificación:</span>
                <span class="info-value">{{ $solicitud->paciente_identificacion }}</span>
            </div>
            @endif
            
            <div class="info-row">
                <span class="info-label">Institución:</span>
                <span class="info-value">{{ $solicitud->institucion_remitente }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Especialidad:</span>
                <span class="info-value">{{ $solicitud->especialidad_solicitada }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Tipo:</span>
                <span class="info-value">{{ ucfirst($solicitud->tipo_solicitud) }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Diagnóstico:</span>
                <span class="info-value">{{ $solicitud->diagnostico_principal }}</span>
            </div>
            
            @if($solicitud->prioridad_ia)
            <div class="info-row">
                <span class="info-label">Prioridad IA:</span>
                <span class="info-value">{{ $solicitud->prioridad_ia }}</span>
            </div>
            @endif
            
            @if($solicitud->score_urgencia)
            <div class="info-row">
                <span class="info-label">Score Urgencia:</span>
                <span class="info-value">{{ $solicitud->score_urgencia }}/100</span>
            </div>
            @endif
            
            <div class="info-row">
                <span class="info-label">Recibido:</span>
                <span class="info-value">{{ $solicitud->fecha_recepcion_email->format('d/m/Y H:i') }}</span>
            </div>
            
            @if($solicitud->medicoEvaluador)
            <div class="info-row">
                <span class="info-label">Evaluado por:</span>
                <span class="info-value">{{ $solicitud->medicoEvaluador->name }}</span>
            </div>
            @endif
        </div>
        @endif

        @if($notification->datos_adicionales && isset($notification->datos_adicionales['observaciones_medico']))
        <div class="notification-content">
            <h3>💬 Observaciones del Médico Evaluador</h3>
            <p>{{ $notification->datos_adicionales['observaciones_medico'] }}</p>
        </div>
        @endif

        <div class="action-buttons">
            @if($notification->tipo_notificacion === 'caso_urgente')
                <a href="{{ url('/medico/evaluar-solicitud/' . $solicitud->id) }}" class="btn btn-urgent">
                    🚨 Evaluar Caso Urgente
                </a>
            @elseif($notification->tipo_notificacion === 'solicitud_aceptada')
                <a href="{{ url('/admin/admisiones') }}" class="btn">
                    ✅ Ver Admisiones
                </a>
            @else
                <a href="{{ url('/medico/bandeja-casos') }}" class="btn">
                    📋 Ver Bandeja de Casos
                </a>
            @endif
            
            <a href="{{ url('/dashboard') }}" class="btn">
                🏠 Ir al Dashboard
            </a>
        </div>

        @if($notification->departamento_destinatario)
        <div class="notification-content">
            <strong>Departamento destinatario:</strong> {{ $notification->departamento_destinatario }}
        </div>
        @endif

        <div class="timestamp">
            <strong>Fecha de notificación:</strong> {{ $notification->created_at->format('d/m/Y H:i:s') }}<br>
            <strong>ID de notificación:</strong> {{ $notification->id }}
            @if($solicitud)
            <br><strong>ID de solicitud:</strong> {{ $solicitud->id }}
            @endif
        </div>

        <div class="footer">
            <p><strong>Sistema Vital Red - Hospital</strong></p>
            <p>Esta es una notificación automática del sistema de gestión de referencias médicas.</p>
            <p>Por favor, no responda a este correo electrónico.</p>
            <p>Si tiene problemas para acceder al sistema, contacte al administrador.</p>
        </div>
    </div>
</body>
</html>
