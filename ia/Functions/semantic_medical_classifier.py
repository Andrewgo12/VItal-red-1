"""
Semantic Medical Classifier for Vital Red System
Advanced AI-based classification of medical requests using semantic analysis
"""

import re
import json
import logging
import numpy as np
from typing import Dict, List, Any, Tuple, Optional
from datetime import datetime
import spacy
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity

logger = logging.getLogger(__name__)

class SemanticMedicalClassifier:
    """
    Advanced semantic classifier for medical requests using NLP and ML techniques
    """
    
    def __init__(self):
        """Initialize the semantic medical classifier"""
        try:
            # Load Spanish language model
            self.nlp = spacy.load("es_core_news_sm")
        except OSError:
            logger.warning("Spanish spaCy model not found, using basic patterns")
            self.nlp = None
        
        # Initialize TF-IDF vectorizer for semantic similarity
        self.vectorizer = TfidfVectorizer(
            max_features=1000,
            stop_words=self._get_spanish_stopwords(),
            ngram_range=(1, 3),
            min_df=1,
            max_df=0.95
        )
        
        # Medical specialty classification patterns
        self.specialty_patterns = {
            'cardiologia': {
                'keywords': [
                    'corazon', 'cardiaco', 'infarto', 'angina', 'arritmia', 'hipertension',
                    'electrocardiograma', 'ecocardiograma', 'cateterismo', 'marcapasos',
                    'insuficiencia cardiaca', 'valvular', 'coronario', 'miocardio'
                ],
                'patterns': [
                    r'dolor\s+(?:en\s+el\s+)?(?:pecho|toracico|precordial)',
                    r'infarto\s+(?:del\s+)?miocardio',
                    r'insuficiencia\s+cardiaca',
                    r'arritmia\s+(?:ventricular|auricular)',
                    r'hipertension\s+arterial'
                ]
            },
            'neurologia': {
                'keywords': [
                    'cerebro', 'neurologico', 'convulsiones', 'epilepsia', 'accidente cerebrovascular',
                    'paralisis', 'parkinson', 'alzheimer', 'cefalea', 'migrana', 'esclerosis',
                    'neuropatia', 'meningitis', 'encefalitis', 'glasgow'
                ],
                'patterns': [
                    r'accidente\s+cerebrovascular',
                    r'perdida\s+de\s+(?:conciencia|conocimiento)',
                    r'convulsiones?\s+(?:tonico|clonicas)?',
                    r'paralisis\s+(?:facial|de\s+extremidades)',
                    r'glasgow\s+(?:menor\s+)?(?:de\s+)?(?:[3-8]|bajo)'
                ]
            },
            'cirugia': {
                'keywords': [
                    'cirugia', 'quirurgico', 'operacion', 'intervencion', 'laparoscopia',
                    'apendicitis', 'hernia', 'trauma', 'fractura', 'hemorragia',
                    'perforacion', 'obstruccion', 'abdomen agudo'
                ],
                'patterns': [
                    r'abdomen\s+agudo',
                    r'trauma\s+(?:abdominal|toracico|craneal)',
                    r'hemorragia\s+(?:digestiva|interna|masiva)',
                    r'fractura\s+(?:expuesta|abierta|multiple)',
                    r'perforacion\s+(?:gastrica|intestinal)'
                ]
            },
            'medicina_interna': {
                'keywords': [
                    'fiebre', 'sepsis', 'infeccion', 'diabetes', 'hipertension',
                    'insuficiencia renal', 'hepatica', 'respiratoria', 'metabolico',
                    'endocrino', 'gastroenterologia', 'neumologia'
                ],
                'patterns': [
                    r'sepsis\s+(?:severa|grave)',
                    r'insuficiencia\s+(?:renal|hepatica|respiratoria)',
                    r'diabetes\s+(?:descompensada|mellitus)',
                    r'shock\s+(?:septico|hipovolemico)',
                    r'fiebre\s+(?:alta|persistente)'
                ]
            },
            'pediatria': {
                'keywords': [
                    'niño', 'niña', 'pediatrico', 'lactante', 'neonato', 'recien nacido',
                    'menor', 'años', 'meses', 'bronquiolitis', 'gastroenteritis'
                ],
                'patterns': [
                    r'(?:niño|niña|menor)\s+de\s+\d+\s+(?:años|meses)',
                    r'(?:lactante|neonato|recien\s+nacido)',
                    r'\d+\s+(?:años|meses)\s+de\s+edad',
                    r'pediatrico|pediatria'
                ]
            },
            'ginecologia': {
                'keywords': [
                    'embarazo', 'gestacion', 'parto', 'cesarea', 'ginecologico',
                    'menstruacion', 'ovario', 'utero', 'vaginal', 'prenatal'
                ],
                'patterns': [
                    r'embarazo\s+(?:de\s+)?\d+\s+semanas',
                    r'trabajo\s+de\s+parto',
                    r'cesarea\s+(?:urgente|programada)',
                    r'hemorragia\s+(?:vaginal|uterina)',
                    r'preeclampsia|eclampsia'
                ]
            },
            'ortopedia': {
                'keywords': [
                    'fractura', 'luxacion', 'esguince', 'articular', 'oseo',
                    'columna', 'vertebral', 'artroscopia', 'protesis', 'osteomielitis'
                ],
                'patterns': [
                    r'fractura\s+(?:de\s+)?(?:femur|tibia|radio|cubito)',
                    r'luxacion\s+(?:de\s+)?(?:hombro|cadera|rodilla)',
                    r'lesion\s+(?:medular|vertebral)',
                    r'dolor\s+(?:lumbar|cervical|articular)'
                ]
            }
        }
        
        # Urgency classification patterns
        self.urgency_indicators = {
            'critico': {
                'score': 95,
                'patterns': [
                    r'paro\s+(?:cardiaco|respiratorio|cardiorespiratorio)',
                    r'shock\s+(?:cardiogenico|septico|hipovolemico)',
                    r'coma\s+(?:profundo|glasgow\s+[3-6])',
                    r'hemorragia\s+masiva',
                    r'trauma\s+(?:craneoencefalico\s+)?severo',
                    r'estado\s+critico'
                ]
            },
            'urgente': {
                'score': 85,
                'patterns': [
                    r'infarto\s+(?:del\s+)?miocardio',
                    r'accidente\s+cerebrovascular',
                    r'embolia\s+pulmonar',
                    r'neumotorax\s+(?:a\s+)?tension',
                    r'abdomen\s+agudo',
                    r'convulsiones\s+(?:persistentes|refractarias)'
                ]
            },
            'alto': {
                'score': 75,
                'patterns': [
                    r'dolor\s+toracico\s+(?:intenso|severo)',
                    r'dificultad\s+respiratoria\s+(?:severa|grave)',
                    r'alteracion\s+del\s+estado\s+mental',
                    r'fiebre\s+alta\s+persistente',
                    r'hemorragia\s+(?:digestiva|activa)'
                ]
            },
            'medio': {
                'score': 50,
                'patterns': [
                    r'dolor\s+(?:abdominal|toracico|cefalico)',
                    r'fiebre\s+(?:moderada|intermitente)',
                    r'nauseas?\s+y\s+vomitos?',
                    r'diarrea\s+(?:persistente|cronica)',
                    r'fatiga\s+(?:extrema|severa)'
                ]
            }
        }
        
        # Temporal urgency indicators
        self.temporal_patterns = {
            'inmediato': {
                'multiplier': 1.5,
                'patterns': [
                    r'inmediato|urgente|ahora|ya',
                    r'no\s+puede\s+esperar',
                    r'requiere\s+atencion\s+inmediata'
                ]
            },
            'hoy': {
                'multiplier': 1.3,
                'patterns': [
                    r'hoy\s+mismo|mismo\s+dia',
                    r'en\s+las\s+proximas\s+horas',
                    r'antes\s+de\s+(?:las\s+)?\d+\s+(?:horas|pm|am)'
                ]
            },
            'esta_semana': {
                'multiplier': 1.1,
                'patterns': [
                    r'esta\s+semana|proximos?\s+dias',
                    r'en\s+(?:2|3|4|5)\s+dias',
                    r'antes\s+del\s+(?:viernes|fin\s+de\s+semana)'
                ]
            }
        }
    
    def classify_medical_request(self, text_content: str, metadata: Dict[str, Any] = None) -> Dict[str, Any]:
        """
        Perform comprehensive semantic classification of medical request
        
        Args:
            text_content: Full text content to analyze
            metadata: Additional metadata about the request
            
        Returns:
            Dict: Classification results with confidence scores
        """
        try:
            logger.info("Starting semantic medical classification")
            
            # Preprocess text
            processed_text = self._preprocess_text(text_content)
            
            # Perform different types of classification
            classification_results = {
                'specialty_classification': self._classify_specialty(processed_text),
                'urgency_classification': self._classify_urgency(processed_text),
                'semantic_analysis': self._perform_semantic_analysis(processed_text),
                'clinical_entities': self._extract_clinical_entities(processed_text),
                'temporal_analysis': self._analyze_temporal_urgency(processed_text),
                'confidence_metrics': {}
            }
            
            # Calculate overall confidence
            classification_results['confidence_metrics'] = self._calculate_confidence_metrics(
                classification_results
            )
            
            # Generate final classification
            final_classification = self._generate_final_classification(classification_results)
            classification_results['final_classification'] = final_classification
            
            logger.info(f"Classification completed with confidence: {final_classification.get('overall_confidence', 0):.2f}")
            
            return classification_results
            
        except Exception as e:
            logger.error(f"Error in semantic classification: {str(e)}")
            return {
                'error': str(e),
                'classification_timestamp': datetime.now().isoformat()
            }
    
    def _preprocess_text(self, text: str) -> str:
        """Preprocess text for analysis"""
        try:
            # Convert to lowercase
            text = text.lower()
            
            # Remove extra whitespace
            text = re.sub(r'\s+', ' ', text)
            
            # Remove special characters but keep medical symbols
            text = re.sub(r'[^\w\s\-\+\%\/\(\)\.]', ' ', text)
            
            # Normalize medical abbreviations
            medical_abbrevs = {
                'fc': 'frecuencia cardiaca',
                'fr': 'frecuencia respiratoria',
                'ta': 'tension arterial',
                'spo2': 'saturacion oxigeno',
                'ecg': 'electrocardiograma',
                'rx': 'radiografia',
                'tac': 'tomografia',
                'rm': 'resonancia magnetica'
            }
            
            for abbrev, full_form in medical_abbrevs.items():
                text = re.sub(rf'\b{abbrev}\b', full_form, text)
            
            return text.strip()
            
        except Exception as e:
            logger.warning(f"Error preprocessing text: {str(e)}")
            return text
    
    def _classify_specialty(self, text: str) -> Dict[str, Any]:
        """Classify medical specialty based on content"""
        try:
            specialty_scores = {}
            
            for specialty, data in self.specialty_patterns.items():
                score = 0
                matches = []
                
                # Check keywords
                for keyword in data['keywords']:
                    if keyword in text:
                        score += 1
                        matches.append(f"keyword: {keyword}")
                
                # Check patterns
                for pattern in data['patterns']:
                    if re.search(pattern, text, re.IGNORECASE):
                        score += 2  # Patterns have higher weight
                        matches.append(f"pattern: {pattern}")
                
                if score > 0:
                    specialty_scores[specialty] = {
                        'score': score,
                        'matches': matches,
                        'confidence': min(score / 10.0, 1.0)  # Normalize to 0-1
                    }
            
            # Find best match
            if specialty_scores:
                best_specialty = max(specialty_scores.keys(), key=lambda x: specialty_scores[x]['score'])
                return {
                    'predicted_specialty': best_specialty,
                    'confidence': specialty_scores[best_specialty]['confidence'],
                    'all_scores': specialty_scores,
                    'matches': specialty_scores[best_specialty]['matches']
                }
            else:
                return {
                    'predicted_specialty': 'medicina_general',
                    'confidence': 0.3,
                    'all_scores': {},
                    'matches': []
                }
                
        except Exception as e:
            logger.error(f"Error in specialty classification: {str(e)}")
            return {'error': str(e)}
    
    def _classify_urgency(self, text: str) -> Dict[str, Any]:
        """Classify urgency level based on content"""
        try:
            urgency_scores = []
            
            for urgency_level, data in self.urgency_indicators.items():
                for pattern in data['patterns']:
                    matches = re.findall(pattern, text, re.IGNORECASE)
                    if matches:
                        urgency_scores.append({
                            'level': urgency_level,
                            'score': data['score'],
                            'matches': matches,
                            'pattern': pattern
                        })
            
            if urgency_scores:
                # Get highest urgency score
                best_urgency = max(urgency_scores, key=lambda x: x['score'])
                
                # Apply temporal multipliers
                temporal_multiplier = self._get_temporal_multiplier(text)
                final_score = min(best_urgency['score'] * temporal_multiplier, 100)
                
                return {
                    'urgency_level': best_urgency['level'],
                    'urgency_score': final_score,
                    'base_score': best_urgency['score'],
                    'temporal_multiplier': temporal_multiplier,
                    'confidence': min(final_score / 100.0, 1.0),
                    'matches': urgency_scores
                }
            else:
                return {
                    'urgency_level': 'bajo',
                    'urgency_score': 25,
                    'confidence': 0.5,
                    'matches': []
                }
                
        except Exception as e:
            logger.error(f"Error in urgency classification: {str(e)}")
            return {'error': str(e)}
    
    def _get_temporal_multiplier(self, text: str) -> float:
        """Calculate temporal urgency multiplier"""
        try:
            for temporal_type, data in self.temporal_patterns.items():
                for pattern in data['patterns']:
                    if re.search(pattern, text, re.IGNORECASE):
                        return data['multiplier']
            
            return 1.0  # No temporal urgency detected
            
        except Exception as e:
            logger.warning(f"Error calculating temporal multiplier: {str(e)}")
            return 1.0
    
    def _perform_semantic_analysis(self, text: str) -> Dict[str, Any]:
        """Perform semantic analysis using NLP"""
        try:
            if not self.nlp:
                return {'error': 'spaCy model not available'}
            
            doc = self.nlp(text)
            
            # Extract entities
            entities = []
            for ent in doc.ents:
                entities.append({
                    'text': ent.text,
                    'label': ent.label_,
                    'start': ent.start_char,
                    'end': ent.end_char
                })
            
            # Extract key phrases (noun phrases)
            noun_phrases = [chunk.text for chunk in doc.noun_chunks if len(chunk.text) > 3]
            
            # Sentiment analysis (basic)
            sentiment_score = self._calculate_sentiment(doc)
            
            return {
                'entities': entities,
                'noun_phrases': noun_phrases[:10],  # Limit to top 10
                'sentiment_score': sentiment_score,
                'token_count': len(doc),
                'sentence_count': len(list(doc.sents))
            }
            
        except Exception as e:
            logger.error(f"Error in semantic analysis: {str(e)}")
            return {'error': str(e)}
    
    def _calculate_sentiment(self, doc) -> float:
        """Calculate basic sentiment score"""
        try:
            # Simple sentiment based on medical urgency words
            positive_words = ['estable', 'mejora', 'recuperacion', 'normal', 'bueno']
            negative_words = ['grave', 'critico', 'severo', 'urgente', 'dolor', 'deterioro']
            
            positive_count = sum(1 for token in doc if token.text.lower() in positive_words)
            negative_count = sum(1 for token in doc if token.text.lower() in negative_words)
            
            total_words = len(doc)
            if total_words == 0:
                return 0.0
            
            sentiment = (positive_count - negative_count) / total_words
            return max(-1.0, min(1.0, sentiment))  # Normalize to -1 to 1
            
        except Exception as e:
            logger.warning(f"Error calculating sentiment: {str(e)}")
            return 0.0
    
    def _extract_clinical_entities(self, text: str) -> Dict[str, List[str]]:
        """Extract clinical entities from text"""
        try:
            entities = {
                'symptoms': [],
                'diagnoses': [],
                'medications': [],
                'procedures': [],
                'vital_signs': [],
                'body_parts': []
            }
            
            # Symptom patterns
            symptom_patterns = [
                r'dolor\s+(?:en\s+)?(?:el\s+)?(\w+)',
                r'(?:presenta|refiere|tiene)\s+(\w+(?:\s+\w+)*)',
                r'sintomas?\s+(?:de\s+)?(\w+(?:\s+\w+)*)'
            ]
            
            for pattern in symptom_patterns:
                matches = re.findall(pattern, text, re.IGNORECASE)
                entities['symptoms'].extend([match.strip() for match in matches if len(match.strip()) > 2])
            
            # Diagnosis patterns
            diagnosis_patterns = [
                r'diagnostico\s+(?:de\s+)?(\w+(?:\s+\w+)*)',
                r'(?:padece|sufre)\s+(?:de\s+)?(\w+(?:\s+\w+)*)',
                r'enfermedad\s+(\w+(?:\s+\w+)*)'
            ]
            
            for pattern in diagnosis_patterns:
                matches = re.findall(pattern, text, re.IGNORECASE)
                entities['diagnoses'].extend([match.strip() for match in matches if len(match.strip()) > 2])
            
            # Medication patterns
            medication_patterns = [
                r'(?:toma|recibe|medicamento)\s+(\w+(?:\s+\w+)*)',
                r'tratamiento\s+con\s+(\w+(?:\s+\w+)*)',
                r'(\w+)\s+(?:mg|mcg|ml|cc)'
            ]
            
            for pattern in medication_patterns:
                matches = re.findall(pattern, text, re.IGNORECASE)
                entities['medications'].extend([match.strip() for match in matches if len(match.strip()) > 2])
            
            # Vital signs patterns
            vital_patterns = [
                r'(?:fc|frecuencia\s+cardiaca)[\s:]*(\d+)',
                r'(?:fr|frecuencia\s+respiratoria)[\s:]*(\d+)',
                r'(?:ta|tension\s+arterial)[\s:]*(\d+/\d+)',
                r'temperatura[\s:]*(\d+\.?\d*)',
                r'(?:spo2|saturacion)[\s:]*(\d+)%?'
            ]
            
            for pattern in vital_patterns:
                matches = re.findall(pattern, text, re.IGNORECASE)
                entities['vital_signs'].extend(matches)
            
            # Remove duplicates and limit results
            for key in entities:
                entities[key] = list(set(entities[key]))[:5]  # Limit to 5 items each
            
            return entities
            
        except Exception as e:
            logger.error(f"Error extracting clinical entities: {str(e)}")
            return {}
    
    def _analyze_temporal_urgency(self, text: str) -> Dict[str, Any]:
        """Analyze temporal urgency indicators"""
        try:
            temporal_indicators = []
            
            for temporal_type, data in self.temporal_patterns.items():
                for pattern in data['patterns']:
                    matches = re.findall(pattern, text, re.IGNORECASE)
                    if matches:
                        temporal_indicators.append({
                            'type': temporal_type,
                            'multiplier': data['multiplier'],
                            'matches': matches,
                            'pattern': pattern
                        })
            
            # Calculate overall temporal urgency
            if temporal_indicators:
                max_multiplier = max(indicator['multiplier'] for indicator in temporal_indicators)
                urgency_level = 'high' if max_multiplier >= 1.4 else 'medium' if max_multiplier >= 1.2 else 'low'
            else:
                max_multiplier = 1.0
                urgency_level = 'low'
            
            return {
                'temporal_urgency_level': urgency_level,
                'temporal_multiplier': max_multiplier,
                'indicators': temporal_indicators
            }
            
        except Exception as e:
            logger.error(f"Error in temporal analysis: {str(e)}")
            return {'error': str(e)}
    
    def _calculate_confidence_metrics(self, classification_results: Dict[str, Any]) -> Dict[str, float]:
        """Calculate confidence metrics for classification results"""
        try:
            confidences = []
            
            # Specialty confidence
            if 'specialty_classification' in classification_results:
                specialty_conf = classification_results['specialty_classification'].get('confidence', 0)
                confidences.append(specialty_conf)
            
            # Urgency confidence
            if 'urgency_classification' in classification_results:
                urgency_conf = classification_results['urgency_classification'].get('confidence', 0)
                confidences.append(urgency_conf)
            
            # Semantic analysis confidence (based on entity extraction)
            if 'semantic_analysis' in classification_results:
                semantic_data = classification_results['semantic_analysis']
                if 'entities' in semantic_data:
                    entity_conf = min(len(semantic_data['entities']) / 5.0, 1.0)  # Normalize
                    confidences.append(entity_conf)
            
            # Clinical entities confidence
            if 'clinical_entities' in classification_results:
                clinical_data = classification_results['clinical_entities']
                total_entities = sum(len(entities) for entities in clinical_data.values())
                clinical_conf = min(total_entities / 10.0, 1.0)  # Normalize
                confidences.append(clinical_conf)
            
            # Calculate overall confidence
            overall_confidence = np.mean(confidences) if confidences else 0.0
            
            return {
                'overall_confidence': float(overall_confidence),
                'specialty_confidence': confidences[0] if len(confidences) > 0 else 0.0,
                'urgency_confidence': confidences[1] if len(confidences) > 1 else 0.0,
                'semantic_confidence': confidences[2] if len(confidences) > 2 else 0.0,
                'clinical_confidence': confidences[3] if len(confidences) > 3 else 0.0
            }
            
        except Exception as e:
            logger.error(f"Error calculating confidence metrics: {str(e)}")
            return {'overall_confidence': 0.0}
    
    def _generate_final_classification(self, classification_results: Dict[str, Any]) -> Dict[str, Any]:
        """Generate final classification based on all analysis results"""
        try:
            # Extract key results
            specialty = classification_results.get('specialty_classification', {}).get('predicted_specialty', 'medicina_general')
            urgency_data = classification_results.get('urgency_classification', {})
            urgency_level = urgency_data.get('urgency_level', 'bajo')
            urgency_score = urgency_data.get('urgency_score', 25)
            
            # Map urgency levels to priority
            priority_mapping = {
                'critico': 'Alta',
                'urgente': 'Alta',
                'alto': 'Alta',
                'medio': 'Media',
                'bajo': 'Baja'
            }
            
            priority = priority_mapping.get(urgency_level, 'Media')
            
            # Generate classification summary
            confidence_metrics = classification_results.get('confidence_metrics', {})
            overall_confidence = confidence_metrics.get('overall_confidence', 0.0)
            
            # Generate explanation
            explanation = self._generate_classification_explanation(classification_results)
            
            return {
                'predicted_specialty': specialty,
                'urgency_level': urgency_level,
                'urgency_score': urgency_score,
                'priority_level': priority,
                'overall_confidence': overall_confidence,
                'explanation': explanation,
                'classification_timestamp': datetime.now().isoformat(),
                'classifier_version': '1.0.0'
            }
            
        except Exception as e:
            logger.error(f"Error generating final classification: {str(e)}")
            return {
                'predicted_specialty': 'medicina_general',
                'urgency_level': 'medio',
                'urgency_score': 50,
                'priority_level': 'Media',
                'overall_confidence': 0.0,
                'explanation': f"Error en clasificación: {str(e)}",
                'classification_timestamp': datetime.now().isoformat()
            }
    
    def _generate_classification_explanation(self, classification_results: Dict[str, Any]) -> str:
        """Generate human-readable explanation of classification"""
        try:
            explanation_parts = []
            
            # Specialty explanation
            specialty_data = classification_results.get('specialty_classification', {})
            if specialty_data.get('predicted_specialty'):
                specialty = specialty_data['predicted_specialty'].replace('_', ' ').title()
                confidence = specialty_data.get('confidence', 0)
                explanation_parts.append(f"Especialidad detectada: {specialty} (confianza: {confidence:.1%})")
            
            # Urgency explanation
            urgency_data = classification_results.get('urgency_classification', {})
            if urgency_data.get('urgency_level'):
                urgency = urgency_data['urgency_level']
                score = urgency_data.get('urgency_score', 0)
                explanation_parts.append(f"Nivel de urgencia: {urgency} (score: {score:.0f}/100)")
            
            # Clinical entities
            clinical_data = classification_results.get('clinical_entities', {})
            if clinical_data:
                entity_counts = {k: len(v) for k, v in clinical_data.items() if v}
                if entity_counts:
                    entity_summary = ", ".join([f"{k}: {v}" for k, v in entity_counts.items()])
                    explanation_parts.append(f"Entidades clínicas detectadas: {entity_summary}")
            
            # Temporal urgency
            temporal_data = classification_results.get('temporal_analysis', {})
            if temporal_data.get('temporal_urgency_level') != 'low':
                temporal_level = temporal_data['temporal_urgency_level']
                explanation_parts.append(f"Urgencia temporal: {temporal_level}")
            
            return ". ".join(explanation_parts) if explanation_parts else "Clasificación basada en análisis semántico básico"
            
        except Exception as e:
            logger.warning(f"Error generating explanation: {str(e)}")
            return "Explicación no disponible"
    
    def _get_spanish_stopwords(self) -> List[str]:
        """Get Spanish stopwords for TF-IDF"""
        return [
            'el', 'la', 'de', 'que', 'y', 'a', 'en', 'un', 'es', 'se', 'no', 'te', 'lo', 'le',
            'da', 'su', 'por', 'son', 'con', 'para', 'al', 'del', 'los', 'las', 'una', 'como',
            'pero', 'sus', 'me', 'hasta', 'hay', 'donde', 'han', 'quien', 'están', 'estado',
            'desde', 'todo', 'nos', 'durante', 'todos', 'uno', 'les', 'ni', 'contra', 'otros',
            'ese', 'eso', 'ante', 'ellos', 'e', 'esto', 'mí', 'antes', 'algunos', 'qué', 'unos',
            'yo', 'otro', 'otras', 'otra', 'él', 'tanto', 'esa', 'estos', 'mucho', 'quienes',
            'nada', 'muchos', 'cual', 'poco', 'ella', 'estar', 'estas', 'algunas', 'algo', 'nosotros'
        ]
