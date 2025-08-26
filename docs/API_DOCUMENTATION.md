# Documentación de APIs - Sistema Vital Red

## Tabla de Contenidos

1. [Información General](#información-general)
2. [Autenticación](#autenticación)
3. [Endpoints de Solicitudes Médicas](#endpoints-de-solicitudes-médicas)
4. [Endpoints de Usuarios](#endpoints-de-usuarios)
5. [Endpoints de Métricas](#endpoints-de-métricas)
6. [Endpoints de Reportes](#endpoints-de-reportes)
7. [Endpoints de Configuración](#endpoints-de-configuración)
8. [Endpoints de Notificaciones](#endpoints-de-notificaciones)
9. [Códigos de Error](#códigos-de-error)
10. [Ejemplos de Uso](#ejemplos-de-uso)

## Información General

### Base URL
```
Producción: https://vitalred.hospital.com/api
Desarrollo: http://localhost:8000/api
```

### Formato de Respuesta
Todas las respuestas siguen el formato JSON estándar:

```json
{
  "success": true|false,
  "message": "Mensaje descriptivo",
  "data": {}, // Datos de respuesta
  "errors": {}, // Errores de validación (si aplica)
  "meta": {} // Metadatos (paginación, etc.)
}
```

### Headers Requeridos
```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

### Versionado
- **Versión Actual**: v1
- **URL con Versión**: `/api/v1/endpoint`
- **Header de Versión**: `API-Version: v1`

## Autenticación

### Login
Obtener token de acceso para autenticación.

```http
POST /api/auth/login
```

**Request Body:**
```json
{
  "email": "medico@hospital.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Login exitoso",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": {
      "id": 1,
      "name": "Dr. Juan Pérez",
      "email": "medico@hospital.com",
      "role": "medico",
      "department": "cardiologia"
    }
  }
}
```

### Logout
Invalidar token actual.

```http
POST /api/auth/logout
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Logout exitoso"
}
```

### Refresh Token
Renovar token de acceso.

```http
POST /api/auth/refresh
Authorization: Bearer {token}
```

### User Info
Obtener información del usuario autenticado.

```http
GET /api/auth/user
Authorization: Bearer {token}
```

## Endpoints de Solicitudes Médicas

### Listar Solicitudes
Obtener lista paginada de solicitudes médicas.

```http
GET /api/solicitudes-medicas
```

**Query Parameters:**
- `page` (int): Número de página (default: 1)
- `per_page` (int): Elementos por página (default: 15, max: 100)
- `estado` (string): Filtrar por estado
- `prioridad` (string): Filtrar por prioridad (Alta, Media, Baja)
- `especialidad` (string): Filtrar por especialidad
- `search` (string): Búsqueda por texto
- `fecha_desde` (date): Fecha inicio (YYYY-MM-DD)
- `fecha_hasta` (date): Fecha fin (YYYY-MM-DD)

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "paciente_nombre": "Juan Pérez",
      "paciente_apellidos": "García",
      "paciente_edad": 45,
      "especialidad_solicitada": "Cardiología",
      "prioridad_ia": "Alta",
      "score_urgencia": 85,
      "estado": "pendiente_evaluacion",
      "fecha_recepcion_email": "2024-01-15T08:30:00Z",
      "institucion_remitente": "Hospital San Juan",
      "diagnostico_principal": "Dolor torácico agudo",
      "medico_evaluador": null,
      "fecha_evaluacion": null
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 150,
    "last_page": 10,
    "from": 1,
    "to": 15
  }
}
```

### Obtener Solicitud Específica
Obtener detalles completos de una solicitud.

```http
GET /api/solicitudes-medicas/{id}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "paciente_nombre": "Juan Pérez",
    "paciente_apellidos": "García",
    "paciente_edad": 45,
    "paciente_sexo": "M",
    "paciente_identificacion": "12345678",
    "especialidad_solicitada": "Cardiología",
    "prioridad_ia": "Alta",
    "score_urgencia": 85,
    "estado": "pendiente_evaluacion",
    "diagnostico_principal": "Dolor torácico agudo",
    "motivo_consulta": "Paciente con dolor precordial de 2 horas de evolución",
    "antecedentes_medicos": "HTA, DM tipo 2",
    "medicamentos_actuales": "Metformina 850mg, Enalapril 10mg",
    "signos_vitales": {
      "frecuencia_cardiaca": 110,
      "tension_sistolica": 150,
      "tension_diastolica": 95,
      "frecuencia_respiratoria": 22,
      "temperatura": 36.8,
      "saturacion_oxigeno": 95
    },
    "analisis_ia": {
      "criterios_urgencia": [
        "Síntomas cardíacos agudos",
        "Signos vitales alterados",
        "Factores de riesgo cardiovascular"
      ],
      "confianza_analisis": 0.92
    },
    "institucion_remitente": "Hospital San Juan",
    "medico_remitente": "Dr. María López",
    "fecha_recepcion_email": "2024-01-15T08:30:00Z",
    "medico_evaluador": null,
    "fecha_evaluacion": null,
    "decision_medica": null,
    "observaciones_medico": null,
    "created_at": "2024-01-15T08:35:00Z",
    "updated_at": "2024-01-15T08:35:00Z"
  }
}
```

### Evaluar Solicitud
Guardar evaluación médica de una solicitud.

```http
PUT /api/solicitudes-medicas/{id}/evaluar
```

**Request Body:**
```json
{
  "decision_medica": "aceptar", // aceptar|rechazar|solicitar_info
  "observaciones_medico": "Caso compatible con síndrome coronario agudo. Se acepta para evaluación urgente en urgencias.",
  "prioridad_medica": "Alta", // Alta|Media|Baja
  "fecha_programada": "2024-01-15T14:00:00Z", // Solo si decision_medica = "aceptar"
  "servicio_destino": "urgencias", // Solo si decision_medica = "aceptar"
  "motivo_rechazo": "no_cumple_criterios", // Solo si decision_medica = "rechazar"
  "informacion_requerida": "Electrocardiograma reciente" // Solo si decision_medica = "solicitar_info"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Evaluación guardada exitosamente",
  "data": {
    "id": 1,
    "estado": "evaluada",
    "decision_medica": "aceptar",
    "medico_evaluador_id": 5,
    "fecha_evaluacion": "2024-01-15T10:45:00Z",
    "observaciones_medico": "Caso compatible con síndrome coronario agudo...",
    "prioridad_medica": "Alta"
  }
}
```

### Crear Solicitud (Manual)
Crear solicitud médica manualmente.

```http
POST /api/solicitudes-medicas
```

**Request Body:**
```json
{
  "paciente_nombre": "Ana García",
  "paciente_apellidos": "Rodríguez",
  "paciente_edad": 34,
  "paciente_sexo": "F",
  "especialidad_solicitada": "Neurología",
  "diagnostico_principal": "Cefalea intensa",
  "motivo_consulta": "Cefalea de inicio súbito",
  "institucion_remitente": "Clínica Norte",
  "medico_remitente": "Dr. Carlos Ruiz",
  "prioridad_manual": "Media"
}
```

## Endpoints de Usuarios

### Listar Usuarios
Obtener lista de usuarios del sistema.

```http
GET /api/users
```

**Query Parameters:**
- `role` (string): Filtrar por rol (medico, administrador)
- `active` (boolean): Filtrar por estado activo
- `department` (string): Filtrar por departamento

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Dr. Juan Pérez",
      "email": "juan.perez@hospital.com",
      "role": "medico",
      "department": "cardiologia",
      "medical_license": "12345",
      "specialties": ["Cardiología", "Medicina Interna"],
      "is_active": true,
      "last_login_at": "2024-01-15T09:00:00Z",
      "evaluations_count": 45,
      "created_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

### Crear Usuario
Crear nuevo usuario en el sistema.

```http
POST /api/users
```

**Request Body:**
```json
{
  "name": "Dra. María González",
  "email": "maria.gonzalez@hospital.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "medico",
  "department": "neurologia",
  "phone": "+57 300 123 4567",
  "medical_license": "67890",
  "specialties": ["Neurología", "Neurocirugía"],
  "is_active": true
}
```

### Actualizar Usuario
Actualizar información de usuario existente.

```http
PUT /api/users/{id}
```

### Eliminar Usuario
Eliminar usuario del sistema.

```http
DELETE /api/users/{id}
```

## Endpoints de Métricas

### Dashboard Principal
Obtener métricas principales para el dashboard.

```http
GET /api/metrics/dashboard
```

**Query Parameters:**
- `period` (string): Período de análisis (today, week, month, year)

**Response (200):**
```json
{
  "success": true,
  "data": {
    "overview": {
      "total_solicitudes": 1247,
      "urgent_pending": 23,
      "acceptance_rate": 78.5,
      "avg_response_time": 2.3
    },
    "solicitudes": {
      "by_priority": {
        "Alta": 156,
        "Media": 789,
        "Baja": 302
      },
      "by_status": {
        "pendiente_evaluacion": 45,
        "evaluada": 1156,
        "aceptada": 892,
        "rechazada": 310
      },
      "by_specialty": {
        "Cardiología": 445,
        "Neurología": 298,
        "Ortopedia": 234,
        "Otros": 270
      }
    },
    "performance": {
      "daily_activity": [
        {"date": "2024-01-15", "count": 45},
        {"date": "2024-01-14", "count": 52}
      ],
      "response_times": {
        "Alta": 1.2,
        "Media": 8.5,
        "Baja": 24.3
      },
      "sla_compliance": {
        "Alta": 95.2,
        "Media": 87.8,
        "Baja": 92.1
      }
    }
  }
}
```

### Métricas Detalladas
Obtener métricas específicas con filtros avanzados.

```http
GET /api/metrics/detailed
```

**Query Parameters:**
- `metric_type` (string): Tipo de métrica (volume, performance, quality)
- `group_by` (string): Agrupar por (day, week, month, specialty, user)
- `start_date` (date): Fecha inicio
- `end_date` (date): Fecha fin

## Endpoints de Reportes

### Reporte de Solicitudes Médicas
Generar reporte de solicitudes médicas.

```http
GET /api/reports/medical-requests
```

**Query Parameters:**
- `format` (string): Formato de salida (json, pdf, excel, csv)
- `period` (string): Período (1month, 3months, 6months, 1year)
- `specialty` (string): Filtrar por especialidad
- `include_charts` (boolean): Incluir gráficos (solo PDF)

**Response (200) - JSON:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_requests": 1247,
      "period": "2024-01-01 to 2024-01-31",
      "acceptance_rate": 78.5,
      "avg_response_time": 2.3
    },
    "by_specialty": [
      {
        "specialty": "Cardiología",
        "total": 445,
        "accepted": 356,
        "rejected": 89,
        "acceptance_rate": 80.0
      }
    ],
    "trends": {
      "daily_volume": [
        {"date": "2024-01-01", "count": 42},
        {"date": "2024-01-02", "count": 38}
      ]
    }
  }
}
```

### Reporte de Rendimiento
Generar reporte de rendimiento del sistema.

```http
GET /api/reports/performance
```

### Reporte de Auditoría
Generar reporte de auditoría del sistema.

```http
GET /api/reports/audit
```

**Query Parameters:**
- `user_id` (int): Filtrar por usuario
- `action` (string): Filtrar por acción
- `start_date` (date): Fecha inicio
- `end_date` (date): Fecha fin

## Endpoints de Configuración

### Estado del Sistema
Obtener estado general del sistema.

```http
GET /api/config/status
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "system_status": "operational",
    "components": {
      "database": {
        "status": "connected",
        "response_time": "12ms"
      },
      "gmail_monitor": {
        "status": "running",
        "last_check": "2024-01-15T10:45:00Z",
        "emails_processed": 156
      },
      "ai_services": {
        "status": "operational",
        "api_keys_active": 3,
        "last_analysis": "2024-01-15T10:44:30Z"
      },
      "notifications": {
        "status": "operational",
        "pending_notifications": 5
      }
    }
  }
}
```

### Configuración Gmail
Actualizar configuración de Gmail.

```http
POST /api/config/gmail
```

**Request Body:**
```json
{
  "email": "solicitudes@hospital.com",
  "app_password": "abcd efgh ijkl mnop",
  "imap_server": "imap.gmail.com",
  "imap_port": 993,
  "check_interval": 5,
  "max_emails_per_check": 50,
  "enabled": true
}
```

### Configuración IA
Actualizar configuración de servicios de IA.

```http
POST /api/config/ai
```

### Probar Conexiones
Probar conectividad de servicios externos.

```http
POST /api/config/test-connections
```

## Endpoints de Notificaciones

### Listar Notificaciones
Obtener notificaciones del usuario actual.

```http
GET /api/notifications
```

**Query Parameters:**
- `unread_only` (boolean): Solo no leídas
- `type` (string): Filtrar por tipo

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "type": "caso_urgente",
      "title": "Caso Urgente Detectado",
      "message": "Nuevo caso de alta prioridad: Juan Pérez - Cardiología",
      "data": {
        "solicitud_id": 123,
        "patient_name": "Juan Pérez",
        "specialty": "Cardiología"
      },
      "read_at": null,
      "created_at": "2024-01-15T10:30:00Z"
    }
  ],
  "meta": {
    "unread_count": 5,
    "total_count": 25
  }
}
```

### Marcar como Leída
Marcar notificación como leída.

```http
PUT /api/notifications/{id}/read
```

### Enviar Notificación
Enviar notificación personalizada (solo admin).

```http
POST /api/notifications/send
```

**Request Body:**
```json
{
  "recipients": [1, 2, 3], // IDs de usuarios
  "type": "system_announcement",
  "title": "Mantenimiento Programado",
  "message": "El sistema estará en mantenimiento el 20/01/2024 de 2:00 AM a 4:00 AM",
  "send_email": true
}
```

## Códigos de Error

### Códigos HTTP Estándar
- `200` - OK: Solicitud exitosa
- `201` - Created: Recurso creado exitosamente
- `400` - Bad Request: Error en la solicitud
- `401` - Unauthorized: No autenticado
- `403` - Forbidden: Sin permisos
- `404` - Not Found: Recurso no encontrado
- `422` - Unprocessable Entity: Error de validación
- `500` - Internal Server Error: Error del servidor

### Errores de Validación (422)
```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "email": ["El campo email es obligatorio"],
    "password": ["La contraseña debe tener al menos 8 caracteres"]
  }
}
```

### Errores de Autenticación (401)
```json
{
  "success": false,
  "message": "Token inválido o expirado",
  "error_code": "TOKEN_EXPIRED"
}
```

### Errores de Autorización (403)
```json
{
  "success": false,
  "message": "No tiene permisos para realizar esta acción",
  "error_code": "INSUFFICIENT_PERMISSIONS"
}
```

## Ejemplos de Uso

### Ejemplo 1: Flujo Completo de Evaluación
```javascript
// 1. Login
const loginResponse = await fetch('/api/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'medico@hospital.com',
    password: 'password123'
  })
});
const { data: { token } } = await loginResponse.json();

// 2. Obtener casos pendientes
const casesResponse = await fetch('/api/solicitudes-medicas?estado=pendiente_evaluacion', {
  headers: { 'Authorization': `Bearer ${token}` }
});
const { data: cases } = await casesResponse.json();

// 3. Evaluar primer caso
const evaluationResponse = await fetch(`/api/solicitudes-medicas/${cases[0].id}/evaluar`, {
  method: 'PUT',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    decision_medica: 'aceptar',
    observaciones_medico: 'Caso urgente, requiere atención inmediata',
    prioridad_medica: 'Alta',
    fecha_programada: '2024-01-15T14:00:00Z',
    servicio_destino: 'urgencias'
  })
});
```

### Ejemplo 2: Generar Reporte Mensual
```python
import requests

# Configuración
base_url = "https://vitalred.hospital.com/api"
token = "your_token_here"
headers = {
    "Authorization": f"Bearer {token}",
    "Content-Type": "application/json"
}

# Generar reporte
response = requests.get(
    f"{base_url}/reports/medical-requests",
    headers=headers,
    params={
        "format": "pdf",
        "period": "1month",
        "include_charts": True
    }
)

# Guardar archivo
with open("reporte_mensual.pdf", "wb") as f:
    f.write(response.content)
```

---

**Versión de API**: v1.0.0  
**Última Actualización**: 2024-01-15  
**Soporte**: api-support@vitalred.com
