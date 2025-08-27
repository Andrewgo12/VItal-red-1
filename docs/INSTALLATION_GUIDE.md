# 🚀 Guía de Instalación - Vital Red

Esta guía te ayudará a instalar y configurar el sistema Vital Red paso a paso.

## 📋 Requisitos del Sistema

### Requisitos Mínimos

- **PHP**: 8.2 o superior
- **Composer**: 2.0 o superior
- **Node.js**: 18.0 o superior
- **MySQL**: 8.0 o superior
- **Memoria RAM**: 2GB mínimo, 4GB recomendado
- **Espacio en disco**: 5GB mínimo

### Requisitos Opcionales

- **Redis**: 6.0 o superior (para cache y colas)
- **Python**: 3.9 o superior (para servicios de IA)
- **Supervisor**: Para gestión de procesos en producción

## 🛠️ Instalación Automática (Recomendada)

### 1. Clonar el Repositorio

```bash
git clone https://github.com/tu-usuario/vital-red.git
cd vital-red
```

### 2. Ejecutar Script de Instalación

```bash
# En Linux/Mac
chmod +x install.sh
./install.sh

# En Windows (PowerShell)
powershell -ExecutionPolicy Bypass -File install.ps1
```

El script automáticamente:
- ✅ Verificará los requisitos del sistema
- ✅ Instalará dependencias PHP y Node.js
- ✅ Configurará el archivo .env
- ✅ Configurará la base de datos
- ✅ Ejecutará migraciones y seeders
- ✅ Compilará los assets frontend
- ✅ Configurará permisos

### 3. Acceder al Sistema

Una vez completada la instalación:

```bash
php artisan serve
```

Visita: http://localhost:8000

**Credenciales por defecto:**
- **Administrador**: admin@vitalred.com / admin123
- **Médico Demo**: medico@vitalred.com / medico123

## 🔧 Instalación Manual

### Paso 1: Preparar el Entorno

```bash
# Clonar repositorio
git clone https://github.com/tu-usuario/vital-red.git
cd vital-red

# Instalar dependencias PHP
composer install

# Instalar dependencias Node.js
npm install
```

### Paso 2: Configurar Base de Datos

```bash
# Crear base de datos MySQL
mysql -u root -p
CREATE DATABASE vital_red CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### Paso 3: Configurar Variables de Entorno

```bash
# Copiar archivo de configuración
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate
```

Editar `.env` con tu configuración:

```env
APP_NAME="Vital Red"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vital_red
DB_USERNAME=root
DB_PASSWORD=tu_password
```

### Paso 4: Configurar Base de Datos

```bash
# Ejecutar migraciones
php artisan migrate

# Ejecutar seeders (datos iniciales)
php artisan db:seed
```

### Paso 5: Configurar Storage

```bash
# Crear enlace simbólico para storage
php artisan storage:link

# Configurar permisos (Linux/Mac)
chmod -R 755 storage bootstrap/cache
```

### Paso 6: Compilar Assets

```bash
# Para desarrollo
npm run dev

# Para producción
npm run build
```

### Paso 7: Iniciar Servidor

```bash
# Servidor de desarrollo
php artisan serve

# El sistema estará disponible en http://localhost:8000
```

## 🐳 Instalación con Docker

### Usando Docker Compose

```bash
# Clonar repositorio
git clone https://github.com/tu-usuario/vital-red.git
cd vital-red

# Construir y ejecutar contenedores
docker-compose up -d

# Ejecutar configuración inicial
docker-compose exec app php artisan vitalred:setup
```

### Dockerfile Personalizado

```dockerfile
FROM php:8.2-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm

# Instalar extensiones PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar directorio de trabajo
WORKDIR /var/www

# Copiar archivos
COPY . .

# Instalar dependencias
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

# Configurar permisos
RUN chown -R www-data:www-data /var/www
RUN chmod -R 755 /var/www/storage

EXPOSE 9000
CMD ["php-fpm"]
```

## ⚙️ Configuración Avanzada

### Configurar Gmail API

1. **Crear proyecto en Google Cloud Console**
   - Ir a https://console.cloud.google.com
   - Crear nuevo proyecto o seleccionar existente
   - Habilitar Gmail API

2. **Crear credenciales OAuth 2.0**
   - Ir a "Credenciales" > "Crear credenciales" > "ID de cliente OAuth 2.0"
   - Tipo de aplicación: "Aplicación web"
   - URIs de redirección autorizados: `http://localhost:8000/auth/gmail/callback`

3. **Configurar en .env**
   ```env
   GMAIL_ENABLED=true
   GMAIL_EMAIL=tu-email@gmail.com
   GMAIL_CREDENTIALS_PATH=storage/app/gmail-credentials.json
   ```

4. **Descargar archivo de credenciales**
   - Descargar el archivo JSON de credenciales
   - Guardarlo como `storage/app/gmail-credentials.json`

### Configurar Gemini AI

1. **Obtener API Key**
   - Ir a https://makersuite.google.com/app/apikey
   - Crear nueva API key

2. **Configurar en .env**
   ```env
   GEMINI_ENABLED=true
   GEMINI_API_KEY=tu-api-key
   GEMINI_MODEL=gemini-pro
   ```

### Configurar Redis (Opcional)

```bash
# Instalar Redis (Ubuntu/Debian)
sudo apt install redis-server

# Configurar en .env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Configurar Supervisor (Producción)

```bash
# Instalar Supervisor
sudo apt install supervisor

# Crear configuración
sudo cp deploy/supervisor-vitalred.conf /etc/supervisor/conf.d/

# Actualizar Supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start vitalred:*
```

## 🔒 Configuración de Seguridad

### SSL/HTTPS (Producción)

```bash
# Instalar Certbot
sudo apt install certbot python3-certbot-nginx

# Obtener certificado SSL
sudo certbot --nginx -d tu-dominio.com

# Configurar renovación automática
sudo crontab -e
# Agregar: 0 12 * * * /usr/bin/certbot renew --quiet
```

### Firewall

```bash
# Configurar UFW (Ubuntu)
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

### Permisos de Archivos

```bash
# Configurar permisos correctos
sudo chown -R www-data:www-data /var/www/vital-red
sudo chmod -R 755 /var/www/vital-red
sudo chmod -R 775 /var/www/vital-red/storage
sudo chmod -R 775 /var/www/vital-red/bootstrap/cache
```

## 🧪 Verificación de Instalación

### Comando de Verificación

```bash
# Verificar estado del sistema
php artisan vitalred:status

# Probar conexiones
php artisan config:test-connections

# Verificar permisos
php artisan config:check-permissions
```

### Tests Automatizados

```bash
# Ejecutar tests
php artisan test

# Tests con cobertura
php artisan test --coverage
```

## 🚨 Solución de Problemas

### Error: "Class not found"

```bash
# Regenerar autoload
composer dump-autoload

# Limpiar cache
php artisan cache:clear
php artisan config:clear
```

### Error: "Permission denied"

```bash
# Corregir permisos
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Error: "Connection refused" (Base de datos)

```bash
# Verificar estado de MySQL
sudo systemctl status mysql

# Reiniciar MySQL
sudo systemctl restart mysql

# Verificar configuración en .env
```

### Error: "Node.js not found"

```bash
# Instalar Node.js (Ubuntu/Debian)
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Verificar instalación
node --version
npm --version
```

## 📊 Monitoreo Post-Instalación

### Logs del Sistema

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Ver logs de workers
tail -f storage/logs/worker.log

# Ver logs de Gmail
tail -f storage/logs/gmail-monitor.log
```

### Métricas del Sistema

```bash
# Estado de workers
supervisorctl status vitalred:*

# Estado de servicios
systemctl status nginx mysql redis-server

# Uso de recursos
htop
df -h
```

## 🔄 Actualizaciones

### Actualizar Sistema

```bash
# Hacer backup
php artisan backup:run

# Actualizar código
git pull origin main

# Actualizar dependencias
composer install --no-dev --optimize-autoloader
npm install && npm run build

# Ejecutar migraciones
php artisan migrate

# Limpiar cache
php artisan cache:clear
php artisan config:cache

# Reiniciar workers
supervisorctl restart vitalred:*
```

## 📞 Soporte

Si encuentras problemas durante la instalación:

- **Documentación**: Revisa la documentación completa en `docs/`
- **Issues**: Reporta problemas en GitHub Issues
- **Email**: soporte@vitalred.com
- **Logs**: Siempre incluye los logs relevantes al reportar problemas

## ✅ Checklist de Instalación

- [ ] Requisitos del sistema verificados
- [ ] Repositorio clonado
- [ ] Dependencias PHP instaladas
- [ ] Dependencias Node.js instaladas
- [ ] Base de datos configurada
- [ ] Variables de entorno configuradas
- [ ] Migraciones ejecutadas
- [ ] Seeders ejecutados
- [ ] Storage configurado
- [ ] Assets compilados
- [ ] Permisos configurados
- [ ] Servidor iniciado
- [ ] Login exitoso con credenciales por defecto
- [ ] Gmail API configurada (opcional)
- [ ] Gemini AI configurada (opcional)
- [ ] Redis configurado (opcional)
- [ ] Supervisor configurado (producción)
- [ ] SSL configurado (producción)
- [ ] Firewall configurado (producción)
- [ ] Monitoreo configurado
- [ ] Backups configurados

¡Felicidades! 🎉 Tu instalación de Vital Red está completa y lista para usar.
