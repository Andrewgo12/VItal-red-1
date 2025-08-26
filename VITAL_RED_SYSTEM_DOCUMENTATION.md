# Sistema Vital Red - Documentación Completa

## Descripción General

El Sistema Vital Red es una plataforma integral de gestión de referencias médicas que utiliza inteligencia artificial para procesar automáticamente correos electrónicos médicos, extraer información clínica relevante, y priorizar casos según criterios médicos establecidos.

## Arquitectura del Sistema

### Componentes Principales

1. **Backend Laravel (PHP)**
   - API REST para gestión de datos
   - Sistema de autenticación y autorización
   - Dashboard administrativo y médico
   - Gestión de notificaciones

2. **Sistema de IA (Python)**
   - Procesamiento automático de emails
   - Análisis de texto médico con NLP
   - Clasificación de prioridades
   - Extracción de datos clínicos

3. **Base de Datos (MySQL)**
   - Almacenamiento de solicitudes médicas
   - Gestión de usuarios y roles
   - Auditoría y logs del sistema

## Funcionalidades Principales

### 1. Procesamiento Automático de Emails
- Monitoreo continuo de Gmail
- Filtrado de emails médicos
- Extracción de texto de adjuntos (PDF, DOC, imágenes)
- Análisis de contenido con IA

### 2. Análisis Médico Inteligente
- Extracción de datos del paciente
- Identificación de signos vitales
- Análisis de diagnósticos y síntomas
- Clasificación automática de urgencia

### 3. Sistema de Priorización
- Algoritmo de scoring de urgencia
- Clasificación en Alta, Media, Baja prioridad
- Criterios médicos específicos
- Notificaciones automáticas para casos urgentes

### 4. Dashboard Médico
- Bandeja de casos pendientes
- Evaluación médica de solicitudes
- Historial de decisiones
- Métricas y estadísticas

### 5. Sistema de Notificaciones
- Alertas en tiempo real
- Notificaciones por email
- Dashboard de notificaciones
- Escalamiento automático

## Instalación y Configuración

### Requisitos del Sistema

#### Backend Laravel
- PHP 8.1 o superior
- Composer
- MySQL 8.0 o superior
- Extensiones PHP: mbstring, xml, bcmath, json, openssl

#### Sistema de IA
- Python 3.8 o superior
- pip (gestor de paquetes Python)
- Acceso a Gmail con contraseña de aplicación
- APIs de Gemini AI (opcional)

### Instalación Paso a Paso

#### 1. Configuración del Backend Laravel

```bash
# Clonar el repositorio
git clone [repository-url]
cd vital-red

# Instalar dependencias PHP
composer install

# Configurar archivo .env
cp .env.example .env
# Editar .env con configuraciones de base de datos

# Generar clave de aplicación
php artisan key:generate

# Ejecutar migraciones
php artisan migrate

# Crear usuario administrador
php artisan db:seed --class=AdminUserSeeder

# Iniciar servidor de desarrollo
php artisan serve
```

#### 2. Configuración del Sistema de IA

```bash
# Navegar al directorio de IA
cd ia

# Ejecutar setup automático
python enhanced_setup.py install

# O instalación manual
pip install -r requirements.txt

# Configurar credenciales
cp config/config_template.json config/config.json
# Editar config.json con credenciales de Gmail y APIs
```

#### 3. Variables de Entorno

Crear archivo `.env` en la raíz del proyecto:

```env
# Base de datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vital_red
DB_USERNAME=root
DB_PASSWORD=

# Gmail
GMAIL_EMAIL=your-email@gmail.com
GMAIL_APP_PASSWORD=your-app-password

# Gemini AI
GEMINI_API_KEY_1=your-gemini-key-1
GEMINI_API_KEY_2=your-gemini-key-2

# Laravel API
LARAVEL_API_URL=http://localhost:8000/api
LARAVEL_API_TOKEN=

# Seguridad
AUDIT_ENABLED=true
ENCRYPT_SENSITIVE_DATA=true
```

## Uso del Sistema

### 1. Acceso al Dashboard

- **URL**: `http://localhost:8000`
- **Administrador**: admin@hospital.com / password
- **Médico**: medico@hospital.com / password

### 2. Monitoreo de Gmail

```bash
# Iniciar servicio de monitoreo
cd ia
python gmail_monitor_service.py start

# Procesar email individual
python process_single_email.py --email-id [EMAIL_ID]

# Procesar archivo .eml
python process_single_email.py --file-path email.eml
```

### 3. Evaluación Médica

1. Acceder al dashboard médico
2. Revisar bandeja de casos
3. Evaluar solicitudes pendientes
4. Tomar decisiones (Aceptar/Rechazar/Solicitar Info)

## Estructura de Archivos

```
vital-red/
├── app/                          # Aplicación Laravel
│   ├── Http/Controllers/         # Controladores
│   ├── Models/                   # Modelos de datos
│   ├── Services/                 # Servicios de negocio
│   └── Http/Middleware/          # Middleware de seguridad
├── database/                     # Migraciones y seeders
├── resources/views/              # Vistas Blade
├── ia/                          # Sistema de IA
│   ├── Functions/               # Módulos de procesamiento
│   ├── gmail_monitor_service.py # Servicio principal
│   ├── process_single_email.py # Procesador individual
│   └── enhanced_setup.py       # Script de instalación
├── config/                      # Configuraciones
└── storage/                     # Almacenamiento temporal
```

## API Endpoints

### Autenticación
- `POST /api/login` - Iniciar sesión
- `POST /api/logout` - Cerrar sesión

### Solicitudes Médicas
- `GET /api/solicitudes-medicas` - Listar solicitudes
- `POST /api/solicitudes-medicas` - Crear solicitud
- `PUT /api/solicitudes-medicas/{id}/evaluar` - Evaluar solicitud

### Gmail Monitor
- `GET /api/gmail-monitor/status` - Estado del servicio
- `POST /api/gmail-monitor/start` - Iniciar monitoreo
- `POST /api/gmail-monitor/stop` - Detener monitoreo

### Métricas
- `GET /api/metrics/dashboard` - Métricas del dashboard
- `GET /api/metrics/detailed` - Métricas detalladas

## Seguridad

### Control de Acceso
- Autenticación basada en roles
- Middleware de autorización
- Auditoría completa de acciones

### Protección de Datos
- Encriptación de datos sensibles
- Logs de auditoría inmutables
- Cumplimiento HIPAA/GDPR

### Monitoreo
- Logs de seguridad
- Detección de actividad sospechosa
- Alertas automáticas

## Mantenimiento

### Logs del Sistema
- Laravel: `storage/logs/laravel.log`
- IA: `ia/logs/gmail_monitor.log`
- Auditoría: `storage/logs/audit.log`

### Respaldos
- Base de datos: Configurar respaldos automáticos
- Archivos: Respaldar directorio `storage/`
- Configuraciones: Respaldar archivos `.env`

### Monitoreo de Rendimiento
- Métricas de procesamiento
- Tiempos de respuesta
- Uso de recursos

## Solución de Problemas

### Problemas Comunes

1. **Error de conexión Gmail**
   - Verificar credenciales
   - Habilitar acceso de aplicaciones menos seguras
   - Usar contraseña de aplicación

2. **Fallo en procesamiento de IA**
   - Verificar dependencias Python
   - Revisar logs de error
   - Comprobar APIs de Gemini

3. **Problemas de base de datos**
   - Verificar conexión MySQL
   - Ejecutar migraciones pendientes
   - Revisar permisos de usuario

### Comandos de Diagnóstico

```bash
# Verificar estado del sistema
php artisan system:status

# Probar conexión de base de datos
php artisan migrate:status

# Verificar configuración de IA
cd ia && python enhanced_setup.py test

# Limpiar caché
php artisan cache:clear
php artisan config:clear
```

## Contacto y Soporte

Para soporte técnico o consultas sobre el sistema:
- Email: soporte@vitalred.com
- Documentación: [URL de documentación]
- Issues: [URL de repositorio]/issues

## Licencia

Este sistema está desarrollado para uso hospitalario y médico. Todos los derechos reservados.
