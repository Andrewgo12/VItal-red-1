"""
Universal Data Transformer for Gmail Integration
Extracts multiple types of data from Gmail emails for all application views
"""

import os
import json
import re
from datetime import datetime, timedelta
from typing import Dict, List, Any, Optional
import logging
from gmail_to_medical_transformer import GmailToMedicalTransformer

logger = logging.getLogger(__name__)

class UniversalDataTransformer:
    """
    Transforms Gmail data into multiple data types for different application views
    """
    
    def __init__(self, base_path: str):
        self.base_path = base_path
        self.medical_transformer = GmailToMedicalTransformer(base_path)
        
        # Keywords for different data types
        self.admin_keywords = [
            'administración', 'admin', 'gestión', 'management', 'reporte', 'report',
            'estadística', 'statistics', 'facturación', 'billing', 'presupuesto', 'budget',
            'personal', 'staff', 'recursos', 'resources', 'política', 'policy'
        ]
        
        self.patient_keywords = [
            'paciente', 'patient', 'historia', 'history', 'expediente', 'record',
            'seguimiento', 'follow-up', 'control', 'check-up', 'cita', 'appointment',
            'medicamento', 'medication', 'tratamiento', 'treatment'
        ]
        
        self.urgency_keywords = [
            'urgencia', 'emergency', 'urgente', 'urgent', 'emergencia', 'crítico', 'critical',
            'inmediato', 'immediate', 'trauma', 'shock', 'paro', 'arrest', 'código', 'code'
        ]
        
        self.hospitalization_keywords = [
            'hospitalización', 'hospitalization', 'ingreso', 'admission', 'alta', 'discharge',
            'cama', 'bed', 'habitación', 'room', 'piso', 'floor', 'unidad', 'unit'
        ]
    
    def extract_admin_data(self, emails: List[Dict[str, Any]]) -> Dict[str, Any]:
        """
        Extract administrative data from emails for admin dashboard
        """
        try:
            admin_emails = []
            
            for email in emails:
                if self._is_admin_email(email):
                    admin_data = self._transform_to_admin_record(email)
                    admin_emails.append(admin_data)
            
            # Generate admin statistics
            admin_stats = self._calculate_admin_statistics(admin_emails)
            
            return {
                'metadata': {
                    'generated_at': datetime.now().isoformat(),
                    'total_records': len(admin_emails),
                    'source': 'Gmail Administrative Data',
                    'version': '1.0'
                },
                'records': admin_emails,
                'statistics': admin_stats
            }
            
        except Exception as e:
            logger.error(f"Error extracting admin data: {str(e)}")
            return self._get_empty_admin_data()
    
    def extract_patient_data(self, emails: List[Dict[str, Any]]) -> Dict[str, Any]:
        """
        Extract patient-related data from emails
        """
        try:
            patient_emails = []
            
            for email in emails:
                if self._is_patient_email(email):
                    patient_data = self._transform_to_patient_record(email)
                    patient_emails.append(patient_data)
            
            # Generate patient statistics
            patient_stats = self._calculate_patient_statistics(patient_emails)
            
            return {
                'metadata': {
                    'generated_at': datetime.now().isoformat(),
                    'total_records': len(patient_emails),
                    'source': 'Gmail Patient Data',
                    'version': '1.0'
                },
                'records': patient_emails,
                'statistics': patient_stats
            }
            
        except Exception as e:
            logger.error(f"Error extracting patient data: {str(e)}")
            return self._get_empty_patient_data()
    
    def extract_urgency_data(self, emails: List[Dict[str, Any]]) -> Dict[str, Any]:
        """
        Extract urgency/emergency data from emails
        """
        try:
            urgency_emails = []
            
            for email in emails:
                if self._is_urgency_email(email):
                    urgency_data = self._transform_to_urgency_record(email)
                    urgency_emails.append(urgency_data)
            
            # Generate urgency statistics
            urgency_stats = self._calculate_urgency_statistics(urgency_emails)
            
            return {
                'metadata': {
                    'generated_at': datetime.now().isoformat(),
                    'total_records': len(urgency_emails),
                    'source': 'Gmail Emergency Data',
                    'version': '1.0'
                },
                'records': urgency_emails,
                'statistics': urgency_stats
            }
            
        except Exception as e:
            logger.error(f"Error extracting urgency data: {str(e)}")
            return self._get_empty_urgency_data()
    
    def extract_hospitalization_data(self, emails: List[Dict[str, Any]]) -> Dict[str, Any]:
        """
        Extract hospitalization data from emails
        """
        try:
            hospitalization_emails = []
            
            for email in emails:
                if self._is_hospitalization_email(email):
                    hosp_data = self._transform_to_hospitalization_record(email)
                    hospitalization_emails.append(hosp_data)
            
            # Generate hospitalization statistics
            hosp_stats = self._calculate_hospitalization_statistics(hospitalization_emails)
            
            return {
                'metadata': {
                    'generated_at': datetime.now().isoformat(),
                    'total_records': len(hospitalization_emails),
                    'source': 'Gmail Hospitalization Data',
                    'version': '1.0'
                },
                'records': hospitalization_emails,
                'statistics': hosp_stats
            }
            
        except Exception as e:
            logger.error(f"Error extracting hospitalization data: {str(e)}")
            return self._get_empty_hospitalization_data()
    
    def extract_historical_data(self, emails: List[Dict[str, Any]]) -> Dict[str, Any]:
        """
        Extract historical data for reports and analytics
        """
        try:
            # Get all medical cases first
            medical_cases = self.medical_transformer.transform_all_medical_emails()
            
            # Transform to historical records
            historical_records = []
            for case in medical_cases:
                historical_record = self._transform_to_historical_record(case)
                historical_records.append(historical_record)
            
            # Generate historical statistics
            historical_stats = self._calculate_historical_statistics(historical_records)
            
            return {
                'metadata': {
                    'generated_at': datetime.now().isoformat(),
                    'total_records': len(historical_records),
                    'source': 'Gmail Historical Data',
                    'version': '1.0'
                },
                'records': historical_records,
                'statistics': historical_stats
            }
            
        except Exception as e:
            logger.error(f"Error extracting historical data: {str(e)}")
            return self._get_empty_historical_data()
    
    def _is_admin_email(self, email: Dict[str, Any]) -> bool:
        """Check if email contains administrative content"""
        text_content = self._get_email_text(email)
        return any(keyword in text_content.lower() for keyword in self.admin_keywords)
    
    def _is_patient_email(self, email: Dict[str, Any]) -> bool:
        """Check if email contains patient-related content"""
        text_content = self._get_email_text(email)
        return any(keyword in text_content.lower() for keyword in self.patient_keywords)
    
    def _is_urgency_email(self, email: Dict[str, Any]) -> bool:
        """Check if email contains urgency/emergency content"""
        text_content = self._get_email_text(email)
        return any(keyword in text_content.lower() for keyword in self.urgency_keywords)
    
    def _is_hospitalization_email(self, email: Dict[str, Any]) -> bool:
        """Check if email contains hospitalization content"""
        text_content = self._get_email_text(email)
        return any(keyword in text_content.lower() for keyword in self.hospitalization_keywords)
    
    def _get_email_text(self, email: Dict[str, Any]) -> str:
        """Extract text content from email regardless of schema type"""
        try:
            if email.get('source_type') == 'professional':
                content_analysis = email.get('content_analysis', {})
                subject = content_analysis.get('subject_information', {}).get('subject_line', '')
                body = content_analysis.get('body_content', {}).get('plain_text_content', '')
                return f"{subject} {body}"
            else:
                metadata = email.get('metadata', {})
                subject = metadata.get('subject', '')
                content = email.get('content', {})
                body = content.get('text', '')
                return f"{subject} {body}"
        except Exception:
            return ""
    
    def _transform_to_admin_record(self, email: Dict[str, Any]) -> Dict[str, Any]:
        """Transform email to admin record"""
        text_content = self._get_email_text(email)
        
        return {
            'id': f"ADM-{email.get('document_identification', {}).get('unique_identifier', 'unknown')[-8:]}",
            'type': 'administrative',
            'subject': self._extract_subject(email),
            'sender': self._extract_sender(email),
            'date': self._extract_date(email),
            'category': self._determine_admin_category(text_content),
            'priority': self._determine_priority(text_content),
            'status': 'Pendiente',
            'summary': text_content[:200] + '...' if len(text_content) > 200 else text_content,
            'emailSource': {
                'originalSubject': self._extract_subject(email),
                'originalSender': self._extract_sender(email),
                'sourceType': email.get('source_type', 'unknown')
            }
        }
    
    def _transform_to_patient_record(self, email: Dict[str, Any]) -> Dict[str, Any]:
        """Transform email to patient record"""
        text_content = self._get_email_text(email)
        
        return {
            'id': f"PAT-{email.get('document_identification', {}).get('unique_identifier', 'unknown')[-8:]}",
            'patientName': self._extract_patient_name(text_content),
            'patientId': self._extract_patient_id(text_content),
            'type': 'patient_communication',
            'subject': self._extract_subject(email),
            'sender': self._extract_sender(email),
            'date': self._extract_date(email),
            'category': self._determine_patient_category(text_content),
            'status': 'Activo',
            'summary': text_content[:200] + '...' if len(text_content) > 200 else text_content
        }
    
    def _transform_to_urgency_record(self, email: Dict[str, Any]) -> Dict[str, Any]:
        """Transform email to urgency record"""
        text_content = self._get_email_text(email)
        
        return {
            'id': f"URG-{email.get('document_identification', {}).get('unique_identifier', 'unknown')[-8:]}",
            'patientName': self._extract_patient_name(text_content),
            'urgencyLevel': self._determine_urgency_level(text_content),
            'condition': self._extract_condition(text_content),
            'arrivalTime': self._extract_date(email),
            'triageCategory': self._determine_triage_category(text_content),
            'status': 'En Triaje',
            'location': self._extract_location(text_content),
            'summary': text_content[:200] + '...' if len(text_content) > 200 else text_content
        }
    
    def _transform_to_hospitalization_record(self, email: Dict[str, Any]) -> Dict[str, Any]:
        """Transform email to hospitalization record"""
        text_content = self._get_email_text(email)
        
        return {
            'id': f"HOSP-{email.get('document_identification', {}).get('unique_identifier', 'unknown')[-8:]}",
            'patientName': self._extract_patient_name(text_content),
            'admissionDate': self._extract_date(email),
            'ward': self._extract_ward(text_content),
            'bedNumber': self._extract_bed_number(text_content),
            'condition': self._extract_condition(text_content),
            'status': 'Hospitalizado',
            'expectedDischarge': self._calculate_expected_discharge(self._extract_date(email)),
            'summary': text_content[:200] + '...' if len(text_content) > 200 else text_content
        }
    
    def _transform_to_historical_record(self, medical_case: Dict[str, Any]) -> Dict[str, Any]:
        """Transform medical case to historical record"""
        return {
            'id': medical_case.get('id', ''),
            'patient': medical_case.get('patient', ''),
            'age': medical_case.get('age', 0),
            'diagnosis': medical_case.get('diagnosis', ''),
            'decision': 'Aceptado',  # Assuming processed cases are accepted
            'priority': medical_case.get('priority', 'Media'),
            'specialty': medical_case.get('specialty', ''),
            'origin': medical_case.get('origin', ''),
            'evaluatedAt': medical_case.get('receivedAt', ''),
            'responseTime': medical_case.get('timeElapsed', ''),
            'observations': medical_case.get('clinicalSummary', ''),
            'outcome': 'Procesado exitosamente',
            'emailSource': medical_case.get('emailSource', {})
        }
    
    # Helper methods for extraction
    def _extract_subject(self, email: Dict[str, Any]) -> str:
        """Extract subject from email"""
        if email.get('source_type') == 'professional':
            return email.get('content_analysis', {}).get('subject_information', {}).get('subject_line', '')
        else:
            return email.get('metadata', {}).get('subject', '')
    
    def _extract_sender(self, email: Dict[str, Any]) -> str:
        """Extract sender from email"""
        if email.get('source_type') == 'professional':
            sender_details = email.get('communication_metadata', {}).get('participant_information', {}).get('sender_details', [])
            return sender_details[0].get('email_address', '') if sender_details else ''
        else:
            from_list = email.get('metadata', {}).get('from', [])
            return from_list[0].get('email', '') if from_list else ''
    
    def _extract_date(self, email: Dict[str, Any]) -> str:
        """Extract date from email"""
        if email.get('source_type') == 'professional':
            return email.get('communication_metadata', {}).get('temporal_information', {}).get('sent_datetime', '')
        else:
            return email.get('metadata', {}).get('date', '')
    
    def _extract_patient_name(self, text: str) -> str:
        """Extract patient name from text"""
        return self.medical_transformer._extract_patient_name(text)
    
    def _extract_patient_id(self, text: str) -> str:
        """Extract patient ID from text"""
        return self.medical_transformer._extract_patient_id(text)
    
    def _extract_condition(self, text: str) -> str:
        """Extract medical condition from text"""
        return self.medical_transformer._extract_diagnosis(text)
    
    def _extract_location(self, text: str) -> str:
        """Extract location from text"""
        patterns = [
            r'hospital\s+([A-Za-z\s]+)',
            r'clínica\s+([A-Za-z\s]+)',
            r'centro\s+([A-Za-z\s]+)'
        ]
        
        for pattern in patterns:
            match = re.search(pattern, text, re.IGNORECASE)
            if match:
                return match.group(1).strip()
        
        return 'Ubicación no especificada'
    
    def _extract_ward(self, text: str) -> str:
        """Extract ward information from text"""
        patterns = [
            r'piso\s+(\d+)',
            r'unidad\s+([A-Za-z\s]+)',
            r'sala\s+([A-Za-z\s]+)'
        ]
        
        for pattern in patterns:
            match = re.search(pattern, text, re.IGNORECASE)
            if match:
                return match.group(1).strip()
        
        return 'Piso General'
    
    def _extract_bed_number(self, text: str) -> str:
        """Extract bed number from text"""
        patterns = [
            r'cama\s+(\d+)',
            r'habitación\s+(\d+)',
            r'cuarto\s+(\d+)'
        ]
        
        for pattern in patterns:
            match = re.search(pattern, text, re.IGNORECASE)
            if match:
                return match.group(1)
        
        return '101'  # Default bed number
    
    def _calculate_expected_discharge(self, admission_date: str) -> str:
        """Calculate expected discharge date"""
        try:
            if admission_date:
                admission_dt = datetime.fromisoformat(admission_date.replace('Z', '+00:00'))
                expected_discharge = admission_dt + timedelta(days=5)  # Default 5 days
                return expected_discharge.isoformat()
        except:
            pass
        
        return (datetime.now() + timedelta(days=5)).isoformat()
    
    # Category determination methods
    def _determine_admin_category(self, text: str) -> str:
        """Determine administrative category"""
        text_lower = text.lower()
        
        if any(word in text_lower for word in ['facturación', 'billing', 'presupuesto', 'budget']):
            return 'Financiero'
        elif any(word in text_lower for word in ['personal', 'staff', 'recursos', 'resources']):
            return 'Recursos Humanos'
        elif any(word in text_lower for word in ['reporte', 'report', 'estadística', 'statistics']):
            return 'Reportes'
        else:
            return 'General'
    
    def _determine_patient_category(self, text: str) -> str:
        """Determine patient category"""
        text_lower = text.lower()
        
        if any(word in text_lower for word in ['seguimiento', 'follow-up', 'control']):
            return 'Seguimiento'
        elif any(word in text_lower for word in ['cita', 'appointment']):
            return 'Citas'
        elif any(word in text_lower for word in ['medicamento', 'medication']):
            return 'Medicamentos'
        else:
            return 'General'
    
    def _determine_urgency_level(self, text: str) -> str:
        """Determine urgency level"""
        text_lower = text.lower()
        
        if any(word in text_lower for word in ['crítico', 'critical', 'paro', 'arrest']):
            return 'Crítico'
        elif any(word in text_lower for word in ['urgente', 'urgent', 'emergencia', 'emergency']):
            return 'Urgente'
        else:
            return 'Moderado'
    
    def _determine_triage_category(self, text: str) -> str:
        """Determine triage category"""
        urgency_level = self._determine_urgency_level(text)
        
        if urgency_level == 'Crítico':
            return 'Rojo'
        elif urgency_level == 'Urgente':
            return 'Amarillo'
        else:
            return 'Verde'
    
    def _determine_priority(self, text: str) -> str:
        """Determine priority level"""
        return self.medical_transformer.determine_priority(text)
    
    # Statistics calculation methods
    def _calculate_admin_statistics(self, records: List[Dict[str, Any]]) -> Dict[str, Any]:
        """Calculate administrative statistics"""
        categories = {}
        priorities = {}
        
        for record in records:
            category = record.get('category', 'General')
            priority = record.get('priority', 'Media')
            
            categories[category] = categories.get(category, 0) + 1
            priorities[priority] = priorities.get(priority, 0) + 1
        
        return {
            'by_category': categories,
            'by_priority': priorities,
            'total_pending': len([r for r in records if r.get('status') == 'Pendiente'])
        }
    
    def _calculate_patient_statistics(self, records: List[Dict[str, Any]]) -> Dict[str, Any]:
        """Calculate patient statistics"""
        categories = {}
        
        for record in records:
            category = record.get('category', 'General')
            categories[category] = categories.get(category, 0) + 1
        
        return {
            'by_category': categories,
            'total_active': len([r for r in records if r.get('status') == 'Activo'])
        }
    
    def _calculate_urgency_statistics(self, records: List[Dict[str, Any]]) -> Dict[str, Any]:
        """Calculate urgency statistics"""
        levels = {}
        triage = {}
        
        for record in records:
            level = record.get('urgencyLevel', 'Moderado')
            triage_cat = record.get('triageCategory', 'Verde')
            
            levels[level] = levels.get(level, 0) + 1
            triage[triage_cat] = triage.get(triage_cat, 0) + 1
        
        return {
            'by_urgency_level': levels,
            'by_triage_category': triage,
            'total_in_triage': len([r for r in records if r.get('status') == 'En Triaje'])
        }
    
    def _calculate_hospitalization_statistics(self, records: List[Dict[str, Any]]) -> Dict[str, Any]:
        """Calculate hospitalization statistics"""
        wards = {}
        
        for record in records:
            ward = record.get('ward', 'General')
            wards[ward] = wards.get(ward, 0) + 1
        
        return {
            'by_ward': wards,
            'total_hospitalized': len([r for r in records if r.get('status') == 'Hospitalizado'])
        }
    
    def _calculate_historical_statistics(self, records: List[Dict[str, Any]]) -> Dict[str, Any]:
        """Calculate historical statistics"""
        decisions = {}
        specialties = {}
        priorities = {}
        
        for record in records:
            decision = record.get('decision', 'Pendiente')
            specialty = record.get('specialty', 'General')
            priority = record.get('priority', 'Media')
            
            decisions[decision] = decisions.get(decision, 0) + 1
            specialties[specialty] = specialties.get(specialty, 0) + 1
            priorities[priority] = priorities.get(priority, 0) + 1
        
        return {
            'by_decision': decisions,
            'by_specialty': specialties,
            'by_priority': priorities
        }
    
    # Empty data fallbacks
    def _get_empty_admin_data(self) -> Dict[str, Any]:
        """Return empty admin data structure"""
        return {
            'metadata': {
                'generated_at': datetime.now().isoformat(),
                'total_records': 0,
                'source': 'Gmail Administrative Data - Empty',
                'version': '1.0'
            },
            'records': [],
            'statistics': {'by_category': {}, 'by_priority': {}, 'total_pending': 0}
        }
    
    def _get_empty_patient_data(self) -> Dict[str, Any]:
        """Return empty patient data structure"""
        return {
            'metadata': {
                'generated_at': datetime.now().isoformat(),
                'total_records': 0,
                'source': 'Gmail Patient Data - Empty',
                'version': '1.0'
            },
            'records': [],
            'statistics': {'by_category': {}, 'total_active': 0}
        }
    
    def _get_empty_urgency_data(self) -> Dict[str, Any]:
        """Return empty urgency data structure"""
        return {
            'metadata': {
                'generated_at': datetime.now().isoformat(),
                'total_records': 0,
                'source': 'Gmail Emergency Data - Empty',
                'version': '1.0'
            },
            'records': [],
            'statistics': {'by_urgency_level': {}, 'by_triage_category': {}, 'total_in_triage': 0}
        }
    
    def _get_empty_hospitalization_data(self) -> Dict[str, Any]:
        """Return empty hospitalization data structure"""
        return {
            'metadata': {
                'generated_at': datetime.now().isoformat(),
                'total_records': 0,
                'source': 'Gmail Hospitalization Data - Empty',
                'version': '1.0'
            },
            'records': [],
            'statistics': {'by_ward': {}, 'total_hospitalized': 0}
        }
    
    def _get_empty_historical_data(self) -> Dict[str, Any]:
        """Return empty historical data structure"""
        return {
            'metadata': {
                'generated_at': datetime.now().isoformat(),
                'total_records': 0,
                'source': 'Gmail Historical Data - Empty',
                'version': '1.0'
            },
            'records': [],
            'statistics': {'by_decision': {}, 'by_specialty': {}, 'by_priority': {}}
        }
    
    def generate_all_data_types(self) -> Dict[str, Any]:
        """
        Generate all data types from Gmail emails
        
        Returns:
            Dict containing all data types for different views
        """
        try:
            # Load processed emails
            emails = self.medical_transformer.load_processed_emails()
            
            # Extract all data types
            all_data = {
                'medical_cases': self.medical_transformer.transform_all_medical_emails(),
                'admin_data': self.extract_admin_data(emails),
                'patient_data': self.extract_patient_data(emails),
                'urgency_data': self.extract_urgency_data(emails),
                'hospitalization_data': self.extract_hospitalization_data(emails),
                'historical_data': self.extract_historical_data(emails)
            }
            
            return all_data
            
        except Exception as e:
            logger.error(f"Error generating all data types: {str(e)}")
            return {
                'medical_cases': [],
                'admin_data': self._get_empty_admin_data(),
                'patient_data': self._get_empty_patient_data(),
                'urgency_data': self._get_empty_urgency_data(),
                'hospitalization_data': self._get_empty_hospitalization_data(),
                'historical_data': self._get_empty_historical_data()
            }
    
    def save_all_data_types(self, output_dir: str = None) -> Dict[str, str]:
        """
        Save all data types to separate JSON files
        
        Args:
            output_dir: Output directory (defaults to base_path)
            
        Returns:
            Dict with file paths for each data type
        """
        try:
            if not output_dir:
                output_dir = self.base_path
            
            # Generate all data
            all_data = self.generate_all_data_types()
            
            # Save each data type
            file_paths = {}
            
            for data_type, data in all_data.items():
                filename = f"{data_type}_for_frontend.json"
                file_path = os.path.join(output_dir, filename)
                
                with open(file_path, 'w', encoding='utf-8') as f:
                    json.dump(data, f, indent=2, ensure_ascii=False)
                
                file_paths[data_type] = file_path
                logger.info(f"Saved {data_type} to: {file_path}")
            
            return file_paths
            
        except Exception as e:
            logger.error(f"Error saving all data types: {str(e)}")
            return {}
