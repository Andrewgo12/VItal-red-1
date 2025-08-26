"""
Professional JSON Schema for Email Processing
Implements APA-style naming conventions and comprehensive structure
"""

import json
import os
from datetime import datetime
from typing import Dict, Any, List
import logging

logger = logging.getLogger(__name__)

class ProfessionalEmailSchema:
    """
    Professional email data schema following APA-style naming conventions
    and comprehensive metadata organization
    """
    
    @staticmethod
    def create_comprehensive_email_record(
        unique_identifier: str,
        email_metadata: Dict[str, Any],
        content_data: Dict[str, Any],
        attachment_data: List[Dict[str, Any]],
        extracted_text_data: Dict[str, Any],
        processing_statistics: Dict[str, Any] = None
    ) -> Dict[str, Any]:
        """
        Create a comprehensive, professionally structured email record
        
        Args:
            unique_identifier: Unique email identifier
            email_metadata: Email metadata from extraction
            content_data: Email content (body, HTML, etc.)
            attachment_data: List of attachment information
            extracted_text_data: Extracted text from email and attachments
            processing_statistics: Processing performance data
            
        Returns:
            Dict: Professionally structured email record
        """
        
        # Processing timestamp
        processing_timestamp = datetime.now().isoformat()
        
        # Create the comprehensive record
        email_record = {
            # === DOCUMENT IDENTIFICATION ===
            "document_identification": {
                "unique_identifier": unique_identifier,
                "document_type": "email_communication",
                "schema_version": "2.0.0",
                "processing_timestamp": processing_timestamp,
                "processing_system": "Gmail Data Extraction System v2.0",
                "data_integrity_status": "verified"
            },
            
            # === COMMUNICATION METADATA ===
            "communication_metadata": {
                "message_identification": {
                    "message_id": email_metadata.get('message_id', ''),
                    "thread_id": email_metadata.get('thread_index', ''),
                    "conversation_topic": email_metadata.get('thread_topic', ''),
                    "in_reply_to_message": email_metadata.get('in_reply_to', ''),
                    "reference_messages": email_metadata.get('references', '').split() if email_metadata.get('references') else []
                },
                
                "temporal_information": {
                    "sent_datetime": email_metadata.get('date'),
                    "sent_datetime_string": email_metadata.get('date_string', ''),
                    "received_datetime": email_metadata.get('received_date'),
                    "timezone_information": ProfessionalEmailSchema._extract_timezone_info(email_metadata.get('date_string', '')),
                    "processing_datetime": processing_timestamp
                },
                
                "participant_information": {
                    "sender_details": ProfessionalEmailSchema._format_participant_data(email_metadata.get('from', [])),
                    "primary_recipients": ProfessionalEmailSchema._format_participant_data(email_metadata.get('to', [])),
                    "carbon_copy_recipients": ProfessionalEmailSchema._format_participant_data(email_metadata.get('cc', [])),
                    "blind_carbon_copy_recipients": ProfessionalEmailSchema._format_participant_data(email_metadata.get('bcc', [])),
                    "reply_to_addresses": ProfessionalEmailSchema._format_participant_data(email_metadata.get('reply_to', [])),
                    "total_recipient_count": len(email_metadata.get('to', [])) + len(email_metadata.get('cc', [])) + len(email_metadata.get('bcc', []))
                },
                
                "priority_and_sensitivity": {
                    "priority_level": email_metadata.get('priority', ''),
                    "importance_level": email_metadata.get('importance', ''),
                    "sensitivity_classification": email_metadata.get('sensitivity', ''),
                    "delivery_receipt_requested": bool(email_metadata.get('delivery_receipt', '')),
                    "read_receipt_requested": bool(email_metadata.get('read_receipt', ''))
                }
            },
            
            # === CONTENT ANALYSIS ===
            "content_analysis": {
                "subject_information": {
                    "subject_line": email_metadata.get('subject', ''),
                    "subject_length": len(email_metadata.get('subject', '')),
                    "subject_language": ProfessionalEmailSchema._detect_language(email_metadata.get('subject', '')),
                    "contains_special_characters": ProfessionalEmailSchema._contains_special_chars(email_metadata.get('subject', ''))
                },
                
                "content_structure": {
                    "is_multipart_message": email_metadata.get('is_multipart', False),
                    "content_type": email_metadata.get('content_type', 'text/plain'),
                    "character_encoding": email_metadata.get('charset', 'utf-8'),
                    "message_size_bytes": email_metadata.get('size', 0),
                    "message_size_human_readable": ProfessionalEmailSchema._format_file_size(email_metadata.get('size', 0))
                },
                
                "body_content": {
                    "plain_text_content": content_data.get('text', ''),
                    "html_content": content_data.get('html', ''),
                    "raw_content": content_data.get('raw', ''),
                    "content_preview": email_metadata.get('body_preview', ''),
                    "estimated_reading_time_minutes": max(1, len(content_data.get('text', '').split()) // 200),
                    "word_count": len(content_data.get('text', '').split()),
                    "character_count": len(content_data.get('text', '')),
                    "contains_html_formatting": bool(content_data.get('html', '')),
                    "detected_language": ProfessionalEmailSchema._detect_language(content_data.get('text', ''))
                }
            },
            
            # === ATTACHMENT INFORMATION ===
            "attachment_information": {
                "attachment_summary": {
                    "has_attachments": email_metadata.get('has_attachments', False),
                    "total_attachment_count": email_metadata.get('attachment_count', 0),
                    "total_attachment_size_bytes": sum(att.get('size_bytes', 0) for att in attachment_data),
                    "total_attachment_size_human_readable": ProfessionalEmailSchema._format_file_size(
                        sum(att.get('size_bytes', 0) for att in attachment_data)
                    )
                },
                
                "attachment_details": [
                    ProfessionalEmailSchema._format_attachment_data(attachment) 
                    for attachment in attachment_data
                ],
                
                "attachment_categories": ProfessionalEmailSchema._categorize_attachments(attachment_data),
                
                "security_assessment": {
                    "potentially_dangerous_files": [
                        att.get('original_filename', '') for att in attachment_data 
                        if att.get('security_analysis', {}).get('risk_level') == 'high'
                    ],
                    "overall_risk_level": ProfessionalEmailSchema._calculate_overall_risk(attachment_data),
                    "scan_timestamp": processing_timestamp
                }
            },
            
            # === EXTRACTED TEXT DATA ===
            "extracted_text_data": {
                "email_body_text": {
                    "plain_text_extraction": extracted_text_data.get('email_body', {}).get('text', ''),
                    "html_text_extraction": extracted_text_data.get('email_body', {}).get('html', ''),
                    "extraction_method": "direct_parsing",
                    "extraction_quality": "high"
                },
                
                "attachment_text_extractions": [
                    {
                        "source_filename": att.get('filename', ''),
                        "extracted_text": att.get('text', ''),
                        "extraction_method": ProfessionalEmailSchema._determine_extraction_method(att.get('filename', '')),
                        "extraction_success": bool(att.get('text', '')),
                        "text_length": len(att.get('text', ''))
                    }
                    for att in extracted_text_data.get('attachments', [])
                ],
                
                "combined_text_content": ProfessionalEmailSchema._combine_all_text(content_data, extracted_text_data),
                
                "text_analysis_summary": {
                    "total_extractable_text_length": len(ProfessionalEmailSchema._combine_all_text(content_data, extracted_text_data)),
                    "successful_extractions": len([att for att in extracted_text_data.get('attachments', []) if att.get('text', '')]),
                    "failed_extractions": len([att for att in extracted_text_data.get('attachments', []) if not att.get('text', '')])
                }
            },
            
            # === TECHNICAL METADATA ===
            "technical_metadata": {
                "email_headers": email_metadata.get('headers', {}),
                "routing_information": email_metadata.get('routing_info', {}),
                "security_headers": email_metadata.get('security_info', {}),
                "authentication_results": email_metadata.get('authentication_results', ''),
                "spam_filtering_results": ProfessionalEmailSchema._extract_spam_info(email_metadata.get('headers', {})),
                "delivery_path": email_metadata.get('received_headers', [])
            },
            
            # === PROCESSING INFORMATION ===
            "processing_information": {
                "extraction_statistics": processing_statistics or {},
                "processing_errors": email_metadata.get('processing_errors', []),
                "data_quality_metrics": {
                    "metadata_completeness_percentage": ProfessionalEmailSchema._calculate_completeness(email_metadata),
                    "content_extraction_success": bool(content_data.get('text') or content_data.get('html')),
                    "attachment_processing_success_rate": ProfessionalEmailSchema._calculate_attachment_success_rate(attachment_data),
                    "overall_processing_quality": "high" if len(email_metadata.get('processing_errors', [])) == 0 else "medium"
                },
                "system_information": {
                    "processing_system_version": "2.0.0",
                    "python_version": ProfessionalEmailSchema._get_python_version(),
                    "processing_environment": "production"
                }
            },
            
            # === COMPLIANCE AND AUDIT ===
            "compliance_and_audit": {
                "data_retention_information": {
                    "retention_category": "business_communication",
                    "suggested_retention_period_years": 7,
                    "deletion_eligible_date": ProfessionalEmailSchema._calculate_deletion_date(processing_timestamp, 7)
                },
                "privacy_classification": {
                    "contains_personal_information": ProfessionalEmailSchema._detect_personal_info(content_data.get('text', '')),
                    "privacy_level": "standard",
                    "gdpr_relevant": True
                },
                "audit_trail": {
                    "created_by": "automated_system",
                    "creation_timestamp": processing_timestamp,
                    "last_modified_timestamp": processing_timestamp,
                    "modification_history": []
                }
            }
        }
        
        return email_record

    @staticmethod
    def _format_participant_data(participants: List[Dict[str, str]]) -> List[Dict[str, str]]:
        """Format participant data with professional structure"""
        formatted_participants = []
        for participant in participants:
            formatted_participants.append({
                "display_name": participant.get('name', ''),
                "email_address": participant.get('email', ''),
                "address_type": "primary",
                "validation_status": "valid" if '@' in participant.get('email', '') else "invalid"
            })
        return formatted_participants

    @staticmethod
    def _extract_timezone_info(date_string: str) -> str:
        """Extract timezone information from date string"""
        if not date_string:
            return "unknown"

        # Look for timezone indicators
        if '+' in date_string or '-' in date_string:
            parts = date_string.split()
            for part in parts:
                if '+' in part or '-' in part:
                    return part

        # Look for named timezones
        timezone_names = ['GMT', 'UTC', 'EST', 'PST', 'CST', 'MST', 'EDT', 'PDT', 'CDT', 'MDT']
        for tz in timezone_names:
            if tz in date_string.upper():
                return tz

        return "unknown"

    @staticmethod
    def _detect_language(text: str) -> str:
        """Simple language detection"""
        if not text:
            return "unknown"

        # Simple heuristic - could be enhanced with proper language detection
        english_indicators = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with']
        spanish_indicators = ['el', 'la', 'y', 'o', 'pero', 'en', 'de', 'con', 'para', 'por']

        text_lower = text.lower()
        english_count = sum(1 for word in english_indicators if word in text_lower)
        spanish_count = sum(1 for word in spanish_indicators if word in text_lower)

        if english_count > spanish_count:
            return "english"
        elif spanish_count > 0:
            return "spanish"
        else:
            return "unknown"

    @staticmethod
    def _contains_special_chars(text: str) -> bool:
        """Check if text contains special characters"""
        if not text:
            return False

        special_chars = set('!@#$%^&*()[]{}|\\:";\'<>?,./')
        return any(char in special_chars for char in text)

    @staticmethod
    def _format_file_size(size_bytes: int) -> str:
        """Format file size in human readable format"""
        if size_bytes == 0:
            return "0 B"

        size_names = ["B", "KB", "MB", "GB", "TB"]
        i = 0
        size = float(size_bytes)

        while size >= 1024.0 and i < len(size_names) - 1:
            size /= 1024.0
            i += 1

        return f"{size:.1f} {size_names[i]}"

    @staticmethod
    def _format_attachment_data(attachment: Dict[str, Any]) -> Dict[str, Any]:
        """Format attachment data professionally"""
        return {
            "file_identification": {
                "original_filename": attachment.get('original_filename', ''),
                "stored_filename": attachment.get('saved_filename', ''),
                "file_extension": os.path.splitext(attachment.get('original_filename', ''))[1].lower(),
                "content_type": attachment.get('content_type', 'unknown')
            },
            "file_properties": {
                "file_size_bytes": attachment.get('size_bytes', 0),
                "file_size_human_readable": ProfessionalEmailSchema._format_file_size(attachment.get('size_bytes', 0)),
                "file_category": attachment.get('category', 'unknown'),
                "creation_timestamp": attachment.get('creation_time', ''),
                "modification_timestamp": attachment.get('modification_time', '')
            },
            "storage_information": {
                "storage_path": attachment.get('file_path', ''),
                "storage_success": attachment.get('saved_successfully', False),
                "checksum_md5": attachment.get('md5_hash', ''),
                "backup_status": "included"
            },
            "security_analysis": attachment.get('security_analysis', {}),
            "text_extraction": {
                "extraction_attempted": True,
                "extraction_successful": bool(attachment.get('extracted_text', '')),
                "extracted_text_length": len(attachment.get('extracted_text', '')),
                "extraction_method": ProfessionalEmailSchema._determine_extraction_method(attachment.get('original_filename', ''))
            }
        }

    @staticmethod
    def _categorize_attachments(attachments: List[Dict[str, Any]]) -> Dict[str, Any]:
        """Categorize attachments by type"""
        categories = {
            "documents": [],
            "images": [],
            "archives": [],
            "executables": [],
            "other": []
        }

        for attachment in attachments:
            category = attachment.get('category', 'other')
            filename = attachment.get('original_filename', '')

            if category == 'document':
                categories["documents"].append(filename)
            elif category == 'image':
                categories["images"].append(filename)
            elif category == 'archive':
                categories["archives"].append(filename)
            elif filename.endswith(('.exe', '.bat', '.cmd', '.scr')):
                categories["executables"].append(filename)
            else:
                categories["other"].append(filename)

        return {
            "document_files": categories["documents"],
            "image_files": categories["images"],
            "archive_files": categories["archives"],
            "executable_files": categories["executables"],
            "other_files": categories["other"],
            "category_counts": {
                "documents": len(categories["documents"]),
                "images": len(categories["images"]),
                "archives": len(categories["archives"]),
                "executables": len(categories["executables"]),
                "other": len(categories["other"])
            }
        }

    @staticmethod
    def _calculate_overall_risk(attachments: List[Dict[str, Any]]) -> str:
        """Calculate overall security risk level"""
        if not attachments:
            return "none"

        risk_levels = [att.get('security_analysis', {}).get('risk_level', 'low') for att in attachments]

        if 'high' in risk_levels:
            return "high"
        elif 'medium' in risk_levels:
            return "medium"
        else:
            return "low"

    @staticmethod
    def _determine_extraction_method(filename: str) -> str:
        """Determine text extraction method based on file type"""
        if not filename:
            return "unknown"

        ext = os.path.splitext(filename)[1].lower()

        if ext in ['.txt', '.csv']:
            return "direct_text_reading"
        elif ext in ['.pdf']:
            return "pdf_text_extraction"
        elif ext in ['.doc', '.docx']:
            return "office_document_parsing"
        elif ext in ['.jpg', '.jpeg', '.png', '.tiff']:
            return "optical_character_recognition"
        else:
            return "generic_text_extraction"

    @staticmethod
    def _combine_all_text(content_data: Dict[str, Any], extracted_text_data: Dict[str, Any]) -> str:
        """Combine all text content from email and attachments"""
        combined_text = []

        # Add email body text
        email_text = content_data.get('text', '')
        if email_text:
            combined_text.append("=== EMAIL BODY ===")
            combined_text.append(email_text)

        # Add attachment text
        for attachment in extracted_text_data.get('attachments', []):
            att_text = attachment.get('text', '')
            if att_text:
                combined_text.append(f"=== ATTACHMENT: {attachment.get('filename', 'Unknown')} ===")
                combined_text.append(att_text)

        return "\n\n".join(combined_text)

    @staticmethod
    def _extract_spam_info(headers: Dict[str, str]) -> Dict[str, Any]:
        """Extract spam filtering information from headers"""
        spam_info = {
            "spam_score": "unknown",
            "spam_status": "unknown",
            "spam_filters": [],
            "quarantine_status": "not_quarantined"
        }

        # Look for common spam headers
        spam_headers = ['X-Spam-Score', 'X-Spam-Status', 'X-Spam-Level', 'X-Spam-Flag']
        for header_name, header_value in headers.items():
            if any(spam_header.lower() in header_name.lower() for spam_header in spam_headers):
                spam_info["spam_filters"].append({
                    "filter_name": header_name,
                    "filter_result": str(header_value)
                })

        return spam_info

    @staticmethod
    def _calculate_completeness(metadata: Dict[str, Any]) -> float:
        """Calculate metadata completeness percentage"""
        required_fields = ['subject', 'from', 'to', 'date', 'message_id']
        optional_fields = ['cc', 'bcc', 'reply_to', 'thread_topic', 'priority']

        required_score = sum(1 for field in required_fields if metadata.get(field))
        optional_score = sum(0.5 for field in optional_fields if metadata.get(field))

        total_possible = len(required_fields) + (len(optional_fields) * 0.5)
        actual_score = required_score + optional_score

        return (actual_score / total_possible) * 100

    @staticmethod
    def _calculate_attachment_success_rate(attachments: List[Dict[str, Any]]) -> float:
        """Calculate attachment processing success rate"""
        if not attachments:
            return 100.0

        successful = sum(1 for att in attachments if att.get('saved_successfully', False))
        return (successful / len(attachments)) * 100

    @staticmethod
    def _get_python_version() -> str:
        """Get Python version information"""
        import sys
        return f"{sys.version_info.major}.{sys.version_info.minor}.{sys.version_info.micro}"

    @staticmethod
    def _calculate_deletion_date(creation_date: str, retention_years: int) -> str:
        """Calculate suggested deletion date"""
        try:
            from datetime import datetime, timedelta
            creation_dt = datetime.fromisoformat(creation_date.replace('Z', '+00:00'))
            deletion_dt = creation_dt + timedelta(days=retention_years * 365)
            return deletion_dt.isoformat()
        except:
            return "unknown"

    @staticmethod
    def _detect_personal_info(text: str) -> bool:
        """Simple detection of potential personal information"""
        if not text:
            return False

        # Simple patterns for personal info detection
        import re

        # Email patterns
        email_pattern = r'\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b'

        # Phone patterns (simple)
        phone_pattern = r'\b\d{3}[-.]?\d{3}[-.]?\d{4}\b'

        # SSN pattern (simple)
        ssn_pattern = r'\b\d{3}-\d{2}-\d{4}\b'

        patterns = [email_pattern, phone_pattern, ssn_pattern]

        for pattern in patterns:
            if re.search(pattern, text):
                return True

        return False

    @staticmethod
    def save_professional_email_record(
        email_record: Dict[str, Any],
        base_path: str,
        unique_identifier: str
    ) -> str:
        """
        Save the professional email record to a well-organized file structure

        Args:
            email_record: The comprehensive email record
            base_path: Base directory path
            unique_identifier: Unique email identifier

        Returns:
            str: Path to the saved file
        """
        try:
            # Create professional directory structure
            email_dir = os.path.join(base_path, "Professional_Email_Records", unique_identifier)
            os.makedirs(email_dir, exist_ok=True)

            # Main comprehensive record
            main_file_path = os.path.join(email_dir, "comprehensive_email_record.json")

            with open(main_file_path, 'w', encoding='utf-8') as f:
                json.dump(email_record, f, indent=2, ensure_ascii=False)

            # Create separate files for major sections for easier access
            sections = {
                "communication_metadata.json": email_record.get("communication_metadata", {}),
                "content_analysis.json": email_record.get("content_analysis", {}),
                "attachment_information.json": email_record.get("attachment_information", {}),
                "extracted_text_data.json": email_record.get("extracted_text_data", {}),
                "technical_metadata.json": email_record.get("technical_metadata", {}),
                "processing_information.json": email_record.get("processing_information", {})
            }

            for filename, data in sections.items():
                section_path = os.path.join(email_dir, filename)
                with open(section_path, 'w', encoding='utf-8') as f:
                    json.dump(data, f, indent=2, ensure_ascii=False)

            # Create a human-readable summary
            summary_path = os.path.join(email_dir, "email_summary.txt")
            ProfessionalEmailSchema._create_human_readable_summary(email_record, summary_path)

            logger.info(f"Professional email record saved: {main_file_path}")
            return main_file_path

        except Exception as e:
            logger.error(f"Error saving professional email record: {str(e)}")
            raise

    @staticmethod
    def _create_human_readable_summary(email_record: Dict[str, Any], summary_path: str):
        """Create a human-readable summary of the email"""
        try:
            comm_meta = email_record.get("communication_metadata", {})
            content_analysis = email_record.get("content_analysis", {})
            attachment_info = email_record.get("attachment_information", {})

            with open(summary_path, 'w', encoding='utf-8') as f:
                f.write("EMAIL COMMUNICATION SUMMARY\n")
                f.write("=" * 50 + "\n\n")

                # Basic information
                f.write("BASIC INFORMATION:\n")
                f.write("-" * 20 + "\n")
                f.write(f"Subject: {content_analysis.get('subject_information', {}).get('subject_line', 'N/A')}\n")

                sender_info = comm_meta.get('participant_information', {}).get('sender_details', [])
                if sender_info:
                    sender = sender_info[0]
                    f.write(f"From: {sender.get('display_name', '')} <{sender.get('email_address', '')}>\n")

                recipients = comm_meta.get('participant_information', {}).get('primary_recipients', [])
                if recipients:
                    f.write(f"To: {', '.join([r.get('email_address', '') for r in recipients])}\n")

                f.write(f"Date: {comm_meta.get('temporal_information', {}).get('sent_datetime', 'N/A')}\n")
                f.write(f"Message Size: {content_analysis.get('content_structure', {}).get('message_size_human_readable', 'N/A')}\n\n")

                # Attachments
                if attachment_info.get('attachment_summary', {}).get('has_attachments', False):
                    f.write("ATTACHMENTS:\n")
                    f.write("-" * 20 + "\n")
                    f.write(f"Total Attachments: {attachment_info.get('attachment_summary', {}).get('total_attachment_count', 0)}\n")
                    f.write(f"Total Size: {attachment_info.get('attachment_summary', {}).get('total_attachment_size_human_readable', 'N/A')}\n")

                    for attachment in attachment_info.get('attachment_details', []):
                        file_info = attachment.get('file_identification', {})
                        file_props = attachment.get('file_properties', {})
                        f.write(f"  - {file_info.get('original_filename', 'Unknown')} ({file_props.get('file_size_human_readable', 'Unknown size')})\n")
                    f.write("\n")

                # Content preview
                content_preview = content_analysis.get('body_content', {}).get('content_preview', '')
                if content_preview:
                    f.write("CONTENT PREVIEW:\n")
                    f.write("-" * 20 + "\n")
                    f.write(content_preview[:500])
                    if len(content_preview) > 500:
                        f.write("...\n")
                    f.write("\n\n")

                # Processing information
                processing_info = email_record.get("processing_information", {})
                f.write("PROCESSING INFORMATION:\n")
                f.write("-" * 20 + "\n")
                f.write(f"Processing Quality: {processing_info.get('data_quality_metrics', {}).get('overall_processing_quality', 'Unknown')}\n")
                f.write(f"Metadata Completeness: {processing_info.get('data_quality_metrics', {}).get('metadata_completeness_percentage', 0):.1f}%\n")

                errors = processing_info.get('processing_errors', [])
                if errors:
                    f.write(f"Processing Errors: {len(errors)}\n")
                else:
                    f.write("Processing Errors: None\n")

        except Exception as e:
            logger.error(f"Error creating human-readable summary: {str(e)}")
