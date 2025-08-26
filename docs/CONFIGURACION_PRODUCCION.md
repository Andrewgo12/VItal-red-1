# Configuración del Entorno de Producción - Sistema Vital Red

## Tabla de Contenidos

1. [Requisitos del Servidor](#requisitos-del-servidor)
2. [Instalación XAMPP](#instalación-xampp)
3. [Configuración de Seguridad](#configuración-de-seguridad)
4. [Configuración de Base de Datos](#configuración-de-base-de-datos)
5. [Configuración de PHP](#configuración-de-php)
6. [Configuración de Apache](#configuración-de-apache)
7. [Configuración SSL/HTTPS](#configuración-ssl-https)
8. [Configuración del Sistema IA](#configuración-del-sistema-ia)
9. [Configuración de Backup](#configuración-de-backup)
10. [Monitoreo y Mantenimiento](#monitoreo-y-mantenimiento)

## Requisitos del Servidor

### Hardware Mínimo Recomendado
- **CPU**: Intel Core i5 o AMD Ryzen 5 (4 núcleos)
- **RAM**: 8 GB mínimo, 16 GB recomendado
- **Almacenamiento**: 500 GB SSD
- **Red**: Conexión Gigabit Ethernet

### Hardware Óptimo para Alto Volumen
- **CPU**: Intel Core i7 o AMD Ryzen 7 (8 núcleos)
- **RAM**: 32 GB
- **Almacenamiento**: 1 TB SSD NVMe + 2 TB HDD para backups
- **Red**: Conexión redundante Gigabit

### Sistema Operativo
- **Windows Server 2019/2022** (recomendado)
- **Windows 10/11 Pro** (para instalaciones pequeñas)
- **Actualizaciones**: Mantener sistema actualizado

### Software Base Requerido
- XAMPP 8.1.x o superior
- Python 3.8+ con pip
- Git para control de versiones
- Antivirus empresarial
- Software de backup

## Instalación XAMPP

### Descarga e Instalación

1. **Descargar XAMPP**
   ```
   URL: https://www.apachefriends.org/download.html
   Versión: 8.1.25 o superior
   Arquitectura: 64-bit
   ```

2. **Ejecutar Instalador**
   - Ejecutar como Administrador
   - Seleccionar componentes:
     ✅ Apache
     ✅ MySQL
     ✅ PHP
     ✅ phpMyAdmin
     ❌ Mercury (no necesario)
     ❌ Tomcat (no necesario)

3. **Directorio de Instalación**
   ```
   Recomendado: C:\xampp
   Evitar: Directorios con espacios o caracteres especiales
   ```

### Configuración Inicial XAMPP

#### Iniciar Servicios
```batch
# Abrir XAMPP Control Panel como Administrador
# Instalar servicios:
- Apache: Install as Service
- MySQL: Install as Service

# Configurar inicio automático:
- Apache: Start
- MySQL: Start
```

#### Verificar Instalación
```
URL de prueba: http://localhost
Debe mostrar: XAMPP Dashboard
```

## Configuración de Seguridad

### Seguridad de MySQL

1. **Ejecutar Script de Seguridad**
   ```sql
   # Abrir MySQL Command Line
   mysql -u root -p
   
   # Cambiar contraseña root
   ALTER USER 'root'@'localhost' IDENTIFIED BY 'ContraseñaSegura123!';
   
   # Eliminar usuarios anónimos
   DELETE FROM mysql.user WHERE User='';
   
   # Eliminar base de datos de prueba
   DROP DATABASE IF EXISTS test;
   
   # Recargar privilegios
   FLUSH PRIVILEGES;
   ```

2. **Crear Usuario para Aplicación**
   ```sql
   CREATE USER 'vitalred_user'@'localhost' IDENTIFIED BY 'VitalRed2024!';
   CREATE DATABASE vital_red CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   GRANT ALL PRIVILEGES ON vital_red.* TO 'vitalred_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

### Seguridad de Apache

1. **Configurar .htaccess**
   ```apache
   # Archivo: C:\xampp\htdocs\vital-red\.htaccess
   
   # Ocultar información del servidor
   ServerTokens Prod
   ServerSignature Off
   
   # Prevenir acceso a archivos sensibles
   <Files ".env">
       Order allow,deny
       Deny from all
   </Files>
   
   <Files "*.log">
       Order allow,deny
       Deny from all
   </Files>
   
   # Configurar headers de seguridad
   Header always set X-Content-Type-Options nosniff
   Header always set X-Frame-Options DENY
   Header always set X-XSS-Protection "1; mode=block"
   Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
   ```

2. **Configurar httpd.conf**
   ```apache
   # Archivo: C:\xampp\apache\conf\httpd.conf
   
   # Ocultar versión de Apache
   ServerTokens Prod
   ServerSignature Off
   
   # Deshabilitar listado de directorios
   Options -Indexes
   
   # Configurar límites
   LimitRequestBody 10485760  # 10MB
   Timeout 60
   KeepAliveTimeout 5
   ```

### Firewall de Windows

1. **Configurar Reglas de Entrada**
   ```powershell
   # Ejecutar como Administrador
   
   # Permitir Apache (Puerto 80)
   New-NetFirewallRule -DisplayName "Apache HTTP" -Direction Inbound -Protocol TCP -LocalPort 80 -Action Allow
   
   # Permitir Apache HTTPS (Puerto 443)
   New-NetFirewallRule -DisplayName "Apache HTTPS" -Direction Inbound -Protocol TCP -LocalPort 443 -Action Allow
   
   # Bloquear MySQL desde exterior (solo localhost)
   New-NetFirewallRule -DisplayName "Block MySQL External" -Direction Inbound -Protocol TCP -LocalPort 3306 -Action Block -RemoteAddress Any
   ```

## Configuración de Base de Datos

### Optimización MySQL

1. **Configurar my.ini**
   ```ini
   # Archivo: C:\xampp\mysql\bin\my.ini
   
   [mysqld]
   # Configuración básica
   port = 3306
   socket = /tmp/mysql.sock
   
   # Configuración de memoria
   innodb_buffer_pool_size = 2G
   innodb_log_file_size = 256M
   innodb_log_buffer_size = 16M
   
   # Configuración de conexiones
   max_connections = 200
   max_connect_errors = 10000
   
   # Configuración de seguridad
   bind-address = 127.0.0.1
   skip-networking = false
   
   # Configuración de logs
   log-error = C:/xampp/mysql/data/mysql_error.log
   slow_query_log = 1
   slow_query_log_file = C:/xampp/mysql/data/mysql_slow.log
   long_query_time = 2
   
   # Configuración de charset
   character-set-server = utf8mb4
   collation-server = utf8mb4_unicode_ci
   ```

2. **Crear Índices Optimizados**
   ```sql
   USE vital_red;
   
   -- Índices para solicitudes_medicas
   CREATE INDEX idx_estado_prioridad ON solicitudes_medicas(estado, prioridad_ia);
   CREATE INDEX idx_fecha_recepcion ON solicitudes_medicas(fecha_recepcion_email);
   CREATE INDEX idx_especialidad ON solicitudes_medicas(especialidad_solicitada);
   CREATE INDEX idx_medico_evaluador ON solicitudes_medicas(medico_evaluador_id);
   
   -- Índices para audit_logs
   CREATE INDEX idx_user_timestamp ON audit_logs(user_id, timestamp);
   CREATE INDEX idx_action_timestamp ON audit_logs(action, timestamp);
   
   -- Índices para notificaciones
   CREATE INDEX idx_estado_fecha ON notificaciones_internas(estado, created_at);
   ```

## Configuración de PHP

### Configurar php.ini

```ini
# Archivo: C:\xampp\php\php.ini

# Configuración básica
max_execution_time = 300
max_input_time = 300
memory_limit = 512M
post_max_size = 50M
upload_max_filesize = 50M

# Configuración de sesiones
session.gc_maxlifetime = 28800  # 8 horas
session.cookie_httponly = 1
session.cookie_secure = 1  # Solo con HTTPS
session.use_strict_mode = 1

# Configuración de errores (producción)
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = C:/xampp/php/logs/php_errors.log

# Configuración de seguridad
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off

# Extensiones requeridas
extension=curl
extension=fileinfo
extension=gd
extension=mbstring
extension=openssl
extension=pdo_mysql
extension=zip

# Configuración de timezone
date.timezone = "America/Bogota"
```

### Configurar Composer

```batch
# Descargar e instalar Composer
# URL: https://getcomposer.org/download/

# Verificar instalación
composer --version

# Configurar variables de entorno
set PATH=%PATH%;C:\composer
```

## Configuración de Apache

### Virtual Host para Vital Red

```apache
# Archivo: C:\xampp\apache\conf\extra\httpd-vhosts.conf

<VirtualHost *:80>
    ServerName vitalred.hospital.local
    DocumentRoot "C:/xampp/htdocs/vital-red/public"
    
    <Directory "C:/xampp/htdocs/vital-red/public">
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>
    
    # Logs
    ErrorLog "C:/xampp/apache/logs/vitalred_error.log"
    CustomLog "C:/xampp/apache/logs/vitalred_access.log" combined
    
    # Redirección a HTTPS
    Redirect permanent / https://vitalred.hospital.local/
</VirtualHost>

<VirtualHost *:443>
    ServerName vitalred.hospital.local
    DocumentRoot "C:/xampp/htdocs/vital-red/public"
    
    # Configuración SSL
    SSLEngine on
    SSLCertificateFile "C:/xampp/apache/conf/ssl.crt/vitalred.crt"
    SSLCertificateKeyFile "C:/xampp/apache/conf/ssl.key/vitalred.key"
    
    <Directory "C:/xampp/htdocs/vital-red/public">
        AllowOverride All
        Require all granted
        Options -Indexes
        
        # Headers de seguridad
        Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
        Header always set X-Content-Type-Options nosniff
        Header always set X-Frame-Options DENY
        Header always set X-XSS-Protection "1; mode=block"
    </Directory>
    
    # Logs
    ErrorLog "C:/xampp/apache/logs/vitalred_ssl_error.log"
    CustomLog "C:/xampp/apache/logs/vitalred_ssl_access.log" combined
</VirtualHost>
```

### Configurar Hosts File

```
# Archivo: C:\Windows\System32\drivers\etc\hosts
# Agregar línea:

127.0.0.1    vitalred.hospital.local
```

## Configuración SSL/HTTPS

### Generar Certificado SSL

```batch
# Navegar a directorio OpenSSL
cd C:\xampp\apache\bin

# Generar clave privada
openssl genrsa -out vitalred.key 2048

# Generar solicitud de certificado
openssl req -new -key vitalred.key -out vitalred.csr
# Completar información:
# Country: CO
# State: Bogota
# City: Bogota
# Organization: Hospital
# Unit: IT
# Common Name: vitalred.hospital.local

# Generar certificado autofirmado
openssl x509 -req -days 365 -in vitalred.csr -signkey vitalred.key -out vitalred.crt

# Mover archivos
move vitalred.crt C:\xampp\apache\conf\ssl.crt\
move vitalred.key C:\xampp\apache\conf\ssl.key\
```

### Habilitar SSL en Apache

```apache
# Archivo: C:\xampp\apache\conf\httpd.conf
# Descomentar líneas:

LoadModule ssl_module modules/mod_ssl.so
Include conf/extra/httpd-ssl.conf
```

## Configuración del Sistema IA

### Instalación Python y Dependencias

```batch
# Verificar Python
python --version

# Navegar a directorio IA
cd C:\xampp\htdocs\vital-red\ia

# Crear entorno virtual
python -m venv venv

# Activar entorno virtual
venv\Scripts\activate

# Instalar dependencias
pip install -r requirements.txt

# Configurar variables de entorno
set PYTHONPATH=C:\xampp\htdocs\vital-red\ia
```

### Configurar Servicio de Monitoreo

```batch
# Crear script de inicio: start_gmail_monitor.bat

@echo off
cd C:\xampp\htdocs\vital-red\ia
call venv\Scripts\activate
python gmail_monitor_service.py start
pause
```

### Configurar Tarea Programada

```powershell
# Crear tarea programada para inicio automático
$action = New-ScheduledTaskAction -Execute "C:\xampp\htdocs\vital-red\ia\start_gmail_monitor.bat"
$trigger = New-ScheduledTaskTrigger -AtStartup
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount

Register-ScheduledTask -TaskName "VitalRed Gmail Monitor" -Action $action -Trigger $trigger -Settings $settings -Principal $principal
```

## Configuración de Backup

### Script de Backup Automático

```batch
# Archivo: backup_vitalred.bat

@echo off
set BACKUP_DIR=D:\Backups\VitalRed
set DATE=%date:~-4,4%%date:~-10,2%%date:~-7,2%
set TIME=%time:~0,2%%time:~3,2%%time:~6,2%
set TIMESTAMP=%DATE%_%TIME%

# Crear directorio de backup
mkdir "%BACKUP_DIR%\%TIMESTAMP%"

# Backup de base de datos
C:\xampp\mysql\bin\mysqldump -u root -p"ContraseñaSegura123!" vital_red > "%BACKUP_DIR%\%TIMESTAMP%\database.sql"

# Backup de archivos
xcopy "C:\xampp\htdocs\vital-red" "%BACKUP_DIR%\%TIMESTAMP%\files\" /E /I /H

# Backup de logs
xcopy "C:\xampp\apache\logs" "%BACKUP_DIR%\%TIMESTAMP%\logs\" /E /I

# Comprimir backup
powershell Compress-Archive -Path "%BACKUP_DIR%\%TIMESTAMP%\*" -DestinationPath "%BACKUP_DIR%\backup_%TIMESTAMP%.zip"

# Limpiar archivos temporales
rmdir /s /q "%BACKUP_DIR%\%TIMESTAMP%"

# Limpiar backups antiguos (más de 30 días)
forfiles /p "%BACKUP_DIR%" /s /m *.zip /d -30 /c "cmd /c del @path"

echo Backup completado: backup_%TIMESTAMP%.zip
```

### Programar Backup Diario

```powershell
# Crear tarea programada para backup diario
$action = New-ScheduledTaskAction -Execute "C:\xampp\htdocs\vital-red\backup_vitalred.bat"
$trigger = New-ScheduledTaskTrigger -Daily -At "02:00AM"
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries

Register-ScheduledTask -TaskName "VitalRed Daily Backup" -Action $action -Trigger $trigger -Settings $settings
```

## Monitoreo y Mantenimiento

### Script de Monitoreo del Sistema

```batch
# Archivo: monitor_system.bat

@echo off
echo === VITAL RED SYSTEM MONITOR ===
echo.

# Verificar servicios Apache y MySQL
sc query Apache2.4 | find "RUNNING" >nul
if %errorlevel%==0 (
    echo [OK] Apache está ejecutándose
) else (
    echo [ERROR] Apache no está ejecutándose
    net start Apache2.4
)

sc query mysql | find "RUNNING" >nul
if %errorlevel%==0 (
    echo [OK] MySQL está ejecutándose
) else (
    echo [ERROR] MySQL no está ejecutándose
    net start mysql
)

# Verificar espacio en disco
for /f "tokens=3" %%a in ('dir C:\ /-c ^| find "bytes free"') do set free=%%a
echo [INFO] Espacio libre en C: %free% bytes

# Verificar logs de errores
if exist "C:\xampp\apache\logs\error.log" (
    for /f %%i in ('find /c "error" "C:\xampp\apache\logs\error.log"') do echo [INFO] Errores en Apache: %%i
)

# Verificar proceso Python
tasklist | find "python.exe" >nul
if %errorlevel%==0 (
    echo [OK] Proceso Python ejecutándose
) else (
    echo [WARNING] Proceso Python no encontrado
)

echo.
echo === MONITOREO COMPLETADO ===
pause
```

### Configurar Alertas por Email

```php
<?php
// Archivo: C:\xampp\htdocs\vital-red\scripts\system_health_check.php

// Verificar estado del sistema
$checks = [
    'database' => checkDatabase(),
    'disk_space' => checkDiskSpace(),
    'apache_logs' => checkApacheLogs(),
    'python_process' => checkPythonProcess()
];

$alerts = [];
foreach ($checks as $check => $status) {
    if (!$status['ok']) {
        $alerts[] = $status['message'];
    }
}

// Enviar alertas si hay problemas
if (!empty($alerts)) {
    $subject = 'ALERTA: Problemas en Sistema Vital Red';
    $message = "Se detectaron los siguientes problemas:\n\n" . implode("\n", $alerts);
    
    mail('admin@hospital.com', $subject, $message);
}

function checkDatabase() {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=vital_red', 'vitalred_user', 'VitalRed2024!');
        return ['ok' => true, 'message' => 'Base de datos OK'];
    } catch (Exception $e) {
        return ['ok' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()];
    }
}

function checkDiskSpace() {
    $freeBytes = disk_free_space('C:');
    $totalBytes = disk_total_space('C:');
    $usedPercent = (($totalBytes - $freeBytes) / $totalBytes) * 100;
    
    if ($usedPercent > 90) {
        return ['ok' => false, 'message' => "Espacio en disco crítico: {$usedPercent}% usado"];
    }
    
    return ['ok' => true, 'message' => 'Espacio en disco OK'];
}

function checkApacheLogs() {
    $errorLog = 'C:/xampp/apache/logs/error.log';
    if (file_exists($errorLog)) {
        $errors = substr_count(file_get_contents($errorLog), 'error');
        if ($errors > 100) {
            return ['ok' => false, 'message' => "Muchos errores en Apache: {$errors}"];
        }
    }
    
    return ['ok' => true, 'message' => 'Logs de Apache OK'];
}

function checkPythonProcess() {
    $output = shell_exec('tasklist | find "python.exe"');
    if (empty($output)) {
        return ['ok' => false, 'message' => 'Proceso Python no está ejecutándose'];
    }
    
    return ['ok' => true, 'message' => 'Proceso Python OK'];
}
?>
```

### Lista de Verificación de Mantenimiento

#### Diario
- ✅ Verificar servicios Apache y MySQL
- ✅ Revisar logs de errores
- ✅ Verificar proceso de monitoreo Gmail
- ✅ Comprobar espacio en disco

#### Semanal
- ✅ Revisar métricas de rendimiento
- ✅ Verificar backups automáticos
- ✅ Limpiar logs antiguos
- ✅ Actualizar definiciones de antivirus

#### Mensual
- ✅ Aplicar actualizaciones de seguridad
- ✅ Revisar configuraciones de seguridad
- ✅ Probar procedimientos de recuperación
- ✅ Analizar tendencias de uso

#### Trimestral
- ✅ Revisar y actualizar documentación
- ✅ Capacitación de personal
- ✅ Auditoría de seguridad
- ✅ Planificación de capacidad

---

**Documento**: Configuración de Producción v1.0  
**Fecha**: 2024-01-15  
**Responsable**: Equipo Técnico Vital Red
