"""
Data Validation and Quality Assurance System
Ensures data integrity and quality throughout the extraction process
"""

import os
import json
import hashlib
import re
from typing import Dict, List, Any, Optional, Tuple
from datetime import datetime
import logging

logger = logging.getLogger(__name__)

class DataValidator:
    """
    Comprehensive data validation system
    """
    
    def __init__(self):
        """Initialize data validator"""
        self.validation_rules = self._load_validation_rules()
        self.quality_thresholds = self._load_quality_thresholds()
    
    def _load_validation_rules(self) -> Dict[str, Any]:
        """Load validation rules"""
        return {
            'email_address': {
                'pattern': r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$',
                'required': True
            },
            'subject': {
                'max_length': 1000,
                'min_length': 1,
                'required': False
            },
            'date': {
                'format': 'iso',
                'required': True
            },
            'unique_id': {
                'pattern': r'^[a-f0-9]{32}$',
                'required': True
            },
            'file_hash': {
                'pattern': r'^[a-f0-9]{32}$',
                'required': True
            }
        }
    
    def _load_quality_thresholds(self) -> Dict[str, Any]:
        """Load quality thresholds"""
        return {
            'min_text_extraction_rate': 0.8,  # 80% of files should have text extracted
            'max_error_rate': 0.1,  # Maximum 10% error rate
            'min_metadata_completeness': 0.9,  # 90% of required metadata fields
            'max_processing_time_per_email': 300,  # 5 minutes per email
            'min_attachment_success_rate': 0.95  # 95% attachment processing success
        }
    
    def validate_email_metadata(self, metadata: Dict[str, Any]) -> Dict[str, Any]:
        """
        Validate email metadata
        
        Args:
            metadata: Email metadata dictionary
            
        Returns:
            Dict: Validation results
        """
        validation_result = {
            'is_valid': True,
            'errors': [],
            'warnings': [],
            'completeness_score': 0.0,
            'quality_score': 0.0
        }
        
        required_fields = ['unique_id', 'subject', 'from', 'date']
        present_fields = 0
        
        # Check required fields
        for field in required_fields:
            if field in metadata and metadata[field]:
                present_fields += 1
            else:
                validation_result['errors'].append(f"Missing required field: {field}")
                validation_result['is_valid'] = False
        
        validation_result['completeness_score'] = present_fields / len(required_fields)
        
        # Validate email addresses
        if 'from' in metadata and metadata['from']:
            for sender in metadata['from']:
                if not self._validate_email_address(sender.get('email', '')):
                    validation_result['warnings'].append(f"Invalid sender email: {sender.get('email', '')}")
        
        # Validate date format
        if 'date' in metadata and metadata['date']:
            if not self._validate_date_format(metadata['date']):
                validation_result['errors'].append("Invalid date format")
                validation_result['is_valid'] = False
        
        # Validate unique ID
        if 'unique_id' in metadata:
            if not self._validate_unique_id(metadata['unique_id']):
                validation_result['errors'].append("Invalid unique ID format")
                validation_result['is_valid'] = False
        
        # Calculate quality score
        validation_result['quality_score'] = self._calculate_metadata_quality_score(metadata, validation_result)
        
        return validation_result
    
    def validate_attachment_data(self, attachment: Dict[str, Any]) -> Dict[str, Any]:
        """
        Validate attachment data
        
        Args:
            attachment: Attachment data dictionary
            
        Returns:
            Dict: Validation results
        """
        validation_result = {
            'is_valid': True,
            'errors': [],
            'warnings': [],
            'integrity_verified': False
        }
        
        # Check required fields
        required_fields = ['original_filename', 'file_path', 'size_bytes', 'md5_hash']
        for field in required_fields:
            if field not in attachment or attachment[field] is None:
                validation_result['errors'].append(f"Missing required attachment field: {field}")
                validation_result['is_valid'] = False
        
        # Verify file exists
        if 'file_path' in attachment and attachment['file_path']:
            if not os.path.exists(attachment['file_path']):
                validation_result['errors'].append(f"Attachment file not found: {attachment['file_path']}")
                validation_result['is_valid'] = False
            else:
                # Verify file integrity
                if self._verify_file_integrity(attachment):
                    validation_result['integrity_verified'] = True
                else:
                    validation_result['errors'].append("File integrity check failed")
                    validation_result['is_valid'] = False
        
        # Validate filename
        if 'original_filename' in attachment:
            if not self._validate_filename(attachment['original_filename']):
                validation_result['warnings'].append("Potentially unsafe filename")
        
        # Check file size reasonableness
        if 'size_bytes' in attachment:
            size_mb = attachment['size_bytes'] / (1024 * 1024)
            if size_mb > 100:  # Files larger than 100MB
                validation_result['warnings'].append(f"Large file size: {size_mb:.1f}MB")
            elif size_mb == 0:
                validation_result['warnings'].append("Zero-byte file")
        
        return validation_result
    
    def validate_extracted_text(self, text_data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Validate extracted text data
        
        Args:
            text_data: Extracted text data
            
        Returns:
            Dict: Validation results
        """
        validation_result = {
            'is_valid': True,
            'errors': [],
            'warnings': [],
            'text_quality_score': 0.0,
            'language_consistency': True
        }
        
        # Check if text was extracted
        email_text = text_data.get('email_body', {}).get('text', '')
        if not email_text:
            validation_result['warnings'].append("No email body text extracted")
        
        # Validate text quality
        if email_text:
            quality_score = self._assess_text_quality(email_text)
            validation_result['text_quality_score'] = quality_score
            
            if quality_score < 0.5:
                validation_result['warnings'].append("Low text quality detected")
        
        # Check attachment text extraction
        attachments = text_data.get('attachments', [])
        extracted_count = sum(1 for att in attachments if att.get('text'))
        total_count = len(attachments)
        
        if total_count > 0:
            extraction_rate = extracted_count / total_count
            if extraction_rate < self.quality_thresholds['min_text_extraction_rate']:
                validation_result['warnings'].append(
                    f"Low text extraction rate: {extraction_rate:.1%}"
                )
        
        return validation_result
    
    def validate_json_structure(self, json_data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Validate JSON structure and schema
        
        Args:
            json_data: JSON data to validate
            
        Returns:
            Dict: Validation results
        """
        validation_result = {
            'is_valid': True,
            'errors': [],
            'warnings': [],
            'schema_compliance': 0.0
        }
        
        # Check top-level structure
        required_sections = ['email_info', 'metadata', 'content', 'attachments', 'statistics']
        present_sections = 0
        
        for section in required_sections:
            if section in json_data:
                present_sections += 1
            else:
                validation_result['errors'].append(f"Missing required section: {section}")
                validation_result['is_valid'] = False
        
        validation_result['schema_compliance'] = present_sections / len(required_sections)
        
        # Validate email_info section
        if 'email_info' in json_data:
            email_info = json_data['email_info']
            if 'unique_id' not in email_info:
                validation_result['errors'].append("Missing unique_id in email_info")
                validation_result['is_valid'] = False
            
            if 'processing_timestamp' not in email_info:
                validation_result['errors'].append("Missing processing_timestamp in email_info")
                validation_result['is_valid'] = False
        
        # Check for circular references or excessive nesting
        try:
            json.dumps(json_data)
        except (TypeError, ValueError) as e:
            validation_result['errors'].append(f"JSON serialization error: {str(e)}")
            validation_result['is_valid'] = False
        
        return validation_result
    
    def _validate_email_address(self, email: str) -> bool:
        """Validate email address format"""
        if not email:
            return False
        pattern = self.validation_rules['email_address']['pattern']
        return bool(re.match(pattern, email))
    
    def _validate_date_format(self, date_str: str) -> bool:
        """Validate date format"""
        try:
            datetime.fromisoformat(date_str.replace('Z', '+00:00'))
            return True
        except (ValueError, AttributeError):
            return False
    
    def _validate_unique_id(self, unique_id: str) -> bool:
        """Validate unique ID format"""
        if not unique_id:
            return False
        pattern = self.validation_rules['unique_id']['pattern']
        return bool(re.match(pattern, unique_id))
    
    def _validate_filename(self, filename: str) -> bool:
        """Validate filename for safety"""
        if not filename:
            return False
        
        # Check for dangerous characters
        dangerous_chars = ['<', '>', ':', '"', '|', '?', '*', '\\', '/']
        if any(char in filename for char in dangerous_chars):
            return False
        
        # Check for dangerous extensions
        dangerous_extensions = ['.exe', '.bat', '.cmd', '.scr', '.vbs', '.js']
        if any(filename.lower().endswith(ext) for ext in dangerous_extensions):
            return False
        
        return True
    
    def _verify_file_integrity(self, attachment: Dict[str, Any]) -> bool:
        """Verify file integrity using hash"""
        try:
            file_path = attachment.get('file_path')
            expected_hash = attachment.get('md5_hash')
            
            if not file_path or not expected_hash:
                return False
            
            # Calculate actual hash
            with open(file_path, 'rb') as f:
                actual_hash = hashlib.md5(f.read()).hexdigest()
            
            return actual_hash == expected_hash
            
        except Exception as e:
            logger.error(f"Error verifying file integrity: {str(e)}")
            return False
    
    def _assess_text_quality(self, text: str) -> float:
        """Assess text quality (0.0 to 1.0)"""
        if not text:
            return 0.0
        
        quality_score = 1.0
        
        # Check for excessive special characters
        special_char_ratio = sum(1 for c in text if not c.isalnum() and not c.isspace()) / len(text)
        if special_char_ratio > 0.3:
            quality_score -= 0.2
        
        # Check for reasonable word length
        words = text.split()
        if words:
            avg_word_length = sum(len(word) for word in words) / len(words)
            if avg_word_length < 2 or avg_word_length > 15:
                quality_score -= 0.1
        
        # Check for repeated characters (OCR artifacts)
        repeated_char_pattern = r'(.)\1{5,}'  # Same character repeated 5+ times
        if re.search(repeated_char_pattern, text):
            quality_score -= 0.2
        
        # Check for reasonable sentence structure
        sentences = re.split(r'[.!?]+', text)
        if sentences:
            avg_sentence_length = sum(len(s.split()) for s in sentences) / len(sentences)
            if avg_sentence_length < 3 or avg_sentence_length > 50:
                quality_score -= 0.1
        
        return max(0.0, quality_score)
    
    def _calculate_metadata_quality_score(self, metadata: Dict[str, Any], 
                                        validation_result: Dict[str, Any]) -> float:
        """Calculate overall metadata quality score"""
        base_score = validation_result['completeness_score']
        
        # Penalty for errors
        error_penalty = len(validation_result['errors']) * 0.1
        warning_penalty = len(validation_result['warnings']) * 0.05
        
        quality_score = base_score - error_penalty - warning_penalty
        
        # Bonus for additional useful fields
        bonus_fields = ['cc', 'bcc', 'thread_info', 'security_info']
        bonus = sum(0.02 for field in bonus_fields if field in metadata and metadata[field])
        
        return max(0.0, min(1.0, quality_score + bonus))

class QualityAssurance:
    """
    Quality assurance system for the entire processing pipeline
    """
    
    def __init__(self):
        """Initialize QA system"""
        self.validator = DataValidator()
        self.qa_results = []
    
    def run_full_qa_check(self, email_data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Run comprehensive QA check on processed email data
        
        Args:
            email_data: Complete processed email data
            
        Returns:
            Dict: QA results
        """
        qa_result = {
            'email_id': email_data.get('email_info', {}).get('unique_id', 'unknown'),
            'timestamp': datetime.now().isoformat(),
            'overall_quality': 'unknown',
            'quality_score': 0.0,
            'validations': {},
            'recommendations': []
        }
        
        # Validate metadata
        metadata = email_data.get('metadata', {})
        qa_result['validations']['metadata'] = self.validator.validate_email_metadata(metadata)
        
        # Validate attachments
        attachments = email_data.get('attachments', {}).get('files', [])
        attachment_validations = []
        for i, attachment in enumerate(attachments):
            validation = self.validator.validate_attachment_data(attachment)
            validation['attachment_index'] = i
            attachment_validations.append(validation)
        qa_result['validations']['attachments'] = attachment_validations
        
        # Validate extracted text
        content = email_data.get('content', {})
        qa_result['validations']['text'] = self.validator.validate_extracted_text(content)
        
        # Validate JSON structure
        qa_result['validations']['json_structure'] = self.validator.validate_json_structure(email_data)
        
        # Calculate overall quality
        qa_result['quality_score'] = self._calculate_overall_quality(qa_result['validations'])
        qa_result['overall_quality'] = self._determine_quality_level(qa_result['quality_score'])
        
        # Generate recommendations
        qa_result['recommendations'] = self._generate_recommendations(qa_result['validations'])
        
        self.qa_results.append(qa_result)
        return qa_result
    
    def _calculate_overall_quality(self, validations: Dict[str, Any]) -> float:
        """Calculate overall quality score"""
        scores = []
        
        # Metadata quality
        metadata_val = validations.get('metadata', {})
        if 'quality_score' in metadata_val:
            scores.append(metadata_val['quality_score'])
        
        # Attachment quality
        attachment_vals = validations.get('attachments', [])
        if attachment_vals:
            valid_attachments = sum(1 for val in attachment_vals if val.get('is_valid', False))
            attachment_score = valid_attachments / len(attachment_vals)
            scores.append(attachment_score)
        
        # Text quality
        text_val = validations.get('text', {})
        if 'text_quality_score' in text_val:
            scores.append(text_val['text_quality_score'])
        
        # JSON structure quality
        json_val = validations.get('json_structure', {})
        if 'schema_compliance' in json_val:
            scores.append(json_val['schema_compliance'])
        
        return sum(scores) / len(scores) if scores else 0.0
    
    def _determine_quality_level(self, score: float) -> str:
        """Determine quality level from score"""
        if score >= 0.9:
            return 'excellent'
        elif score >= 0.8:
            return 'good'
        elif score >= 0.6:
            return 'fair'
        elif score >= 0.4:
            return 'poor'
        else:
            return 'critical'
    
    def _generate_recommendations(self, validations: Dict[str, Any]) -> List[str]:
        """Generate improvement recommendations"""
        recommendations = []
        
        # Check metadata issues
        metadata_val = validations.get('metadata', {})
        if metadata_val.get('completeness_score', 1.0) < 0.8:
            recommendations.append("Improve metadata extraction completeness")
        
        # Check attachment issues
        attachment_vals = validations.get('attachments', [])
        failed_attachments = [val for val in attachment_vals if not val.get('is_valid', True)]
        if failed_attachments:
            recommendations.append(f"Fix {len(failed_attachments)} attachment processing issues")
        
        # Check text quality
        text_val = validations.get('text', {})
        if text_val.get('text_quality_score', 1.0) < 0.6:
            recommendations.append("Improve text extraction quality")
        
        return recommendations
    
    def get_qa_summary(self) -> Dict[str, Any]:
        """Get QA summary for all processed emails"""
        if not self.qa_results:
            return {}
        
        total_emails = len(self.qa_results)
        quality_levels = [result['overall_quality'] for result in self.qa_results]
        quality_scores = [result['quality_score'] for result in self.qa_results]
        
        from collections import Counter
        quality_distribution = Counter(quality_levels)
        
        return {
            'total_emails_checked': total_emails,
            'average_quality_score': sum(quality_scores) / len(quality_scores),
            'quality_distribution': dict(quality_distribution),
            'emails_needing_attention': sum(1 for score in quality_scores if score < 0.6),
            'common_issues': self._identify_common_issues()
        }
    
    def _identify_common_issues(self) -> List[str]:
        """Identify common issues across all QA results"""
        issue_counts = {}
        
        for result in self.qa_results:
            for validation_type, validation in result['validations'].items():
                if isinstance(validation, dict):
                    for error in validation.get('errors', []):
                        issue_counts[error] = issue_counts.get(error, 0) + 1
                    for warning in validation.get('warnings', []):
                        issue_counts[warning] = issue_counts.get(warning, 0) + 1
                elif isinstance(validation, list):
                    for val in validation:
                        for error in val.get('errors', []):
                            issue_counts[error] = issue_counts.get(error, 0) + 1
        
        # Return top 5 most common issues
        sorted_issues = sorted(issue_counts.items(), key=lambda x: x[1], reverse=True)
        return [issue for issue, count in sorted_issues[:5]]
