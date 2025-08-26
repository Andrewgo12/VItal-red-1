#!/usr/bin/env python3
"""
Continuous Gmail Processor for Vital Red Medical System
Monitors Gmail inbox continuously for new medical referral emails
"""

import os
import sys
import time
import json
import logging
import threading
from datetime import datetime, timedelta
from typing import Dict, List, Any, Optional
import schedule

# Add Functions directory to path
sys.path.append(os.path.join(os.path.dirname(__file__), 'Functions'))

from gmail_connector import GmailConnector
from gmail_to_medical_transformer import GmailToMedicalTransformer
from metadata_extractor import MetadataExtractor
from attachment_processor import AttachmentProcessor
from text_extractor import TextExtractor
from json_converter import JSONConverter
from monitoring import PerformanceMonitor
from data_validator import QualityAssurance
import requests

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('continuous_gmail_processing.log'),
        logging.StreamHandler()
    ]
)

logger = logging.getLogger(__name__)

class ContinuousGmailProcessor:
    """
    Continuous Gmail processor for medical referral emails
    """
    
    def __init__(self, config_file: str = None):
        """
        Initialize continuous processor
        
        Args:
            config_file: Path to configuration file
        """
        self.base_path = os.path.dirname(os.path.abspath(__file__))
        self.config = self._load_config(config_file)
        self.is_running = False
        self.last_check = datetime.now() - timedelta(hours=1)
        
        # Initialize components
        self.gmail_connector = None
        self.medical_transformer = GmailToMedicalTransformer(self.base_path)
        self.metadata_extractor = MetadataExtractor()
        self.attachment_processor = AttachmentProcessor(self.base_path)
        self.text_extractor = TextExtractor(self.base_path)
        self.json_converter = JSONConverter(self.base_path)
        self.performance_monitor = PerformanceMonitor()
        self.qa_system = QualityAssurance()
        
        # Processing statistics
        self.stats = {
            'total_emails_checked': 0,
            'medical_emails_found': 0,
            'emails_processed': 0,
            'processing_errors': 0,
            'last_check_time': None,
            'uptime_start': datetime.now()
        }
        
        # Laravel API configuration
        self.laravel_api_url = self.config.get('laravel_api_url', 'http://localhost:8000/api')
        self.laravel_api_token = self.config.get('laravel_api_token', '')
        
    def _load_config(self, config_file: str = None) -> Dict[str, Any]:
        """Load configuration from file or environment"""
        config = {
            'gmail_email': os.getenv('GMAIL_EMAIL'),
            'gmail_password': os.getenv('GMAIL_APP_PASSWORD'),
            'check_interval_minutes': int(os.getenv('CHECK_INTERVAL_MINUTES', '5')),
            'max_emails_per_check': int(os.getenv('MAX_EMAILS_PER_CHECK', '50')),
            'laravel_api_url': os.getenv('LARAVEL_API_URL', 'http://localhost:8000/api'),
            'laravel_api_token': os.getenv('LARAVEL_API_TOKEN', ''),
            'medical_keywords_threshold': int(os.getenv('MEDICAL_KEYWORDS_THRESHOLD', '2')),
            'enable_real_time_notifications': os.getenv('ENABLE_REAL_TIME_NOTIFICATIONS', 'true').lower() == 'true'
        }
        
        if config_file and os.path.exists(config_file):
            with open(config_file, 'r') as f:
                file_config = json.load(f)
                config.update(file_config)
        
        return config
    
    def start_monitoring(self):
        """Start continuous monitoring of Gmail"""
        logger.info("Starting continuous Gmail monitoring for medical emails")
        logger.info(f"Check interval: {self.config['check_interval_minutes']} minutes")
        logger.info(f"Max emails per check: {self.config['max_emails_per_check']}")
        
        self.is_running = True
        
        # Initialize Gmail connection
        try:
            self.gmail_connector = GmailConnector(
                self.config['gmail_email'], 
                self.config['gmail_password']
            )
            logger.info("Gmail connection established successfully")
        except Exception as e:
            logger.error(f"Failed to establish Gmail connection: {str(e)}")
            return False
        
        # Schedule periodic checks
        schedule.every(self.config['check_interval_minutes']).minutes.do(self._check_for_new_emails)
        
        # Start performance monitoring
        self.performance_monitor.start_monitoring()
        
        # Main monitoring loop
        try:
            while self.is_running:
                schedule.run_pending()
                time.sleep(30)  # Check every 30 seconds for scheduled tasks
                
        except KeyboardInterrupt:
            logger.info("Received interrupt signal, stopping monitoring...")
            self.stop_monitoring()
        except Exception as e:
            logger.error(f"Unexpected error in monitoring loop: {str(e)}")
            self.stop_monitoring()
    
    def stop_monitoring(self):
        """Stop continuous monitoring"""
        logger.info("Stopping continuous Gmail monitoring")
        self.is_running = False
        
        if self.gmail_connector:
            self.gmail_connector.disconnect()
        
        self.performance_monitor.stop_monitoring()
        
        # Log final statistics
        uptime = datetime.now() - self.stats['uptime_start']
        logger.info(f"Final statistics:")
        logger.info(f"  Uptime: {uptime}")
        logger.info(f"  Total emails checked: {self.stats['total_emails_checked']}")
        logger.info(f"  Medical emails found: {self.stats['medical_emails_found']}")
        logger.info(f"  Emails processed: {self.stats['emails_processed']}")
        logger.info(f"  Processing errors: {self.stats['processing_errors']}")
    
    def _check_for_new_emails(self):
        """Check for new emails since last check"""
        try:
            logger.info("Checking for new medical emails...")
            
            # Calculate time range for search
            since_time = self.last_check
            current_time = datetime.now()
            
            # Search for emails received since last check
            search_criteria = f'SINCE "{since_time.strftime("%d-%b-%Y")}"'
            
            # Get email IDs
            email_ids = self.gmail_connector.search_emails(
                criteria=search_criteria,
                folder_name="INBOX"
            )
            
            if not email_ids:
                logger.info("No new emails found")
                self.last_check = current_time
                return
            
            # Limit emails to process
            email_ids = email_ids[:self.config['max_emails_per_check']]
            self.stats['total_emails_checked'] += len(email_ids)
            
            logger.info(f"Found {len(email_ids)} new emails to check")
            
            # Process each email
            medical_emails_found = 0
            for email_id in email_ids:
                try:
                    if self._process_single_email(email_id):
                        medical_emails_found += 1
                        self.stats['medical_emails_found'] += 1
                        
                except Exception as e:
                    logger.error(f"Error processing email {email_id}: {str(e)}")
                    self.stats['processing_errors'] += 1
            
            logger.info(f"Processed {medical_emails_found} medical emails out of {len(email_ids)} total emails")
            
            self.last_check = current_time
            self.stats['last_check_time'] = current_time
            
        except Exception as e:
            logger.error(f"Error in email check cycle: {str(e)}")
            self.stats['processing_errors'] += 1
    
    def _process_single_email(self, email_id: str) -> bool:
        """
        Process a single email and determine if it's medical
        
        Args:
            email_id: Email UID
            
        Returns:
            bool: True if email was medical and processed
        """
        try:
            # Fetch email
            email_message = self.gmail_connector.fetch_email(email_id)
            if not email_message:
                return False
            
            # Generate unique ID
            unique_id = self.gmail_connector.generate_unique_id(email_message)
            
            # Extract metadata
            metadata = self.metadata_extractor.extract_metadata(email_message, unique_id)
            
            # Quick check if email is medical-related
            if not self._is_medical_email_quick_check(metadata, email_message):
                return False
            
            logger.info(f"Medical email detected: {metadata.get('subject', 'No subject')}")
            
            # Full processing for medical emails
            return self._process_medical_email(email_id, email_message, unique_id, metadata)
            
        except Exception as e:
            logger.error(f"Error processing email {email_id}: {str(e)}")
            return False
    
    def _is_medical_email_quick_check(self, metadata: Dict[str, Any], email_message) -> bool:
        """Quick check to determine if email is medical-related"""
        try:
            # Check subject line
            subject = metadata.get('subject', '').lower()
            
            # Check sender domain/email
            sender_email = ''
            if metadata.get('from'):
                sender_email = metadata['from'][0].get('email', '').lower()
            
            # Medical keywords in subject
            medical_keywords = [
                'paciente', 'patient', 'referencia', 'referral', 'remision', 'remisión',
                'urgente', 'urgent', 'hospital', 'clinica', 'clínica', 'medico', 'médico',
                'doctor', 'consulta', 'consultation', 'traslado', 'transfer', 'eps',
                'diagnostico', 'diagnóstico', 'tratamiento', 'treatment'
            ]
            
            keyword_count = sum(1 for keyword in medical_keywords if keyword in subject)
            
            # Check if sender is from medical institution
            medical_domains = [
                'hospital', 'clinica', 'eps', 'salud', 'medico', 'health',
                'clinic', 'medical', 'medicina', 'ips'
            ]
            
            is_medical_sender = any(domain in sender_email for domain in medical_domains)
            
            # Consider medical if has keywords or is from medical sender
            return keyword_count >= self.config['medical_keywords_threshold'] or is_medical_sender
            
        except Exception as e:
            logger.warning(f"Error in quick medical check: {str(e)}")
            return True  # Process anyway if unsure
    
    def _process_medical_email(self, email_id: str, email_message, unique_id: str, metadata: Dict[str, Any]) -> bool:
        """Process confirmed medical email"""
        try:
            start_time = time.time()
            
            # Process attachments
            attachments = self.attachment_processor.process_email_attachments(email_message, unique_id)
            
            # Extract text content
            body_content = self.text_extractor.extract_email_body(email_message)
            
            # Extract text from attachments
            extracted_text_data = {
                'email_body': body_content,
                'attachments': []
            }
            
            for attachment in attachments:
                if attachment.get('saved_successfully'):
                    extracted_text = self.text_extractor.extract_text_from_file(
                        attachment['file_path'], 
                        attachment['original_filename']
                    )
                    extracted_text_data['attachments'].append({
                        'filename': attachment['original_filename'],
                        'text': extracted_text
                    })
            
            # Create professional record
            processing_stats = {
                'processing_time': time.time() - start_time,
                'extraction_method': 'continuous_monitoring',
                'email_id': email_id,
                'unique_id': unique_id
            }
            
            professional_record = self.json_converter.create_professional_email_record(
                unique_id,
                metadata,
                body_content,
                attachments,
                extracted_text_data,
                processing_stats
            )
            
            # Save professional record
            self.json_converter.save_professional_email_record(unique_id, professional_record)
            
            # Transform to medical case
            medical_case = self.medical_transformer.transform_email_to_medical_case(professional_record)
            
            # Send to Laravel API
            if self.config['laravel_api_url']:
                self._send_to_laravel_api(medical_case)
            
            # Send real-time notification if enabled
            if self.config['enable_real_time_notifications'] and medical_case.get('prioridad') == 'Alta':
                self._send_urgent_notification(medical_case)
            
            self.stats['emails_processed'] += 1
            
            logger.info(f"Successfully processed medical email: {unique_id}")
            return True
            
        except Exception as e:
            logger.error(f"Error processing medical email {unique_id}: {str(e)}")
            self.stats['processing_errors'] += 1
            return False
    
    def _send_to_laravel_api(self, medical_case: Dict[str, Any]):
        """Send processed medical case to Laravel API"""
        try:
            headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
            
            if self.laravel_api_token:
                headers['Authorization'] = f'Bearer {self.laravel_api_token}'
            
            response = requests.post(
                f"{self.laravel_api_url}/solicitudes-medicas",
                json=medical_case,
                headers=headers,
                timeout=30
            )
            
            if response.status_code == 201:
                logger.info(f"Medical case sent to Laravel API successfully")
            else:
                logger.warning(f"Laravel API returned status {response.status_code}: {response.text}")
                
        except Exception as e:
            logger.error(f"Error sending to Laravel API: {str(e)}")
    
    def _send_urgent_notification(self, medical_case: Dict[str, Any]):
        """Send urgent notification for high-priority cases"""
        try:
            notification_data = {
                'type': 'urgent_medical_case',
                'patient_name': medical_case.get('paciente_nombre', 'Paciente no identificado'),
                'institution': medical_case.get('institucion_remitente', 'Institución no identificada'),
                'priority': medical_case.get('prioridad', 'Alta'),
                'specialty': medical_case.get('especialidad_solicitada', 'No especificada'),
                'timestamp': datetime.now().isoformat()
            }
            
            # Send to Laravel notification endpoint
            if self.laravel_api_url:
                requests.post(
                    f"{self.laravel_api_url}/notificaciones/urgente",
                    json=notification_data,
                    timeout=10
                )
            
            logger.info(f"Urgent notification sent for case: {medical_case.get('paciente_nombre')}")
            
        except Exception as e:
            logger.error(f"Error sending urgent notification: {str(e)}")
    
    def get_status(self) -> Dict[str, Any]:
        """Get current processor status"""
        return {
            'is_running': self.is_running,
            'uptime': str(datetime.now() - self.stats['uptime_start']),
            'last_check': self.stats['last_check_time'].isoformat() if self.stats['last_check_time'] else None,
            'statistics': self.stats,
            'configuration': {
                'check_interval_minutes': self.config['check_interval_minutes'],
                'max_emails_per_check': self.config['max_emails_per_check'],
                'gmail_email': self.config['gmail_email']
            }
        }

def main():
    """Main entry point"""
    processor = ContinuousGmailProcessor()
    
    try:
        processor.start_monitoring()
    except KeyboardInterrupt:
        logger.info("Shutting down gracefully...")
    finally:
        processor.stop_monitoring()

if __name__ == "__main__":
    main()
