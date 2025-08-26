"""
Medical Priority Classifier for Vital Red System
Advanced algorithm for automatic medical case prioritization
"""

import re
import json
import logging
import numpy as np
from typing import Dict, List, Any, Tuple, Optional
from datetime import datetime
from enhanced_medical_analyzer import EnhancedMedicalAnalyzer

logger = logging.getLogger(__name__)

class MedicalPriorityClassifier:
    """
    Advanced medical priority classification system
    """
    
    def __init__(self):
        """Initialize the medical priority classifier"""
        self.medical_analyzer = EnhancedMedicalAnalyzer()
        
        # Priority scoring weights
        self.scoring_weights = {
            'urgency_keywords': 0.25,
            'vital_signs': 0.20,
            'clinical_severity': 0.20,
            'age_factor': 0.10,
            'specialty_urgency': 0.15,
            'temporal_urgency': 0.10
        }
        
        # Specialty urgency mapping
        self.specialty_urgency = {
            'Cardiología': 0.8,
            'Neurología': 0.9,
            'Cirugía': 0.7,
            'Medicina Interna': 0.6,
            'Pediatría': 0.7,
            'Ginecología': 0.5,
            'Ortopedia': 0.4,
            'Dermatología': 0.3,
            'Oftalmología': 0.4,
            'Otorrinolaringología': 0.4,
            'Urología': 0.5,
            'Neumología': 0.7,
            'Gastroenterología': 0.6,
            'Endocrinología': 0.5,
            'Reumatología': 0.4,
            'Hematología': 0.7,
            'Infectología': 0.8,
            'Nefrología': 0.7,
            'Oncología': 0.8,
            'Psiquiatría': 0.6,
            'Anestesiología': 0.8,
            'Radiología': 0.5,
            'Patología': 0.4,
            'Geriatría': 0.6
        }
        
        # Critical conditions patterns
        self.critical_conditions = {
            'cardiovascular': {
                'patterns': [
                    r'infarto(?:\s+del?\s+miocardio)?',
                    r'angina\s+inestable',
                    r'arritmia\s+(?:maligna|ventricular)',
                    r'shock\s+cardiogenico',
                    r'edema\s+agudo\s+(?:de\s+)?pulmon',
                    r'taponamiento\s+cardiaco',
                    r'diseccion\s+aortica'
                ],
                'score': 95
            },
            'neurological': {
                'patterns': [
                    r'accidente\s+cerebrovascular',
                    r'stroke',
                    r'hemorragia\s+(?:cerebral|intracraneal)',
                    r'estado\s+epileptico',
                    r'coma',
                    r'glasgow\s+(?:menor\s+)?(?:de\s+)?(?:[3-8]|3|4|5|6|7|8)',
                    r'hipertension\s+intracraneal'
                ],
                'score': 90
            },
            'respiratory': {
                'patterns': [
                    r'insuficiencia\s+respiratoria\s+aguda',
                    r'neumotorax\s+(?:a\s+)?tension',
                    r'embolia\s+pulmonar',
                    r'edema\s+agudo\s+(?:de\s+)?pulmon',
                    r'crisis\s+asmatica\s+severa',
                    r'saturacion\s+(?:menor\s+)?(?:de\s+)?(?:[0-8][0-9]|90)%?'
                ],
                'score': 85
            },
            'trauma': {
                'patterns': [
                    r'trauma\s+(?:craneoencefalico|grave|severo)',
                    r'politraumatismo',
                    r'hemorragia\s+(?:masiva|activa)',
                    r'shock\s+hipovolemico',
                    r'fractura\s+(?:expuesta|abierta)',
                    r'lesion\s+medular'
                ],
                'score': 88
            },
            'gastrointestinal': {
                'patterns': [
                    r'hemorragia\s+digestiva\s+(?:alta|baja|masiva)',
                    r'abdomen\s+agudo',
                    r'perforacion\s+(?:gastrica|intestinal)',
                    r'obstruccion\s+intestinal',
                    r'pancreatitis\s+aguda\s+severa'
                ],
                'score': 80
            },
            'infectious': {
                'patterns': [
                    r'sepsis\s+severa',
                    r'shock\s+septico',
                    r'meningitis',
                    r'encefalitis',
                    r'endocarditis',
                    r'neutropenia\s+febril'
                ],
                'score': 85
            }
        }
        
        # Age-based risk factors
        self.age_risk_factors = {
            'pediatric': {'min_age': 0, 'max_age': 2, 'multiplier': 1.3},
            'elderly': {'min_age': 75, 'max_age': 120, 'multiplier': 1.2},
            'very_elderly': {'min_age': 85, 'max_age': 120, 'multiplier': 1.4}
        }
        
        # Temporal urgency indicators
        self.temporal_indicators = {
            'immediate': {
                'patterns': [
                    r'inmediato',
                    r'immediate',
                    r'ahora',
                    r'now',
                    r'urgente',
                    r'urgent',
                    r'emergencia',
                    r'emergency'
                ],
                'score': 1.0
            },
            'today': {
                'patterns': [
                    r'hoy',
                    r'today',
                    r'mismo\s+dia',
                    r'same\s+day'
                ],
                'score': 0.8
            },
            'this_week': {
                'patterns': [
                    r'esta\s+semana',
                    r'this\s+week',
                    r'proximos?\s+dias',
                    r'next\s+(?:few\s+)?days'
                ],
                'score': 0.6
            }
        }
    
    def classify_priority(self, medical_case_data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Classify medical case priority using comprehensive algorithm
        
        Args:
            medical_case_data: Medical case data from analyzer
            
        Returns:
            Dict: Priority classification results
        """
        try:
            # Extract text for analysis
            text_content = self._extract_text_content(medical_case_data)
            
            # Perform medical analysis
            medical_analysis = self.medical_analyzer.analyze_medical_text(text_content)
            
            # Calculate individual scores
            scores = {
                'urgency_keywords': self._score_urgency_keywords(text_content),
                'vital_signs': self._score_vital_signs(medical_analysis.get('vital_signs', {})),
                'clinical_severity': self._score_clinical_severity(text_content),
                'age_factor': self._score_age_factor(medical_analysis.get('patient_info', {})),
                'specialty_urgency': self._score_specialty_urgency(medical_analysis.get('specialty')),
                'temporal_urgency': self._score_temporal_urgency(text_content)
            }
            
            # Calculate weighted final score
            final_score = self._calculate_weighted_score(scores)
            
            # Determine priority level
            priority_level = self._determine_priority_level(final_score)
            
            # Generate criteria explanation
            criteria_explanation = self._generate_criteria_explanation(scores, medical_analysis)
            
            # Compile results
            classification_result = {
                'priority_level': priority_level,
                'urgency_score': round(final_score, 2),
                'individual_scores': scores,
                'criteria_explanation': criteria_explanation,
                'medical_analysis': medical_analysis,
                'classification_timestamp': datetime.now().isoformat(),
                'confidence_level': self._calculate_confidence_level(scores, medical_analysis)
            }
            
            logger.info(f"Priority classification complete: {priority_level} (Score: {final_score:.2f})")
            
            return classification_result
            
        except Exception as e:
            logger.error(f"Error in priority classification: {str(e)}")
            return {
                'priority_level': 'Media',
                'urgency_score': 50.0,
                'error': str(e),
                'classification_timestamp': datetime.now().isoformat()
            }
    
    def _extract_text_content(self, medical_case_data: Dict[str, Any]) -> str:
        """Extract all text content for analysis"""
        try:
            text_parts = []
            
            # Extract from different data sources
            if isinstance(medical_case_data, dict):
                # From professional email format
                if 'content_analysis' in medical_case_data:
                    content = medical_case_data['content_analysis']
                    if 'subject_information' in content:
                        text_parts.append(content['subject_information'].get('subject_line', ''))
                    if 'body_content' in content:
                        text_parts.append(content['body_content'].get('plain_text_content', ''))
                
                # From extracted text data
                if 'extracted_text_data' in medical_case_data:
                    extracted = medical_case_data['extracted_text_data']
                    text_parts.append(extracted.get('email_body', ''))
                    if 'attachments' in extracted:
                        for att in extracted['attachments']:
                            text_parts.append(att.get('text', ''))
                
                # Direct text fields
                for field in ['diagnostico_principal', 'motivo_consulta', 'motivo_remision', 
                             'enfermedad_actual', 'observaciones_adicionales']:
                    if field in medical_case_data:
                        text_parts.append(str(medical_case_data[field]))
            
            return ' '.join(filter(None, text_parts)).lower()
            
        except Exception as e:
            logger.warning(f"Error extracting text content: {str(e)}")
            return ""
    
    def _score_urgency_keywords(self, text: str) -> float:
        """Score based on urgency keywords"""
        try:
            score = 0.0
            
            # Check for critical conditions
            for condition_type, condition_data in self.critical_conditions.items():
                for pattern in condition_data['patterns']:
                    if re.search(pattern, text, re.IGNORECASE):
                        score = max(score, condition_data['score'])
                        logger.debug(f"Critical condition detected: {pattern} (Score: {condition_data['score']})")
            
            # If no critical conditions, use basic urgency keywords
            if score == 0:
                urgency_keywords = {
                    'critico': 80, 'critical': 80, 'grave': 70, 'severo': 70,
                    'urgente': 60, 'urgent': 60, 'emergencia': 75, 'emergency': 75,
                    'inmediato': 65, 'immediate': 65, 'shock': 85, 'paro': 90,
                    'codigo azul': 95, 'code blue': 95, 'uci': 60, 'icu': 60
                }
                
                for keyword, keyword_score in urgency_keywords.items():
                    if keyword in text:
                        score = max(score, keyword_score)
            
            return min(score, 100.0)
            
        except Exception as e:
            logger.warning(f"Error scoring urgency keywords: {str(e)}")
            return 0.0
    
    def _score_vital_signs(self, vital_signs: Dict[str, Any]) -> float:
        """Score based on vital signs abnormalities"""
        try:
            score = 0.0
            abnormal_count = 0
            
            # Heart rate scoring
            hr = vital_signs.get('heart_rate')
            if hr:
                if hr < 50 or hr > 120:
                    score += 30
                    abnormal_count += 1
                elif hr < 60 or hr > 100:
                    score += 15
                    abnormal_count += 1
            
            # Blood pressure scoring
            systolic = vital_signs.get('blood_pressure_systolic')
            diastolic = vital_signs.get('blood_pressure_diastolic')
            if systolic and diastolic:
                if systolic < 90 or systolic > 180 or diastolic > 110:
                    score += 35
                    abnormal_count += 1
                elif systolic < 100 or systolic > 160 or diastolic > 90:
                    score += 20
                    abnormal_count += 1
            
            # Temperature scoring
            temp = vital_signs.get('temperature')
            if temp:
                if temp > 39.0 or temp < 35.0:
                    score += 25
                    abnormal_count += 1
                elif temp > 38.5 or temp < 36.0:
                    score += 15
                    abnormal_count += 1
            
            # Oxygen saturation scoring
            spo2 = vital_signs.get('oxygen_saturation')
            if spo2:
                if spo2 < 90:
                    score += 40
                    abnormal_count += 1
                elif spo2 < 95:
                    score += 25
                    abnormal_count += 1
            
            # Respiratory rate scoring
            rr = vital_signs.get('respiratory_rate')
            if rr:
                if rr < 8 or rr > 30:
                    score += 30
                    abnormal_count += 1
                elif rr < 12 or rr > 24:
                    score += 15
                    abnormal_count += 1
            
            # Glasgow scale scoring
            glasgow = vital_signs.get('glasgow_scale')
            if glasgow:
                try:
                    glasgow_score = int(glasgow)
                    if glasgow_score <= 8:
                        score += 50
                        abnormal_count += 1
                    elif glasgow_score <= 12:
                        score += 30
                        abnormal_count += 1
                except ValueError:
                    pass
            
            # Bonus for multiple abnormal vital signs
            if abnormal_count >= 3:
                score *= 1.3
            elif abnormal_count >= 2:
                score *= 1.2
            
            return min(score, 100.0)
            
        except Exception as e:
            logger.warning(f"Error scoring vital signs: {str(e)}")
            return 0.0
    
    def _score_clinical_severity(self, text: str) -> float:
        """Score based on clinical severity indicators"""
        try:
            score = 0.0
            
            # Severity indicators
            severity_indicators = {
                'dolor intenso': 20, 'severe pain': 20, 'dolor severo': 25,
                'hemorragia': 30, 'hemorrhage': 30, 'sangrado': 25,
                'dificultad respiratoria': 25, 'dyspnea': 25, 'disnea': 25,
                'perdida de conciencia': 35, 'loss of consciousness': 35,
                'convulsiones': 30, 'seizures': 30, 'crisis convulsiva': 30,
                'vomito': 10, 'vomiting': 10, 'nauseas': 5,
                'fiebre alta': 15, 'high fever': 15, 'hipertermia': 20,
                'hipotermia': 25, 'hypothermia': 25,
                'deshidratacion': 15, 'dehydration': 15,
                'alteracion del estado mental': 30, 'altered mental status': 30,
                'agitacion': 15, 'agitation': 15, 'confusion': 20,
                'paralisis': 35, 'paralysis': 35, 'parestesias': 20,
                'dolor toracico': 25, 'chest pain': 25, 'dolor precordial': 30
            }
            
            for indicator, indicator_score in severity_indicators.items():
                if indicator in text:
                    score += indicator_score
            
            # Functional status indicators
            functional_indicators = {
                'postrado': 20, 'bedridden': 20, 'incapacitado': 25,
                'dependiente': 15, 'dependent': 15, 'ambulatorio': -5,
                'independiente': -10, 'independent': -10
            }
            
            for indicator, indicator_score in functional_indicators.items():
                if indicator in text:
                    score += indicator_score
            
            return max(min(score, 100.0), 0.0)
            
        except Exception as e:
            logger.warning(f"Error scoring clinical severity: {str(e)}")
            return 0.0
    
    def _score_age_factor(self, patient_info: Dict[str, Any]) -> float:
        """Score based on age-related risk factors"""
        try:
            age = patient_info.get('age')
            if not age:
                return 0.0
            
            base_score = 0.0
            
            # Apply age-based multipliers
            for risk_category, risk_data in self.age_risk_factors.items():
                if risk_data['min_age'] <= age <= risk_data['max_age']:
                    base_score = 30 * risk_data['multiplier']
                    break
            
            # Additional scoring for extreme ages
            if age < 1:  # Neonates
                base_score = 50
            elif age > 90:  # Very elderly
                base_score = 45
            
            return min(base_score, 100.0)
            
        except Exception as e:
            logger.warning(f"Error scoring age factor: {str(e)}")
            return 0.0
    
    def _score_specialty_urgency(self, specialty: Optional[str]) -> float:
        """Score based on specialty urgency level"""
        try:
            if not specialty:
                return 50.0  # Default score
            
            urgency_factor = self.specialty_urgency.get(specialty, 0.5)
            return urgency_factor * 100
            
        except Exception as e:
            logger.warning(f"Error scoring specialty urgency: {str(e)}")
            return 50.0
    
    def _score_temporal_urgency(self, text: str) -> float:
        """Score based on temporal urgency indicators"""
        try:
            score = 0.0
            
            for urgency_level, urgency_data in self.temporal_indicators.items():
                for pattern in urgency_data['patterns']:
                    if re.search(pattern, text, re.IGNORECASE):
                        score = max(score, urgency_data['score'] * 100)
                        break
            
            return score
            
        except Exception as e:
            logger.warning(f"Error scoring temporal urgency: {str(e)}")
            return 0.0
    
    def _calculate_weighted_score(self, scores: Dict[str, float]) -> float:
        """Calculate weighted final score"""
        try:
            weighted_score = 0.0
            
            for score_type, score_value in scores.items():
                weight = self.scoring_weights.get(score_type, 0.0)
                weighted_score += score_value * weight
            
            return min(weighted_score, 100.0)
            
        except Exception as e:
            logger.warning(f"Error calculating weighted score: {str(e)}")
            return 50.0
    
    def _determine_priority_level(self, final_score: float) -> str:
        """Determine priority level based on final score"""
        try:
            if final_score >= 70:
                return 'Alta'
            elif final_score >= 40:
                return 'Media'
            else:
                return 'Baja'
                
        except Exception as e:
            logger.warning(f"Error determining priority level: {str(e)}")
            return 'Media'
    
    def _generate_criteria_explanation(self, scores: Dict[str, float], medical_analysis: Dict[str, Any]) -> List[str]:
        """Generate explanation of prioritization criteria"""
        try:
            explanations = []
            
            # Urgency keywords explanation
            if scores['urgency_keywords'] > 60:
                explanations.append(f"Palabras clave de urgencia detectadas (Score: {scores['urgency_keywords']:.1f})")
            
            # Vital signs explanation
            if scores['vital_signs'] > 30:
                explanations.append(f"Signos vitales alterados (Score: {scores['vital_signs']:.1f})")
            
            # Clinical severity explanation
            if scores['clinical_severity'] > 40:
                explanations.append(f"Indicadores de severidad clínica (Score: {scores['clinical_severity']:.1f})")
            
            # Age factor explanation
            if scores['age_factor'] > 30:
                age = medical_analysis.get('patient_info', {}).get('age')
                if age:
                    explanations.append(f"Factor de edad de riesgo: {age} años (Score: {scores['age_factor']:.1f})")
            
            # Specialty urgency explanation
            specialty = medical_analysis.get('specialty')
            if specialty and scores['specialty_urgency'] > 60:
                explanations.append(f"Especialidad de alta urgencia: {specialty} (Score: {scores['specialty_urgency']:.1f})")
            
            # Temporal urgency explanation
            if scores['temporal_urgency'] > 50:
                explanations.append(f"Indicadores de urgencia temporal (Score: {scores['temporal_urgency']:.1f})")
            
            return explanations
            
        except Exception as e:
            logger.warning(f"Error generating criteria explanation: {str(e)}")
            return ["Error generando explicación de criterios"]
    
    def _calculate_confidence_level(self, scores: Dict[str, float], medical_analysis: Dict[str, Any]) -> str:
        """Calculate confidence level of the classification"""
        try:
            # Count available data points
            data_points = 0
            
            if scores['urgency_keywords'] > 0:
                data_points += 1
            if scores['vital_signs'] > 0:
                data_points += 1
            if scores['clinical_severity'] > 0:
                data_points += 1
            if scores['age_factor'] > 0:
                data_points += 1
            if medical_analysis.get('specialty'):
                data_points += 1
            
            # Determine confidence based on available data
            if data_points >= 4:
                return 'Alta'
            elif data_points >= 2:
                return 'Media'
            else:
                return 'Baja'
                
        except Exception as e:
            logger.warning(f"Error calculating confidence level: {str(e)}")
            return 'Media'
