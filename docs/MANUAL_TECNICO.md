# Manual Técnico - Sistema Vital Red

## Tabla de Contenidos

1. [Arquitectura del Sistema](#arquitectura-del-sistema)
2. [Instalación y Configuración](#instalación-y-configuración)
3. [Estructura del Código](#estructura-del-código)
4. [APIs y Servicios](#apis-y-servicios)
5. [Base de Datos](#base-de-datos)
6. [Seguridad](#seguridad)
7. [Monitoreo y Logs](#monitoreo-y-logs)
8. [Mantenimiento](#mantenimiento)
9. [Solución de Problemas](#solución-de-problemas)

## Arquitectura del Sistema

### Componentes Principales

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Backend       │    │   Sistema IA    │
│   (Laravel)     │◄──►│   (Laravel)     │◄──►│   (Python)      │
│   - Dashboard   │    │   - API REST    │    │   - Gmail Mon.  │
│   - Interfaces  │    │   - Auth        │    │   - Análisis IA │
│   - Reportes    │    │   - Notif.      │    │   - Clasificac. │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         └───────────────────────┼───────────────────────┘
                                 │
                    ┌─────────────────┐
                    │   Base de Datos │
                    │   (MySQL)       │
                    │   - Solicitudes │
                    │   - Usuarios    │
                    │   - Auditoría   │
                    └─────────────────┘
```

### Tecnologías Utilizadas

- **Backend**: Laravel 10.x (PHP 8.1+)
- **Frontend**: Blade Templates, Bootstrap 5, jQuery
- **Base de Datos**: MySQL 8.0+
- **Sistema IA**: Python 3.8+, spaCy, scikit-learn
- **APIs Externas**: Gemini AI, Gmail IMAP
- **Cache**: Redis/File Cache
- **Queue**: Laravel Queue (Database/Redis)

## Instalación y Configuración

### Requisitos del Sistema

#### Servidor Web
- PHP 8.1 o superior
- Composer 2.x
- Node.js 16+ (para assets)
- MySQL 8.0+
- Redis (opcional, recomendado)

#### Sistema de IA
- Python 3.8+
- pip package manager
- Acceso a Gmail con App Password
- APIs de Gemini AI

### Instalación Paso a Paso

#### 1. Clonar Repositorio
```bash
git clone <repository-url>
cd vital-red
```

#### 2. Configurar Backend Laravel
```bash
# Instalar dependencias PHP
composer install

# Copiar archivo de configuración
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate

# Configurar base de datos en .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vital_red
DB_USERNAME=root
DB_PASSWORD=

# Ejecutar migraciones
php artisan migrate

# Crear usuario administrador
php artisan db:seed --class=AdminUserSeeder
```

#### 3. Configurar Sistema de IA
```bash
cd ia

# Instalación automática
python enhanced_setup.py install

# O instalación manual
pip install -r requirements.txt

# Configurar credenciales
cp config/config_template.json config/config.json
# Editar config.json con credenciales reales
```

#### 4. Variables de Entorno Críticas
```env
# Gmail Configuration
GMAIL_EMAIL=your-email@gmail.com
GMAIL_APP_PASSWORD=your-16-char-app-password

# Gemini AI
GEMINI_API_KEY_1=your-gemini-api-key-1
GEMINI_API_KEY_2=your-gemini-api-key-2

# Security
AUDIT_ENABLED=true
ENCRYPT_SENSITIVE_DATA=true

# Notifications
NOTIFICATIONS_EMAIL_ENABLED=true
ADMIN_EMAIL=admin@hospital.com
```

## Estructura del Código

### Backend Laravel

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/              # Controladores administrativos
│   │   ├── Medico/             # Controladores médicos
│   │   └── API/                # Controladores API
│   ├── Middleware/
│   │   ├── AuditMiddleware.php # Auditoría automática
│   │   └── RoleMiddleware.php  # Control de roles
│   └── Requests/               # Validaciones de formularios
├── Models/
│   ├── SolicitudMedica.php     # Modelo principal
│   ├── User.php                # Usuarios del sistema
│   ├── AuditLog.php            # Logs de auditoría
│   └── NotificacionInterna.php # Notificaciones
├── Services/
│   ├── GeminiAIService.php     # Integración Gemini AI
│   ├── NotificationService.php # Sistema notificaciones
│   └── MetricsService.php      # Métricas del sistema
└── Events/                     # Eventos en tiempo real
```

### Sistema de IA (Python)

```
ia/
├── Functions/
│   ├── gmail_connector.py           # Conexión Gmail
│   ├── enhanced_medical_analyzer.py # Análisis médico IA
│   ├── medical_priority_classifier.py # Clasificación prioridades
│   ├── semantic_medical_classifier.py # Análisis semántico
│   └── text_extractor.py           # Extracción de texto
├── gmail_monitor_service.py         # Servicio principal
├── process_single_email.py          # Procesador individual
├── enhanced_setup.py               # Script instalación
└── config/
    └── config.json                 # Configuración IA
```

## APIs y Servicios

### Endpoints Principales

#### Autenticación
```http
POST /api/login
POST /api/logout
GET  /api/user
```

#### Solicitudes Médicas
```http
GET    /api/solicitudes-medicas
GET    /api/solicitudes-medicas/{id}
POST   /api/solicitudes-medicas
PUT    /api/solicitudes-medicas/{id}/evaluar
DELETE /api/solicitudes-medicas/{id}
```

#### Métricas y Reportes
```http
GET /api/metrics/dashboard
GET /api/metrics/detailed
GET /api/reports/medical-requests
GET /api/reports/performance
GET /api/reports/audit
```

#### Sistema de Configuración
```http
GET  /api/config/status
POST /api/config/gmail
POST /api/config/ai
POST /api/config/notifications
```

### Servicios Internos

#### GeminiAIService
```php
// Análisis de texto médico
$result = $geminiService->analyzeMedicalText($text);

// Clasificación de prioridad
$priority = $geminiService->classifyPriority($medicalData);

// Extracción de entidades
$entities = $geminiService->extractMedicalEntities($text);
```

#### NotificationService
```php
// Notificación caso urgente
$notificationService->sendUrgentCaseNotification($solicitud);

// Notificación evaluación
$notificationService->sendEvaluationNotification($solicitud, $medico, $data);

// Notificación sistema
$notificationService->sendSystemAlert($alertData);
```

## Base de Datos

### Tablas Principales

#### solicitudes_medicas
```sql
CREATE TABLE solicitudes_medicas (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    paciente_nombre VARCHAR(255) NOT NULL,
    paciente_apellidos VARCHAR(255),
    paciente_edad INT,
    paciente_sexo ENUM('M', 'F'),
    especialidad_solicitada VARCHAR(100) NOT NULL,
    prioridad_ia ENUM('Alta', 'Media', 'Baja'),
    score_urgencia INT,
    estado ENUM('pendiente_evaluacion', 'en_evaluacion', 'evaluada', 'aceptada', 'rechazada'),
    decision_medica ENUM('aceptar', 'rechazar', 'solicitar_info'),
    medico_evaluador_id BIGINT,
    fecha_recepcion_email TIMESTAMP,
    fecha_evaluacion TIMESTAMP,
    -- Campos adicionales...
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### users
```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('administrador', 'medico') NOT NULL,
    department VARCHAR(100),
    medical_license VARCHAR(50),
    specialties JSON,
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### audit_logs
```sql
CREATE TABLE audit_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT,
    user_name VARCHAR(255),
    action VARCHAR(100) NOT NULL,
    resource_type VARCHAR(50),
    resource_id BIGINT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Índices Importantes
```sql
-- Índices para rendimiento
CREATE INDEX idx_solicitudes_estado ON solicitudes_medicas(estado);
CREATE INDEX idx_solicitudes_prioridad ON solicitudes_medicas(prioridad_ia);
CREATE INDEX idx_solicitudes_fecha ON solicitudes_medicas(fecha_recepcion_email);
CREATE INDEX idx_audit_user_action ON audit_logs(user_id, action);
CREATE INDEX idx_audit_timestamp ON audit_logs(timestamp);
```

## Seguridad

### Autenticación y Autorización

#### Middleware de Seguridad
```php
// app/Http/Middleware/AuditMiddleware.php
public function handle($request, Closure $next)
{
    $response = $next($request);
    
    // Log all actions for audit
    AuditLog::logActivity(
        $request->route()->getActionName(),
        $request->route()->parameter('id'),
        $request->all(),
        $response->getStatusCode()
    );
    
    return $response;
}
```

#### Control de Roles
```php
// Verificación de roles en controladores
public function __construct()
{
    $this->middleware('auth');
    $this->middleware('role:administrador')->only(['index', 'create', 'store']);
    $this->middleware('role:medico')->only(['evaluate', 'update']);
}
```

### Protección de Datos

#### Encriptación de Datos Sensibles
```php
// Encriptación automática en modelos
protected $encrypted = [
    'paciente_identificacion',
    'paciente_telefono',
    'observaciones_medico'
];

public function setEncryptedAttribute($key, $value)
{
    $this->attributes[$key] = encrypt($value);
}
```

#### Validación de Entrada
```php
// app/Http/Requests/EvaluacionMedicaRequest.php
public function rules()
{
    return [
        'decision_medica' => 'required|in:aceptar,rechazar,solicitar_info',
        'observaciones_medico' => 'required|string|max:2000',
        'prioridad_medica' => 'nullable|in:Alta,Media,Baja'
    ];
}
```

### Auditoría Completa

#### Configuración de Auditoría
```php
// config/security.php
return [
    'audit' => [
        'enabled' => env('AUDIT_ENABLED', true),
        'log_requests' => true,
        'log_responses' => false,
        'exclude_routes' => ['api/health', 'api/status']
    ]
];
```

## Monitoreo y Logs

### Estructura de Logs

```
storage/logs/
├── laravel.log              # Logs generales de Laravel
├── audit.log               # Logs de auditoría
├── gmail_monitor.log       # Logs del monitor Gmail
├── ai_processing.log       # Logs de procesamiento IA
└── performance.log         # Logs de rendimiento
```

### Métricas del Sistema

#### Dashboard de Métricas
```php
// Métricas en tiempo real
$metrics = [
    'overview' => [
        'total_solicitudes' => SolicitudMedica::count(),
        'urgent_pending' => SolicitudMedica::urgentPending()->count(),
        'acceptance_rate' => $this->calculateAcceptanceRate()
    ],
    'performance' => [
        'avg_response_time' => $this->calculateAverageResponseTime(),
        'sla_compliance' => $this->calculateSLACompliance()
    ]
];
```

#### Alertas Automáticas
```php
// Sistema de alertas
if ($urgentCasesCount > 10) {
    $this->notificationService->sendSystemAlert([
        'type' => 'high_urgent_volume',
        'message' => "Alto volumen de casos urgentes: {$urgentCasesCount}",
        'severity' => 'high'
    ]);
}
```

## Mantenimiento

### Comandos Artisan Personalizados

#### Backup del Sistema
```bash
# Backup completo
php artisan system:backup --type=full --compress --encrypt

# Backup solo base de datos
php artisan system:backup --type=database

# Backup con retención personalizada
php artisan system:backup --retention=60
```

#### Limpieza de Datos
```bash
# Limpiar logs antiguos
php artisan logs:clean --days=30

# Limpiar notificaciones antiguas
php artisan notifications:clean --days=90

# Optimizar base de datos
php artisan db:optimize
```

#### Monitoreo del Sistema
```bash
# Estado del sistema
php artisan system:status

# Verificar configuración
php artisan config:verify

# Probar conexiones
php artisan system:test-connections
```

### Tareas Programadas

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Backup diario
    $schedule->command('system:backup --type=database')
             ->daily()
             ->at('02:00');
    
    // Limpieza semanal
    $schedule->command('logs:clean --days=30')
             ->weekly();
    
    // Métricas cada hora
    $schedule->command('metrics:calculate')
             ->hourly();
}
```

## Solución de Problemas

### Problemas Comunes

#### 1. Error de Conexión Gmail
```bash
# Verificar configuración
python ia/test_gmail_connection.py

# Logs específicos
tail -f ia/logs/gmail_monitor.log

# Solución común
# - Verificar App Password de Gmail
# - Confirmar acceso IMAP habilitado
# - Revisar firewall/proxy
```

#### 2. Fallo en Procesamiento IA
```bash
# Verificar dependencias Python
cd ia && python enhanced_setup.py test

# Verificar APIs Gemini
python test_gemini_connection.py

# Logs de IA
tail -f ia/logs/ai_processing.log
```

#### 3. Problemas de Rendimiento
```sql
-- Verificar consultas lentas
SHOW PROCESSLIST;

-- Analizar índices
EXPLAIN SELECT * FROM solicitudes_medicas WHERE estado = 'pendiente_evaluacion';

-- Optimizar tablas
OPTIMIZE TABLE solicitudes_medicas;
```

### Comandos de Diagnóstico

```bash
# Estado general del sistema
php artisan system:health-check

# Verificar permisos
php artisan system:check-permissions

# Probar notificaciones
php artisan notifications:test

# Verificar cola de trabajos
php artisan queue:work --once --verbose
```

### Contacto de Soporte

- **Email Técnico**: soporte-tecnico@vitalred.com
- **Documentación**: https://docs.vitalred.com
- **Issues**: https://github.com/vitalred/issues
- **Slack**: #vital-red-support

---

**Versión del Manual**: 1.0.0  
**Última Actualización**: 2024-01-15  
**Autor**: Equipo de Desarrollo Vital Red
