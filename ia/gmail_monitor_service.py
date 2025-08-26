#!/usr/bin/env python3
"""
Gmail Monitor Service for Vital Red
Windows service wrapper for continuous Gmail monitoring
"""

import os
import sys
import time
import json
import logging
import signal
import threading
from pathlib import Path
from continuous_gmail_processor import ContinuousGmailProcessor

# Configure logging for service
log_dir = Path(__file__).parent / 'logs'
log_dir.mkdir(exist_ok=True)

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler(log_dir / 'gmail_monitor_service.log'),
        logging.StreamHandler()
    ]
)

logger = logging.getLogger(__name__)

class GmailMonitorService:
    """
    Service wrapper for Gmail monitoring
    """
    
    def __init__(self):
        self.processor = None
        self.is_running = False
        self.monitor_thread = None
        
        # Service configuration
        self.config_file = Path(__file__).parent / 'service_config.json'
        self.pid_file = Path(__file__).parent / 'gmail_monitor.pid'
        self.status_file = Path(__file__).parent / 'service_status.json'
        
        # Load configuration
        self.config = self._load_service_config()
        
        # Setup signal handlers
        signal.signal(signal.SIGINT, self._signal_handler)
        signal.signal(signal.SIGTERM, self._signal_handler)
    
    def _load_service_config(self) -> dict:
        """Load service configuration"""
        default_config = {
            'auto_restart': True,
            'restart_delay_seconds': 30,
            'max_restart_attempts': 5,
            'health_check_interval': 300,  # 5 minutes
            'log_rotation_days': 7,
            'enable_status_reporting': True
        }
        
        if self.config_file.exists():
            try:
                with open(self.config_file, 'r') as f:
                    file_config = json.load(f)
                    default_config.update(file_config)
            except Exception as e:
                logger.warning(f"Error loading service config: {e}")
        
        return default_config
    
    def _signal_handler(self, signum, frame):
        """Handle shutdown signals"""
        logger.info(f"Received signal {signum}, shutting down service...")
        self.stop()
    
    def start(self):
        """Start the Gmail monitor service"""
        if self.is_running:
            logger.warning("Service is already running")
            return False
        
        logger.info("Starting Gmail Monitor Service")
        
        # Write PID file
        try:
            with open(self.pid_file, 'w') as f:
                f.write(str(os.getpid()))
        except Exception as e:
            logger.error(f"Error writing PID file: {e}")
        
        self.is_running = True
        
        # Start monitoring in separate thread
        self.monitor_thread = threading.Thread(target=self._run_monitor, daemon=True)
        self.monitor_thread.start()
        
        # Start health check thread
        health_thread = threading.Thread(target=self._health_check_loop, daemon=True)
        health_thread.start()
        
        # Start status reporting thread
        if self.config['enable_status_reporting']:
            status_thread = threading.Thread(target=self._status_reporting_loop, daemon=True)
            status_thread.start()
        
        logger.info("Gmail Monitor Service started successfully")
        
        # Keep main thread alive
        try:
            while self.is_running:
                time.sleep(1)
        except KeyboardInterrupt:
            logger.info("Received keyboard interrupt")
        finally:
            self.stop()
        
        return True
    
    def stop(self):
        """Stop the Gmail monitor service"""
        if not self.is_running:
            return
        
        logger.info("Stopping Gmail Monitor Service")
        self.is_running = False
        
        # Stop processor
        if self.processor:
            self.processor.stop_monitoring()
        
        # Wait for monitor thread to finish
        if self.monitor_thread and self.monitor_thread.is_alive():
            self.monitor_thread.join(timeout=10)
        
        # Clean up PID file
        try:
            if self.pid_file.exists():
                self.pid_file.unlink()
        except Exception as e:
            logger.error(f"Error removing PID file: {e}")
        
        # Update status file
        self._update_status_file('stopped')
        
        logger.info("Gmail Monitor Service stopped")
    
    def _run_monitor(self):
        """Run the Gmail processor with auto-restart capability"""
        restart_attempts = 0
        
        while self.is_running and restart_attempts < self.config['max_restart_attempts']:
            try:
                logger.info(f"Starting Gmail processor (attempt {restart_attempts + 1})")
                
                # Create and start processor
                self.processor = ContinuousGmailProcessor()
                self._update_status_file('running')
                
                # Start monitoring
                self.processor.start_monitoring()
                
                # If we get here, monitoring stopped normally
                break
                
            except Exception as e:
                logger.error(f"Gmail processor error: {e}")
                restart_attempts += 1
                
                if restart_attempts < self.config['max_restart_attempts'] and self.config['auto_restart']:
                    logger.info(f"Restarting in {self.config['restart_delay_seconds']} seconds...")
                    self._update_status_file('restarting')
                    time.sleep(self.config['restart_delay_seconds'])
                else:
                    logger.error("Max restart attempts reached or auto-restart disabled")
                    self._update_status_file('failed')
                    self.is_running = False
                    break
    
    def _health_check_loop(self):
        """Periodic health check"""
        while self.is_running:
            try:
                time.sleep(self.config['health_check_interval'])
                
                if not self.is_running:
                    break
                
                # Check if processor is still running
                if self.processor and not self.processor.is_running:
                    logger.warning("Processor stopped unexpectedly")
                    if self.config['auto_restart']:
                        logger.info("Triggering restart...")
                        # The monitor thread will handle restart
                
                # Update health status
                self._update_health_status()
                
            except Exception as e:
                logger.error(f"Health check error: {e}")
    
    def _status_reporting_loop(self):
        """Periodic status reporting"""
        while self.is_running:
            try:
                time.sleep(60)  # Report every minute
                
                if self.processor:
                    status = self.processor.get_status()
                    self._update_status_file('running', status)
                
            except Exception as e:
                logger.error(f"Status reporting error: {e}")
    
    def _update_status_file(self, status: str, details: dict = None):
        """Update service status file"""
        try:
            status_data = {
                'status': status,
                'timestamp': time.time(),
                'pid': os.getpid(),
                'details': details or {}
            }
            
            with open(self.status_file, 'w') as f:
                json.dump(status_data, f, indent=2, default=str)
                
        except Exception as e:
            logger.error(f"Error updating status file: {e}")
    
    def _update_health_status(self):
        """Update health status"""
        try:
            health_data = {
                'service_running': self.is_running,
                'processor_running': self.processor.is_running if self.processor else False,
                'last_health_check': time.time(),
                'uptime': time.time() - (self.processor.stats['uptime_start'].timestamp() if self.processor else time.time())
            }
            
            health_file = Path(__file__).parent / 'health_status.json'
            with open(health_file, 'w') as f:
                json.dump(health_data, f, indent=2, default=str)
                
        except Exception as e:
            logger.error(f"Error updating health status: {e}")
    
    def get_status(self) -> dict:
        """Get current service status"""
        try:
            if self.status_file.exists():
                with open(self.status_file, 'r') as f:
                    return json.load(f)
        except Exception as e:
            logger.error(f"Error reading status file: {e}")
        
        return {'status': 'unknown', 'error': 'Status file not available'}
    
    def is_service_running(self) -> bool:
        """Check if service is running based on PID file"""
        if not self.pid_file.exists():
            return False
        
        try:
            with open(self.pid_file, 'r') as f:
                pid = int(f.read().strip())
            
            # Check if process is still running
            try:
                os.kill(pid, 0)  # Signal 0 just checks if process exists
                return True
            except OSError:
                # Process doesn't exist, clean up PID file
                self.pid_file.unlink()
                return False
                
        except Exception as e:
            logger.error(f"Error checking service status: {e}")
            return False

def main():
    """Main entry point for service"""
    import argparse
    
    parser = argparse.ArgumentParser(description='Gmail Monitor Service for Vital Red')
    parser.add_argument('action', choices=['start', 'stop', 'restart', 'status'], 
                       help='Service action to perform')
    
    args = parser.parse_args()
    
    service = GmailMonitorService()
    
    if args.action == 'start':
        if service.is_service_running():
            print("Service is already running")
            sys.exit(1)
        else:
            service.start()
    
    elif args.action == 'stop':
        if service.is_service_running():
            # Send signal to running service
            try:
                with open(service.pid_file, 'r') as f:
                    pid = int(f.read().strip())
                os.kill(pid, signal.SIGTERM)
                print("Stop signal sent to service")
            except Exception as e:
                print(f"Error stopping service: {e}")
                sys.exit(1)
        else:
            print("Service is not running")
    
    elif args.action == 'restart':
        if service.is_service_running():
            # Stop first
            try:
                with open(service.pid_file, 'r') as f:
                    pid = int(f.read().strip())
                os.kill(pid, signal.SIGTERM)
                time.sleep(5)  # Wait for graceful shutdown
            except Exception as e:
                print(f"Error stopping service: {e}")
        
        # Start
        service.start()
    
    elif args.action == 'status':
        if service.is_service_running():
            status = service.get_status()
            print(f"Service Status: {json.dumps(status, indent=2, default=str)}")
        else:
            print("Service is not running")

if __name__ == "__main__":
    main()
