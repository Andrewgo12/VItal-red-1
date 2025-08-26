<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;
use ZipArchive;

class BackupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:administrador');
    }

    /**
     * Create system backup
     */
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:database,files,full',
            'compress' => 'boolean',
            'encrypt' => 'boolean',
            'description' => 'nullable|string|max:255'
        ]);

        try {
            $backupType = $request->type;
            $timestamp = now()->format('Y-m-d_H-i-s');
            $backupName = "backup_{$backupType}_{$timestamp}";
            $backupPath = storage_path("backups/{$backupName}");

            // Create backup directory
            if (!file_exists(storage_path('backups'))) {
                mkdir(storage_path('backups'), 0755, true);
            }

            if (!file_exists($backupPath)) {
                mkdir($backupPath, 0755, true);
            }

            $backupInfo = [
                'name' => $backupName,
                'type' => $backupType,
                'created_at' => now(),
                'created_by' => Auth::user()->name,
                'description' => $request->description,
                'files' => []
            ];

            // Perform backup based on type
            switch ($backupType) {
                case 'database':
                    $this->createDatabaseBackup($backupPath, $backupInfo);
                    break;
                case 'files':
                    $this->createFilesBackup($backupPath, $backupInfo);
                    break;
                case 'full':
                    $this->createDatabaseBackup($backupPath, $backupInfo);
                    $this->createFilesBackup($backupPath, $backupInfo);
                    break;
            }

            // Create backup info file
            file_put_contents(
                $backupPath . '/backup_info.json',
                json_encode($backupInfo, JSON_PRETTY_PRINT)
            );

            // Compress if requested
            if ($request->get('compress', true)) {
                $zipPath = $this->compressBackup($backupPath, $backupName);
                $backupInfo['compressed'] = true;
                $backupInfo['zip_path'] = $zipPath;
                
                // Remove uncompressed files
                $this->removeDirectory($backupPath);
            }

            // Encrypt if requested
            if ($request->get('encrypt', false)) {
                $encryptedPath = $this->encryptBackup($zipPath ?? $backupPath, $backupName);
                $backupInfo['encrypted'] = true;
                $backupInfo['encrypted_path'] = $encryptedPath;
            }

            // Clean old backups (keep last 10)
            $this->cleanOldBackups();

            return response()->json([
                'success' => true,
                'message' => 'Backup creado exitosamente',
                'data' => $backupInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear backup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List available backups
     */
    public function list(): JsonResponse
    {
        try {
            $backupsPath = storage_path('backups');
            $backups = [];

            if (!file_exists($backupsPath)) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $files = scandir($backupsPath);
            
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                
                $filePath = $backupsPath . '/' . $file;
                
                if (is_file($filePath)) {
                    $backups[] = [
                        'name' => $file,
                        'size' => $this->formatBytes(filesize($filePath)),
                        'created_at' => Carbon::createFromTimestamp(filemtime($filePath)),
                        'type' => $this->getBackupType($file),
                        'is_compressed' => str_ends_with($file, '.zip'),
                        'is_encrypted' => str_ends_with($file, '.enc')
                    ];
                } elseif (is_dir($filePath)) {
                    $infoFile = $filePath . '/backup_info.json';
                    if (file_exists($infoFile)) {
                        $info = json_decode(file_get_contents($infoFile), true);
                        $info['size'] = $this->formatBytes($this->getDirectorySize($filePath));
                        $backups[] = $info;
                    }
                }
            }

            // Sort by creation date (newest first)
            usort($backups, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            return response()->json([
                'success' => true,
                'data' => $backups
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al listar backups: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete backup
     */
    public function delete(Request $request, string $backupName): JsonResponse
    {
        try {
            $backupsPath = storage_path('backups');
            $backupPath = $backupsPath . '/' . $backupName;

            if (!file_exists($backupPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup no encontrado'
                ], 404);
            }

            if (is_file($backupPath)) {
                unlink($backupPath);
            } elseif (is_dir($backupPath)) {
                $this->removeDirectory($backupPath);
            }

            return response()->json([
                'success' => true,
                'message' => 'Backup eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar backup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download backup
     */
    public function download(string $backupName)
    {
        $backupsPath = storage_path('backups');
        $backupPath = $backupsPath . '/' . $backupName;

        if (!file_exists($backupPath)) {
            abort(404, 'Backup no encontrado');
        }

        return response()->download($backupPath);
    }

    /**
     * Restore backup
     */
    public function restore(Request $request, string $backupName): JsonResponse
    {
        $request->validate([
            'confirm' => 'required|boolean|accepted'
        ]);

        try {
            $backupsPath = storage_path('backups');
            $backupPath = $backupsPath . '/' . $backupName;

            if (!file_exists($backupPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup no encontrado'
                ], 404);
            }

            // Extract if compressed
            if (str_ends_with($backupName, '.zip')) {
                $extractPath = $backupsPath . '/restore_temp_' . time();
                $this->extractBackup($backupPath, $extractPath);
                $backupPath = $extractPath;
            }

            // Read backup info
            $infoFile = $backupPath . '/backup_info.json';
            if (!file_exists($infoFile)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Información de backup no encontrada'
                ], 400);
            }

            $backupInfo = json_decode(file_get_contents($infoFile), true);

            // Restore based on backup type
            switch ($backupInfo['type']) {
                case 'database':
                    $this->restoreDatabase($backupPath);
                    break;
                case 'files':
                    $this->restoreFiles($backupPath);
                    break;
                case 'full':
                    $this->restoreDatabase($backupPath);
                    $this->restoreFiles($backupPath);
                    break;
            }

            // Clean up temporary files
            if (isset($extractPath)) {
                $this->removeDirectory($extractPath);
            }

            return response()->json([
                'success' => true,
                'message' => 'Backup restaurado exitosamente',
                'data' => $backupInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar backup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create database backup
     */
    private function createDatabaseBackup(string $backupPath, array &$backupInfo): void
    {
        $dbConfig = config('database.connections.' . config('database.default'));
        $dumpFile = $backupPath . '/database.sql';

        $command = sprintf(
            'mysqldump -h%s -P%s -u%s -p%s %s > %s',
            $dbConfig['host'],
            $dbConfig['port'],
            $dbConfig['username'],
            $dbConfig['password'],
            $dbConfig['database'],
            $dumpFile
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Error al crear backup de base de datos');
        }

        $backupInfo['files'][] = [
            'name' => 'database.sql',
            'size' => filesize($dumpFile),
            'type' => 'database'
        ];
    }

    /**
     * Create files backup
     */
    private function createFilesBackup(string $backupPath, array &$backupInfo): void
    {
        $filesToBackup = [
            'app' => app_path(),
            'config' => config_path(),
            'database' => database_path(),
            'resources' => resource_path(),
            'routes' => base_path('routes'),
            'storage_app' => storage_path('app'),
            'public' => public_path(),
            '.env' => base_path('.env')
        ];

        foreach ($filesToBackup as $name => $path) {
            if (file_exists($path)) {
                $destinationPath = $backupPath . '/files/' . $name;
                
                if (is_file($path)) {
                    if (!file_exists(dirname($destinationPath))) {
                        mkdir(dirname($destinationPath), 0755, true);
                    }
                    copy($path, $destinationPath);
                    $size = filesize($destinationPath);
                } else {
                    $this->copyDirectory($path, $destinationPath);
                    $size = $this->getDirectorySize($destinationPath);
                }

                $backupInfo['files'][] = [
                    'name' => $name,
                    'size' => $size,
                    'type' => 'files'
                ];
            }
        }
    }

    /**
     * Compress backup
     */
    private function compressBackup(string $backupPath, string $backupName): string
    {
        $zipPath = storage_path("backups/{$backupName}.zip");
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            throw new \Exception('No se pudo crear el archivo ZIP');
        }

        $this->addDirectoryToZip($zip, $backupPath, '');
        $zip->close();

        return $zipPath;
    }

    /**
     * Add directory to ZIP
     */
    private function addDirectoryToZip(ZipArchive $zip, string $dir, string $zipDir): void
    {
        $files = scandir($dir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $filePath = $dir . '/' . $file;
            $zipPath = $zipDir ? $zipDir . '/' . $file : $file;
            
            if (is_file($filePath)) {
                $zip->addFile($filePath, $zipPath);
            } elseif (is_dir($filePath)) {
                $zip->addEmptyDir($zipPath);
                $this->addDirectoryToZip($zip, $filePath, $zipPath);
            }
        }
    }

    /**
     * Copy directory recursively
     */
    private function copyDirectory(string $source, string $destination): void
    {
        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        $files = scandir($source);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $sourcePath = $source . '/' . $file;
            $destPath = $destination . '/' . $file;
            
            if (is_file($sourcePath)) {
                copy($sourcePath, $destPath);
            } elseif (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destPath);
            }
        }
    }

    /**
     * Remove directory recursively
     */
    private function removeDirectory(string $dir): void
    {
        if (!file_exists($dir)) return;
        
        $files = scandir($dir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $filePath = $dir . '/' . $file;
            
            if (is_file($filePath)) {
                unlink($filePath);
            } elseif (is_dir($filePath)) {
                $this->removeDirectory($filePath);
            }
        }
        
        rmdir($dir);
    }

    /**
     * Get directory size
     */
    private function getDirectorySize(string $dir): int
    {
        $size = 0;
        $files = scandir($dir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $filePath = $dir . '/' . $file;
            
            if (is_file($filePath)) {
                $size += filesize($filePath);
            } elseif (is_dir($filePath)) {
                $size += $this->getDirectorySize($filePath);
            }
        }
        
        return $size;
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Get backup type from filename
     */
    private function getBackupType(string $filename): string
    {
        if (str_contains($filename, '_database_')) return 'database';
        if (str_contains($filename, '_files_')) return 'files';
        if (str_contains($filename, '_full_')) return 'full';
        return 'unknown';
    }

    /**
     * Clean old backups
     */
    private function cleanOldBackups(): void
    {
        $backupsPath = storage_path('backups');
        $files = glob($backupsPath . '/*');
        
        // Sort by modification time (newest first)
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        // Keep only the 10 most recent backups
        $filesToDelete = array_slice($files, 10);
        
        foreach ($filesToDelete as $file) {
            if (is_file($file)) {
                unlink($file);
            } elseif (is_dir($file)) {
                $this->removeDirectory($file);
            }
        }
    }

    /**
     * Extract backup
     */
    private function extractBackup(string $zipPath, string $extractPath): void
    {
        $zip = new ZipArchive();
        
        if ($zip->open($zipPath) !== TRUE) {
            throw new \Exception('No se pudo abrir el archivo ZIP');
        }
        
        if (!file_exists($extractPath)) {
            mkdir($extractPath, 0755, true);
        }
        
        $zip->extractTo($extractPath);
        $zip->close();
    }

    /**
     * Restore database
     */
    private function restoreDatabase(string $backupPath): void
    {
        $sqlFile = $backupPath . '/database.sql';
        
        if (!file_exists($sqlFile)) {
            throw new \Exception('Archivo de base de datos no encontrado en el backup');
        }

        $dbConfig = config('database.connections.' . config('database.default'));
        
        $command = sprintf(
            'mysql -h%s -P%s -u%s -p%s %s < %s',
            $dbConfig['host'],
            $dbConfig['port'],
            $dbConfig['username'],
            $dbConfig['password'],
            $dbConfig['database'],
            $sqlFile
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Error al restaurar base de datos');
        }
    }

    /**
     * Restore files
     */
    private function restoreFiles(string $backupPath): void
    {
        $filesPath = $backupPath . '/files';
        
        if (!file_exists($filesPath)) {
            throw new \Exception('Archivos no encontrados en el backup');
        }

        // Restore each backed up directory/file
        $restoreMap = [
            'app' => app_path(),
            'config' => config_path(),
            'resources' => resource_path(),
            'routes' => base_path('routes'),
            '.env' => base_path('.env')
        ];

        foreach ($restoreMap as $backupName => $targetPath) {
            $sourcePath = $filesPath . '/' . $backupName;
            
            if (file_exists($sourcePath)) {
                if (is_file($sourcePath)) {
                    copy($sourcePath, $targetPath);
                } else {
                    // Remove existing directory and restore from backup
                    if (file_exists($targetPath)) {
                        $this->removeDirectory($targetPath);
                    }
                    $this->copyDirectory($sourcePath, $targetPath);
                }
            }
        }
    }
}
