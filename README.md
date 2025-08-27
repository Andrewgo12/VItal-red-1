# 🏥 Vital Red - Sistema de Gestión Médica

Sistema integral de gestión médica con inteligencia artificial para la evaluación y priorización de solicitudes médicas.

## 🚀 Características Principales

- **Gestión de Solicitudes Médicas**: Recepción, evaluación y seguimiento de casos médicos
- **Inteligencia Artificial**: Clasificación automática de prioridades usando Gemini AI
- **Monitoreo de Gmail**: Procesamiento automático de emails médicos
- **Dashboard Interactivo**: Métricas en tiempo real y análisis de tendencias
- **Sistema de Roles**: Administradores y médicos con permisos específicos
- **Notificaciones**: Sistema de alertas para casos urgentes
- **Reportes**: Generación de reportes detallados y exportación de datos
- **API REST**: Endpoints completos para integración externa

## 🛠️ Tecnologías Utilizadas

### Backend
- **Laravel 11**: Framework PHP moderno
- **MySQL**: Base de datos relacional
- **Redis**: Cache y colas de trabajo
- **Laravel Sanctum**: Autenticación API

### Frontend
- **Inertia.js**: SPA sin API
- **Vue.js 3**: Framework JavaScript reactivo
- **Bootstrap 5**: Framework CSS
- **Chart.js**: Gráficos interactivos

### Inteligencia Artificial
- **Python**: Servicios de IA
- **Google Gemini AI**: Análisis de texto médico
- **Gmail API**: Monitoreo de correos

## 📋 Requisitos del Sistema

- **PHP**: >= 8.2
- **Composer**: >= 2.0
- **Node.js**: >= 18.0
- **MySQL**: >= 8.0
- **Redis**: >= 6.0 (opcional)
- **Python**: >= 3.9 (para servicios de IA)

## 🔧 Instalación Rápida

### 1. Clonar el Repositorio
```bash
git clone https://github.com/tu-usuario/vital-red.git
cd vital-red
```

### 2. Instalar Dependencias PHP
```bash
composer install
```

### 3. Instalar Dependencias JavaScript
```bash
npm install
```

### 4. Configurar Entorno
```bash
cp .env.example .env
# Editar .env con tu configuración de base de datos
```

### 5. Configuración Automática
```bash
php artisan vitalred:setup
```

### 6. Compilar Assets
```bash
npm run build
```

### 7. Iniciar Servidor
```bash
php artisan serve
```

## 🔐 Acceso al Sistema

Después de la instalación, puedes acceder con:

| Rol | Email | Contraseña |
|-----|-------|------------|
| Administrador | admin@vitalred.com | admin123 |
| Médico Demo | medico@vitalred.com | medico123 |

⚠️ **IMPORTANTE**: Cambia estas contraseñas antes de usar en producción.

## 📁 Estructura del Proyecto

```
vital-red/
├── app/
│   ├── Http/Controllers/     # Controladores
│   ├── Models/              # Modelos Eloquent
│   ├── Services/            # Servicios de negocio
│   └── Console/Commands/    # Comandos Artisan
├── database/
│   ├── migrations/          # Migraciones de BD
│   └── seeders/            # Datos iniciales
├── resources/
│   ├── js/                 # Componentes Vue.js
│   └── views/              # Plantillas Blade
├── routes/
│   ├── web.php             # Rutas web
│   └── api.php             # Rutas API
├── ia/                     # Servicios Python IA
│   ├── main.py
│   ├── gmail_processor.py
│   └── medical_analyzer.py
└── public/                 # Assets públicos
```

## 🔧 Configuración Avanzada

### Variables de Entorno Importantes

```env
# Aplicación
APP_NAME="Vital Red"
APP_URL=http://localhost:8000

# Base de Datos
DB_CONNECTION=mysql
DB_DATABASE=vital_red
DB_USERNAME=root
DB_PASSWORD=

# Gmail API
GMAIL_ENABLED=true
GMAIL_EMAIL=tu-email@gmail.com
GMAIL_CREDENTIALS_PATH=storage/app/gmail-credentials.json

# Gemini AI
GEMINI_ENABLED=true
GEMINI_API_KEY=tu-api-key
GEMINI_MODEL=gemini-pro

# Notificaciones
NOTIFICATION_URGENT_THRESHOLD=2
NOTIFICATION_CHANNELS=email,internal
```

### Configurar Gmail API

1. Crear proyecto en Google Cloud Console
2. Habilitar Gmail API
3. Crear credenciales OAuth 2.0
4. Descargar archivo JSON y colocarlo en `storage/app/gmail-credentials.json`
5. Configurar variables GMAIL_* en .env

### Configurar Gemini AI

1. Obtener API key de Google AI Studio
2. Configurar GEMINI_API_KEY en .env
3. Ajustar GEMINI_MODEL según necesidades

## 🚀 Despliegue en Producción

### 1. Servidor Web
```bash
# Optimizar para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
```

### 2. Configurar Colas
```bash
# Instalar supervisor (Ubuntu/Debian)
sudo apt install supervisor

# Configurar worker
sudo nano /etc/supervisor/conf.d/vital-red-worker.conf
```

Contenido del archivo supervisor:
```ini
[program:vital-red-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/vital-red/artisan queue:work redis --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/vital-red/storage/logs/worker.log
```

### 3. Configurar Cron
```bash
# Agregar a crontab
* * * * * cd /path/to/vital-red && php artisan schedule:run >> /dev/null 2>&1
```

## 📊 Uso del Sistema

### Para Administradores

1. **Gestión de Usuarios**: Crear, editar y gestionar médicos
2. **Configuración del Sistema**: Ajustar parámetros de IA y notificaciones
3. **Reportes**: Generar análisis de rendimiento y tendencias
4. **Monitoreo**: Supervisar métricas del sistema en tiempo real

### Para Médicos

1. **Bandeja de Casos**: Revisar solicitudes pendientes
2. **Evaluación**: Evaluar casos con herramientas de IA
3. **Seguimiento**: Monitorear casos evaluados
4. **Dashboard**: Ver métricas personales de rendimiento

## 🔌 API REST

El sistema incluye una API REST completa:

```bash
# Autenticación
POST /api/auth/login
POST /api/auth/logout

# Solicitudes médicas
GET /api/solicitudes-medicas
POST /api/solicitudes-medicas
PUT /api/solicitudes-medicas/{id}

# Usuarios
GET /api/users
POST /api/users
PUT /api/users/{id}

# Métricas
GET /api/metrics/dashboard
GET /api/metrics/detailed
```

Documentación completa de API disponible en `/api/documentation`

## 🧪 Testing

```bash
# Ejecutar tests
php artisan test

# Tests con cobertura
php artisan test --coverage

# Tests específicos
php artisan test --filter=SolicitudMedicaTest
```

## 🐛 Troubleshooting

### Problemas Comunes

1. **Error de permisos**:
   ```bash
   sudo chown -R www-data:www-data storage bootstrap/cache
   sudo chmod -R 755 storage bootstrap/cache
   ```

2. **Error de memoria PHP**:
   ```bash
   # Aumentar en php.ini
   memory_limit = 512M
   ```

3. **Error de conexión a BD**:
   - Verificar credenciales en .env
   - Asegurar que MySQL esté ejecutándose
   - Crear base de datos manualmente si es necesario

4. **Error de Gmail API**:
   - Verificar credenciales JSON
   - Comprobar permisos de API
   - Revisar logs en storage/logs/

## 📝 Contribución

1. Fork el proyecto
2. Crear rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver archivo `LICENSE` para más detalles.

## 🆘 Soporte

- **Documentación**: [docs/](docs/)
- **Issues**: [GitHub Issues](https://github.com/tu-usuario/vital-red/issues)
- **Email**: soporte@vitalred.com

## 🙏 Agradecimientos

- Laravel Framework
- Vue.js Community
- Google AI Team
- Todos los contribuidores

---

**Vital Red** - Transformando la gestión médica con inteligencia artificial 🏥✨
