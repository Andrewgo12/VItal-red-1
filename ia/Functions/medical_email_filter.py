"""
Advanced Medical Email Filter for Vital Red System
Sophisticated filtering system to identify medical referral emails
"""

import re
import os
import json
import logging
from typing import Dict, List, Any, Tuple, Optional
from datetime import datetime
import hashlib

logger = logging.getLogger(__name__)

class MedicalEmailFilter:
    """
    Advanced filter to identify and classify medical emails
    """
    
    def __init__(self, config_path: str = None):
        """
        Initialize medical email filter
        
        Args:
            config_path: Path to filter configuration file
        """
        self.config = self._load_filter_config(config_path)
        
        # Medical institution domains
        self.medical_domains = [
            'hospital', 'clinica', 'clinic', 'eps', 'salud', 'health',
            'medico', 'medical', 'medicina', 'ips', 'eapb', 'healthcare',
            'sanitas', 'sura', 'compensar', 'famisanar', 'nuevaeps',
            'saludtotal', 'coomeva', 'cafesalud', 'colsanitas'
        ]
        
        # Medical file extensions
        self.medical_file_extensions = [
            '.pdf', '.doc', '.docx', '.jpg', '.jpeg', '.png', '.tiff',
            '.dcm', '.dicom', '.hl7', '.xml'
        ]
        
        # Urgency indicators
        self.urgency_keywords = [
            'urgente', 'urgent', 'emergencia', 'emergency', 'crítico', 'critico',
            'grave', 'severo', 'inmediato', 'immediate', 'prioritario', 'priority',
            'ambulancia', 'ambulance', 'uci', 'icu', 'shock', 'paro', 'arrest',
            'código azul', 'code blue', 'código rojo', 'code red'
        ]
        
        # Referral-specific keywords
        self.referral_keywords = [
            'referencia', 'referral', 'remisión', 'remision', 'traslado', 'transfer',
            'interconsulta', 'solicitud', 'request', 'autorización', 'autorizacion',
            'evaluación', 'evaluacion', 'valoración', 'valoracion', 'segunda opinión',
            'second opinion', 'nivel superior', 'especialista', 'specialist'
        ]
        
        # Medical specialties
        self.medical_specialties = [
            'cardiología', 'cardiology', 'neurología', 'neurology', 'ginecología',
            'gynecology', 'pediatría', 'pediatrics', 'ortopedia', 'orthopedics',
            'medicina interna', 'internal medicine', 'cirugía', 'surgery',
            'anestesiología', 'anesthesiology', 'radiología', 'radiology',
            'patología', 'pathology', 'oncología', 'oncology', 'psiquiatría',
            'psychiatry', 'dermatología', 'dermatology', 'oftalmología',
            'ophthalmology', 'otorrinolaringología', 'ent', 'urología', 'urology',
            'neumología', 'pulmonology', 'gastroenterología', 'gastroenterology',
            'endocrinología', 'endocrinology', 'reumatología', 'rheumatology',
            'hematología', 'hematology', 'infectología', 'infectious diseases',
            'nefrología', 'nephrology', 'geriatría', 'geriatrics'
        ]
        
        # Patient identification patterns
        self.patient_id_patterns = [
            r'\b(?:cc|cedula|cédula|id|identificación|identificacion)[\s:]*(\d{6,12})\b',
            r'\b(?:ti|tarjeta de identidad)[\s:]*(\d{6,12})\b',
            r'\b(?:ce|cedula extranjeria|cédula extranjería)[\s:]*(\d{6,12})\b',
            r'\b(?:pasaporte|passport)[\s:]*([a-zA-Z0-9]{6,12})\b'
        ]
        
        # Medical record patterns
        self.medical_record_patterns = [
            r'\b(?:historia|historia clínica|medical record|hc)[\s:]*(\d{6,12})\b',
            r'\b(?:número de historia|numero de historia)[\s:]*(\d{6,12})\b',
            r'\b(?:expediente|file)[\s:]*(\d{6,12})\b'
        ]
    
    def _load_filter_config(self, config_path: str = None) -> Dict[str, Any]:
        """Load filter configuration"""
        default_config = {
            'medical_threshold': 3,  # Minimum medical keywords required
            'urgency_threshold': 1,  # Minimum urgency keywords for high priority
            'referral_threshold': 1,  # Minimum referral keywords required
            'domain_weight': 2,      # Weight for medical domain senders
            'attachment_weight': 1,  # Weight for medical attachments
            'enable_fuzzy_matching': True,
            'min_confidence_score': 0.6,
            'enable_learning': True
        }
        
        if config_path and os.path.exists(config_path):
            try:
                with open(config_path, 'r', encoding='utf-8') as f:
                    file_config = json.load(f)
                    default_config.update(file_config)
            except Exception as e:
                logger.warning(f"Error loading filter config: {e}")
        
        return default_config
    
    def analyze_email(self, email_data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Comprehensive analysis of email to determine if it's medical
        
        Args:
            email_data: Email data from processor
            
        Returns:
            Dict: Analysis results with confidence scores
        """
        analysis = {
            'is_medical': False,
            'confidence_score': 0.0,
            'is_referral': False,
            'urgency_level': 'Baja',
            'detected_specialty': None,
            'detected_patient_info': {},
            'medical_indicators': [],
            'referral_indicators': [],
            'urgency_indicators': [],
            'sender_analysis': {},
            'attachment_analysis': {},
            'content_analysis': {}
        }
        
        try:
            # Extract text content for analysis
            text_content = self._extract_text_content(email_data)
            
            # Analyze sender
            sender_analysis = self._analyze_sender(email_data)
            analysis['sender_analysis'] = sender_analysis
            
            # Analyze content
            content_analysis = self._analyze_content(text_content)
            analysis['content_analysis'] = content_analysis
            
            # Analyze attachments
            attachment_analysis = self._analyze_attachments(email_data)
            analysis['attachment_analysis'] = attachment_analysis
            
            # Calculate confidence score
            confidence_score = self._calculate_confidence_score(
                sender_analysis, content_analysis, attachment_analysis
            )
            analysis['confidence_score'] = confidence_score
            
            # Determine if medical
            analysis['is_medical'] = confidence_score >= self.config['min_confidence_score']
            
            # Determine if referral
            analysis['is_referral'] = (
                content_analysis['referral_score'] >= self.config['referral_threshold'] and
                analysis['is_medical']
            )
            
            # Determine urgency level
            analysis['urgency_level'] = self._determine_urgency_level(content_analysis)
            
            # Extract patient information
            analysis['detected_patient_info'] = self._extract_patient_info(text_content)
            
            # Detect medical specialty
            analysis['detected_specialty'] = self._detect_specialty(text_content)
            
            # Compile indicators
            analysis['medical_indicators'] = content_analysis['medical_keywords_found']
            analysis['referral_indicators'] = content_analysis['referral_keywords_found']
            analysis['urgency_indicators'] = content_analysis['urgency_keywords_found']
            
            logger.info(f"Email analysis complete - Medical: {analysis['is_medical']}, "
                       f"Confidence: {confidence_score:.2f}, Urgency: {analysis['urgency_level']}")
            
            return analysis
            
        except Exception as e:
            logger.error(f"Error analyzing email: {str(e)}")
            return analysis
    
    def _extract_text_content(self, email_data: Dict[str, Any]) -> str:
        """Extract all text content from email"""
        text_content = ""
        
        try:
            # Extract from different email formats
            if email_data.get('source_type') == 'professional':
                content_analysis = email_data.get('content_analysis', {})
                subject = content_analysis.get('subject_information', {}).get('subject_line', '')
                body = content_analysis.get('body_content', {}).get('plain_text_content', '')
                text_content = f"{subject} {body}"
                
                # Add attachment text if available
                extracted_text = email_data.get('extracted_text_data', {})
                if extracted_text.get('attachments'):
                    for att in extracted_text['attachments']:
                        text_content += f" {att.get('text', '')}"
            else:
                # Traditional format
                metadata = email_data.get('metadata', {})
                subject = metadata.get('subject', '')
                content = email_data.get('content', {})
                body = content.get('text', '')
                text_content = f"{subject} {body}"
            
            return text_content.lower()
            
        except Exception as e:
            logger.warning(f"Error extracting text content: {str(e)}")
            return ""
    
    def _analyze_sender(self, email_data: Dict[str, Any]) -> Dict[str, Any]:
        """Analyze sender information"""
        analysis = {
            'is_medical_domain': False,
            'domain_score': 0,
            'sender_email': '',
            'sender_name': '',
            'institution_indicators': []
        }
        
        try:
            # Extract sender information
            if email_data.get('source_type') == 'professional':
                participants = email_data.get('communication_metadata', {}).get('participant_information', {})
                sender_details = participants.get('sender_details', [])
                if sender_details:
                    analysis['sender_email'] = sender_details[0].get('email_address', '').lower()
                    analysis['sender_name'] = sender_details[0].get('display_name', '')
            else:
                metadata = email_data.get('metadata', {})
                if metadata.get('from'):
                    analysis['sender_email'] = metadata['from'][0].get('email', '').lower()
                    analysis['sender_name'] = metadata['from'][0].get('name', '')
            
            # Check if sender domain is medical
            for domain in self.medical_domains:
                if domain in analysis['sender_email']:
                    analysis['is_medical_domain'] = True
                    analysis['domain_score'] += self.config['domain_weight']
                    analysis['institution_indicators'].append(domain)
            
            # Check sender name for medical indicators
            sender_name_lower = analysis['sender_name'].lower()
            medical_titles = ['dr', 'doctor', 'dra', 'doctora', 'md', 'medico', 'médico']
            for title in medical_titles:
                if title in sender_name_lower:
                    analysis['domain_score'] += 1
                    analysis['institution_indicators'].append(f"medical_title_{title}")
            
            return analysis
            
        except Exception as e:
            logger.warning(f"Error analyzing sender: {str(e)}")
            return analysis
    
    def _analyze_content(self, text_content: str) -> Dict[str, Any]:
        """Analyze email content for medical indicators"""
        analysis = {
            'medical_score': 0,
            'referral_score': 0,
            'urgency_score': 0,
            'medical_keywords_found': [],
            'referral_keywords_found': [],
            'urgency_keywords_found': [],
            'specialty_keywords_found': []
        }
        
        try:
            # Count medical keywords
            for keyword in self.medical_keywords:
                if keyword.lower() in text_content:
                    analysis['medical_score'] += 1
                    analysis['medical_keywords_found'].append(keyword)
            
            # Count referral keywords
            for keyword in self.referral_keywords:
                if keyword.lower() in text_content:
                    analysis['referral_score'] += 1
                    analysis['referral_keywords_found'].append(keyword)
            
            # Count urgency keywords
            for keyword in self.urgency_keywords:
                if keyword.lower() in text_content:
                    analysis['urgency_score'] += 1
                    analysis['urgency_keywords_found'].append(keyword)
            
            # Count specialty keywords
            for specialty in self.medical_specialties:
                if specialty.lower() in text_content:
                    analysis['specialty_keywords_found'].append(specialty)
            
            return analysis
            
        except Exception as e:
            logger.warning(f"Error analyzing content: {str(e)}")
            return analysis
    
    def _analyze_attachments(self, email_data: Dict[str, Any]) -> Dict[str, Any]:
        """Analyze email attachments for medical indicators"""
        analysis = {
            'has_medical_attachments': False,
            'attachment_score': 0,
            'medical_file_types': [],
            'attachment_count': 0
        }
        
        try:
            # Get attachment information
            attachments = []
            if email_data.get('source_type') == 'professional':
                attachment_info = email_data.get('attachment_information', {})
                attachments = attachment_info.get('attachment_details', [])
            else:
                attachments = email_data.get('attachments', {}).get('files', [])
            
            analysis['attachment_count'] = len(attachments)
            
            # Check for medical file types
            for attachment in attachments:
                filename = attachment.get('filename', '').lower()
                
                # Check file extension
                for ext in self.medical_file_extensions:
                    if filename.endswith(ext):
                        analysis['has_medical_attachments'] = True
                        analysis['attachment_score'] += self.config['attachment_weight']
                        analysis['medical_file_types'].append(ext)
                
                # Check filename for medical terms
                medical_file_terms = ['epicrisis', 'historia', 'medical', 'lab', 'resultado', 'examen']
                for term in medical_file_terms:
                    if term in filename:
                        analysis['attachment_score'] += 1
            
            return analysis
            
        except Exception as e:
            logger.warning(f"Error analyzing attachments: {str(e)}")
            return analysis
    
    def _calculate_confidence_score(self, sender_analysis: Dict, content_analysis: Dict, attachment_analysis: Dict) -> float:
        """Calculate overall confidence score"""
        try:
            # Base score from content
            base_score = min(content_analysis['medical_score'] / 10.0, 1.0)
            
            # Bonus from sender
            sender_bonus = min(sender_analysis['domain_score'] / 5.0, 0.3)
            
            # Bonus from attachments
            attachment_bonus = min(attachment_analysis['attachment_score'] / 3.0, 0.2)
            
            # Referral bonus
            referral_bonus = min(content_analysis['referral_score'] / 3.0, 0.2)
            
            # Calculate final score
            confidence_score = base_score + sender_bonus + attachment_bonus + referral_bonus
            
            return min(confidence_score, 1.0)
            
        except Exception as e:
            logger.warning(f"Error calculating confidence score: {str(e)}")
            return 0.0
    
    def _determine_urgency_level(self, content_analysis: Dict) -> str:
        """Determine urgency level based on content analysis"""
        urgency_score = content_analysis['urgency_score']
        
        if urgency_score >= 3:
            return 'Alta'
        elif urgency_score >= 1:
            return 'Media'
        else:
            return 'Baja'
    
    def _extract_patient_info(self, text_content: str) -> Dict[str, Any]:
        """Extract patient information from text"""
        patient_info = {
            'patient_id': None,
            'medical_record': None,
            'patient_name': None
        }
        
        try:
            # Extract patient ID
            for pattern in self.patient_id_patterns:
                match = re.search(pattern, text_content, re.IGNORECASE)
                if match:
                    patient_info['patient_id'] = match.group(1)
                    break
            
            # Extract medical record number
            for pattern in self.medical_record_patterns:
                match = re.search(pattern, text_content, re.IGNORECASE)
                if match:
                    patient_info['medical_record'] = match.group(1)
                    break
            
            # Extract patient name (basic pattern)
            name_patterns = [
                r'paciente:?\s*([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+(?:\s+[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)+)',
                r'nombre:?\s*([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+(?:\s+[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)+)'
            ]
            
            for pattern in name_patterns:
                match = re.search(pattern, text_content, re.IGNORECASE)
                if match:
                    patient_info['patient_name'] = match.group(1).strip()
                    break
            
            return patient_info
            
        except Exception as e:
            logger.warning(f"Error extracting patient info: {str(e)}")
            return patient_info
    
    def _detect_specialty(self, text_content: str) -> Optional[str]:
        """Detect medical specialty from content"""
        try:
            for specialty in self.medical_specialties:
                if specialty.lower() in text_content:
                    return specialty
            return None
            
        except Exception as e:
            logger.warning(f"Error detecting specialty: {str(e)}")
            return None
    
    def create_filter_report(self, analysis_results: List[Dict[str, Any]]) -> Dict[str, Any]:
        """Create comprehensive filter performance report"""
        try:
            total_emails = len(analysis_results)
            medical_emails = sum(1 for r in analysis_results if r['is_medical'])
            referral_emails = sum(1 for r in analysis_results if r['is_referral'])
            urgent_emails = sum(1 for r in analysis_results if r['urgency_level'] == 'Alta')
            
            avg_confidence = sum(r['confidence_score'] for r in analysis_results) / total_emails if total_emails > 0 else 0
            
            specialty_distribution = {}
            for result in analysis_results:
                if result['detected_specialty']:
                    specialty = result['detected_specialty']
                    specialty_distribution[specialty] = specialty_distribution.get(specialty, 0) + 1
            
            report = {
                'timestamp': datetime.now().isoformat(),
                'total_emails_analyzed': total_emails,
                'medical_emails_detected': medical_emails,
                'referral_emails_detected': referral_emails,
                'urgent_emails_detected': urgent_emails,
                'medical_detection_rate': medical_emails / total_emails if total_emails > 0 else 0,
                'average_confidence_score': avg_confidence,
                'specialty_distribution': specialty_distribution,
                'filter_configuration': self.config
            }
            
            return report
            
        except Exception as e:
            logger.error(f"Error creating filter report: {str(e)}")
            return {'error': str(e)}

# Add missing medical_keywords attribute
MedicalEmailFilter.medical_keywords = [
    # Términos básicos médicos
    'paciente', 'patient', 'diagnóstico', 'diagnosis', 'síntomas', 'symptoms',
    'médico', 'doctor', 'hospital', 'clínica', 'clinic', 'referencia', 'referral',
    'urgente', 'urgent', 'emergencia', 'emergency', 'consulta', 'consultation',
    'tratamiento', 'treatment', 'medicamento', 'medication', 'laboratorio', 'lab',
    'radiografía', 'x-ray', 'ecg', 'electrocardiograma', 'presión arterial',
    'blood pressure', 'frecuencia cardíaca', 'heart rate', 'temperatura', 'fever',
    
    # Términos específicos de referencia y contra-referencia
    'remisión', 'remision', 'traslado', 'transfer', 'interconsulta', 'eps',
    'ips', 'eapb', 'autorización', 'autorizacion', 'solicitud', 'request',
    'evaluación', 'evaluacion', 'valoración', 'valoracion', 'segunda opinión',
    'second opinion', 'especialista', 'specialist', 'nivel superior'
]
