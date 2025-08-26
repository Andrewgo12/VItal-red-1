"""
Backup and Recovery System
Provides data backup, recovery, and disaster recovery capabilities
"""

import os
import json
import shutil
import zipfile
import sqlite3
from typing import Dict, List, Any, Optional
from datetime import datetime, timedelta
import logging
import hashlib

logger = logging.getLogger(__name__)

class BackupManager:
    """
    Manages backup operations for processed email data
    """
    
    def __init__(self, base_path: str, backup_path: str = None):
        """
        Initialize backup manager
        
        Args:
            base_path: Base path of the ia folder
            backup_path: Path for backup storage
        """
        self.base_path = base_path
        self.backup_path = backup_path or os.path.join(base_path, "backups")
        os.makedirs(self.backup_path, exist_ok=True)
        
        # Initialize backup database
        self.db_path = os.path.join(self.backup_path, "backup_index.db")
        self._init_backup_database()
        self._connection = None

    def __enter__(self):
        """Context manager entry"""
        return self

    def __exit__(self, exc_type, exc_val, exc_tb):
        """Context manager exit"""
        self.close()

    def close(self):
        """Close database connections"""
        if hasattr(self, '_connection') and self._connection:
            try:
                self._connection.close()
                self._connection = None
            except:
                pass
    
    def _init_backup_database(self):
        """Initialize backup tracking database"""
        conn = sqlite3.connect(self.db_path)
        try:
            conn.execute("""
                CREATE TABLE IF NOT EXISTS backups (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    backup_id TEXT UNIQUE,
                    timestamp TEXT,
                    backup_type TEXT,
                    file_path TEXT,
                    size_bytes INTEGER,
                    checksum TEXT,
                    email_count INTEGER,
                    status TEXT,
                    metadata TEXT
                )
            """)

            conn.execute("""
                CREATE TABLE IF NOT EXISTS backup_contents (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    backup_id TEXT,
                    email_unique_id TEXT,
                    file_path TEXT,
                    file_type TEXT,
                    FOREIGN KEY (backup_id) REFERENCES backups (backup_id)
                )
            """)
            conn.commit()
        finally:
            conn.close()
    
    def create_full_backup(self, include_attachments: bool = True) -> Dict[str, Any]:
        """
        Create full backup of all processed data
        
        Args:
            include_attachments: Whether to include attachment files
            
        Returns:
            Dict: Backup operation result
        """
        backup_id = f"full_{datetime.now().strftime('%Y%m%d_%H%M%S')}"
        backup_file = os.path.join(self.backup_path, f"{backup_id}.zip")
        
        result = {
            'backup_id': backup_id,
            'backup_file': backup_file,
            'success': False,
            'email_count': 0,
            'total_size_bytes': 0,
            'files_backed_up': 0,
            'error': None
        }
        
        try:
            with zipfile.ZipFile(backup_file, 'w', zipfile.ZIP_DEFLATED) as zipf:
                # Backup JSON files
                json_path = os.path.join(self.base_path, "Json")
                if os.path.exists(json_path):
                    for root, dirs, files in os.walk(json_path):
                        for file in files:
                            if file.endswith('.json'):
                                file_path = os.path.join(root, file)
                                arcname = os.path.relpath(file_path, self.base_path)
                                zipf.write(file_path, arcname)
                                result['files_backed_up'] += 1
                
                # Backup text files
                text_path = os.path.join(self.base_path, "Text")
                if os.path.exists(text_path):
                    for root, dirs, files in os.walk(text_path):
                        for file in files:
                            file_path = os.path.join(root, file)
                            arcname = os.path.relpath(file_path, self.base_path)
                            zipf.write(file_path, arcname)
                            result['files_backed_up'] += 1
                
                # Backup attachments if requested
                if include_attachments:
                    for folder in ["Archivos", "Imagenes"]:
                        folder_path = os.path.join(self.base_path, folder)
                        if os.path.exists(folder_path):
                            for root, dirs, files in os.walk(folder_path):
                                for file in files:
                                    file_path = os.path.join(root, file)
                                    arcname = os.path.relpath(file_path, self.base_path)
                                    zipf.write(file_path, arcname)
                                    result['files_backed_up'] += 1
            
            # Calculate backup statistics
            result['total_size_bytes'] = os.path.getsize(backup_file)
            result['email_count'] = self._count_emails_in_backup(backup_file)
            
            # Calculate checksum
            checksum = self._calculate_file_checksum(backup_file)
            
            # Record backup in database
            self._record_backup(
                backup_id=backup_id,
                backup_type='full',
                file_path=backup_file,
                size_bytes=result['total_size_bytes'],
                checksum=checksum,
                email_count=result['email_count'],
                metadata={'include_attachments': include_attachments}
            )
            
            result['success'] = True
            logger.info(f"Full backup created: {backup_file}")
            
        except Exception as e:
            result['error'] = str(e)
            logger.error(f"Error creating full backup: {str(e)}")
        
        return result
    
    def create_incremental_backup(self, since_date: datetime) -> Dict[str, Any]:
        """
        Create incremental backup of data since specified date
        
        Args:
            since_date: Only backup files modified since this date
            
        Returns:
            Dict: Backup operation result
        """
        backup_id = f"incr_{datetime.now().strftime('%Y%m%d_%H%M%S')}"
        backup_file = os.path.join(self.backup_path, f"{backup_id}.zip")
        
        result = {
            'backup_id': backup_id,
            'backup_file': backup_file,
            'success': False,
            'email_count': 0,
            'total_size_bytes': 0,
            'files_backed_up': 0,
            'since_date': since_date.isoformat(),
            'error': None
        }
        
        try:
            since_timestamp = since_date.timestamp()
            
            with zipfile.ZipFile(backup_file, 'w', zipfile.ZIP_DEFLATED) as zipf:
                # Check all directories for modified files
                for folder in ["Json", "Text", "Archivos", "Imagenes"]:
                    folder_path = os.path.join(self.base_path, folder)
                    if os.path.exists(folder_path):
                        for root, dirs, files in os.walk(folder_path):
                            for file in files:
                                file_path = os.path.join(root, file)
                                file_mtime = os.path.getmtime(file_path)
                                
                                if file_mtime > since_timestamp:
                                    arcname = os.path.relpath(file_path, self.base_path)
                                    zipf.write(file_path, arcname)
                                    result['files_backed_up'] += 1
            
            if result['files_backed_up'] > 0:
                result['total_size_bytes'] = os.path.getsize(backup_file)
                result['email_count'] = self._count_emails_in_backup(backup_file)
                
                checksum = self._calculate_file_checksum(backup_file)
                
                self._record_backup(
                    backup_id=backup_id,
                    backup_type='incremental',
                    file_path=backup_file,
                    size_bytes=result['total_size_bytes'],
                    checksum=checksum,
                    email_count=result['email_count'],
                    metadata={'since_date': since_date.isoformat()}
                )
                
                result['success'] = True
                logger.info(f"Incremental backup created: {backup_file}")
            else:
                # No files to backup, remove empty zip
                os.remove(backup_file)
                result['success'] = True
                logger.info("No files modified since last backup")
            
        except Exception as e:
            result['error'] = str(e)
            logger.error(f"Error creating incremental backup: {str(e)}")
        
        return result
    
    def restore_backup(self, backup_id: str, restore_path: str = None) -> Dict[str, Any]:
        """
        Restore data from backup
        
        Args:
            backup_id: ID of backup to restore
            restore_path: Path to restore to (defaults to original location)
            
        Returns:
            Dict: Restore operation result
        """
        result = {
            'backup_id': backup_id,
            'success': False,
            'files_restored': 0,
            'error': None
        }
        
        try:
            # Get backup information
            backup_info = self._get_backup_info(backup_id)
            if not backup_info:
                result['error'] = f"Backup {backup_id} not found"
                return result
            
            backup_file = backup_info['file_path']
            if not os.path.exists(backup_file):
                result['error'] = f"Backup file not found: {backup_file}"
                return result
            
            # Verify backup integrity
            if not self._verify_backup_integrity(backup_file, backup_info['checksum']):
                result['error'] = "Backup integrity check failed"
                return result
            
            restore_base = restore_path or self.base_path
            
            with zipfile.ZipFile(backup_file, 'r') as zipf:
                zipf.extractall(restore_base)
                result['files_restored'] = len(zipf.namelist())
            
            result['success'] = True
            logger.info(f"Backup {backup_id} restored to {restore_base}")
            
        except Exception as e:
            result['error'] = str(e)
            logger.error(f"Error restoring backup {backup_id}: {str(e)}")
        
        return result
    
    def list_backups(self) -> List[Dict[str, Any]]:
        """
        List all available backups
        
        Returns:
            List[Dict]: List of backup information
        """
        backups = []
        
        try:
            with sqlite3.connect(self.db_path) as conn:
                cursor = conn.execute("""
                    SELECT backup_id, timestamp, backup_type, file_path, 
                           size_bytes, email_count, status, metadata
                    FROM backups 
                    ORDER BY timestamp DESC
                """)
                
                for row in cursor.fetchall():
                    backup_info = {
                        'backup_id': row[0],
                        'timestamp': row[1],
                        'backup_type': row[2],
                        'file_path': row[3],
                        'size_bytes': row[4],
                        'size_mb': round(row[4] / (1024 * 1024), 2),
                        'email_count': row[5],
                        'status': row[6],
                        'metadata': json.loads(row[7]) if row[7] else {}
                    }
                    backups.append(backup_info)
        
        except Exception as e:
            logger.error(f"Error listing backups: {str(e)}")
        
        return backups
    
    def cleanup_old_backups(self, keep_days: int = 30, keep_count: int = 10) -> Dict[str, Any]:
        """
        Clean up old backups based on age and count
        
        Args:
            keep_days: Keep backups newer than this many days
            keep_count: Keep at least this many recent backups
            
        Returns:
            Dict: Cleanup operation result
        """
        result = {
            'success': False,
            'backups_deleted': 0,
            'space_freed_mb': 0,
            'error': None
        }
        
        try:
            cutoff_date = datetime.now() - timedelta(days=keep_days)
            
            with sqlite3.connect(self.db_path) as conn:
                # Get backups to potentially delete
                cursor = conn.execute("""
                    SELECT backup_id, file_path, size_bytes, timestamp
                    FROM backups 
                    WHERE timestamp < ?
                    ORDER BY timestamp DESC
                """, (cutoff_date.isoformat(),))
                
                old_backups = cursor.fetchall()
                
                # Keep at least keep_count backups
                if len(old_backups) > keep_count:
                    backups_to_delete = old_backups[keep_count:]
                    
                    for backup_id, file_path, size_bytes, timestamp in backups_to_delete:
                        if os.path.exists(file_path):
                            os.remove(file_path)
                            result['space_freed_mb'] += size_bytes / (1024 * 1024)
                        
                        # Remove from database
                        conn.execute("DELETE FROM backup_contents WHERE backup_id = ?", (backup_id,))
                        conn.execute("DELETE FROM backups WHERE backup_id = ?", (backup_id,))
                        result['backups_deleted'] += 1
                        
                        logger.info(f"Deleted old backup: {backup_id}")
            
            result['success'] = True
            result['space_freed_mb'] = round(result['space_freed_mb'], 2)
            
        except Exception as e:
            result['error'] = str(e)
            logger.error(f"Error cleaning up backups: {str(e)}")
        
        return result
    
    def _record_backup(self, backup_id: str, backup_type: str, file_path: str,
                      size_bytes: int, checksum: str, email_count: int, metadata: Dict[str, Any]):
        """Record backup in database"""
        with sqlite3.connect(self.db_path) as conn:
            conn.execute("""
                INSERT INTO backups 
                (backup_id, timestamp, backup_type, file_path, size_bytes, 
                 checksum, email_count, status, metadata)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            """, (
                backup_id,
                datetime.now().isoformat(),
                backup_type,
                file_path,
                size_bytes,
                checksum,
                email_count,
                'completed',
                json.dumps(metadata)
            ))
    
    def _get_backup_info(self, backup_id: str) -> Optional[Dict[str, Any]]:
        """Get backup information from database"""
        with sqlite3.connect(self.db_path) as conn:
            cursor = conn.execute("""
                SELECT backup_id, timestamp, backup_type, file_path, 
                       size_bytes, checksum, email_count, status, metadata
                FROM backups 
                WHERE backup_id = ?
            """, (backup_id,))
            
            row = cursor.fetchone()
            if row:
                return {
                    'backup_id': row[0],
                    'timestamp': row[1],
                    'backup_type': row[2],
                    'file_path': row[3],
                    'size_bytes': row[4],
                    'checksum': row[5],
                    'email_count': row[6],
                    'status': row[7],
                    'metadata': json.loads(row[8]) if row[8] else {}
                }
        return None
    
    def _calculate_file_checksum(self, file_path: str) -> str:
        """Calculate MD5 checksum of file"""
        hash_md5 = hashlib.md5()
        with open(file_path, "rb") as f:
            for chunk in iter(lambda: f.read(4096), b""):
                hash_md5.update(chunk)
        return hash_md5.hexdigest()
    
    def _verify_backup_integrity(self, file_path: str, expected_checksum: str) -> bool:
        """Verify backup file integrity"""
        actual_checksum = self._calculate_file_checksum(file_path)
        return actual_checksum == expected_checksum
    
    def _count_emails_in_backup(self, backup_file: str) -> int:
        """Count number of emails in backup"""
        try:
            with zipfile.ZipFile(backup_file, 'r') as zipf:
                # Count unique email directories in Json folder
                email_dirs = set()
                for file_path in zipf.namelist():
                    if file_path.startswith('Json/') and '/' in file_path[5:]:
                        email_id = file_path.split('/')[1]
                        email_dirs.add(email_id)
                return len(email_dirs)
        except:
            return 0

class RecoveryManager:
    """
    Manages data recovery operations
    """
    
    def __init__(self, base_path: str):
        """
        Initialize recovery manager
        
        Args:
            base_path: Base path of the ia folder
        """
        self.base_path = base_path
        self.backup_manager = BackupManager(base_path)
    
    def recover_corrupted_email(self, unique_id: str) -> Dict[str, Any]:
        """
        Attempt to recover a corrupted email from backups
        
        Args:
            unique_id: Unique ID of the email to recover
            
        Returns:
            Dict: Recovery operation result
        """
        result = {
            'unique_id': unique_id,
            'success': False,
            'recovered_files': [],
            'source_backup': None,
            'error': None
        }
        
        try:
            # Find backups that contain this email
            backups = self.backup_manager.list_backups()
            
            for backup in backups:
                if self._backup_contains_email(backup['backup_id'], unique_id):
                    # Try to extract just this email's files
                    extracted_files = self._extract_email_from_backup(backup['backup_id'], unique_id)
                    if extracted_files:
                        result['success'] = True
                        result['recovered_files'] = extracted_files
                        result['source_backup'] = backup['backup_id']
                        logger.info(f"Recovered email {unique_id} from backup {backup['backup_id']}")
                        break
            
            if not result['success']:
                result['error'] = f"Email {unique_id} not found in any backup"
        
        except Exception as e:
            result['error'] = str(e)
            logger.error(f"Error recovering email {unique_id}: {str(e)}")
        
        return result
    
    def _backup_contains_email(self, backup_id: str, unique_id: str) -> bool:
        """Check if backup contains specific email"""
        backup_info = self.backup_manager._get_backup_info(backup_id)
        if not backup_info:
            return False
        
        backup_file = backup_info['file_path']
        if not os.path.exists(backup_file):
            return False
        
        try:
            with zipfile.ZipFile(backup_file, 'r') as zipf:
                # Look for files belonging to this email
                for file_path in zipf.namelist():
                    if unique_id in file_path:
                        return True
        except:
            pass
        
        return False
    
    def _extract_email_from_backup(self, backup_id: str, unique_id: str) -> List[str]:
        """Extract specific email files from backup"""
        backup_info = self.backup_manager._get_backup_info(backup_id)
        if not backup_info:
            return []
        
        backup_file = backup_info['file_path']
        extracted_files = []
        
        try:
            with zipfile.ZipFile(backup_file, 'r') as zipf:
                for file_path in zipf.namelist():
                    if unique_id in file_path:
                        # Extract to original location
                        extract_path = os.path.join(self.base_path, file_path)
                        os.makedirs(os.path.dirname(extract_path), exist_ok=True)
                        
                        with zipf.open(file_path) as source, open(extract_path, 'wb') as target:
                            shutil.copyfileobj(source, target)
                        
                        extracted_files.append(extract_path)
        
        except Exception as e:
            logger.error(f"Error extracting email from backup: {str(e)}")
        
        return extracted_files
