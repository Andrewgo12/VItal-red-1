"""
Enhanced Medical Text Analyzer for Vital Red System
Advanced medical text analysis with clinical data extraction
"""

import re
import json
import logging
from typing import Dict, List, Any, Optional, Tuple
from datetime import datetime
import spacy
from spacy.matcher import Matcher

logger = logging.getLogger(__name__)

class EnhancedMedicalAnalyzer:
    """
    Enhanced medical text analyzer with advanced clinical data extraction
    """
    
    def __init__(self):
        """Initialize the enhanced medical analyzer"""
        try:
            # Load Spanish language model
            self.nlp = spacy.load("es_core_news_sm")
        except OSError:
            logger.warning("Spanish spaCy model not found, using basic patterns")
            self.nlp = None
        
        self.matcher = Matcher(self.nlp.vocab) if self.nlp else None
        self._setup_patterns()
        
        # Medical specialties mapping
        self.specialties_mapping = {
            'cardiologia': 'Cardiología',
            'cardiology': 'Cardiología',
            'neurologia': 'Neurología',
            'neurology': 'Neurología',
            'ginecologia': 'Ginecología',
            'gynecology': 'Ginecología',
            'pediatria': 'Pediatría',
            'pediatrics': 'Pediatría',
            'ortopedia': 'Ortopedia',
            'orthopedics': 'Ortopedia',
            'medicina interna': 'Medicina Interna',
            'internal medicine': 'Medicina Interna',
            'cirugia': 'Cirugía',
            'surgery': 'Cirugía',
            'anestesiologia': 'Anestesiología',
            'anesthesiology': 'Anestesiología',
            'radiologia': 'Radiología',
            'radiology': 'Radiología',
            'patologia': 'Patología',
            'pathology': 'Patología',
            'oncologia': 'Oncología',
            'oncology': 'Oncología',
            'psiquiatria': 'Psiquiatría',
            'psychiatry': 'Psiquiatría',
            'dermatologia': 'Dermatología',
            'dermatology': 'Dermatología',
            'oftalmologia': 'Oftalmología',
            'ophthalmology': 'Oftalmología',
            'otorrinolaringologia': 'Otorrinolaringología',
            'ent': 'Otorrinolaringología',
            'urologia': 'Urología',
            'urology': 'Urología',
            'neumologia': 'Neumología',
            'pulmonology': 'Neumología',
            'gastroenterologia': 'Gastroenterología',
            'gastroenterology': 'Gastroenterología',
            'endocrinologia': 'Endocrinología',
            'endocrinology': 'Endocrinología',
            'reumatologia': 'Reumatología',
            'rheumatology': 'Reumatología',
            'hematologia': 'Hematología',
            'hematology': 'Hematología',
            'infectologia': 'Infectología',
            'infectious diseases': 'Infectología',
            'nefrologia': 'Nefrología',
            'nephrology': 'Nefrología',
            'geriatria': 'Geriatría',
            'geriatrics': 'Geriatría'
        }
        
        # Priority keywords for urgency scoring
        self.urgency_keywords = {
            'critico': 10,
            'critical': 10,
            'grave': 8,
            'severo': 8,
            'severe': 8,
            'urgente': 7,
            'urgent': 7,
            'emergencia': 9,
            'emergency': 9,
            'inmediato': 8,
            'immediate': 8,
            'shock': 10,
            'paro': 10,
            'arrest': 10,
            'codigo azul': 10,
            'code blue': 10,
            'codigo rojo': 10,
            'code red': 10,
            'uci': 7,
            'icu': 7,
            'cuidados intensivos': 7,
            'intensive care': 7,
            'ventilador': 6,
            'ventilator': 6,
            'oxigeno': 5,
            'oxygen': 5,
            'dolor intenso': 6,
            'severe pain': 6,
            'hemorragia': 8,
            'hemorrhage': 8,
            'infarto': 9,
            'infarction': 9,
            'accidente cerebrovascular': 9,
            'stroke': 9,
            'convulsiones': 7,
            'seizures': 7,
            'coma': 9,
            'inconsciente': 8,
            'unconscious': 8
        }
    
    def _setup_patterns(self):
        """Setup spaCy patterns for medical entity extraction"""
        if not self.matcher:
            return
        
        # Patient identification patterns
        patient_patterns = [
            [{"LOWER": {"IN": ["paciente", "patient"]}}, {"IS_ALPHA": True}, {"IS_ALPHA": True}],
            [{"LOWER": {"IN": ["nombre", "name"]}}, {"TEXT": ":"}, {"IS_ALPHA": True}, {"IS_ALPHA": True}]
        ]
        self.matcher.add("PATIENT_NAME", patient_patterns)
        
        # Age patterns
        age_patterns = [
            [{"LOWER": {"IN": ["edad", "age"]}}, {"TEXT": ":"}, {"LIKE_NUM": True}],
            [{"LIKE_NUM": True}, {"LOWER": {"IN": ["años", "years", "año"]}}]
        ]
        self.matcher.add("AGE", age_patterns)
        
        # Vital signs patterns
        vital_patterns = [
            [{"LOWER": {"IN": ["fc", "frecuencia", "heart"]}}, {"LOWER": {"IN": ["cardiaca", "rate"]}}, {"TEXT": ":"}, {"LIKE_NUM": True}],
            [{"LOWER": {"IN": ["fr", "frecuencia"]}}, {"LOWER": {"IN": ["respiratoria", "respiratory"]}}, {"TEXT": ":"}, {"LIKE_NUM": True}],
            [{"LOWER": {"IN": ["ta", "tension", "blood"]}}, {"LOWER": {"IN": ["arterial", "pressure"]}}, {"TEXT": ":"}, {"LIKE_NUM": True}]
        ]
        self.matcher.add("VITAL_SIGNS", vital_patterns)
    
    def analyze_medical_text(self, text: str) -> Dict[str, Any]:
        """
        Comprehensive medical text analysis
        
        Args:
            text: Medical text to analyze
            
        Returns:
            Dict: Comprehensive analysis results
        """
        try:
            analysis = {
                'patient_info': self._extract_patient_info(text),
                'clinical_data': self._extract_clinical_data(text),
                'vital_signs': self._extract_vital_signs(text),
                'medications': self._extract_medications(text),
                'procedures': self._extract_procedures(text),
                'diagnoses': self._extract_diagnoses(text),
                'specialty': self._detect_specialty(text),
                'urgency_score': self._calculate_urgency_score(text),
                'priority_level': self._determine_priority_level(text),
                'referral_type': self._detect_referral_type(text),
                'institution_info': self._extract_institution_info(text),
                'temporal_info': self._extract_temporal_info(text),
                'clinical_context': self._extract_clinical_context(text)
            }
            
            # Add confidence scores
            analysis['confidence_scores'] = self._calculate_confidence_scores(analysis)
            
            return analysis
            
        except Exception as e:
            logger.error(f"Error in medical text analysis: {str(e)}")
            return {'error': str(e)}
    
    def _extract_patient_info(self, text: str) -> Dict[str, Any]:
        """Extract patient demographic information"""
        patient_info = {
            'name': None,
            'age': None,
            'gender': None,
            'identification': None,
            'phone': None
        }
        
        try:
            text_lower = text.lower()
            
            # Extract patient name
            name_patterns = [
                r'(?:paciente|patient|nombre|name)[\s:]*([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+(?:\s+[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)+)',
                r'(?:sr|sra|señor|señora|mr|mrs)[\s\.]*([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+(?:\s+[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)+)'
            ]
            
            for pattern in name_patterns:
                match = re.search(pattern, text, re.IGNORECASE)
                if match:
                    patient_info['name'] = match.group(1).strip()
                    break
            
            # Extract age
            age_patterns = [
                r'(?:edad|age)[\s:]*(\d{1,3})\s*(?:años|years|año)',
                r'(\d{1,3})\s*(?:años|years|año)',
                r'(?:de|of)\s*(\d{1,3})\s*(?:años|years)'
            ]
            
            for pattern in age_patterns:
                match = re.search(pattern, text_lower)
                if match:
                    age = int(match.group(1))
                    if 0 <= age <= 120:  # Reasonable age range
                        patient_info['age'] = age
                        break
            
            # Extract gender
            if any(word in text_lower for word in ['masculino', 'male', 'hombre', 'varón']):
                patient_info['gender'] = 'masculino'
            elif any(word in text_lower for word in ['femenino', 'female', 'mujer']):
                patient_info['gender'] = 'femenino'
            
            # Extract identification
            id_patterns = [
                r'(?:cc|cedula|cédula|id|identificación|identificacion)[\s:]*(\d{6,12})',
                r'(?:ti|tarjeta de identidad)[\s:]*(\d{6,12})',
                r'(?:ce|cedula extranjeria|cédula extranjería)[\s:]*(\d{6,12})'
            ]
            
            for pattern in id_patterns:
                match = re.search(pattern, text_lower)
                if match:
                    patient_info['identification'] = match.group(1)
                    break
            
            # Extract phone
            phone_patterns = [
                r'(?:telefono|teléfono|phone|cel|celular|movil|móvil)[\s:]*(\d{7,15})',
                r'(?:contacto|contact)[\s:]*(\d{7,15})'
            ]
            
            for pattern in phone_patterns:
                match = re.search(pattern, text_lower)
                if match:
                    patient_info['phone'] = match.group(1)
                    break
            
            return patient_info
            
        except Exception as e:
            logger.warning(f"Error extracting patient info: {str(e)}")
            return patient_info
    
    def _extract_vital_signs(self, text: str) -> Dict[str, Any]:
        """Extract vital signs from text"""
        vital_signs = {
            'heart_rate': None,
            'respiratory_rate': None,
            'blood_pressure_systolic': None,
            'blood_pressure_diastolic': None,
            'temperature': None,
            'oxygen_saturation': None,
            'glasgow_scale': None
        }
        
        try:
            text_lower = text.lower()
            
            # Heart rate
            hr_patterns = [
                r'(?:fc|frecuencia cardiaca|heart rate)[\s:]*(\d{2,3})',
                r'(\d{2,3})\s*(?:lpm|bpm|latidos)'
            ]
            
            for pattern in hr_patterns:
                match = re.search(pattern, text_lower)
                if match:
                    hr = int(match.group(1))
                    if 30 <= hr <= 250:  # Reasonable range
                        vital_signs['heart_rate'] = hr
                        break
            
            # Respiratory rate
            rr_patterns = [
                r'(?:fr|frecuencia respiratoria|respiratory rate)[\s:]*(\d{1,2})',
                r'(\d{1,2})\s*(?:rpm|respiraciones)'
            ]
            
            for pattern in rr_patterns:
                match = re.search(pattern, text_lower)
                if match:
                    rr = int(match.group(1))
                    if 5 <= rr <= 60:  # Reasonable range
                        vital_signs['respiratory_rate'] = rr
                        break
            
            # Blood pressure
            bp_patterns = [
                r'(?:ta|tension arterial|blood pressure|presion arterial|presión arterial)[\s:]*(\d{2,3})/(\d{2,3})',
                r'(\d{2,3})/(\d{2,3})\s*(?:mmhg|mm hg)'
            ]
            
            for pattern in bp_patterns:
                match = re.search(pattern, text_lower)
                if match:
                    systolic = int(match.group(1))
                    diastolic = int(match.group(2))
                    if 60 <= systolic <= 300 and 30 <= diastolic <= 200:
                        vital_signs['blood_pressure_systolic'] = systolic
                        vital_signs['blood_pressure_diastolic'] = diastolic
                        break
            
            # Temperature
            temp_patterns = [
                r'(?:temperatura|temperature|temp)[\s:]*(\d{1,2}\.?\d?)\s*(?:°c|celsius|grados)',
                r'(\d{1,2}\.?\d?)\s*(?:°c|celsius|grados)'
            ]
            
            for pattern in temp_patterns:
                match = re.search(pattern, text_lower)
                if match:
                    temp = float(match.group(1))
                    if 30.0 <= temp <= 45.0:  # Reasonable range
                        vital_signs['temperature'] = temp
                        break
            
            # Oxygen saturation
            spo2_patterns = [
                r'(?:spo2|saturacion|saturación|oxygen saturation)[\s:]*(\d{1,3})%?',
                r'(\d{1,3})%\s*(?:spo2|saturacion|saturación)'
            ]
            
            for pattern in spo2_patterns:
                match = re.search(pattern, text_lower)
                if match:
                    spo2 = int(match.group(1))
                    if 50 <= spo2 <= 100:  # Reasonable range
                        vital_signs['oxygen_saturation'] = spo2
                        break
            
            # Glasgow scale
            glasgow_patterns = [
                r'(?:glasgow|escala de glasgow|gcs)[\s:]*(\d{1,2})/15',
                r'(?:glasgow|gcs)[\s:]*(\d{1,2})'
            ]
            
            for pattern in glasgow_patterns:
                match = re.search(pattern, text_lower)
                if match:
                    glasgow = int(match.group(1))
                    if 3 <= glasgow <= 15:  # Valid Glasgow range
                        vital_signs['glasgow_scale'] = str(glasgow)
                        break
            
            return vital_signs
            
        except Exception as e:
            logger.warning(f"Error extracting vital signs: {str(e)}")
            return vital_signs
    
    def _extract_clinical_data(self, text: str) -> Dict[str, Any]:
        """Extract clinical information"""
        clinical_data = {
            'chief_complaint': None,
            'current_illness': None,
            'medical_history': None,
            'physical_examination': None,
            'assessment_plan': None
        }
        
        try:
            text_lower = text.lower()
            
            # Chief complaint
            complaint_patterns = [
                r'(?:motivo de consulta|chief complaint|consulta por)[\s:]*([^\.]+)',
                r'(?:presenta|refiere|consulta por)[\s:]*([^\.]+)'
            ]
            
            for pattern in complaint_patterns:
                match = re.search(pattern, text_lower)
                if match:
                    clinical_data['chief_complaint'] = match.group(1).strip()
                    break
            
            # Current illness
            illness_patterns = [
                r'(?:enfermedad actual|present illness|cuadro actual)[\s:]*([^\.]+)',
                r'(?:historia de la enfermedad actual)[\s:]*([^\.]+)'
            ]
            
            for pattern in illness_patterns:
                match = re.search(pattern, text_lower)
                if match:
                    clinical_data['current_illness'] = match.group(1).strip()
                    break
            
            # Medical history
            history_patterns = [
                r'(?:antecedentes|medical history|historia medica|historia médica)[\s:]*([^\.]+)',
                r'(?:antecedentes patologicos|antecedentes patológicos)[\s:]*([^\.]+)'
            ]
            
            for pattern in history_patterns:
                match = re.search(pattern, text_lower)
                if match:
                    clinical_data['medical_history'] = match.group(1).strip()
                    break
            
            return clinical_data
            
        except Exception as e:
            logger.warning(f"Error extracting clinical data: {str(e)}")
            return clinical_data
    
    def _extract_medications(self, text: str) -> List[str]:
        """Extract medications from text"""
        try:
            medications = []
            text_lower = text.lower()
            
            # Common medication patterns
            med_patterns = [
                r'(?:medicamentos|medications|farmacos|fármacos|tratamiento)[\s:]*([^\.]+)',
                r'(?:toma|taking|recibe)[\s:]*([^\.]+)',
                r'(?:mg|mcg|g|ml|cc)\b'
            ]
            
            # Look for medication sections
            for pattern in med_patterns[:2]:  # First two patterns
                match = re.search(pattern, text_lower)
                if match:
                    med_text = match.group(1).strip()
                    # Split by common separators
                    meds = re.split(r'[,;]\s*', med_text)
                    medications.extend([med.strip() for med in meds if med.strip()])
                    break
            
            return medications[:10]  # Limit to 10 medications
            
        except Exception as e:
            logger.warning(f"Error extracting medications: {str(e)}")
            return []
    
    def _extract_diagnoses(self, text: str) -> Dict[str, Any]:
        """Extract diagnoses from text"""
        diagnoses = {
            'primary': None,
            'secondary': [],
            'differential': []
        }
        
        try:
            text_lower = text.lower()
            
            # Primary diagnosis
            primary_patterns = [
                r'(?:diagnostico principal|primary diagnosis|diagnostico|diagnóstico)[\s:]*([^\.]+)',
                r'(?:impresion diagnostica|impresión diagnóstica)[\s:]*([^\.]+)'
            ]
            
            for pattern in primary_patterns:
                match = re.search(pattern, text_lower)
                if match:
                    diagnoses['primary'] = match.group(1).strip()
                    break
            
            # Secondary diagnoses
            secondary_patterns = [
                r'(?:diagnosticos secundarios|diagnósticos secundarios|secondary diagnosis)[\s:]*([^\.]+)',
                r'(?:otros diagnosticos|otros diagnósticos)[\s:]*([^\.]+)'
            ]
            
            for pattern in secondary_patterns:
                match = re.search(pattern, text_lower)
                if match:
                    secondary_text = match.group(1).strip()
                    diagnoses['secondary'] = [d.strip() for d in re.split(r'[,;]\s*', secondary_text) if d.strip()]
                    break
            
            return diagnoses
            
        except Exception as e:
            logger.warning(f"Error extracting diagnoses: {str(e)}")
            return diagnoses
    
    def _detect_specialty(self, text: str) -> Optional[str]:
        """Detect medical specialty from text"""
        try:
            text_lower = text.lower()
            
            for specialty_key, specialty_name in self.specialties_mapping.items():
                if specialty_key in text_lower:
                    return specialty_name
            
            return None
            
        except Exception as e:
            logger.warning(f"Error detecting specialty: {str(e)}")
            return None
    
    def _calculate_urgency_score(self, text: str) -> float:
        """Calculate urgency score based on keywords"""
        try:
            text_lower = text.lower()
            total_score = 0
            
            for keyword, score in self.urgency_keywords.items():
                if keyword in text_lower:
                    total_score += score
            
            # Normalize score to 0-100 range
            max_possible_score = 50  # Reasonable maximum
            normalized_score = min(total_score / max_possible_score * 100, 100)
            
            return round(normalized_score, 2)
            
        except Exception as e:
            logger.warning(f"Error calculating urgency score: {str(e)}")
            return 0.0
    
    def _determine_priority_level(self, text: str) -> str:
        """Determine priority level based on urgency score and keywords"""
        try:
            urgency_score = self._calculate_urgency_score(text)
            text_lower = text.lower()
            
            # High priority indicators
            high_priority_keywords = [
                'critico', 'critical', 'emergencia', 'emergency', 'urgente', 'urgent',
                'shock', 'paro', 'codigo azul', 'code blue', 'uci', 'icu'
            ]
            
            if urgency_score >= 70 or any(keyword in text_lower for keyword in high_priority_keywords):
                return 'Alta'
            elif urgency_score >= 30:
                return 'Media'
            else:
                return 'Baja'
                
        except Exception as e:
            logger.warning(f"Error determining priority level: {str(e)}")
            return 'Media'
    
    def _detect_referral_type(self, text: str) -> str:
        """Detect type of medical referral"""
        try:
            text_lower = text.lower()
            
            if any(word in text_lower for word in ['hospitalizacion', 'hospitalización', 'internacion', 'internación', 'admission']):
                return 'hospitalizacion'
            elif any(word in text_lower for word in ['cirugia', 'cirugía', 'surgery', 'operacion', 'operación']):
                return 'cirugia'
            elif any(word in text_lower for word in ['urgencia', 'urgente', 'emergency', 'urgent']):
                return 'urgencia'
            else:
                return 'consulta'
                
        except Exception as e:
            logger.warning(f"Error detecting referral type: {str(e)}")
            return 'consulta'
    
    def _extract_institution_info(self, text: str) -> Dict[str, Any]:
        """Extract referring institution information"""
        institution_info = {
            'name': None,
            'doctor': None,
            'contact': None
        }
        
        try:
            # Extract institution name
            institution_patterns = [
                r'(?:hospital|clinica|clínica|centro medico|centro médico|ips|eps)[\s]*([^,\n\.]+)',
                r'(?:de|from)[\s]*(?:hospital|clinica|clínica)[\s]*([^,\n\.]+)'
            ]
            
            for pattern in institution_patterns:
                match = re.search(pattern, text, re.IGNORECASE)
                if match:
                    institution_info['name'] = match.group(1).strip()
                    break
            
            # Extract referring doctor
            doctor_patterns = [
                r'(?:dr|dra|doctor|doctora|medico|médico)[\s\.]*([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+(?:\s+[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)*)',
                r'(?:remite|refers|solicita)[\s:]*(?:dr|dra|doctor|doctora)[\s\.]*([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+(?:\s+[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)*)'
            ]
            
            for pattern in doctor_patterns:
                match = re.search(pattern, text, re.IGNORECASE)
                if match:
                    institution_info['doctor'] = match.group(1).strip()
                    break
            
            return institution_info
            
        except Exception as e:
            logger.warning(f"Error extracting institution info: {str(e)}")
            return institution_info
    
    def _extract_temporal_info(self, text: str) -> Dict[str, Any]:
        """Extract temporal information from text"""
        temporal_info = {
            'symptom_duration': None,
            'onset_date': None,
            'evolution': None
        }
        
        try:
            text_lower = text.lower()
            
            # Duration patterns
            duration_patterns = [
                r'(?:desde hace|for|durante)[\s]*(\d+)[\s]*(?:dias|días|days|semanas|weeks|meses|months|años|years)',
                r'(\d+)[\s]*(?:dias|días|days|semanas|weeks|meses|months)[\s]*(?:de evolucion|de evolución|evolution)'
            ]
            
            for pattern in duration_patterns:
                match = re.search(pattern, text_lower)
                if match:
                    temporal_info['symptom_duration'] = match.group(0).strip()
                    break
            
            return temporal_info
            
        except Exception as e:
            logger.warning(f"Error extracting temporal info: {str(e)}")
            return temporal_info
    
    def _extract_clinical_context(self, text: str) -> Dict[str, Any]:
        """Extract clinical context and reasoning"""
        context = {
            'reason_for_referral': None,
            'requested_evaluation': None,
            'clinical_question': None,
            'oxygen_requirement': 'NO'
        }
        
        try:
            text_lower = text.lower()
            
            # Reason for referral
            referral_patterns = [
                r'(?:motivo de remision|motivo de remisión|reason for referral|solicita)[\s:]*([^\.]+)',
                r'(?:se remite por|referred for)[\s:]*([^\.]+)'
            ]
            
            for pattern in referral_patterns:
                match = re.search(pattern, text_lower)
                if match:
                    context['reason_for_referral'] = match.group(1).strip()
                    break
            
            # Oxygen requirement
            if any(word in text_lower for word in ['oxigeno', 'oxygen', 'o2', 'ventilador', 'ventilator']):
                context['oxygen_requirement'] = 'SI'
            
            return context
            
        except Exception as e:
            logger.warning(f"Error extracting clinical context: {str(e)}")
            return context
    
    def _extract_procedures(self, text: str) -> List[str]:
        """Extract medical procedures from text"""
        try:
            procedures = []
            text_lower = text.lower()
            
            # Common procedure keywords
            procedure_keywords = [
                'biopsia', 'biopsy', 'endoscopia', 'endoscopy', 'cateterismo', 'catheterization',
                'dialisis', 'diálisis', 'dialysis', 'quimioterapia', 'chemotherapy',
                'radioterapia', 'radiotherapy', 'cirugia', 'cirugía', 'surgery',
                'operacion', 'operación', 'operation', 'procedimiento', 'procedure'
            ]
            
            for keyword in procedure_keywords:
                if keyword in text_lower:
                    procedures.append(keyword.title())
            
            return procedures
            
        except Exception as e:
            logger.warning(f"Error extracting procedures: {str(e)}")
            return []
    
    def _calculate_confidence_scores(self, analysis: Dict[str, Any]) -> Dict[str, float]:
        """Calculate confidence scores for extracted data"""
        try:
            scores = {
                'patient_info': 0.0,
                'clinical_data': 0.0,
                'vital_signs': 0.0,
                'overall': 0.0
            }
            
            # Patient info confidence
            patient_fields = ['name', 'age', 'gender', 'identification']
            filled_patient_fields = sum(1 for field in patient_fields if analysis['patient_info'].get(field))
            scores['patient_info'] = filled_patient_fields / len(patient_fields)
            
            # Clinical data confidence
            clinical_fields = ['chief_complaint', 'current_illness', 'medical_history']
            filled_clinical_fields = sum(1 for field in clinical_fields if analysis['clinical_data'].get(field))
            scores['clinical_data'] = filled_clinical_fields / len(clinical_fields)
            
            # Vital signs confidence
            vital_fields = ['heart_rate', 'respiratory_rate', 'blood_pressure_systolic', 'temperature']
            filled_vital_fields = sum(1 for field in vital_fields if analysis['vital_signs'].get(field))
            scores['vital_signs'] = filled_vital_fields / len(vital_fields)
            
            # Overall confidence
            scores['overall'] = (scores['patient_info'] + scores['clinical_data'] + scores['vital_signs']) / 3
            
            return scores
            
        except Exception as e:
            logger.warning(f"Error calculating confidence scores: {str(e)}")
            return {'patient_info': 0.0, 'clinical_data': 0.0, 'vital_signs': 0.0, 'overall': 0.0}
