<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use ZipArchive;

class BackupSystemCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'system:backup 
                            {--type=full : Type of backup (full, database, files)}
                            {--compress : Compress backup files}
                            {--encrypt : Encrypt backup files}
                            {--retention=30 : Retention period in days}';

    /**
     * The console command description.
     */
    protected $description = 'Create system backup including database and files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Iniciando proceso de backup del sistema...');
        
        $type = $this->option('type');
        $compress = $this->option('compress');
        $encrypt = $this->option('encrypt');
        $retention = (int) $this->option('retention');
        
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupName = "vital_red_backup_{$type}_{$timestamp}";
        
        try {
            // Create backup directory
            $backupPath = $this->createBackupDirectory($backupName);
            
            $this->info("📁 Directorio de backup creado: {$backupPath}");
            
            // Perform backup based on type
            switch ($type) {
                case 'full':
                    $this->performFullBackup($backupPath);
                    break;
                case 'database':
                    $this->performDatabaseBackup($backupPath);
                    break;
                case 'files':
                    $this->performFilesBackup($backupPath);
                    break;
                default:
                    $this->error("❌ Tipo de backup no válido: {$type}");
                    return 1;
            }
            
            // Create backup manifest
            $this->createBackupManifest($backupPath, $type);
            
            // Compress if requested
            if ($compress) {
                $this->compressBackup($backupPath);
            }
            
            // Encrypt if requested
            if ($encrypt) {
                $this->encryptBackup($backupPath);
            }
            
            // Clean old backups
            $this->cleanOldBackups($retention);
            
            // Verify backup integrity
            $this->verifyBackup($backupPath);
            
            $this->info("✅ Backup completado exitosamente: {$backupName}");
            
            // Log backup completion
            Log::info("System backup completed", [
                'type' => $type,
                'backup_name' => $backupName,
                'compressed' => $compress,
                'encrypted' => $encrypt,
                'size' => $this->getDirectorySize($backupPath)
            ]);
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Error durante el backup: " . $e->getMessage());
            Log::error("System backup failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
    
    /**
     * Create backup directory
     */
    private function createBackupDirectory(string $backupName): string
    {
        $backupPath = storage_path("backups/{$backupName}");
        
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }
        
        return $backupPath;
    }
    
    /**
     * Perform full system backup
     */
    private function performFullBackup(string $backupPath): void
    {
        $this->info("🔄 Realizando backup completo...");
        
        // Database backup
        $this->performDatabaseBackup($backupPath);
        
        // Files backup
        $this->performFilesBackup($backupPath);
        
        // Configuration backup
        $this->performConfigBackup($backupPath);
        
        $this->info("✅ Backup completo finalizado");
    }
    
    /**
     * Perform database backup
     */
    private function performDatabaseBackup(string $backupPath): void
    {
        $this->info("🗄️ Realizando backup de base de datos...");
        
        $dbConfig = config('database.connections.' . config('database.default'));
        $dbName = $dbConfig['database'];
        $dbUser = $dbConfig['username'];
        $dbPassword = $dbConfig['password'];
        $dbHost = $dbConfig['host'];
        $dbPort = $dbConfig['port'];
        
        $sqlFile = $backupPath . "/database_backup.sql";
        
        // Use mysqldump for MySQL databases
        if ($dbConfig['driver'] === 'mysql') {
            $command = sprintf(
                'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s',
                escapeshellarg($dbHost),
                escapeshellarg($dbPort),
                escapeshellarg($dbUser),
                escapeshellarg($dbPassword),
                escapeshellarg($dbName),
                escapeshellarg($sqlFile)
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new \Exception("Error en backup de base de datos: " . implode("\n", $output));
            }
        } else {
            // Fallback for other database types
            $this->performManualDatabaseBackup($backupPath);
        }
        
        $this->info("✅ Backup de base de datos completado");
    }
    
    /**
     * Perform manual database backup for non-MySQL databases
     */
    private function performManualDatabaseBackup(string $backupPath): void
    {
        $tables = DB::select('SHOW TABLES');
        $sqlContent = "-- Vital Red Database Backup\n";
        $sqlContent .= "-- Generated on: " . now()->toDateTimeString() . "\n\n";
        
        foreach ($tables as $table) {
            $tableName = array_values((array) $table)[0];
            
            // Get table structure
            $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`")[0];
            $sqlContent .= "-- Table: {$tableName}\n";
            $sqlContent .= $createTable->{'Create Table'} . ";\n\n";
            
            // Get table data
            $rows = DB::table($tableName)->get();
            
            if ($rows->count() > 0) {
                $sqlContent .= "-- Data for table: {$tableName}\n";
                
                foreach ($rows as $row) {
                    $values = array_map(function($value) {
                        return is_null($value) ? 'NULL' : "'" . addslashes($value) . "'";
                    }, (array) $row);
                    
                    $sqlContent .= "INSERT INTO `{$tableName}` VALUES (" . implode(', ', $values) . ");\n";
                }
                
                $sqlContent .= "\n";
            }
        }
        
        file_put_contents($backupPath . "/database_backup.sql", $sqlContent);
    }
    
    /**
     * Perform files backup
     */
    private function performFilesBackup(string $backupPath): void
    {
        $this->info("📁 Realizando backup de archivos...");
        
        $filesToBackup = [
            'storage/app' => 'storage_app',
            'storage/logs' => 'storage_logs',
            'public/uploads' => 'public_uploads',
            'ia/output' => 'ia_output',
            'ia/attachments' => 'ia_attachments'
        ];
        
        foreach ($filesToBackup as $source => $destination) {
            $sourcePath = base_path($source);
            $destPath = $backupPath . "/{$destination}";
            
            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destPath);
                $this->info("  ✅ Copiado: {$source}");
            }
        }
        
        $this->info("✅ Backup de archivos completado");
    }
    
    /**
     * Perform configuration backup
     */
    private function performConfigBackup(string $backupPath): void
    {
        $this->info("⚙️ Realizando backup de configuración...");
        
        $configFiles = [
            '.env',
            'config/app.php',
            'config/database.php',
            'config/security.php',
            'ia/config.json'
        ];
        
        $configBackupPath = $backupPath . "/config";
        mkdir($configBackupPath, 0755, true);
        
        foreach ($configFiles as $configFile) {
            $sourcePath = base_path($configFile);
            
            if (file_exists($sourcePath)) {
                $destPath = $configBackupPath . "/" . basename($configFile);
                copy($sourcePath, $destPath);
                $this->info("  ✅ Copiado: {$configFile}");
            }
        }
        
        $this->info("✅ Backup de configuración completado");
    }
    
    /**
     * Create backup manifest
     */
    private function createBackupManifest(string $backupPath, string $type): void
    {
        $manifest = [
            'backup_info' => [
                'name' => basename($backupPath),
                'type' => $type,
                'created_at' => now()->toISOString(),
                'version' => config('app.version', '1.0.0'),
                'environment' => config('app.env')
            ],
            'system_info' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'database_driver' => config('database.default'),
                'server_os' => PHP_OS
            ],
            'backup_contents' => $this->getBackupContents($backupPath),
            'checksums' => $this->generateChecksums($backupPath)
        ];
        
        file_put_contents(
            $backupPath . "/backup_manifest.json",
            json_encode($manifest, JSON_PRETTY_PRINT)
        );
        
        $this->info("📋 Manifiesto de backup creado");
    }
    
    /**
     * Get backup contents
     */
    private function getBackupContents(string $backupPath): array
    {
        $contents = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($backupPath)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = str_replace($backupPath . '/', '', $file->getPathname());
                $contents[] = [
                    'file' => $relativePath,
                    'size' => $file->getSize(),
                    'modified' => date('Y-m-d H:i:s', $file->getMTime())
                ];
            }
        }
        
        return $contents;
    }
    
    /**
     * Generate checksums for backup files
     */
    private function generateChecksums(string $backupPath): array
    {
        $checksums = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($backupPath)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() !== 'backup_manifest.json') {
                $relativePath = str_replace($backupPath . '/', '', $file->getPathname());
                $checksums[$relativePath] = hash_file('sha256', $file->getPathname());
            }
        }
        
        return $checksums;
    }
    
    /**
     * Compress backup
     */
    private function compressBackup(string $backupPath): void
    {
        $this->info("🗜️ Comprimiendo backup...");
        
        $zipFile = $backupPath . ".zip";
        $zip = new ZipArchive();
        
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($backupPath)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $relativePath = str_replace($backupPath . '/', '', $file->getPathname());
                    $zip->addFile($file->getPathname(), $relativePath);
                }
            }
            
            $zip->close();
            
            // Remove original directory
            $this->removeDirectory($backupPath);
            
            $this->info("✅ Backup comprimido: " . basename($zipFile));
        } else {
            throw new \Exception("No se pudo crear el archivo ZIP");
        }
    }
    
    /**
     * Encrypt backup
     */
    private function encryptBackup(string $backupPath): void
    {
        $this->info("🔐 Encriptando backup...");
        
        $encryptionKey = config('app.key');
        
        if (is_dir($backupPath)) {
            $zipFile = $backupPath . ".zip";
            $this->compressBackup($backupPath);
            $backupPath = $zipFile;
        }
        
        $encryptedFile = $backupPath . ".enc";
        $data = file_get_contents($backupPath);
        $encryptedData = encrypt($data);
        
        file_put_contents($encryptedFile, $encryptedData);
        unlink($backupPath);
        
        $this->info("✅ Backup encriptado: " . basename($encryptedFile));
    }
    
    /**
     * Clean old backups
     */
    private function cleanOldBackups(int $retentionDays): void
    {
        $this->info("🧹 Limpiando backups antiguos...");
        
        $backupsPath = storage_path('backups');
        $cutoffDate = now()->subDays($retentionDays);
        $deletedCount = 0;
        
        if (is_dir($backupsPath)) {
            $items = scandir($backupsPath);
            
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                
                $itemPath = $backupsPath . '/' . $item;
                $itemDate = Carbon::createFromTimestamp(filemtime($itemPath));
                
                if ($itemDate->lt($cutoffDate)) {
                    if (is_dir($itemPath)) {
                        $this->removeDirectory($itemPath);
                    } else {
                        unlink($itemPath);
                    }
                    $deletedCount++;
                }
            }
        }
        
        $this->info("✅ Eliminados {$deletedCount} backups antiguos");
    }
    
    /**
     * Verify backup integrity
     */
    private function verifyBackup(string $backupPath): void
    {
        $this->info("🔍 Verificando integridad del backup...");
        
        $manifestFile = $backupPath . "/backup_manifest.json";
        
        if (file_exists($manifestFile)) {
            $manifest = json_decode(file_get_contents($manifestFile), true);
            $checksums = $manifest['checksums'] ?? [];
            
            $verificationErrors = 0;
            
            foreach ($checksums as $file => $expectedChecksum) {
                $filePath = $backupPath . "/" . $file;
                
                if (file_exists($filePath)) {
                    $actualChecksum = hash_file('sha256', $filePath);
                    
                    if ($actualChecksum !== $expectedChecksum) {
                        $this->error("  ❌ Checksum mismatch: {$file}");
                        $verificationErrors++;
                    }
                } else {
                    $this->error("  ❌ Archivo faltante: {$file}");
                    $verificationErrors++;
                }
            }
            
            if ($verificationErrors === 0) {
                $this->info("✅ Verificación de integridad exitosa");
            } else {
                throw new \Exception("Errores de verificación encontrados: {$verificationErrors}");
            }
        } else {
            $this->warn("⚠️ No se encontró manifiesto para verificación");
        }
    }
    
    /**
     * Copy directory recursively
     */
    private function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $destPath = $destination . '/' . $iterator->getSubPathName();
            
            if ($item->isDir()) {
                if (!is_dir($destPath)) {
                    mkdir($destPath, 0755, true);
                }
            } else {
                copy($item->getPathname(), $destPath);
            }
        }
    }
    
    /**
     * Remove directory recursively
     */
    private function removeDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            
            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    rmdir($item->getPathname());
                } else {
                    unlink($item->getPathname());
                }
            }
            
            rmdir($directory);
        }
    }
    
    /**
     * Get directory size
     */
    private function getDirectorySize(string $directory): string
    {
        $size = 0;
        
        if (is_dir($directory)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        } elseif (is_file($directory)) {
            $size = filesize($directory);
        }
        
        return $this->formatBytes($size);
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
