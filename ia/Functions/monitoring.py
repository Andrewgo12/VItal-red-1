"""
Advanced Monitoring and Logging System
Provides comprehensive monitoring, performance tracking, and alerting
"""

import os
import sys
import time
import psutil
import logging
import json
from typing import Dict, List, Any, Optional
from datetime import datetime, timedelta
import threading
from collections import deque, defaultdict

class PerformanceMonitor:
    """
    Monitor system performance during email processing
    """
    
    def __init__(self, max_history: int = 1000):
        """
        Initialize performance monitor
        
        Args:
            max_history: Maximum number of performance samples to keep
        """
        self.max_history = max_history
        self.metrics_history = deque(maxlen=max_history)
        self.start_time = None
        self.monitoring_active = False
        self.monitor_thread = None
        self.lock = threading.Lock()
        
        # Performance thresholds
        self.thresholds = {
            'cpu_percent': 80.0,
            'memory_percent': 85.0,
            'disk_usage_percent': 90.0,
            'processing_time_per_email': 60.0  # seconds
        }
        
        # Alert counters
        self.alerts = defaultdict(int)
    
    def start_monitoring(self, interval: float = 5.0):
        """
        Start performance monitoring in background thread
        
        Args:
            interval: Monitoring interval in seconds
        """
        if self.monitoring_active:
            return
        
        self.monitoring_active = True
        self.start_time = datetime.now()
        
        def monitor_loop():
            while self.monitoring_active:
                try:
                    metrics = self.collect_metrics()
                    with self.lock:
                        self.metrics_history.append(metrics)
                    
                    # Check for alerts
                    self.check_alerts(metrics)
                    
                    time.sleep(interval)
                except Exception as e:
                    logging.error(f"Error in monitoring loop: {str(e)}")
        
        self.monitor_thread = threading.Thread(target=monitor_loop, daemon=True)
        self.monitor_thread.start()
    
    def stop_monitoring(self):
        """Stop performance monitoring"""
        self.monitoring_active = False
        if self.monitor_thread:
            self.monitor_thread.join(timeout=10)
    
    def collect_metrics(self) -> Dict[str, Any]:
        """
        Collect current system metrics
        
        Returns:
            Dict: Current system metrics
        """
        try:
            # CPU metrics
            cpu_percent = psutil.cpu_percent(interval=1)
            cpu_count = psutil.cpu_count()
            
            # Memory metrics
            memory = psutil.virtual_memory()
            
            # Disk metrics
            disk = psutil.disk_usage('/')
            
            # Process metrics
            process = psutil.Process()
            process_memory = process.memory_info()
            
            metrics = {
                'timestamp': datetime.now().isoformat(),
                'cpu': {
                    'percent': cpu_percent,
                    'count': cpu_count,
                    'load_avg': os.getloadavg() if hasattr(os, 'getloadavg') else [0, 0, 0]
                },
                'memory': {
                    'total_gb': memory.total / (1024**3),
                    'available_gb': memory.available / (1024**3),
                    'percent_used': memory.percent,
                    'process_rss_mb': process_memory.rss / (1024**2),
                    'process_vms_mb': process_memory.vms / (1024**2)
                },
                'disk': {
                    'total_gb': disk.total / (1024**3),
                    'free_gb': disk.free / (1024**3),
                    'percent_used': (disk.used / disk.total) * 100
                },
                'network': self.get_network_stats(),
                'uptime_seconds': (datetime.now() - self.start_time).total_seconds() if self.start_time else 0
            }
            
            return metrics
            
        except Exception as e:
            logging.error(f"Error collecting metrics: {str(e)}")
            return {'timestamp': datetime.now().isoformat(), 'error': str(e)}
    
    def get_network_stats(self) -> Dict[str, int]:
        """Get network I/O statistics"""
        try:
            net_io = psutil.net_io_counters()
            return {
                'bytes_sent': net_io.bytes_sent,
                'bytes_recv': net_io.bytes_recv,
                'packets_sent': net_io.packets_sent,
                'packets_recv': net_io.packets_recv
            }
        except:
            return {'bytes_sent': 0, 'bytes_recv': 0, 'packets_sent': 0, 'packets_recv': 0}
    
    def check_alerts(self, metrics: Dict[str, Any]):
        """
        Check metrics against thresholds and generate alerts
        
        Args:
            metrics: Current metrics
        """
        alerts = []
        
        # CPU alert
        cpu_percent = metrics.get('cpu', {}).get('percent', 0)
        if cpu_percent > self.thresholds['cpu_percent']:
            alerts.append(f"High CPU usage: {cpu_percent:.1f}%")
            self.alerts['high_cpu'] += 1
        
        # Memory alert
        memory_percent = metrics.get('memory', {}).get('percent_used', 0)
        if memory_percent > self.thresholds['memory_percent']:
            alerts.append(f"High memory usage: {memory_percent:.1f}%")
            self.alerts['high_memory'] += 1
        
        # Disk alert
        disk_percent = metrics.get('disk', {}).get('percent_used', 0)
        if disk_percent > self.thresholds['disk_usage_percent']:
            alerts.append(f"High disk usage: {disk_percent:.1f}%")
            self.alerts['high_disk'] += 1
        
        # Log alerts
        for alert in alerts:
            logging.warning(f"PERFORMANCE ALERT: {alert}")
    
    def get_performance_summary(self) -> Dict[str, Any]:
        """
        Get performance summary statistics
        
        Returns:
            Dict: Performance summary
        """
        with self.lock:
            if not self.metrics_history:
                return {}
            
            # Calculate averages and peaks
            cpu_values = [m.get('cpu', {}).get('percent', 0) for m in self.metrics_history]
            memory_values = [m.get('memory', {}).get('percent_used', 0) for m in self.metrics_history]
            
            summary = {
                'monitoring_duration_minutes': (datetime.now() - self.start_time).total_seconds() / 60 if self.start_time else 0,
                'samples_collected': len(self.metrics_history),
                'cpu': {
                    'average_percent': sum(cpu_values) / len(cpu_values) if cpu_values else 0,
                    'peak_percent': max(cpu_values) if cpu_values else 0,
                    'min_percent': min(cpu_values) if cpu_values else 0
                },
                'memory': {
                    'average_percent': sum(memory_values) / len(memory_values) if memory_values else 0,
                    'peak_percent': max(memory_values) if memory_values else 0,
                    'min_percent': min(memory_values) if memory_values else 0
                },
                'alerts_triggered': dict(self.alerts),
                'last_metrics': self.metrics_history[-1] if self.metrics_history else {}
            }
            
            return summary

class ProcessingLogger:
    """
    Advanced logging system for email processing
    """
    
    def __init__(self, log_dir: str = "logs"):
        """
        Initialize processing logger
        
        Args:
            log_dir: Directory for log files
        """
        self.log_dir = log_dir
        os.makedirs(log_dir, exist_ok=True)
        
        # Setup loggers
        self.setup_loggers()
        
        # Processing statistics
        self.processing_stats = {
            'emails_processed': 0,
            'emails_failed': 0,
            'attachments_processed': 0,
            'total_processing_time': 0,
            'errors': [],
            'warnings': []
        }
    
    def setup_loggers(self):
        """Setup different loggers for different purposes"""
        
        # Main processing logger
        self.main_logger = logging.getLogger('gmail_processor')
        self.main_logger.setLevel(logging.INFO)
        
        # Error logger
        self.error_logger = logging.getLogger('gmail_errors')
        self.error_logger.setLevel(logging.ERROR)
        
        # Performance logger
        self.perf_logger = logging.getLogger('gmail_performance')
        self.perf_logger.setLevel(logging.INFO)
        
        # Create handlers
        main_handler = logging.FileHandler(os.path.join(self.log_dir, 'processing.log'))
        error_handler = logging.FileHandler(os.path.join(self.log_dir, 'errors.log'))
        perf_handler = logging.FileHandler(os.path.join(self.log_dir, 'performance.log'))
        
        # Create formatters
        detailed_formatter = logging.Formatter(
            '%(asctime)s - %(name)s - %(levelname)s - %(funcName)s:%(lineno)d - %(message)s'
        )
        
        # Set formatters
        main_handler.setFormatter(detailed_formatter)
        error_handler.setFormatter(detailed_formatter)
        perf_handler.setFormatter(detailed_formatter)
        
        # Add handlers
        self.main_logger.addHandler(main_handler)
        self.error_logger.addHandler(error_handler)
        self.perf_logger.addHandler(perf_handler)

        # Store handlers for cleanup
        self.handlers = [main_handler, error_handler, perf_handler]

    def cleanup_handlers(self):
        """Clean up log handlers to release file locks"""
        try:
            for handler in getattr(self, 'handlers', []):
                try:
                    if hasattr(handler, 'stream') and handler.stream:
                        handler.stream.close()
                    handler.close()
                except:
                    pass

            # Remove handlers from loggers
            for logger in [self.main_logger, self.error_logger, self.perf_logger]:
                for handler in logger.handlers[:]:
                    try:
                        logger.removeHandler(handler)
                    except:
                        pass
        except:
            pass
    
    def log_email_processing_start(self, email_id: str, unique_id: str):
        """Log start of email processing"""
        self.main_logger.info(f"Starting processing - Email ID: {email_id}, Unique ID: {unique_id}")
    
    def log_email_processing_complete(self, unique_id: str, processing_time: float, 
                                    attachment_count: int, success: bool):
        """Log completion of email processing"""
        if success:
            self.main_logger.info(
                f"Completed processing - Unique ID: {unique_id}, "
                f"Time: {processing_time:.2f}s, Attachments: {attachment_count}"
            )
            self.processing_stats['emails_processed'] += 1
        else:
            self.error_logger.error(
                f"Failed processing - Unique ID: {unique_id}, Time: {processing_time:.2f}s"
            )
            self.processing_stats['emails_failed'] += 1
        
        self.processing_stats['total_processing_time'] += processing_time
        self.processing_stats['attachments_processed'] += attachment_count
    
    def log_performance_metrics(self, metrics: Dict[str, Any]):
        """Log performance metrics"""
        self.perf_logger.info(f"Performance metrics: {json.dumps(metrics, indent=2)}")
    
    def log_error(self, error_type: str, error_message: str, context: Dict[str, Any] = None):
        """Log error with context"""
        error_entry = {
            'timestamp': datetime.now().isoformat(),
            'type': error_type,
            'message': error_message,
            'context': context or {}
        }
        
        self.error_logger.error(json.dumps(error_entry, indent=2))
        self.processing_stats['errors'].append(error_entry)
    
    def log_warning(self, warning_message: str, context: Dict[str, Any] = None):
        """Log warning with context"""
        warning_entry = {
            'timestamp': datetime.now().isoformat(),
            'message': warning_message,
            'context': context or {}
        }
        
        self.main_logger.warning(json.dumps(warning_entry, indent=2))
        self.processing_stats['warnings'].append(warning_entry)
    
    def get_processing_statistics(self) -> Dict[str, Any]:
        """Get processing statistics"""
        stats = self.processing_stats.copy()
        
        if stats['emails_processed'] > 0:
            stats['average_processing_time'] = stats['total_processing_time'] / stats['emails_processed']
            stats['success_rate'] = stats['emails_processed'] / (stats['emails_processed'] + stats['emails_failed'])
        else:
            stats['average_processing_time'] = 0
            stats['success_rate'] = 0
        
        return stats
    
    def create_processing_report(self, output_path: str):
        """Create comprehensive processing report"""
        stats = self.get_processing_statistics()
        
        report = {
            'report_generated': datetime.now().isoformat(),
            'processing_statistics': stats,
            'recent_errors': stats['errors'][-10:],  # Last 10 errors
            'recent_warnings': stats['warnings'][-10:]  # Last 10 warnings
        }
        
        with open(output_path, 'w') as f:
            json.dump(report, f, indent=2, default=str)

class SystemHealthChecker:
    """
    Check system health and requirements
    """
    
    @staticmethod
    def check_system_requirements() -> Dict[str, Any]:
        """
        Check if system meets requirements for email processing
        
        Returns:
            Dict: System health check results
        """
        health_check = {
            'timestamp': datetime.now().isoformat(),
            'overall_status': 'healthy',
            'checks': {},
            'recommendations': []
        }
        
        # Check available memory
        memory = psutil.virtual_memory()
        memory_gb = memory.available / (1024**3)
        
        health_check['checks']['memory'] = {
            'available_gb': memory_gb,
            'status': 'good' if memory_gb > 2 else 'warning' if memory_gb > 1 else 'critical'
        }
        
        if memory_gb < 2:
            health_check['recommendations'].append("Consider increasing available memory for better performance")
        
        # Check disk space
        disk = psutil.disk_usage('/')
        free_gb = disk.free / (1024**3)
        
        health_check['checks']['disk_space'] = {
            'free_gb': free_gb,
            'status': 'good' if free_gb > 10 else 'warning' if free_gb > 5 else 'critical'
        }
        
        if free_gb < 10:
            health_check['recommendations'].append("Ensure sufficient disk space for email storage")
        
        # Check CPU
        cpu_count = psutil.cpu_count()
        health_check['checks']['cpu'] = {
            'core_count': cpu_count,
            'status': 'good' if cpu_count >= 4 else 'warning' if cpu_count >= 2 else 'critical'
        }
        
        # Check Python version
        python_version = sys.version_info
        health_check['checks']['python_version'] = {
            'version': f"{python_version.major}.{python_version.minor}.{python_version.micro}",
            'status': 'good' if python_version >= (3, 7) else 'critical'
        }
        
        # Overall status
        statuses = [check['status'] for check in health_check['checks'].values()]
        if 'critical' in statuses:
            health_check['overall_status'] = 'critical'
        elif 'warning' in statuses:
            health_check['overall_status'] = 'warning'
        
        return health_check
