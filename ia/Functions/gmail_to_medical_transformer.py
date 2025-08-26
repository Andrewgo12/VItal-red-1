"""
Gmail to Medical Case Transformer
Convierte emails procesados de Gmail en casos médicos para las vistas del frontend
"""

import os
import json
import re
from datetime import datetime, timedelta
from typing import Dict, List, Any, Optional
import logging

logger = logging.getLogger(__name__)

class GmailToMedicalTransformer:
    """
    Transforma datos de Gmail procesados en casos médicos para el frontend
    """

    def __init__(self, base_path: str):
        """
        Initialize transformer

        Args:
            base_path: Base path for the ia folder
        """
        self.base_path = base_path
        self.json_path = os.path.join(base_path, "Json")
        self.professional_path = os.path.join(base_path, "Professional_Email_Records")

        # Patrones para identificar emails médicos - Expandido
        self.medical_keywords = [
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
            'second opinion', 'especialista', 'specialist', 'nivel superior',

            # Términos de urgencia y prioridad
            'crítico', 'critico', 'grave', 'severo', 'inmediato', 'immediate',
            'prioritario', 'priority', 'ambulancia', 'ambulance', 'uci', 'icu',
            'cuidados intensivos', 'intensive care', 'shock', 'paro', 'arrest',

            # Especialidades médicas comunes
            'cardiología', 'cardiology', 'neurología', 'neurology', 'ginecología',
            'gynecology', 'pediatría', 'pediatrics', 'ortopedia', 'orthopedics',
            'medicina interna', 'internal medicine', 'cirugía', 'surgery',
            'anestesiología', 'anesthesiology', 'radiología', 'radiology',
            'patología', 'pathology', 'oncología', 'oncology', 'psiquiatría',
            'psychiatry', 'dermatología', 'dermatology', 'oftalmología',
            'ophthalmology', 'otorrinolaringología', 'ent', 'urología', 'urology',

            # Términos de procedimientos
            'cirugía', 'surgery', 'operación', 'operation', 'procedimiento',
            'procedure', 'biopsia', 'biopsy', 'endoscopia', 'endoscopy',
            'cateterismo', 'catheterization', 'diálisis', 'dialysis',
            'quimioterapia', 'chemotherapy', 'radioterapia', 'radiotherapy',

            # Términos de documentos médicos
            'epicrisis', 'historia clínica', 'medical history', 'resumen clínico',
            'clinical summary', 'informe médico', 'medical report', 'exámenes',
            'examenes', 'tests', 'resultados', 'results', 'laboratorios',
            'laboratories', 'imágenes', 'images', 'estudios', 'studies'
        ]

        # Especialidades médicas
        self.medical_specialties = {
            'cardiología': ['corazón', 'cardíaco', 'infarto', 'arritmia', 'hipertensión', 'ecg'],
            'neurología': ['cerebro', 'neurológico', 'convulsión', 'epilepsia', 'migraña', 'cefalea'],
            'ginecología': ['embarazo', 'gestación', 'prenatal', 'obstétrico', 'ginecológico'],
            'ortopedia': ['fractura', 'hueso', 'articulación', 'ortopédico', 'traumatismo'],
            'pediatría': ['niño', 'pediátrico', 'infantil', 'bebé', 'menor'],
            'medicina_interna': ['diabetes', 'hipertensión', 'colesterol', 'interno']
        }

        # Niveles de prioridad
        self.priority_keywords = {
            'Alta': ['urgente', 'emergencia', 'crítico', 'grave', 'severo', 'inmediato'],
            'Media': ['importante', 'pronto', 'moderado', 'seguimiento'],
            'Baja': ['rutina', 'control', 'preventivo', 'programado']
        }

    def load_processed_emails(self) -> List[Dict[str, Any]]:
        """
        Carga todos los emails procesados desde las carpetas JSON

        Returns:
            List[Dict]: Lista de emails procesados
        """
        processed_emails = []

        try:
            # Buscar en carpeta JSON tradicional
            if os.path.exists(self.json_path):
                for email_folder in os.listdir(self.json_path):
                    email_folder_path = os.path.join(self.json_path, email_folder)
                    if os.path.isdir(email_folder_path):
                        email_data_file = os.path.join(email_folder_path, "email_data.json")
                        if os.path.exists(email_data_file):
                            with open(email_data_file, 'r', encoding='utf-8') as f:
                                email_data = json.load(f)
                                email_data['source_type'] = 'traditional'
                                email_data['source_path'] = email_folder_path
                                processed_emails.append(email_data)

            # Buscar en carpeta Professional_Email_Records
            if os.path.exists(self.professional_path):
                for email_folder in os.listdir(self.professional_path):
                    email_folder_path = os.path.join(self.professional_path, email_folder)
                    if os.path.isdir(email_folder_path):
                        comprehensive_file = os.path.join(email_folder_path, "comprehensive_email_record.json")
                        if os.path.exists(comprehensive_file):
                            with open(comprehensive_file, 'r', encoding='utf-8') as f:
                                email_data = json.load(f)
                                email_data['source_type'] = 'professional'
                                email_data['source_path'] = email_folder_path
                                processed_emails.append(email_data)

            logger.info(f"Loaded {len(processed_emails)} processed emails")
            return processed_emails

        except Exception as e:
            logger.error(f"Error loading processed emails: {str(e)}")
            return []

    def is_medical_email(self, email_data: Dict[str, Any]) -> bool:
        """
        Determina si un email es relacionado con medicina

        Args:
            email_data: Datos del email

        Returns:
            bool: True si es email médico
        """
        try:
            # Extraer texto relevante del email
            text_content = ""

            if email_data.get('source_type') == 'professional':
                # Email con schema profesional
                content_analysis = email_data.get('content_analysis', {})
                subject = content_analysis.get('subject_information', {}).get('subject_line', '')
                body = content_analysis.get('body_content', {}).get('plain_text_content', '')
                text_content = f"{subject} {body}".lower()
            else:
                # Email con schema tradicional
                metadata = email_data.get('metadata', {})
                subject = metadata.get('subject', '')
                content = email_data.get('content', {})
                body = content.get('text', '')
                text_content = f"{subject} {body}".lower()

            # Buscar palabras clave médicas
            medical_score = 0
            for keyword in self.medical_keywords:
                if keyword.lower() in text_content:
                    medical_score += 1

            # Considerar médico si tiene al menos 2 palabras clave médicas
            return medical_score >= 2

        except Exception as e:
            logger.warning(f"Error checking if email is medical: {str(e)}")
            return False

    def extract_patient_info(self, email_data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Extrae información del paciente del email

        Args:
            email_data: Datos del email

        Returns:
            Dict: Información del paciente
        """
        try:
            # Extraer texto del email
            text_content = ""
            if email_data.get('source_type') == 'professional':
                content_analysis = email_data.get('content_analysis', {})
                text_content = content_analysis.get('body_content', {}).get('plain_text_content', '')
            else:
                content = email_data.get('content', {})
                text_content = content.get('text', '')

            # Patrones para extraer información
            patient_info = {
                'name': self._extract_patient_name(text_content),
                'age': self._extract_age(text_content),
                'gender': self._extract_gender(text_content),
                'id': self._extract_patient_id(text_content),
                'phone': self._extract_phone(text_content),
                'diagnosis': self._extract_diagnosis(text_content)
            }

            return patient_info

        except Exception as e:
            logger.warning(f"Error extracting patient info: {str(e)}")
            return {
                'name': 'Paciente No Identificado',
                'age': 0,
                'gender': 'No especificado',
                'id': '',
                'phone': '',
                'diagnosis': 'Diagnóstico pendiente'
            }

    def _extract_patient_name(self, text: str) -> str:
        """Extrae el nombre del paciente del texto"""
        patterns = [
            r'paciente:?\s*([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+(?:\s+[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)+)',
            r'nombre:?\s*([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+(?:\s+[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)+)',
            r'patient:?\s*([A-Z][a-z]+(?:\s+[A-Z][a-z]+)+)'
        ]

        for pattern in patterns:
            match = re.search(pattern, text, re.IGNORECASE)
            if match:
                return match.group(1).strip()

        return f"Paciente {datetime.now().strftime('%Y%m%d%H%M')}"

    def _extract_age(self, text: str) -> int:
        """Extrae la edad del paciente"""
        patterns = [
            r'(\d{1,3})\s*años?',
            r'edad:?\s*(\d{1,3})',
            r'(\d{1,3})\s*years?\s*old'
        ]

        for pattern in patterns:
            match = re.search(pattern, text, re.IGNORECASE)
            if match:
                age = int(match.group(1))
                if 0 <= age <= 120:
                    return age

        return 45  # Edad por defecto

    def _extract_gender(self, text: str) -> str:
        """Extrae el género del paciente"""
        if re.search(r'\b(femenino|mujer|femenina|female)\b', text, re.IGNORECASE):
            return 'Femenino'
        elif re.search(r'\b(masculino|hombre|masculino|male)\b', text, re.IGNORECASE):
            return 'Masculino'
        return 'No especificado'

    def _extract_patient_id(self, text: str) -> str:
        """Extrae el ID del paciente"""
        patterns = [
            r'id:?\s*([A-Z0-9]{6,})',
            r'identificación:?\s*([0-9]{8,})',
            r'cédula:?\s*([0-9]{8,})'
        ]

        for pattern in patterns:
            match = re.search(pattern, text, re.IGNORECASE)
            if match:
                return match.group(1)

        return f"P{datetime.now().strftime('%Y%m%d%H%M%S')}"

    def _extract_phone(self, text: str) -> str:
        """Extrae el teléfono del paciente"""
        patterns = [
            r'(\+57\s*[0-9]{3}\s*[0-9]{3}\s*[0-9]{4})',
            r'([0-9]{3}\s*[0-9]{3}\s*[0-9]{4})',
            r'teléfono:?\s*([0-9\s\-\+]{10,})'
        ]

        for pattern in patterns:
            match = re.search(pattern, text, re.IGNORECASE)
            if match:
                return match.group(1).strip()

        return '+57 300 000 0000'

    def _extract_diagnosis(self, text: str) -> str:
        """Extrae el diagnóstico del texto"""
        patterns = [
            r'diagnóstico:?\s*([^.\n]{10,100})',
            r'diagnosis:?\s*([^.\n]{10,100})',
            r'presenta:?\s*([^.\n]{10,100})'
        ]

        for pattern in patterns:
            match = re.search(pattern, text, re.IGNORECASE)
            if match:
                return match.group(1).strip()

        return 'Diagnóstico por determinar'

    def determine_specialty(self, text: str) -> str:
        """Determina la especialidad médica basada en el contenido"""
        text_lower = text.lower()

        for specialty, keywords in self.medical_specialties.items():
            score = sum(1 for keyword in keywords if keyword in text_lower)
            if score >= 2:
                return specialty.replace('_', ' ').title()

        return 'Medicina General'

    def determine_priority(self, text: str) -> str:
        """Determina la prioridad del caso"""
        text_lower = text.lower()

        for priority, keywords in self.priority_keywords.items():
            if any(keyword in text_lower for keyword in keywords):
                return priority

        return 'Media'  # Prioridad por defecto

    def transform_email_to_medical_case(self, email_data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Transforma un email en un caso médico para el frontend

        Args:
            email_data: Datos del email procesado

        Returns:
            Dict: Caso médico formateado para el frontend
        """
        try:
            # Extraer información básica
            if email_data.get('source_type') == 'professional':
                # Schema profesional
                doc_id = email_data.get('document_identification', {})
                comm_meta = email_data.get('communication_metadata', {})
                content_analysis = email_data.get('content_analysis', {})

                unique_id = doc_id.get('unique_identifier', '')
                subject = content_analysis.get('subject_information', {}).get('subject_line', '')
                sender_info = comm_meta.get('participant_information', {}).get('sender_details', [{}])[0]
                date_info = comm_meta.get('temporal_information', {}).get('sent_datetime', '')
                text_content = content_analysis.get('body_content', {}).get('plain_text_content', '')

            else:
                # Schema tradicional
                metadata = email_data.get('metadata', {})
                content = email_data.get('content', {})

                unique_id = metadata.get('unique_id', '')
                subject = metadata.get('subject', '')
                sender_info = metadata.get('from', [{}])[0] if metadata.get('from') else {}
                date_info = metadata.get('date', '')
                text_content = content.get('text', '')

            # Extraer información del paciente
            patient_info = self.extract_patient_info(email_data)

            # Determinar especialidad y prioridad
            full_text = f"{subject} {text_content}"
            specialty = self.determine_specialty(full_text)
            priority = self.determine_priority(full_text)

            # Calcular tiempo transcurrido
            try:
                if date_info:
                    email_date = datetime.fromisoformat(date_info.replace('Z', '+00:00'))
                    time_elapsed = datetime.now() - email_date.replace(tzinfo=None)

                    if time_elapsed.days > 0:
                        time_elapsed_str = f"{time_elapsed.days}d {time_elapsed.seconds//3600}h"
                    else:
                        hours = time_elapsed.seconds // 3600
                        minutes = (time_elapsed.seconds % 3600) // 60
                        time_elapsed_str = f"{hours}h {minutes}min"
                else:
                    time_elapsed_str = "Tiempo desconocido"
            except:
                time_elapsed_str = "Tiempo desconocido"

            # Crear caso médico
            medical_case = {
                'id': f"REF-{unique_id[-8:]}",
                'patient': patient_info['name'],
                'age': patient_info['age'],
                'gender': patient_info['gender'],
                'diagnosis': patient_info['diagnosis'],
                'priority': priority,
                'status': 'Pendiente',
                'origin': sender_info.get('email', 'Email no identificado'),
                'specialty': specialty,
                'receivedAt': date_info,
                'timeElapsed': time_elapsed_str,
                'clinicalSummary': text_content[:300] + '...' if len(text_content) > 300 else text_content,
                'vitalSigns': {
                    'bloodPressure': 'No registrado',
                    'heartRate': 'No registrado',
                    'temperature': 'No registrado',
                    'oxygenSaturation': 'No registrado',
                    'respiratoryRate': 'No registrado'
                },
                'medicalHistory': ['Información extraída de email'],
                'currentMedications': ['Por determinar'],
                'attachments': self._get_attachments_info(email_data),
                'referringDoctor': {
                    'name': sender_info.get('name', 'No especificado'),
                    'specialty': specialty,
                    'phone': patient_info['phone'],
                    'email': sender_info.get('email', '')
                },
                'urgencyScore': self._calculate_urgency_score(priority, full_text),
                'aiConfidence': 85,  # Confianza base del AI
                'estimatedCost': '$1,500,000',
                'emailSource': {
                    'originalSubject': subject,
                    'originalSender': sender_info.get('email', ''),
                    'originalDate': date_info,
                    'sourceType': email_data.get('source_type', 'traditional'),
                    'sourcePath': email_data.get('source_path', '')
                }
            }

            return medical_case

        except Exception as e:
            logger.error(f"Error transforming email to medical case: {str(e)}")
            return self._create_fallback_case(email_data)

    def _get_attachments_info(self, email_data: Dict[str, Any]) -> List[Dict[str, Any]]:
        """Extrae información de attachments"""
        attachments = []

        try:
            if email_data.get('source_type') == 'professional':
                att_info = email_data.get('attachment_information', {})
                att_details = att_info.get('attachment_details', [])

                for att in att_details:
                    file_id = att.get('file_identification', {})
                    file_props = att.get('file_properties', {})

                    attachments.append({
                        'name': file_id.get('original_filename', 'Archivo'),
                        'type': file_id.get('file_extension', '').upper(),
                        'size': file_props.get('file_size_human_readable', '0 KB'),
                        'url': '/placeholder.jpg'  # URL por defecto
                    })
            else:
                # Schema tradicional
                att_list = email_data.get('attachments', [])
                for att in att_list:
                    attachments.append({
                        'name': att.get('original_filename', 'Archivo'),
                        'type': att.get('content_type', '').upper(),
                        'size': f"{att.get('size_bytes', 0) / 1024:.1f} KB",
                        'url': '/placeholder.jpg'
                    })
        except Exception as e:
            logger.warning(f"Error extracting attachments: {str(e)}")

        return attachments

    def _calculate_urgency_score(self, priority: str, text: str) -> float:
        """Calcula el score de urgencia"""
        base_scores = {'Alta': 8.0, 'Media': 5.0, 'Baja': 2.0}
        score = base_scores.get(priority, 5.0)

        # Ajustar basado en palabras clave críticas
        critical_keywords = ['emergencia', 'crítico', 'grave', 'urgente', 'inmediato']
        for keyword in critical_keywords:
            if keyword in text.lower():
                score += 1.0

        return min(10.0, score)

    def _create_fallback_case(self, email_data: Dict[str, Any]) -> Dict[str, Any]:
        """Crea un caso médico básico cuando falla la transformación"""
        unique_id = email_data.get('metadata', {}).get('unique_id', 'unknown')

        return {
            'id': f"REF-{unique_id[-8:]}",
            'patient': 'Paciente No Identificado',
            'age': 0,
            'gender': 'No especificado',
            'diagnosis': 'Información incompleta en email',
            'priority': 'Media',
            'status': 'Pendiente',
            'origin': 'Email procesado',
            'specialty': 'Medicina General',
            'receivedAt': datetime.now().isoformat(),
            'timeElapsed': '0h 0min',
            'clinicalSummary': 'Email requiere revisión manual para extracción de información médica.',
            'emailSource': {
                'originalSubject': 'Email no procesable',
                'sourceType': email_data.get('source_type', 'unknown'),
                'sourcePath': email_data.get('source_path', '')
            }
        }

    def transform_all_medical_emails(self) -> List[Dict[str, Any]]:
        """
        Transforma todos los emails médicos en casos para el frontend

        Returns:
            List[Dict]: Lista de casos médicos
        """
        try:
            # Cargar emails procesados
            all_emails = self.load_processed_emails()
            logger.info(f"Processing {len(all_emails)} emails for medical content")

            # Filtrar emails médicos y transformar
            medical_cases = []
            for email_data in all_emails:
                if self.is_medical_email(email_data):
                    medical_case = self.transform_email_to_medical_case(email_data)
                    medical_cases.append(medical_case)

            logger.info(f"Transformed {len(medical_cases)} medical emails into cases")
            return medical_cases

        except Exception as e:
            logger.error(f"Error transforming medical emails: {str(e)}")
            return []

    def save_medical_cases_json(self, medical_cases: List[Dict[str, Any]], output_path: str = None) -> str:
        """
        Guarda los casos médicos en un archivo JSON para el frontend

        Args:
            medical_cases: Lista de casos médicos
            output_path: Ruta de salida (opcional)

        Returns:
            str: Ruta del archivo guardado
        """
        try:
            if not output_path:
                output_path = os.path.join(self.base_path, "medical_cases_for_frontend.json")

            # Crear estructura completa para el frontend
            frontend_data = {
                'metadata': {
                    'generated_at': datetime.now().isoformat(),
                    'total_cases': len(medical_cases),
                    'source': 'Gmail Data Extraction System v2.0',
                    'version': '1.0'
                },
                'cases': medical_cases,
                'statistics': {
                    'by_priority': {
                        'Alta': len([c for c in medical_cases if c['priority'] == 'Alta']),
                        'Media': len([c for c in medical_cases if c['priority'] == 'Media']),
                        'Baja': len([c for c in medical_cases if c['priority'] == 'Baja'])
                    },
                    'by_specialty': {},
                    'by_status': {
                        'Pendiente': len([c for c in medical_cases if c['status'] == 'Pendiente'])
                    }
                }
            }

            # Calcular estadísticas por especialidad
            specialties = {}
            for case in medical_cases:
                specialty = case['specialty']
                specialties[specialty] = specialties.get(specialty, 0) + 1
            frontend_data['statistics']['by_specialty'] = specialties

            # Guardar archivo
            with open(output_path, 'w', encoding='utf-8') as f:
                json.dump(frontend_data, f, indent=2, ensure_ascii=False)

            logger.info(f"Medical cases saved to: {output_path}")
            return output_path

        except Exception as e:
            logger.error(f"Error saving medical cases: {str(e)}")
            return ""
