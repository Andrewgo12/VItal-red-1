"""
Gmail Data Extraction and Processing System
Main orchestration script for comprehensive Gmail data extraction
"""

import os
import sys
import logging
from typing import Dict, List, Any, Optional
import datetime
import time

# Add Functions directory to path
sys.path.append(os.path.join(os.path.dirname(__file__), 'Functions'))

from gmail_connector import GmailConnector
from metadata_extractor import MetadataExtractor
from attachment_processor import AttachmentProcessor
from text_extractor import TextExtractor
from json_converter import JSONConverter

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('gmail_processing.log'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

class GmailProcessor:
    """
    Main Gmail processing orchestrator
    """
    
    def __init__(self, email_address: str, password: str, base_path: str = None):
        """
        Initialize Gmail processor
        
        Args:
            email_address: Gmail email address
            password: App password for Gmail
            base_path: Base path for ia folder (defaults to current directory)
        """
        self.email_address = email_address
        self.password = password
        
        # Set base path
        if base_path is None:
            self.base_path = os.path.dirname(os.path.abspath(__file__))
        else:
            self.base_path = base_path
        
        # Initialize components
        self.gmail_connector = GmailConnector(email_address, password)
        self.metadata_extractor = MetadataExtractor()
        self.attachment_processor = AttachmentProcessor(self.base_path)
        self.text_extractor = TextExtractor(self.base_path)
        self.json_converter = JSONConverter(self.base_path)
        
        # Processing statistics
        self.stats = {
            'total_emails': 0,
            'processed_emails': 0,
            'failed_emails': 0,
            'total_attachments': 0,
            'start_time': None,
            'end_time': None
        }
        
        self.processed_emails = []
    
    def process_single_email(self, email_id: str) -> Dict[str, Any]:
        """
        Process a single email completely
        
        Args:
            email_id: Email UID
            
        Returns:
            Dict: Processing result
        """
        result = {
            'email_id': email_id,
            'unique_id': None,
            'success': False,
            'error': None,
            'processing_time': 0,
            'attachment_count': 0,
            'total_size_bytes': 0
        }
        
        start_time = time.time()
        
        try:
            # Fetch email
            logger.info(f"Processing email {email_id}")
            email_message = self.gmail_connector.fetch_email(email_id)
            
            if not email_message:
                result['error'] = "Failed to fetch email"
                return result
            
            # Generate unique ID
            unique_id = self.gmail_connector.generate_unique_id(email_message)
            result['unique_id'] = unique_id
            
            logger.info(f"Processing email with unique ID: {unique_id}")
            
            # Extract metadata
            metadata = self.metadata_extractor.extract_metadata(email_message, unique_id)
            
            # Extract email body content
            body_content = self.text_extractor.extract_email_body(email_message)
            
            # Process attachments
            attachments = self.attachment_processor.process_email_attachments(email_message, unique_id)
            
            # Extract text from attachments
            for attachment in attachments:
                if attachment.get('saved_successfully') and attachment.get('file_path'):
                    extracted_text = self.text_extractor.extract_from_file(attachment['file_path'])
                    attachment['extracted_text'] = extracted_text
            
            # Prepare extracted text summary
            extracted_text = {
                'email_body': body_content,
                'attachments': [
                    {
                        'filename': att.get('original_filename'),
                        'text': att.get('extracted_text', '')
                    } for att in attachments if att.get('extracted_text')
                ]
            }
            
            # Save extracted text to file
            text_file_path = self.text_extractor.save_extracted_text(unique_id, {
                'email_body': body_content,
                'attachments': attachments
            })
            
            # Prepare processing statistics
            processing_stats = {
                'processing_time': time.time() - start_time,
                'extraction_method': 'imap_direct',
                'retry_count': 0,
                'memory_usage_mb': 0  # Could be implemented with psutil
            }

            # Create comprehensive JSON structure with enhanced analysis
            email_data = self.json_converter.create_enhanced_email_schema(
                unique_id, metadata, body_content, attachments, extracted_text, processing_stats
            )
            
            # Validate JSON schema
            validation_errors = self.json_converter.validate_json_schema(email_data)
            if validation_errors:
                logger.warning(f"JSON validation warnings for {unique_id}: {validation_errors}")
                email_data['processing_info']['warnings'].extend(validation_errors)
            
            # Save JSON data
            json_path = self.json_converter.save_email_json(unique_id, email_data)
            
            # Update result
            result['success'] = True
            result['attachment_count'] = len(attachments)
            result['total_size_bytes'] = sum(att.get('size_bytes', 0) for att in attachments)
            result['json_path'] = json_path
            result['text_path'] = text_file_path
            result['subject'] = metadata.get('subject', '')
            result['sender'] = metadata.get('from', [{}])[0].get('email', '') if metadata.get('from') else ''
            result['date'] = metadata.get('date')
            result['attachment_types'] = list(set(att.get('content_type', '') for att in attachments))
            
            # Update statistics
            self.stats['processed_emails'] += 1
            self.stats['total_attachments'] += len(attachments)
            
            logger.info(f"Successfully processed email {unique_id}")
            
        except Exception as e:
            logger.error(f"Error processing email {email_id}: {str(e)}")
            result['error'] = str(e)
            self.stats['failed_emails'] += 1
        
        finally:
            result['processing_time'] = time.time() - start_time
        
        return result
    
    def process_emails(self, folder_name: str = "INBOX", criteria: str = "ALL", 
                      max_emails: Optional[int] = None, start_from: int = 0) -> List[Dict[str, Any]]:
        """
        Process multiple emails
        
        Args:
            folder_name: Gmail folder to process
            criteria: Search criteria for emails
            max_emails: Maximum number of emails to process (None for all)
            start_from: Email index to start from
            
        Returns:
            List[Dict]: Processing results for all emails
        """
        self.stats['start_time'] = datetime.datetime.now()
        
        try:
            # Connect to Gmail
            if not self.gmail_connector.connect():
                logger.error("Failed to connect to Gmail")
                return []
            
            # Select folder
            if not self.gmail_connector.select_folder(folder_name):
                logger.error(f"Failed to select folder: {folder_name}")
                return []
            
            # Search for emails
            email_ids = self.gmail_connector.search_emails(criteria)
            self.stats['total_emails'] = len(email_ids)
            
            logger.info(f"Found {len(email_ids)} emails in {folder_name}")
            
            # Apply start_from and max_emails limits
            if start_from > 0:
                email_ids = email_ids[start_from:]
                logger.info(f"Starting from email {start_from}")
            
            if max_emails:
                email_ids = email_ids[:max_emails]
                logger.info(f"Processing maximum {max_emails} emails")
            
            # Process each email
            for i, email_id in enumerate(email_ids, 1):
                logger.info(f"Processing email {i}/{len(email_ids)}")
                result = self.process_single_email(email_id)
                self.processed_emails.append(result)
                
                # Log progress every 10 emails
                if i % 10 == 0:
                    logger.info(f"Progress: {i}/{len(email_ids)} emails processed")
            
            # Create summary JSON
            summary_path = self.json_converter.create_summary_json(self.processed_emails)
            logger.info(f"Created processing summary: {summary_path}")
            
        except Exception as e:
            logger.error(f"Error in email processing: {str(e)}")
        
        finally:
            # Disconnect from Gmail
            self.gmail_connector.disconnect()
            self.stats['end_time'] = datetime.datetime.now()
        
        return self.processed_emails
    
    def get_processing_statistics(self) -> Dict[str, Any]:
        """
        Get comprehensive processing statistics
        
        Returns:
            Dict: Processing statistics
        """
        stats = self.stats.copy()
        
        if stats['start_time'] and stats['end_time']:
            duration = stats['end_time'] - stats['start_time']
            stats['total_duration_seconds'] = duration.total_seconds()
            stats['average_time_per_email'] = duration.total_seconds() / max(stats['processed_emails'], 1)
        
        stats['success_rate'] = (stats['processed_emails'] / max(stats['total_emails'], 1)) * 100
        
        return stats
    
    def print_summary(self):
        """Print processing summary"""
        stats = self.get_processing_statistics()
        
        print("\n" + "="*60)
        print("GMAIL PROCESSING SUMMARY")
        print("="*60)
        print(f"Total emails found: {stats['total_emails']}")
        print(f"Successfully processed: {stats['processed_emails']}")
        print(f"Failed: {stats['failed_emails']}")
        print(f"Success rate: {stats.get('success_rate', 0):.1f}%")
        print(f"Total attachments: {stats['total_attachments']}")
        
        if stats.get('total_duration_seconds'):
            print(f"Total processing time: {stats['total_duration_seconds']:.1f} seconds")
            print(f"Average time per email: {stats.get('average_time_per_email', 0):.1f} seconds")
        
        print("="*60)

    def process_with_retry(self, email_id: str, max_retries: int = 3) -> Dict[str, Any]:
        """
        Process email with retry mechanism

        Args:
            email_id: Email UID
            max_retries: Maximum number of retry attempts

        Returns:
            Dict: Processing result
        """
        last_error = None

        for attempt in range(max_retries):
            try:
                result = self.process_single_email(email_id)
                if result['success']:
                    return result
                last_error = result.get('error', 'Unknown error')
            except Exception as e:
                last_error = str(e)
                logger.warning(f"Attempt {attempt + 1} failed for email {email_id}: {str(e)}")

                if attempt < max_retries - 1:
                    import time
                    time.sleep(2 ** attempt)  # Exponential backoff

        # All attempts failed
        return {
            'email_id': email_id,
            'unique_id': None,
            'success': False,
            'error': f"Failed after {max_retries} attempts. Last error: {last_error}",
            'processing_time': 0,
            'attachment_count': 0,
            'total_size_bytes': 0
        }

    def process_emails_with_filters(self, filters: Dict[str, Any]) -> List[Dict[str, Any]]:
        """
        Process emails with advanced filtering options

        Args:
            filters: Dictionary with filtering options
                    - folder: Gmail folder
                    - from_email: sender email
                    - to_email: recipient email
                    - subject_contains: subject filter
                    - date_range: tuple of (start_date, end_date)
                    - has_attachments: boolean
                    - min_size: minimum email size
                    - max_size: maximum email size
                    - max_emails: maximum number to process

        Returns:
            List[Dict]: Processing results
        """
        self.stats['start_time'] = datetime.datetime.now()

        try:
            if not self.gmail_connector.connect():
                logger.error("Failed to connect to Gmail")
                return []

            folder_name = filters.get('folder', 'INBOX')
            if not self.gmail_connector.select_folder(folder_name):
                logger.error(f"Failed to select folder: {folder_name}")
                return []

            # Build search criteria
            search_criteria = {}

            if filters.get('from_email'):
                search_criteria['from_email'] = filters['from_email']

            if filters.get('to_email'):
                search_criteria['to_email'] = filters['to_email']

            if filters.get('subject_contains'):
                search_criteria['subject'] = filters['subject_contains']

            if filters.get('date_range'):
                start_date, end_date = filters['date_range']
                search_criteria['since_date'] = start_date
                search_criteria['before_date'] = end_date

            if filters.get('has_attachments'):
                search_criteria['has_attachment'] = True

            if filters.get('min_size'):
                search_criteria['larger_than'] = filters['min_size']

            if filters.get('max_size'):
                search_criteria['smaller_than'] = filters['max_size']

            # Search emails
            email_ids = self.gmail_connector.search_emails_advanced(search_criteria)
            self.stats['total_emails'] = len(email_ids)

            logger.info(f"Found {len(email_ids)} emails matching filters")

            # Apply max_emails limit
            max_emails = filters.get('max_emails')
            if max_emails:
                email_ids = email_ids[:max_emails]

            # Process emails
            for i, email_id in enumerate(email_ids, 1):
                logger.info(f"Processing email {i}/{len(email_ids)}")
                result = self.process_with_retry(email_id)
                self.processed_emails.append(result)

                if result['success']:
                    self.stats['processed_emails'] += 1
                    self.stats['total_attachments'] += result['attachment_count']
                else:
                    self.stats['failed_emails'] += 1

            # Create summary
            summary_path = self.json_converter.create_summary_json(self.processed_emails)
            logger.info(f"Created processing summary: {summary_path}")

        except Exception as e:
            logger.error(f"Error in filtered email processing: {str(e)}")

        finally:
            self.gmail_connector.disconnect()
            self.stats['end_time'] = datetime.datetime.now()

        return self.processed_emails

    def analyze_email_patterns(self) -> Dict[str, Any]:
        """
        Analyze patterns in processed emails

        Returns:
            Dict: Pattern analysis results
        """
        if not self.processed_emails:
            return {}

        analysis = {
            'temporal_patterns': {},
            'sender_patterns': {},
            'content_patterns': {},
            'attachment_patterns': {},
            'security_patterns': {}
        }

        # Temporal analysis
        dates = []
        hours = []
        for email in self.processed_emails:
            if email.get('date'):
                try:
                    date_obj = datetime.datetime.fromisoformat(email['date'].replace('Z', '+00:00'))
                    dates.append(date_obj.date())
                    hours.append(date_obj.hour)
                except:
                    continue

        if dates:
            from collections import Counter
            analysis['temporal_patterns'] = {
                'date_range': {
                    'start': min(dates).isoformat(),
                    'end': max(dates).isoformat()
                },
                'most_active_hours': dict(Counter(hours).most_common(5)),
                'total_days': len(set(dates))
            }

        # Sender analysis
        senders = [email.get('sender', '') for email in self.processed_emails if email.get('sender')]
        if senders:
            from collections import Counter
            sender_counts = Counter(senders)
            analysis['sender_patterns'] = {
                'unique_senders': len(sender_counts),
                'top_senders': dict(sender_counts.most_common(10)),
                'single_email_senders': sum(1 for count in sender_counts.values() if count == 1)
            }

        # Content patterns
        subjects = [email.get('subject', '') for email in self.processed_emails if email.get('subject')]
        if subjects:
            # Find common words in subjects
            import re
            all_words = []
            for subject in subjects:
                words = re.findall(r'\b\w+\b', subject.lower())
                all_words.extend(words)

            from collections import Counter
            word_counts = Counter(all_words)
            analysis['content_patterns'] = {
                'common_subject_words': dict(word_counts.most_common(20)),
                'average_subject_length': sum(len(s) for s in subjects) / len(subjects)
            }

        # Attachment patterns
        total_attachments = sum(email.get('attachment_count', 0) for email in self.processed_emails)
        emails_with_attachments = sum(1 for email in self.processed_emails if email.get('attachment_count', 0) > 0)

        analysis['attachment_patterns'] = {
            'total_attachments': total_attachments,
            'emails_with_attachments': emails_with_attachments,
            'attachment_rate': emails_with_attachments / len(self.processed_emails) if self.processed_emails else 0,
            'average_attachments_per_email': total_attachments / len(self.processed_emails) if self.processed_emails else 0
        }

        return analysis

def main():
    """
    Main function for command-line usage
    """
    import argparse
    
    parser = argparse.ArgumentParser(description='Gmail Data Extraction System')
    parser.add_argument('email', help='Gmail email address')
    parser.add_argument('password', help='Gmail app password')
    parser.add_argument('--folder', default='INBOX', help='Gmail folder to process')
    parser.add_argument('--criteria', default='ALL', help='Search criteria')
    parser.add_argument('--max-emails', type=int, help='Maximum number of emails to process')
    parser.add_argument('--start-from', type=int, default=0, help='Email index to start from')
    parser.add_argument('--base-path', help='Base path for ia folder')
    
    args = parser.parse_args()
    
    # Create processor
    processor = GmailProcessor(args.email, args.password, args.base_path)
    
    # Process emails
    results = processor.process_emails(
        folder_name=args.folder,
        criteria=args.criteria,
        max_emails=args.max_emails,
        start_from=args.start_from
    )
    
    # Print summary
    processor.print_summary()

if __name__ == "__main__":
    main()
