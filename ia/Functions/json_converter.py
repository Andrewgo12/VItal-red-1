"""
JSON Conversion System
Converts all extracted content to structured JSON format
"""

import json
import os
from typing import Dict, List, Any, Optional
import datetime
import logging
from professional_json_schema import ProfessionalEmailSchema

logger = logging.getLogger(__name__)

class JSONConverter:
    """
    Converts extracted email data to structured JSON format
    """
    
    def __init__(self, base_path: str):
        """
        Initialize JSON converter
        
        Args:
            base_path: Base path for the ia folder
        """
        self.base_path = base_path
        self.json_path = os.path.join(base_path, "Json")
        os.makedirs(self.json_path, exist_ok=True)
    
    def create_email_schema(self, unique_id: str, metadata: Dict[str, Any], 
                           body_content: Dict[str, str], attachments: List[Dict[str, Any]], 
                           extracted_text: Dict[str, Any]) -> Dict[str, Any]:
        """
        Create comprehensive email JSON schema
        
        Args:
            unique_id: Unique email identifier
            metadata: Email metadata
            body_content: Email body content
            attachments: List of attachment information
            extracted_text: Extracted text content
            
        Returns:
            Dict: Complete email data structure
        """
        email_data = {
            "email_info": {
                "unique_id": unique_id,
                "processing_timestamp": datetime.datetime.now().isoformat(),
                "schema_version": "1.0"
            },
            "metadata": metadata,
            "content": {
                "body": body_content,
                "extracted_text": extracted_text
            },
            "attachments": {
                "count": len(attachments),
                "total_size_bytes": sum(att.get('size_bytes', 0) for att in attachments),
                "files": attachments
            },
            "statistics": self.calculate_statistics(metadata, body_content, attachments),
            "processing_info": {
                "extraction_successful": True,
                "errors": [],
                "warnings": []
            }
        }
        
        return email_data
    
    def calculate_statistics(self, metadata: Dict[str, Any], body_content: Dict[str, str], 
                           attachments: List[Dict[str, Any]]) -> Dict[str, Any]:
        """
        Calculate statistics about the email
        
        Args:
            metadata: Email metadata
            body_content: Email body content
            attachments: List of attachments
            
        Returns:
            Dict: Statistics dictionary
        """
        stats = {
            "text_length": {
                "body_text": len(body_content.get('text', '')),
                "body_html": len(body_content.get('html', '')),
                "total_extracted_text": 0
            },
            "attachments": {
                "total_count": len(attachments),
                "by_category": {},
                "by_type": {},
                "total_size_bytes": 0,
                "largest_file": None,
                "smallest_file": None
            },
            "recipients": {
                "to_count": len(metadata.get('to', [])),
                "cc_count": len(metadata.get('cc', [])),
                "bcc_count": len(metadata.get('bcc', [])),
                "total_recipients": len(metadata.get('to', [])) + len(metadata.get('cc', [])) + len(metadata.get('bcc', []))
            },
            "headers": {
                "total_headers": len(metadata.get('headers', {})),
                "x_headers_count": len(metadata.get('x_headers', {}))
            }
        }
        
        # Calculate attachment statistics
        if attachments:
            categories = {}
            types = {}
            sizes = []
            
            for att in attachments:
                # Category stats
                category = att.get('category', 'unknown')
                categories[category] = categories.get(category, 0) + 1
                
                # Type stats
                content_type = att.get('content_type', 'unknown')
                types[content_type] = types.get(content_type, 0) + 1
                
                # Size stats
                size = att.get('size_bytes', 0)
                sizes.append(size)
                stats['attachments']['total_size_bytes'] += size
                
                # Text length from attachments
                if att.get('extracted_text'):
                    stats['text_length']['total_extracted_text'] += len(att['extracted_text'])
            
            stats['attachments']['by_category'] = categories
            stats['attachments']['by_type'] = types
            
            if sizes:
                max_size = max(sizes)
                min_size = min(sizes)
                
                # Find files with max/min sizes
                for att in attachments:
                    if att.get('size_bytes') == max_size:
                        stats['attachments']['largest_file'] = {
                            'filename': att.get('original_filename'),
                            'size_bytes': max_size
                        }
                    if att.get('size_bytes') == min_size:
                        stats['attachments']['smallest_file'] = {
                            'filename': att.get('original_filename'),
                            'size_bytes': min_size
                        }
        
        return stats
    
    def save_email_json(self, unique_id: str, email_data: Dict[str, Any]) -> str:
        """
        Save email data as JSON file
        
        Args:
            unique_id: Unique email identifier
            email_data: Complete email data structure
            
        Returns:
            str: Path to saved JSON file
        """
        try:
            # Create email-specific folder
            email_json_folder = os.path.join(self.json_path, unique_id)
            os.makedirs(email_json_folder, exist_ok=True)
            
            # Main email data file
            main_json_path = os.path.join(email_json_folder, "email_data.json")
            
            with open(main_json_path, 'w', encoding='utf-8') as f:
                json.dump(email_data, f, indent=2, ensure_ascii=False, default=str)
            
            # Create separate files for different data types
            self.save_metadata_json(email_json_folder, email_data['metadata'])
            self.save_attachments_json(email_json_folder, email_data['attachments'])
            self.save_content_json(email_json_folder, email_data['content'])
            self.save_statistics_json(email_json_folder, email_data['statistics'])
            
            logger.info(f"Saved JSON data for email {unique_id}")
            return main_json_path
            
        except Exception as e:
            logger.error(f"Error saving JSON for email {unique_id}: {str(e)}")
            return ""
    
    def save_metadata_json(self, folder_path: str, metadata: Dict[str, Any]):
        """Save metadata as separate JSON file"""
        try:
            metadata_path = os.path.join(folder_path, "metadata.json")
            with open(metadata_path, 'w', encoding='utf-8') as f:
                json.dump(metadata, f, indent=2, ensure_ascii=False, default=str)
        except Exception as e:
            logger.error(f"Error saving metadata JSON: {str(e)}")
    
    def save_attachments_json(self, folder_path: str, attachments: Dict[str, Any]):
        """Save attachments data as separate JSON file"""
        try:
            attachments_path = os.path.join(folder_path, "attachments.json")
            with open(attachments_path, 'w', encoding='utf-8') as f:
                json.dump(attachments, f, indent=2, ensure_ascii=False, default=str)
        except Exception as e:
            logger.error(f"Error saving attachments JSON: {str(e)}")
    
    def save_content_json(self, folder_path: str, content: Dict[str, Any]):
        """Save content data as separate JSON file"""
        try:
            content_path = os.path.join(folder_path, "content.json")
            with open(content_path, 'w', encoding='utf-8') as f:
                json.dump(content, f, indent=2, ensure_ascii=False, default=str)
        except Exception as e:
            logger.error(f"Error saving content JSON: {str(e)}")
    
    def save_statistics_json(self, folder_path: str, statistics: Dict[str, Any]):
        """Save statistics as separate JSON file"""
        try:
            stats_path = os.path.join(folder_path, "statistics.json")
            with open(stats_path, 'w', encoding='utf-8') as f:
                json.dump(statistics, f, indent=2, ensure_ascii=False, default=str)
        except Exception as e:
            logger.error(f"Error saving statistics JSON: {str(e)}")
    
    def create_summary_json(self, processed_emails: List[Dict[str, Any]]) -> str:
        """
        Create summary JSON for all processed emails
        
        Args:
            processed_emails: List of processed email summaries
            
        Returns:
            str: Path to summary JSON file
        """
        try:
            summary_data = {
                "processing_summary": {
                    "timestamp": datetime.datetime.now().isoformat(),
                    "total_emails_processed": len(processed_emails),
                    "successful_extractions": sum(1 for email in processed_emails if email.get('success', False)),
                    "failed_extractions": sum(1 for email in processed_emails if not email.get('success', False))
                },
                "statistics": {
                    "total_attachments": sum(email.get('attachment_count', 0) for email in processed_emails),
                    "total_size_bytes": sum(email.get('total_size_bytes', 0) for email in processed_emails),
                    "date_range": self.get_date_range(processed_emails),
                    "sender_distribution": self.get_sender_distribution(processed_emails),
                    "attachment_types": self.get_attachment_type_distribution(processed_emails)
                },
                "emails": processed_emails
            }
            
            summary_path = os.path.join(self.json_path, "processing_summary.json")
            
            with open(summary_path, 'w', encoding='utf-8') as f:
                json.dump(summary_data, f, indent=2, ensure_ascii=False, default=str)
            
            logger.info(f"Created processing summary: {summary_path}")
            return summary_path
            
        except Exception as e:
            logger.error(f"Error creating summary JSON: {str(e)}")
            return ""
    
    def get_date_range(self, processed_emails: List[Dict[str, Any]]) -> Dict[str, str]:
        """Get date range of processed emails"""
        dates = []
        for email in processed_emails:
            if email.get('date'):
                try:
                    if isinstance(email['date'], str):
                        date_obj = datetime.datetime.fromisoformat(email['date'].replace('Z', '+00:00'))
                        dates.append(date_obj)
                except:
                    continue
        
        if dates:
            return {
                "earliest": min(dates).isoformat(),
                "latest": max(dates).isoformat(),
                "span_days": (max(dates) - min(dates)).days
            }
        return {"earliest": None, "latest": None, "span_days": 0}
    
    def get_sender_distribution(self, processed_emails: List[Dict[str, Any]]) -> Dict[str, int]:
        """Get distribution of email senders"""
        senders = {}
        for email in processed_emails:
            sender = email.get('sender', 'Unknown')
            senders[sender] = senders.get(sender, 0) + 1
        return dict(sorted(senders.items(), key=lambda x: x[1], reverse=True)[:10])  # Top 10
    
    def get_attachment_type_distribution(self, processed_emails: List[Dict[str, Any]]) -> Dict[str, int]:
        """Get distribution of attachment types"""
        types = {}
        for email in processed_emails:
            for att_type in email.get('attachment_types', []):
                types[att_type] = types.get(att_type, 0) + 1
        return dict(sorted(types.items(), key=lambda x: x[1], reverse=True))
    
    def validate_json_schema(self, email_data: Dict[str, Any]) -> List[str]:
        """
        Validate JSON schema for completeness
        
        Args:
            email_data: Email data dictionary
            
        Returns:
            List[str]: List of validation errors
        """
        errors = []
        
        # Required top-level keys
        required_keys = ['email_info', 'metadata', 'content', 'attachments', 'statistics', 'processing_info']
        for key in required_keys:
            if key not in email_data:
                errors.append(f"Missing required key: {key}")
        
        # Validate email_info
        if 'email_info' in email_data:
            email_info = email_data['email_info']
            if 'unique_id' not in email_info:
                errors.append("Missing unique_id in email_info")
            if 'processing_timestamp' not in email_info:
                errors.append("Missing processing_timestamp in email_info")
        
        # Validate metadata
        if 'metadata' in email_data:
            metadata = email_data['metadata']
            if 'subject' not in metadata:
                errors.append("Missing subject in metadata")
            if 'from' not in metadata:
                errors.append("Missing from in metadata")
        
        return errors

    def create_enhanced_email_schema(self, unique_id: str, metadata: Dict[str, Any],
                                   body_content: Dict[str, str], attachments: List[Dict[str, Any]],
                                   extracted_text: Dict[str, Any], processing_stats: Dict[str, Any] = None) -> Dict[str, Any]:
        """
        Create enhanced email JSON schema with additional analysis

        Args:
            unique_id: Unique email identifier
            metadata: Email metadata
            body_content: Email body content
            attachments: List of attachment information
            extracted_text: Extracted text content
            processing_stats: Processing statistics

        Returns:
            Dict: Enhanced email data structure
        """
        # Base schema
        email_data = self.create_email_schema(unique_id, metadata, body_content, attachments, extracted_text)

        # Enhanced analysis
        email_data['analysis'] = {
            'content_analysis': self._analyze_email_content(body_content, extracted_text),
            'attachment_analysis': self._analyze_attachments(attachments),
            'security_analysis': self._analyze_email_security(metadata, attachments),
            'communication_analysis': self._analyze_communication_patterns(metadata),
            'technical_analysis': self._analyze_technical_headers(metadata)
        }

        # Processing information
        if processing_stats:
            email_data['processing_info'].update(processing_stats)

        # Data quality metrics
        email_data['quality_metrics'] = self._calculate_quality_metrics(email_data)

        return email_data

    def _analyze_email_content(self, body_content: Dict[str, str], extracted_text: Dict[str, Any]) -> Dict[str, Any]:
        """Analyze email content for insights"""
        analysis = {
            'content_type': 'unknown',
            'language_detected': 'unknown',
            'sentiment': 'neutral',
            'urgency_indicators': [],
            'topics': [],
            'contains_links': False,
            'contains_images': False,
            'text_quality': 'good'
        }

        text = body_content.get('text', '')
        html = body_content.get('html', '')

        if text:
            # Language detection
            try:
                from langdetect import detect
                analysis['language_detected'] = detect(text)
            except:
                pass

            # Urgency indicators
            urgency_keywords = ['urgent', 'asap', 'immediately', 'emergency', 'critical', 'deadline']
            analysis['urgency_indicators'] = [word for word in urgency_keywords if word.lower() in text.lower()]

            # Check for links
            import re
            analysis['contains_links'] = bool(re.search(r'http[s]?://(?:[a-zA-Z]|[0-9]|[$-_@.&+]|[!*\\(\\),]|(?:%[0-9a-fA-F][0-9a-fA-F]))+', text))

        if html:
            analysis['contains_images'] = '<img' in html.lower()
            analysis['content_type'] = 'html'
        elif text:
            analysis['content_type'] = 'plain_text'

        return analysis

    def _analyze_attachments(self, attachments: List[Dict[str, Any]]) -> Dict[str, Any]:
        """Analyze attachments for patterns and risks"""
        analysis = {
            'total_count': len(attachments),
            'total_size_mb': 0,
            'risk_assessment': 'low',
            'file_types': {},
            'suspicious_files': [],
            'encrypted_files': [],
            'large_files': []
        }

        if not attachments:
            return analysis

        total_size = 0
        high_risk_count = 0

        for att in attachments:
            size_bytes = att.get('size_bytes', 0)
            total_size += size_bytes

            # File type distribution
            content_type = att.get('content_type', 'unknown')
            analysis['file_types'][content_type] = analysis['file_types'].get(content_type, 0) + 1

            # Security analysis
            security = att.get('security_analysis', {})
            if security.get('risk_level') == 'high':
                high_risk_count += 1
                analysis['suspicious_files'].append(att.get('original_filename', 'unknown'))

            if security.get('encrypted'):
                analysis['encrypted_files'].append(att.get('original_filename', 'unknown'))

            # Large files (>10MB)
            if size_bytes > 10 * 1024 * 1024:
                analysis['large_files'].append({
                    'filename': att.get('original_filename', 'unknown'),
                    'size_mb': round(size_bytes / (1024 * 1024), 2)
                })

        analysis['total_size_mb'] = round(total_size / (1024 * 1024), 2)

        # Overall risk assessment
        if high_risk_count > 0:
            analysis['risk_assessment'] = 'high'
        elif len(analysis['encrypted_files']) > 0:
            analysis['risk_assessment'] = 'medium'

        return analysis

    def _analyze_email_security(self, metadata: Dict[str, Any], attachments: List[Dict[str, Any]]) -> Dict[str, Any]:
        """Analyze email security aspects"""
        analysis = {
            'authentication_status': 'unknown',
            'spf_status': 'unknown',
            'dkim_status': 'unknown',
            'dmarc_status': 'unknown',
            'overall_trust_score': 0.5,
            'security_warnings': []
        }

        security_info = metadata.get('security_info', {})

        # Parse authentication results
        auth_results = security_info.get('authentication_results', '').lower()

        if 'spf=pass' in auth_results:
            analysis['spf_status'] = 'pass'
        elif 'spf=fail' in auth_results:
            analysis['spf_status'] = 'fail'
            analysis['security_warnings'].append('SPF validation failed')

        if 'dkim=pass' in auth_results:
            analysis['dkim_status'] = 'pass'
        elif 'dkim=fail' in auth_results:
            analysis['dkim_status'] = 'fail'
            analysis['security_warnings'].append('DKIM validation failed')

        if 'dmarc=pass' in auth_results:
            analysis['dmarc_status'] = 'pass'
        elif 'dmarc=fail' in auth_results:
            analysis['dmarc_status'] = 'fail'
            analysis['security_warnings'].append('DMARC validation failed')

        # Calculate trust score
        trust_score = 0.5  # Base score

        if analysis['spf_status'] == 'pass':
            trust_score += 0.2
        elif analysis['spf_status'] == 'fail':
            trust_score -= 0.3

        if analysis['dkim_status'] == 'pass':
            trust_score += 0.2
        elif analysis['dkim_status'] == 'fail':
            trust_score -= 0.3

        if analysis['dmarc_status'] == 'pass':
            trust_score += 0.1
        elif analysis['dmarc_status'] == 'fail':
            trust_score -= 0.2

        # Check for suspicious attachments
        for att in attachments:
            security = att.get('security_analysis', {})
            if security.get('risk_level') == 'high':
                trust_score -= 0.2
                analysis['security_warnings'].append(f"High-risk attachment: {att.get('original_filename', 'unknown')}")

        analysis['overall_trust_score'] = max(0.0, min(1.0, trust_score))

        return analysis

    def _analyze_communication_patterns(self, metadata: Dict[str, Any]) -> Dict[str, Any]:
        """Analyze communication patterns"""
        analysis = {
            'communication_type': 'unknown',
            'recipient_count': 0,
            'is_broadcast': False,
            'is_personal': False,
            'is_automated': False,
            'thread_participation': False
        }

        # Count recipients
        to_count = len(metadata.get('to', []))
        cc_count = len(metadata.get('cc', []))
        bcc_count = len(metadata.get('bcc', []))
        total_recipients = to_count + cc_count + bcc_count

        analysis['recipient_count'] = total_recipients

        # Determine communication type
        if total_recipients > 10:
            analysis['communication_type'] = 'broadcast'
            analysis['is_broadcast'] = True
        elif total_recipients == 1:
            analysis['communication_type'] = 'personal'
            analysis['is_personal'] = True
        else:
            analysis['communication_type'] = 'group'

        # Check for automated emails
        subject = metadata.get('subject', '').lower()
        automated_indicators = ['no-reply', 'noreply', 'automated', 'notification', 'alert']
        analysis['is_automated'] = any(indicator in subject for indicator in automated_indicators)

        # Thread participation
        thread_info = metadata.get('thread_info', {})
        analysis['thread_participation'] = thread_info.get('is_reply', False)

        return analysis

    def _analyze_technical_headers(self, metadata: Dict[str, Any]) -> Dict[str, Any]:
        """Analyze technical email headers"""
        analysis = {
            'routing_hops': 0,
            'delivery_delay_estimated': False,
            'client_info': {},
            'server_info': {},
            'encryption_used': False
        }

        routing_info = metadata.get('routing_info', {})
        analysis['routing_hops'] = routing_info.get('total_hops', 0)

        # Analyze headers for client/server information
        headers = metadata.get('headers', {})

        # Look for client information
        user_agent = headers.get('User-Agent', '') or headers.get('X-Mailer', '')
        if user_agent:
            analysis['client_info']['user_agent'] = user_agent

        # Check for encryption indicators
        received_headers = routing_info.get('received_headers', [])
        for received in received_headers:
            if 'tls' in received.lower() or 'ssl' in received.lower():
                analysis['encryption_used'] = True
                break

        return analysis

    def _calculate_quality_metrics(self, email_data: Dict[str, Any]) -> Dict[str, Any]:
        """Calculate data quality metrics"""
        metrics = {
            'completeness_score': 0.0,
            'extraction_success_rate': 0.0,
            'data_integrity_score': 0.0,
            'overall_quality_score': 0.0
        }

        # Completeness score
        required_fields = ['subject', 'from', 'date', 'content']
        present_fields = 0

        metadata = email_data.get('metadata', {})
        for field in required_fields:
            if metadata.get(field):
                present_fields += 1

        metrics['completeness_score'] = present_fields / len(required_fields)

        # Extraction success rate
        attachments = email_data.get('attachments', {}).get('files', [])
        if attachments:
            successful_extractions = sum(1 for att in attachments if att.get('saved_successfully', False))
            metrics['extraction_success_rate'] = successful_extractions / len(attachments)
        else:
            metrics['extraction_success_rate'] = 1.0  # No attachments to fail

        # Data integrity score (based on validation errors)
        processing_info = email_data.get('processing_info', {})
        error_count = len(processing_info.get('errors', []))
        warning_count = len(processing_info.get('warnings', []))

        # Penalize errors more than warnings
        integrity_penalty = (error_count * 0.2) + (warning_count * 0.1)
        metrics['data_integrity_score'] = max(0.0, 1.0 - integrity_penalty)

        # Overall quality score (weighted average)
        metrics['overall_quality_score'] = (
            metrics['completeness_score'] * 0.4 +
            metrics['extraction_success_rate'] * 0.3 +
            metrics['data_integrity_score'] * 0.3
        )

        return metrics

    def create_professional_email_record(
        self,
        unique_id: str,
        metadata: Dict[str, Any],
        body_content: Dict[str, Any],
        attachments: List[Dict[str, Any]],
        extracted_text: Dict[str, Any],
        processing_stats: Dict[str, Any] = None
    ) -> Dict[str, Any]:
        """
        Create a professional, comprehensive email record using the new schema

        Args:
            unique_id: Unique email identifier
            metadata: Email metadata
            body_content: Email body content
            attachments: List of attachment data
            extracted_text: Extracted text data
            processing_stats: Processing statistics

        Returns:
            Dict: Professional email record
        """
        try:
            # Create the professional record using the new schema
            professional_record = ProfessionalEmailSchema.create_comprehensive_email_record(
                unique_identifier=unique_id,
                email_metadata=metadata,
                content_data=body_content,
                attachment_data=attachments,
                extracted_text_data=extracted_text,
                processing_statistics=processing_stats or {}
            )

            return professional_record

        except Exception as e:
            logger.error(f"Error creating professional email record: {str(e)}")
            # Fallback to basic schema if professional schema fails
            return self.create_enhanced_email_schema(
                unique_id, metadata, body_content, attachments, extracted_text, processing_stats
            )

    def save_professional_email_record(
        self,
        unique_id: str,
        professional_record: Dict[str, Any]
    ) -> str:
        """
        Save professional email record with organized file structure

        Args:
            unique_id: Unique email identifier
            professional_record: Professional email record

        Returns:
            str: Path to main record file
        """
        try:
            return ProfessionalEmailSchema.save_professional_email_record(
                professional_record, self.base_path, unique_id
            )
        except Exception as e:
            logger.error(f"Error saving professional email record: {str(e)}")
            # Fallback to regular JSON save
            return self.save_email_json(unique_id, professional_record)
