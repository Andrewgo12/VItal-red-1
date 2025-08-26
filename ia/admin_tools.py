"""
Advanced Administration Tools
Comprehensive management interface for the Gmail processing system
"""

import os
import sys
import json
import argparse
from typing import Dict, List, Any, Optional
from datetime import datetime, timedelta
import logging

# Add Functions directory to path
sys.path.append(os.path.join(os.path.dirname(__file__), 'Functions'))

from gmail_processor import GmailProcessor
from monitoring import PerformanceMonitor, ProcessingLogger, SystemHealthChecker
from data_validator import DataValidator, QualityAssurance
from backup_recovery import BackupManager, RecoveryManager
from config import load_complete_config, AdvancedConfig

class AdminInterface:
    """
    Advanced administration interface
    """
    
    def __init__(self, base_path: str = None):
        """
        Initialize admin interface
        
        Args:
            base_path: Base path for the ia folder
        """
        self.base_path = base_path or os.path.dirname(os.path.abspath(__file__))
        self.config = load_complete_config()
        
        # Initialize components
        self.performance_monitor = PerformanceMonitor()
        self.processing_logger = ProcessingLogger()
        self.data_validator = DataValidator()
        self.qa_system = QualityAssurance()
        self.backup_manager = BackupManager(self.base_path)
        self.recovery_manager = RecoveryManager(self.base_path)
        
        # Setup logging
        logging.basicConfig(
            level=getattr(logging, self.config.get('log_level', 'INFO')),
            format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
        )
        self.logger = logging.getLogger(__name__)
    
    def system_status(self) -> Dict[str, Any]:
        """
        Get comprehensive system status
        
        Returns:
            Dict: System status information
        """
        status = {
            'timestamp': datetime.now().isoformat(),
            'system_health': {},
            'performance': {},
            'storage': {},
            'backups': {},
            'quality_metrics': {}
        }
        
        try:
            # System health check
            status['system_health'] = SystemHealthChecker.check_system_requirements()
            
            # Performance metrics
            if self.performance_monitor.monitoring_active:
                status['performance'] = self.performance_monitor.get_performance_summary()
            else:
                status['performance'] = {'status': 'monitoring_not_active'}
            
            # Storage information
            status['storage'] = self._get_storage_info()
            
            # Backup information
            backups = self.backup_manager.list_backups()
            status['backups'] = {
                'total_backups': len(backups),
                'latest_backup': backups[0] if backups else None,
                'total_backup_size_mb': sum(b.get('size_mb', 0) for b in backups)
            }
            
            # Quality metrics
            status['quality_metrics'] = self.qa_system.get_qa_summary()
            
        except Exception as e:
            self.logger.error(f"Error getting system status: {str(e)}")
            status['error'] = str(e)
        
        return status
    
    def start_monitoring(self):
        """Start system monitoring"""
        if self.config.get('enable_performance_monitoring', True):
            interval = self.config.get('monitoring_interval', 5.0)
            self.performance_monitor.start_monitoring(interval)
            self.logger.info("Performance monitoring started")
        else:
            self.logger.info("Performance monitoring disabled in configuration")
    
    def stop_monitoring(self):
        """Stop system monitoring"""
        self.performance_monitor.stop_monitoring()
        self.logger.info("Performance monitoring stopped")
    
    def create_backup(self, backup_type: str = 'full', include_attachments: bool = True) -> Dict[str, Any]:
        """
        Create system backup
        
        Args:
            backup_type: Type of backup ('full' or 'incremental')
            include_attachments: Whether to include attachment files
            
        Returns:
            Dict: Backup operation result
        """
        self.logger.info(f"Creating {backup_type} backup...")
        
        if backup_type == 'full':
            result = self.backup_manager.create_full_backup(include_attachments)
        elif backup_type == 'incremental':
            # Get last backup date
            backups = self.backup_manager.list_backups()
            if backups:
                last_backup_date = datetime.fromisoformat(backups[0]['timestamp'])
            else:
                last_backup_date = datetime.now() - timedelta(days=1)
            
            result = self.backup_manager.create_incremental_backup(last_backup_date)
        else:
            result = {'success': False, 'error': f'Unknown backup type: {backup_type}'}
        
        if result['success']:
            self.logger.info(f"Backup created successfully: {result.get('backup_id')}")
        else:
            self.logger.error(f"Backup failed: {result.get('error')}")
        
        return result
    
    def restore_backup(self, backup_id: str, restore_path: str = None) -> Dict[str, Any]:
        """
        Restore from backup
        
        Args:
            backup_id: ID of backup to restore
            restore_path: Path to restore to
            
        Returns:
            Dict: Restore operation result
        """
        self.logger.info(f"Restoring backup: {backup_id}")
        
        result = self.backup_manager.restore_backup(backup_id, restore_path)
        
        if result['success']:
            self.logger.info(f"Backup restored successfully: {result['files_restored']} files")
        else:
            self.logger.error(f"Restore failed: {result.get('error')}")
        
        return result
    
    def cleanup_system(self, cleanup_options: Dict[str, Any] = None) -> Dict[str, Any]:
        """
        Perform system cleanup
        
        Args:
            cleanup_options: Cleanup configuration options
            
        Returns:
            Dict: Cleanup operation result
        """
        options = cleanup_options or {}
        
        result = {
            'success': True,
            'operations': {},
            'total_space_freed_mb': 0,
            'errors': []
        }
        
        try:
            # Cleanup old backups
            if options.get('cleanup_backups', True):
                keep_days = options.get('backup_retention_days', 30)
                keep_count = options.get('backup_retention_count', 10)
                
                backup_cleanup = self.backup_manager.cleanup_old_backups(keep_days, keep_count)
                result['operations']['backup_cleanup'] = backup_cleanup
                result['total_space_freed_mb'] += backup_cleanup.get('space_freed_mb', 0)
            
            # Cleanup temporary files
            if options.get('cleanup_temp_files', True):
                temp_cleanup = self._cleanup_temp_files()
                result['operations']['temp_cleanup'] = temp_cleanup
                result['total_space_freed_mb'] += temp_cleanup.get('space_freed_mb', 0)
            
            # Cleanup log files
            if options.get('cleanup_logs', True):
                log_cleanup = self._cleanup_old_logs()
                result['operations']['log_cleanup'] = log_cleanup
                result['total_space_freed_mb'] += log_cleanup.get('space_freed_mb', 0)
            
            self.logger.info(f"System cleanup completed. Space freed: {result['total_space_freed_mb']:.2f} MB")
            
        except Exception as e:
            result['success'] = False
            result['errors'].append(str(e))
            self.logger.error(f"Error during system cleanup: {str(e)}")
        
        return result
    
    def validate_data_integrity(self, email_id: str = None) -> Dict[str, Any]:
        """
        Validate data integrity
        
        Args:
            email_id: Specific email ID to validate (None for all)
            
        Returns:
            Dict: Validation results
        """
        self.logger.info("Starting data integrity validation...")
        
        result = {
            'success': True,
            'total_emails_checked': 0,
            'validation_errors': 0,
            'validation_warnings': 0,
            'detailed_results': [],
            'summary': {}
        }
        
        try:
            json_path = os.path.join(self.base_path, "Json")
            
            if email_id:
                # Validate specific email
                email_dirs = [email_id] if os.path.exists(os.path.join(json_path, email_id)) else []
            else:
                # Validate all emails
                email_dirs = [d for d in os.listdir(json_path) if os.path.isdir(os.path.join(json_path, d))]
            
            for email_dir in email_dirs:
                email_json_path = os.path.join(json_path, email_dir, "email_data.json")
                
                if os.path.exists(email_json_path):
                    with open(email_json_path, 'r', encoding='utf-8') as f:
                        email_data = json.load(f)
                    
                    # Run QA check
                    qa_result = self.qa_system.run_full_qa_check(email_data)
                    result['detailed_results'].append(qa_result)
                    
                    # Count errors and warnings
                    for validation_type, validation in qa_result['validations'].items():
                        if isinstance(validation, dict):
                            result['validation_errors'] += len(validation.get('errors', []))
                            result['validation_warnings'] += len(validation.get('warnings', []))
                        elif isinstance(validation, list):
                            for val in validation:
                                result['validation_errors'] += len(val.get('errors', []))
                                result['validation_warnings'] += len(val.get('warnings', []))
                    
                    result['total_emails_checked'] += 1
            
            # Generate summary
            result['summary'] = self.qa_system.get_qa_summary()
            
            self.logger.info(f"Data validation completed. Checked {result['total_emails_checked']} emails")
            
        except Exception as e:
            result['success'] = False
            result['error'] = str(e)
            self.logger.error(f"Error during data validation: {str(e)}")
        
        return result
    
    def generate_report(self, report_type: str = 'comprehensive') -> Dict[str, Any]:
        """
        Generate system report
        
        Args:
            report_type: Type of report to generate
            
        Returns:
            Dict: Report data
        """
        self.logger.info(f"Generating {report_type} report...")
        
        report = {
            'report_type': report_type,
            'generated_at': datetime.now().isoformat(),
            'system_info': {},
            'processing_stats': {},
            'quality_metrics': {},
            'performance_data': {},
            'recommendations': []
        }
        
        try:
            # System information
            report['system_info'] = self.system_status()
            
            # Processing statistics
            report['processing_stats'] = self.processing_logger.get_processing_statistics()
            
            # Quality metrics
            report['quality_metrics'] = self.qa_system.get_qa_summary()
            
            # Performance data
            if self.performance_monitor.monitoring_active:
                report['performance_data'] = self.performance_monitor.get_performance_summary()
            
            # Generate recommendations
            report['recommendations'] = self._generate_recommendations(report)
            
            # Save report
            report_filename = f"system_report_{report_type}_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json"
            report_path = os.path.join(self.base_path, "reports", report_filename)
            os.makedirs(os.path.dirname(report_path), exist_ok=True)
            
            with open(report_path, 'w', encoding='utf-8') as f:
                json.dump(report, f, indent=2, default=str)
            
            report['report_file'] = report_path
            self.logger.info(f"Report saved to: {report_path}")
            
        except Exception as e:
            report['error'] = str(e)
            self.logger.error(f"Error generating report: {str(e)}")
        
        return report
    
    def _get_storage_info(self) -> Dict[str, Any]:
        """Get storage information"""
        storage_info = {}
        
        try:
            import psutil
            
            # Overall disk usage
            disk_usage = psutil.disk_usage(self.base_path)
            storage_info['disk'] = {
                'total_gb': disk_usage.total / (1024**3),
                'free_gb': disk_usage.free / (1024**3),
                'used_gb': disk_usage.used / (1024**3),
                'percent_used': (disk_usage.used / disk_usage.total) * 100
            }
            
            # Folder sizes
            folders = ['Json', 'Text', 'Archivos', 'Imagenes', 'backups']
            storage_info['folders'] = {}
            
            for folder in folders:
                folder_path = os.path.join(self.base_path, folder)
                if os.path.exists(folder_path):
                    size_bytes = self._get_folder_size(folder_path)
                    storage_info['folders'][folder] = {
                        'size_bytes': size_bytes,
                        'size_mb': size_bytes / (1024**2),
                        'size_gb': size_bytes / (1024**3)
                    }
        
        except Exception as e:
            storage_info['error'] = str(e)
        
        return storage_info
    
    def _get_folder_size(self, folder_path: str) -> int:
        """Get total size of folder"""
        total_size = 0
        for dirpath, dirnames, filenames in os.walk(folder_path):
            for filename in filenames:
                file_path = os.path.join(dirpath, filename)
                try:
                    total_size += os.path.getsize(file_path)
                except (OSError, FileNotFoundError):
                    pass
        return total_size
    
    def _cleanup_temp_files(self) -> Dict[str, Any]:
        """Cleanup temporary files"""
        # Implementation for cleaning temporary files
        return {'space_freed_mb': 0, 'files_deleted': 0}
    
    def _cleanup_old_logs(self) -> Dict[str, Any]:
        """Cleanup old log files"""
        # Implementation for cleaning old logs
        return {'space_freed_mb': 0, 'files_deleted': 0}
    
    def _generate_recommendations(self, report: Dict[str, Any]) -> List[str]:
        """Generate system recommendations based on report data"""
        recommendations = []
        
        # Check system health
        system_health = report.get('system_info', {}).get('system_health', {})
        if system_health.get('overall_status') != 'healthy':
            recommendations.extend(system_health.get('recommendations', []))
        
        # Check storage
        storage = report.get('system_info', {}).get('storage', {})
        disk_info = storage.get('disk', {})
        if disk_info.get('percent_used', 0) > 80:
            recommendations.append("Consider freeing up disk space or expanding storage")
        
        # Check quality metrics
        quality = report.get('quality_metrics', {})
        if quality.get('emails_needing_attention', 0) > 0:
            recommendations.append("Review emails with quality issues")
        
        return recommendations

def main():
    """Main function for command-line administration"""
    parser = argparse.ArgumentParser(description='Gmail Processing System Administration')
    parser.add_argument('command', choices=[
        'status', 'backup', 'restore', 'cleanup', 'validate', 'report', 'monitor'
    ], help='Administration command to execute')
    
    parser.add_argument('--backup-type', choices=['full', 'incremental'], default='full',
                       help='Type of backup to create')
    parser.add_argument('--backup-id', help='Backup ID for restore operation')
    parser.add_argument('--restore-path', help='Path to restore backup to')
    parser.add_argument('--email-id', help='Specific email ID to validate')
    parser.add_argument('--report-type', default='comprehensive', help='Type of report to generate')
    parser.add_argument('--base-path', help='Base path for ia folder')
    
    args = parser.parse_args()
    
    # Initialize admin interface
    admin = AdminInterface(args.base_path)
    
    try:
        if args.command == 'status':
            status = admin.system_status()
            print(json.dumps(status, indent=2, default=str))
        
        elif args.command == 'backup':
            result = admin.create_backup(args.backup_type)
            print(json.dumps(result, indent=2, default=str))
        
        elif args.command == 'restore':
            if not args.backup_id:
                print("Error: --backup-id required for restore operation")
                return
            result = admin.restore_backup(args.backup_id, args.restore_path)
            print(json.dumps(result, indent=2, default=str))
        
        elif args.command == 'cleanup':
            result = admin.cleanup_system()
            print(json.dumps(result, indent=2, default=str))
        
        elif args.command == 'validate':
            result = admin.validate_data_integrity(args.email_id)
            print(json.dumps(result, indent=2, default=str))
        
        elif args.command == 'report':
            result = admin.generate_report(args.report_type)
            print(json.dumps(result, indent=2, default=str))
        
        elif args.command == 'monitor':
            print("Starting monitoring... Press Ctrl+C to stop")
            admin.start_monitoring()
            try:
                import time
                while True:
                    time.sleep(10)
                    status = admin.system_status()
                    print(f"Status: {status['system_health']['overall_status']}")
            except KeyboardInterrupt:
                admin.stop_monitoring()
                print("\nMonitoring stopped")
    
    except Exception as e:
        print(f"Error executing command: {str(e)}")

if __name__ == "__main__":
    main()
