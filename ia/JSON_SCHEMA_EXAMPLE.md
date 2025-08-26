# Professional Email JSON Schema - Example and Documentation

## 📋 Overview

This document explains the professional JSON schema used by the Gmail Data Extraction System v2.0. The schema follows APA-style naming conventions and provides comprehensive, self-documenting email records.

## 🏗️ Schema Structure

The professional email record is organized into seven main sections:

1. **Document Identification** - Basic record information
2. **Communication Metadata** - Email communication details
3. **Content Analysis** - Email content and structure analysis
4. **Attachment Information** - File attachments and security
5. **Extracted Text Data** - All text content from email and attachments
6. **Technical Metadata** - Email headers and routing information
7. **Processing Information** - System processing details and quality metrics
8. **Compliance and Audit** - Data retention and privacy information

## 📄 Complete Example

```json
{
  "document_identification": {
    "unique_identifier": "email_001_1642781234567",
    "document_type": "email_communication",
    "schema_version": "2.0.0",
    "processing_timestamp": "2024-01-21T15:30:45.123Z",
    "processing_system": "Gmail Data Extraction System v2.0",
    "data_integrity_status": "verified"
  },
  
  "communication_metadata": {
    "message_identification": {
      "message_id": "<CABc123def456@mail.gmail.com>",
      "thread_id": "thread_789xyz",
      "conversation_topic": "Project Meeting Notes",
      "in_reply_to_message": "<CABc789xyz123@mail.gmail.com>",
      "reference_messages": [
        "<CABc456abc789@mail.gmail.com>",
        "<CABc789xyz123@mail.gmail.com>"
      ]
    },
    
    "temporal_information": {
      "sent_datetime": "2024-01-21T14:25:30.000Z",
      "sent_datetime_string": "Sun, 21 Jan 2024 14:25:30 +0000",
      "received_datetime": "2024-01-21T14:25:32.456Z",
      "timezone_information": "+0000",
      "processing_datetime": "2024-01-21T15:30:45.123Z"
    },
    
    "participant_information": {
      "sender_details": [
        {
          "display_name": "John Smith",
          "email_address": "john.smith@company.com",
          "address_type": "primary",
          "validation_status": "valid"
        }
      ],
      "primary_recipients": [
        {
          "display_name": "Jane Doe",
          "email_address": "jane.doe@company.com",
          "address_type": "primary",
          "validation_status": "valid"
        }
      ],
      "carbon_copy_recipients": [
        {
          "display_name": "Bob Wilson",
          "email_address": "bob.wilson@company.com",
          "address_type": "primary",
          "validation_status": "valid"
        }
      ],
      "blind_carbon_copy_recipients": [],
      "reply_to_addresses": [],
      "total_recipient_count": 2
    },
    
    "priority_and_sensitivity": {
      "priority_level": "normal",
      "importance_level": "high",
      "sensitivity_classification": "normal",
      "delivery_receipt_requested": false,
      "read_receipt_requested": false
    }
  },
  
  "content_analysis": {
    "subject_information": {
      "subject_line": "Project Meeting Notes - Action Items",
      "subject_length": 35,
      "subject_language": "english",
      "contains_special_characters": true
    },
    
    "content_structure": {
      "is_multipart_message": true,
      "content_type": "multipart/mixed",
      "character_encoding": "utf-8",
      "message_size_bytes": 15420,
      "message_size_human_readable": "15.1 KB"
    },
    
    "body_content": {
      "plain_text_content": "Hi Jane,\n\nPlease find attached the meeting notes from today's project discussion. The key action items are:\n\n1. Review budget proposal by Friday\n2. Schedule follow-up meeting with stakeholders\n3. Prepare technical specifications\n\nLet me know if you have any questions.\n\nBest regards,\nJohn",
      "html_content": "<html><body><p>Hi Jane,</p><p>Please find attached the meeting notes from today's project discussion. The key action items are:</p><ol><li>Review budget proposal by Friday</li><li>Schedule follow-up meeting with stakeholders</li><li>Prepare technical specifications</li></ol><p>Let me know if you have any questions.</p><p>Best regards,<br>John</p></body></html>",
      "raw_content": "",
      "content_preview": "Hi Jane, Please find attached the meeting notes from today's project discussion. The key action items are: 1. Review budget proposal by Friday 2. Schedule follow-up meeting...",
      "estimated_reading_time_minutes": 1,
      "word_count": 52,
      "character_count": 312,
      "contains_html_formatting": true,
      "detected_language": "english"
    }
  },
  
  "attachment_information": {
    "attachment_summary": {
      "has_attachments": true,
      "total_attachment_count": 2,
      "total_attachment_size_bytes": 1048576,
      "total_attachment_size_human_readable": "1.0 MB"
    },
    
    "attachment_details": [
      {
        "file_identification": {
          "original_filename": "Meeting_Notes_2024-01-21.pdf",
          "stored_filename": "Meeting_Notes_2024-01-21.pdf",
          "file_extension": ".pdf",
          "content_type": "application/pdf"
        },
        "file_properties": {
          "file_size_bytes": 524288,
          "file_size_human_readable": "512.0 KB",
          "file_category": "document",
          "creation_timestamp": "2024-01-21T14:20:00.000Z",
          "modification_timestamp": "2024-01-21T14:24:30.000Z"
        },
        "storage_information": {
          "storage_path": "/path/to/Archivos/email_001_1642781234567/Meeting_Notes_2024-01-21.pdf",
          "storage_success": true,
          "checksum_md5": "d41d8cd98f00b204e9800998ecf8427e",
          "backup_status": "included"
        },
        "security_analysis": {
          "risk_level": "low",
          "potential_threats": [],
          "file_signature_valid": true,
          "scan_timestamp": "2024-01-21T15:30:45.123Z"
        },
        "text_extraction": {
          "extraction_attempted": true,
          "extraction_successful": true,
          "extracted_text_length": 1250,
          "extraction_method": "pdf_text_extraction"
        }
      }
    ],
    
    "attachment_categories": {
      "document_files": ["Meeting_Notes_2024-01-21.pdf"],
      "image_files": ["chart_diagram.png"],
      "archive_files": [],
      "executable_files": [],
      "other_files": [],
      "category_counts": {
        "documents": 1,
        "images": 1,
        "archives": 0,
        "executables": 0,
        "other": 0
      }
    },
    
    "security_assessment": {
      "potentially_dangerous_files": [],
      "overall_risk_level": "low",
      "scan_timestamp": "2024-01-21T15:30:45.123Z"
    }
  },
  
  "extracted_text_data": {
    "email_body_text": {
      "plain_text_extraction": "Hi Jane, Please find attached the meeting notes from today's project discussion...",
      "html_text_extraction": "Hi Jane, Please find attached the meeting notes from today's project discussion...",
      "extraction_method": "direct_parsing",
      "extraction_quality": "high"
    },
    
    "attachment_text_extractions": [
      {
        "source_filename": "Meeting_Notes_2024-01-21.pdf",
        "extracted_text": "PROJECT MEETING NOTES\nDate: January 21, 2024\nAttendees: John Smith, Jane Doe, Bob Wilson\n\nAGENDA ITEMS:\n1. Budget Review\n2. Timeline Discussion\n3. Resource Allocation\n\nACTION ITEMS:\n- Jane: Review budget proposal by Friday\n- Bob: Schedule stakeholder meeting\n- John: Prepare technical specifications",
        "extraction_method": "pdf_text_extraction",
        "extraction_success": true,
        "text_length": 1250
      }
    ],
    
    "combined_text_content": "=== EMAIL BODY ===\nHi Jane, Please find attached the meeting notes...\n\n=== ATTACHMENT: Meeting_Notes_2024-01-21.pdf ===\nPROJECT MEETING NOTES\nDate: January 21, 2024...",
    
    "text_analysis_summary": {
      "total_extractable_text_length": 1562,
      "successful_extractions": 2,
      "failed_extractions": 0
    }
  },
  
  "technical_metadata": {
    "email_headers": {
      "Return-Path": "<john.smith@company.com>",
      "Received": "from mail.company.com by gmail.com...",
      "Message-ID": "<CABc123def456@mail.gmail.com>",
      "Date": "Sun, 21 Jan 2024 14:25:30 +0000",
      "From": "John Smith <john.smith@company.com>",
      "To": "Jane Doe <jane.doe@company.com>",
      "Subject": "Project Meeting Notes - Action Items",
      "MIME-Version": "1.0",
      "Content-Type": "multipart/mixed"
    },
    
    "routing_information": {
      "delivery_path": [
        "from mail.company.com by gmail.com with ESMTP",
        "from [192.168.1.100] by mail.company.com with ESMTP"
      ],
      "hop_count": 2,
      "total_delivery_time_seconds": 2.456
    },
    
    "security_headers": {
      "spf_result": "pass",
      "dkim_signature": "v=1; a=rsa-sha256; c=relaxed/relaxed; d=company.com",
      "dmarc_result": "pass",
      "authentication_results": "spf=pass; dkim=pass; dmarc=pass"
    },
    
    "spam_filtering_results": {
      "spam_score": "0.1",
      "spam_status": "clean",
      "spam_filters": [],
      "quarantine_status": "not_quarantined"
    }
  },
  
  "processing_information": {
    "extraction_statistics": {
      "processing_time": 2.45,
      "extraction_method": "imap_direct",
      "retry_count": 0,
      "memory_usage_mb": 45.2,
      "batch_number": 1
    },
    
    "processing_errors": [],
    
    "data_quality_metrics": {
      "metadata_completeness_percentage": 95.5,
      "content_extraction_success": true,
      "attachment_processing_success_rate": 100.0,
      "overall_processing_quality": "high"
    },
    
    "system_information": {
      "processing_system_version": "2.0.0",
      "python_version": "3.9.7",
      "processing_environment": "production"
    }
  },
  
  "compliance_and_audit": {
    "data_retention_information": {
      "retention_category": "business_communication",
      "suggested_retention_period_years": 7,
      "deletion_eligible_date": "2031-01-21T15:30:45.123Z"
    },
    
    "privacy_classification": {
      "contains_personal_information": true,
      "privacy_level": "standard",
      "gdpr_relevant": true
    },
    
    "audit_trail": {
      "created_by": "automated_system",
      "creation_timestamp": "2024-01-21T15:30:45.123Z",
      "last_modified_timestamp": "2024-01-21T15:30:45.123Z",
      "modification_history": []
    }
  }
}
```

## 🔍 Field Explanations

### Document Identification
- **unique_identifier**: Unique ID for this email record
- **document_type**: Always "email_communication"
- **schema_version**: Version of the JSON schema used
- **processing_timestamp**: When this record was created
- **data_integrity_status**: Verification status of the data

### Communication Metadata
- **message_identification**: Email IDs and thread information
- **temporal_information**: All date/time related data
- **participant_information**: Sender and recipient details
- **priority_and_sensitivity**: Email priority and sensitivity settings

### Content Analysis
- **subject_information**: Subject line analysis
- **content_structure**: Technical email structure details
- **body_content**: Email body text and analysis

### Attachment Information
- **attachment_summary**: Overview of all attachments
- **attachment_details**: Detailed information for each file
- **attachment_categories**: Files organized by type
- **security_assessment**: Security analysis results

### Extracted Text Data
- **email_body_text**: Text extracted from email body
- **attachment_text_extractions**: Text from each attachment
- **combined_text_content**: All text combined for searching
- **text_analysis_summary**: Statistics about text extraction

### Technical Metadata
- **email_headers**: All email headers
- **routing_information**: Email delivery path
- **security_headers**: Email security verification
- **spam_filtering_results**: Spam detection results

### Processing Information
- **extraction_statistics**: Performance metrics
- **processing_errors**: Any errors that occurred
- **data_quality_metrics**: Quality assessment scores
- **system_information**: System version details

### Compliance and Audit
- **data_retention_information**: How long to keep this data
- **privacy_classification**: Privacy and GDPR information
- **audit_trail**: Record creation and modification history

## 💡 Using the JSON Data

### For Developers
- All field names are self-documenting
- Consistent naming conventions throughout
- Comprehensive error handling information
- Quality metrics for data validation

### For Data Analysis
- Rich metadata for filtering and sorting
- Text content ready for search indexing
- Attachment categorization for analysis
- Performance metrics for optimization

### For Compliance
- Complete audit trail
- Privacy classification
- Retention period suggestions
- Data integrity verification

This professional schema ensures that all email data is captured, organized, and documented in a comprehensive, standardized format suitable for enterprise use.
