#!/usr/bin/env python3
"""
Single Email Processor for Vital Red System
Process individual emails manually or on-demand
"""

import os
import sys
import json
import logging
import argparse
from datetime import datetime
from typing import Dict, Any, Optional

# Add Functions directory to path
sys.path.append(os.path.join(os.path.dirname(__file__), 'Functions'))

from gmail_connector import GmailConnector
from gmail_to_medical_transformer import GmailToMedicalTransformer
from metadata_extractor import MetadataExtractor
from attachment_processor import AttachmentProcessor
from text_extractor import TextExtractor
from json_converter import JSONConverter
from medical_email_filter import MedicalEmailFilter
from enhanced_medical_analyzer import EnhancedMedicalAnalyzer
from medical_priority_classifier import MedicalPriorityClassifier
import requests

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('single_email_processing.log'),
        logging.StreamHandler()
    ]
)

logger = logging.getLogger(__name__)

class SingleEmailProcessor:
    """
    Process individual emails with comprehensive medical analysis
    """
    
    def __init__(self, config_file: str = None):
        """Initialize the single email processor"""
        self.base_path = os.path.dirname(os.path.abspath(__file__))
        self.config = self._load_config(config_file)
        
        # Initialize components
        self.gmail_connector = None
        self.medical_transformer = GmailToMedicalTransformer(self.base_path)
        self.metadata_extractor = MetadataExtractor()
        self.attachment_processor = AttachmentProcessor(self.base_path)
        self.text_extractor = TextExtractor(self.base_path)
        self.json_converter = JSONConverter(self.base_path)
        self.medical_filter = MedicalEmailFilter()
        self.medical_analyzer = EnhancedMedicalAnalyzer()
        self.priority_classifier = MedicalPriorityClassifier()
        
        # Laravel API configuration
        self.laravel_api_url = self.config.get('laravel_api_url', 'http://localhost:8000/api')
        self.laravel_api_token = self.config.get('laravel_api_token', '')
    
    def _load_config(self, config_file: str = None) -> Dict[str, Any]:
        """Load configuration from file or environment"""
        config = {
            'gmail_email': os.getenv('GMAIL_EMAIL'),
            'gmail_password': os.getenv('GMAIL_APP_PASSWORD'),
            'laravel_api_url': os.getenv('LARAVEL_API_URL', 'http://localhost:8000/api'),
            'laravel_api_token': os.getenv('LARAVEL_API_TOKEN', ''),
            'output_format': 'json',
            'save_to_file': True,
            'send_to_laravel': True
        }
        
        if config_file and os.path.exists(config_file):
            with open(config_file, 'r') as f:
                file_config = json.load(f)
                config.update(file_config)
        
        return config
    
    def process_email_by_id(self, email_id: str) -> Dict[str, Any]:
        """
        Process a specific email by its ID
        
        Args:
            email_id: Gmail email UID
            
        Returns:
            Dict: Processing results
        """
        try:
            logger.info(f"Starting processing of email ID: {email_id}")
            
            # Initialize Gmail connection
            if not self.gmail_connector:
                self.gmail_connector = GmailConnector(
                    self.config['gmail_email'], 
                    self.config['gmail_password']
                )
            
            # Fetch email
            email_message = self.gmail_connector.fetch_email(email_id)
            if not email_message:
                return {
                    'success': False,
                    'error': f'Could not fetch email with ID: {email_id}'
                }
            
            # Generate unique ID
            unique_id = self.gmail_connector.generate_unique_id(email_message)
            
            # Process email
            result = self._process_email_message(email_message, unique_id, email_id)
            
            logger.info(f"Email processing completed for ID: {email_id}")
            return result
            
        except Exception as e:
            logger.error(f"Error processing email {email_id}: {str(e)}")
            return {
                'success': False,
                'error': str(e),
                'email_id': email_id
            }
    
    def process_email_from_file(self, file_path: str) -> Dict[str, Any]:
        """
        Process email from saved .eml file
        
        Args:
            file_path: Path to .eml file
            
        Returns:
            Dict: Processing results
        """
        try:
            logger.info(f"Processing email from file: {file_path}")
            
            import email
            
            with open(file_path, 'r', encoding='utf-8') as f:
                email_message = email.message_from_file(f)
            
            # Generate unique ID based on file
            unique_id = f"file_{os.path.basename(file_path)}_{int(datetime.now().timestamp())}"
            
            # Process email
            result = self._process_email_message(email_message, unique_id, file_path)
            
            logger.info(f"File processing completed: {file_path}")
            return result
            
        except Exception as e:
            logger.error(f"Error processing file {file_path}: {str(e)}")
            return {
                'success': False,
                'error': str(e),
                'file_path': file_path
            }
    
    def _process_email_message(self, email_message, unique_id: str, source_id: str) -> Dict[str, Any]:
        """Process email message with comprehensive analysis"""
        try:
            processing_start = datetime.now()
            
            # Extract metadata
            metadata = self.metadata_extractor.extract_metadata(email_message, unique_id)
            
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
                'processing_time': (datetime.now() - processing_start).total_seconds(),
                'extraction_method': 'single_email_processor',
                'source_id': source_id,
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
            
            # Analyze if email is medical
            medical_analysis = self.medical_filter.analyze_email(professional_record)
            
            result = {
                'success': True,
                'unique_id': unique_id,
                'source_id': source_id,
                'is_medical': medical_analysis['is_medical'],
                'confidence_score': medical_analysis['confidence_score'],
                'professional_record': professional_record,
                'medical_analysis': medical_analysis,
                'processing_stats': processing_stats
            }
            
            # If medical, perform comprehensive analysis
            if medical_analysis['is_medical']:
                result.update(self._perform_medical_analysis(professional_record, medical_analysis))
            
            # Save results if configured
            if self.config.get('save_to_file', True):
                self._save_results(result, unique_id)
            
            # Send to Laravel if configured and medical
            if self.config.get('send_to_laravel', True) and medical_analysis['is_medical']:
                laravel_result = self._send_to_laravel(result)
                result['laravel_submission'] = laravel_result
            
            return result
            
        except Exception as e:
            logger.error(f"Error in email message processing: {str(e)}")
            return {
                'success': False,
                'error': str(e),
                'unique_id': unique_id,
                'source_id': source_id
            }
    
    def _perform_medical_analysis(self, professional_record: Dict[str, Any], medical_analysis: Dict[str, Any]) -> Dict[str, Any]:
        """Perform comprehensive medical analysis"""
        try:
            logger.info("Performing comprehensive medical analysis")
            
            # Enhanced medical text analysis
            text_content = self._extract_all_text(professional_record)
            enhanced_analysis = self.medical_analyzer.analyze_medical_text(text_content)
            
            # Priority classification
            priority_classification = self.priority_classifier.classify_priority(professional_record)
            
            # Transform to medical case
            medical_case = self.medical_transformer.transform_email_to_medical_case(professional_record)
            
            # Enhance medical case with analysis results
            if enhanced_analysis and not enhanced_analysis.get('error'):
                medical_case.update({
                    'paciente_edad': enhanced_analysis.get('patient_info', {}).get('age'),
                    'paciente_sexo': enhanced_analysis.get('patient_info', {}).get('gender'),
                    'frecuencia_cardiaca': enhanced_analysis.get('vital_signs', {}).get('heart_rate'),
                    'frecuencia_respiratoria': enhanced_analysis.get('vital_signs', {}).get('respiratory_rate'),
                    'temperatura': enhanced_analysis.get('vital_signs', {}).get('temperature'),
                    'tension_sistolica': enhanced_analysis.get('vital_signs', {}).get('blood_pressure_systolic'),
                    'tension_diastolica': enhanced_analysis.get('vital_signs', {}).get('blood_pressure_diastolic'),
                    'saturacion_oxigeno': enhanced_analysis.get('vital_signs', {}).get('oxygen_saturation'),
                    'escala_glasgow': enhanced_analysis.get('vital_signs', {}).get('glasgow_scale'),
                    'antecedentes_medicos': enhanced_analysis.get('clinical_data', {}).get('medical_history'),
                    'medicamentos_actuales': ', '.join(enhanced_analysis.get('medications', [])) if enhanced_analysis.get('medications') else None
                })
            
            # Enhance with priority classification
            if priority_classification and not priority_classification.get('error'):
                medical_case.update({
                    'prioridad': priority_classification.get('priority_level', 'Media'),
                    'score_urgencia': priority_classification.get('urgency_score'),
                    'criterios_priorizacion': priority_classification.get('criteria_explanation')
                })
            
            return {
                'enhanced_medical_analysis': enhanced_analysis,
                'priority_classification': priority_classification,
                'medical_case': medical_case
            }
            
        except Exception as e:
            logger.error(f"Error in medical analysis: {str(e)}")
            return {
                'enhanced_medical_analysis': {'error': str(e)},
                'priority_classification': {'error': str(e)},
                'medical_case': None
            }
    
    def _extract_all_text(self, professional_record: Dict[str, Any]) -> str:
        """Extract all text content from professional record"""
        try:
            text_parts = []
            
            # Subject
            content_analysis = professional_record.get('content_analysis', {})
            subject_info = content_analysis.get('subject_information', {})
            if subject_info.get('subject_line'):
                text_parts.append(subject_info['subject_line'])
            
            # Body content
            body_content = content_analysis.get('body_content', {})
            if body_content.get('plain_text_content'):
                text_parts.append(body_content['plain_text_content'])
            
            # Attachment text
            extracted_text = professional_record.get('extracted_text_data', {})
            if extracted_text.get('email_body'):
                text_parts.append(extracted_text['email_body'])
            
            if extracted_text.get('attachments'):
                for att in extracted_text['attachments']:
                    if att.get('text'):
                        text_parts.append(att['text'])
            
            return ' '.join(filter(None, text_parts))
            
        except Exception as e:
            logger.warning(f"Error extracting text: {str(e)}")
            return ""
    
    def _save_results(self, result: Dict[str, Any], unique_id: str) -> None:
        """Save processing results to file"""
        try:
            output_dir = os.path.join(self.base_path, 'output', 'single_processing')
            os.makedirs(output_dir, exist_ok=True)
            
            timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
            filename = f"email_processing_{unique_id}_{timestamp}.json"
            filepath = os.path.join(output_dir, filename)
            
            with open(filepath, 'w', encoding='utf-8') as f:
                json.dump(result, f, indent=2, ensure_ascii=False, default=str)
            
            logger.info(f"Results saved to: {filepath}")
            
        except Exception as e:
            logger.error(f"Error saving results: {str(e)}")
    
    def _send_to_laravel(self, result: Dict[str, Any]) -> Dict[str, Any]:
        """Send medical case to Laravel API"""
        try:
            if not result.get('medical_case'):
                return {'success': False, 'error': 'No medical case to send'}
            
            headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
            
            if self.laravel_api_token:
                headers['Authorization'] = f'Bearer {self.laravel_api_token}'
            
            response = requests.post(
                f"{self.laravel_api_url}/gmail-monitor/receive-medical-case",
                json=result['medical_case'],
                headers=headers,
                timeout=30
            )
            
            if response.status_code == 201:
                logger.info("Medical case sent to Laravel API successfully")
                return {
                    'success': True,
                    'response': response.json(),
                    'status_code': response.status_code
                }
            else:
                logger.warning(f"Laravel API returned status {response.status_code}: {response.text}")
                return {
                    'success': False,
                    'error': f"API returned status {response.status_code}",
                    'response': response.text
                }
                
        except Exception as e:
            logger.error(f"Error sending to Laravel API: {str(e)}")
            return {
                'success': False,
                'error': str(e)
            }

def main():
    """Main entry point"""
    parser = argparse.ArgumentParser(description='Process individual emails for medical analysis')
    parser.add_argument('--email-id', type=str, help='Gmail email UID to process')
    parser.add_argument('--file-path', type=str, help='Path to .eml file to process')
    parser.add_argument('--config', type=str, help='Path to configuration file')
    parser.add_argument('--output-format', choices=['json', 'summary'], default='json', help='Output format')
    parser.add_argument('--no-save', action='store_true', help='Do not save results to file')
    parser.add_argument('--no-laravel', action='store_true', help='Do not send to Laravel API')
    
    args = parser.parse_args()
    
    if not args.email_id and not args.file_path:
        parser.error("Either --email-id or --file-path must be specified")
    
    # Initialize processor
    processor = SingleEmailProcessor(args.config)
    
    # Override config based on arguments
    if args.no_save:
        processor.config['save_to_file'] = False
    if args.no_laravel:
        processor.config['send_to_laravel'] = False
    
    # Process email
    if args.email_id:
        result = processor.process_email_by_id(args.email_id)
    else:
        result = processor.process_email_from_file(args.file_path)
    
    # Output results
    if args.output_format == 'json':
        print(json.dumps(result, indent=2, ensure_ascii=False, default=str))
    else:
        # Summary format
        if result['success']:
            print(f"Processing successful: {result['unique_id']}")
            print(f"Medical email: {result.get('is_medical', False)}")
            if result.get('is_medical'):
                print(f"Confidence: {result.get('confidence_score', 0):.2f}")
                if result.get('medical_case'):
                    case = result['medical_case']
                    print(f"Patient: {case.get('paciente_nombre', 'Unknown')}")
                    print(f"Institution: {case.get('institucion_remitente', 'Unknown')}")
                    print(f"Priority: {case.get('prioridad', 'Unknown')}")
        else:
            print(f"Processing failed: {result.get('error', 'Unknown error')}")

if __name__ == "__main__":
    main()
