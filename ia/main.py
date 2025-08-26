#!/usr/bin/env python3
"""
Gmail Data Extraction System - Main Script
Comprehensive email processing and data extraction from Gmail
"""

import os
import sys
import time
import logging
from datetime import datetime
from typing import List, Dict, Any
from dotenv import load_dotenv

# Load environment variables from .env file
load_dotenv()

# Add Functions directory to path
sys.path.append(os.path.join(os.path.dirname(__file__), 'Functions'))

# Import all components
from config import load_complete_config
from gmail_connector import GmailConnector
from metadata_extractor import MetadataExtractor
from attachment_processor import AttachmentProcessor
from text_extractor import TextExtractor
from json_converter import JSONConverter
from monitoring import PerformanceMonitor, ProcessingLogger
from backup_recovery import BackupManager
from data_validator import QualityAssurance
from batch_processor import BatchProcessor, ProgressTracker
from professional_json_schema import ProfessionalEmailSchema

def setup_logging(config: Dict[str, Any]) -> logging.Logger:
    """Setup logging configuration"""
    log_level = getattr(logging, config.get('log_level', 'INFO').upper())
    
    logging.basicConfig(
        level=log_level,
        format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
        handlers=[
            logging.FileHandler('gmail_processing.log'),
            logging.StreamHandler(sys.stdout)
        ]
    )
    
    return logging.getLogger('gmail_processor')

def process_single_email(email_msg, unique_id: str, processors: Dict[str, Any], logger: logging.Logger) -> Dict[str, Any]:
    """Process a single email through the complete pipeline"""
    
    try:
        start_time = time.time()
        
        # Step 1: Extract metadata
        logger.info(f"Extracting metadata for email {unique_id}")
        metadata = processors['metadata_extractor'].extract_metadata(email_msg, unique_id)
        
        # Step 2: Process attachments
        logger.info(f"Processing attachments for email {unique_id}")
        attachments = processors['attachment_processor'].process_email_attachments(email_msg, unique_id)
        
        # Step 3: Extract text content
        logger.info(f"Extracting text content for email {unique_id}")
        body_content = processors['text_extractor'].extract_email_body(email_msg)
        
        # Extract text from attachments
        for attachment in attachments:
            if attachment['saved_successfully']:
                try:
                    extracted_text = processors['text_extractor'].extract_from_file(attachment['file_path'])
                    attachment['extracted_text'] = extracted_text
                except Exception as e:
                    logger.warning(f"Failed to extract text from {attachment['original_filename']}: {e}")
                    attachment['extracted_text'] = ""
        
        # Step 4: Create extracted text summary
        extracted_text_data = {
            'email_body': body_content,
            'attachments': [
                {
                    'filename': att['original_filename'],
                    'text': att.get('extracted_text', '')
                } for att in attachments
            ]
        }
        
        # Save extracted text
        text_file_path = processors['text_extractor'].save_extracted_text(unique_id, extracted_text_data)
        
        # Step 5: Convert to Professional JSON Schema
        processing_time = time.time() - start_time
        processing_stats = {
            'processing_time': processing_time,
            'extraction_method': 'imap_direct',
            'retry_count': 0,
            'memory_usage_mb': processors.get('memory_usage', 0),
            'batch_number': processors.get('batch_number', 1)
        }

        # Create professional email record
        email_data = processors['json_converter'].create_professional_email_record(
            unique_id,
            metadata,
            body_content,
            attachments,
            extracted_text_data,
            processing_stats
        )

        # Step 6: Save Professional JSON data
        json_file_path = processors['json_converter'].save_professional_email_record(unique_id, email_data)
        
        # Step 7: Quality assurance
        qa_result = processors['qa_system'].run_full_qa_check(email_data)
        
        logger.info(f"Successfully processed email {unique_id} in {processing_time:.2f}s")
        
        return {
            'unique_id': unique_id,
            'success': True,
            'processing_time': processing_time,
            'attachment_count': len(attachments),
            'text_file': text_file_path,
            'json_file': json_file_path,
            'qa_score': qa_result.get('quality_score', 0),
            'subject': metadata.get('subject', 'No Subject'),
            'sender': metadata.get('from', [{}])[0].get('email', 'Unknown'),
            'date': metadata.get('date', 'Unknown'),
            'total_size_bytes': sum(att.get('size_bytes', 0) for att in attachments),
            'attachment_types': list(set(att.get('content_type', 'unknown') for att in attachments))
        }
        
    except Exception as e:
        logger.error(f"Failed to process email {unique_id}: {e}")
        return {
            'unique_id': unique_id,
            'success': False,
            'error': str(e),
            'processing_time': time.time() - start_time
        }

def main():
    """Main processing function"""
    
    print("=" * 70)
    print("GMAIL DATA EXTRACTION SYSTEM")
    print("=" * 70)
    print(f"Starting at: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print()
    
    try:
        # Load configuration
        print("📋 Loading configuration...")
        config = load_complete_config()
        
        # Setup logging
        logger = setup_logging(config)
        logger.info("Gmail processing system started")
        
        # Initialize performance monitoring
        print("📊 Initializing performance monitoring...")
        performance_monitor = PerformanceMonitor()
        processing_logger = ProcessingLogger(config['base_path'])
        
        # Start monitoring
        performance_monitor.start_monitoring()
        
        # Initialize all processors
        print("🔧 Initializing processors...")
        processors = {
            'metadata_extractor': MetadataExtractor,
            'attachment_processor': AttachmentProcessor(config['base_path']),
            'text_extractor': TextExtractor(config['base_path']),
            'json_converter': JSONConverter(config['base_path']),
            'qa_system': QualityAssurance()
        }
        
        # Connect to Gmail
        print("📧 Connecting to Gmail...")
        gmail_connector = GmailConnector(
            email_address=config['email_address'],
            password=config['password'],
            imap_server=config.get('imap_server', 'imap.gmail.com'),
            port=config.get('imap_port', 993)
        )
        
        if not gmail_connector.connect():
            logger.error("Failed to connect to Gmail")
            print("❌ Failed to connect to Gmail. Check your credentials.")
            return
        
        print(f"✅ Connected to Gmail: {config['email_address']}")
        
        # Search for emails
        print("🔍 Searching for emails...")
        email_ids = gmail_connector.search_emails()
        
        if not email_ids:
            print("📭 No emails found to process")
            return
        
        total_emails = len(email_ids)
        max_emails = config.get('max_emails', 0)
        
        if max_emails > 0 and total_emails > max_emails:
            email_ids = email_ids[:max_emails]
            total_emails = max_emails
            print(f"📊 Limited processing to {max_emails} emails")
        
        print(f"📊 Found {total_emails} emails to process")
        print()

        # Initialize batch processor for large-scale processing
        def progress_callback(progress_percent, stats):
            print(f"📊 Batch Progress: {progress_percent:.1f}% - "
                  f"Processed: {stats['processed_emails']}/{stats['total_emails']} - "
                  f"Success Rate: {(stats['successful_emails']/max(1,stats['processed_emails'])*100):.1f}% - "
                  f"Memory: {stats['memory_usage_mb']:.1f}MB")

        batch_processor = BatchProcessor(
            base_path=config['base_path'],
            batch_size=min(50, max(10, total_emails // 10)),  # Dynamic batch size
            max_workers=min(4, max(1, total_emails // 100)),   # Dynamic worker count
            memory_limit_mb=config.get('memory_limit_mb', 2048),
            progress_callback=progress_callback
        )

        # Process emails using batch processor
        print(f"🚀 Starting high-performance batch processing...")
        print(f"   Batch size: {batch_processor.batch_size}")
        print(f"   Workers: {batch_processor.max_workers}")
        print(f"   Memory limit: {batch_processor.memory_limit_mb}MB")
        print()

        processed_emails = batch_processor.process_email_batch(
            email_ids,
            gmail_connector,
            processors,
            process_single_email
        )

        # Get final statistics
        batch_stats = batch_processor.get_statistics()
        successful_count = batch_stats['successful_emails']
        failed_count = batch_stats['failed_emails']
        
        # Stop monitoring
        performance_monitor.stop_monitoring()
        
        # Generate comprehensive summary
        print("=" * 70)
        print("COMPREHENSIVE PROCESSING SUMMARY")
        print("=" * 70)
        print(f"Total emails processed: {batch_stats['processed_emails']}")
        print(f"Successful: {successful_count}")
        print(f"Failed: {failed_count}")
        print(f"Success rate: {(successful_count/max(1,batch_stats['processed_emails'])*100):.1f}%")
        print(f"Processing efficiency: {(batch_stats['processed_emails']/total_emails*100):.1f}%")

        # Calculate totals
        total_attachments = sum(r.get('attachment_count', 0) for r in processed_emails if r.get('success'))
        total_processing_time = batch_stats['processing_time']

        print(f"Total attachments processed: {total_attachments}")
        print(f"Total processing time: {total_processing_time:.2f}s ({total_processing_time/60:.1f} minutes)")
        print(f"Average time per email: {batch_stats['average_time_per_email']:.2f}s")
        print(f"Peak memory usage: {batch_stats['peak_memory_mb']:.1f}MB")
        print(f"Batches processed: {batch_stats['batches_processed']}")

        # Performance summary
        perf_summary = performance_monitor.get_performance_summary()
        print(f"System monitoring duration: {perf_summary['monitoring_duration_minutes']:.2f} minutes")
        print(f"Performance samples collected: {perf_summary['samples_collected']}")

        # Processing rate calculations
        if total_processing_time > 0:
            emails_per_second = batch_stats['processed_emails'] / total_processing_time
            emails_per_hour = emails_per_second * 3600
            print(f"Processing rate: {emails_per_second:.2f} emails/second ({emails_per_hour:.0f} emails/hour)")

            # Estimate capacity for daily processing
            daily_capacity = emails_per_hour * 8  # 8-hour work day
            print(f"Estimated daily capacity: {daily_capacity:.0f} emails/day")
        
        # Create backup if enabled
        if config.get('backup_enabled', False):
            print("\n💾 Creating backup...")
            try:
                with BackupManager(config['base_path']) as backup_mgr:
                    backup_result = backup_mgr.create_full_backup(include_attachments=True)
                    if backup_result['success']:
                        print(f"✅ Backup created: {backup_result['backup_id']}")
                    else:
                        print("❌ Backup failed")
            except Exception as e:
                print(f"❌ Backup error: {e}")
        
        # Create processing report
        print("\n📊 Creating processing report...")
        try:
            report_path = os.path.join(config['base_path'], f"processing_report_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json")
            processing_logger.create_processing_report(report_path)
            print(f"✅ Report saved: {report_path}")
        except Exception as e:
            print(f"❌ Report creation failed: {e}")
        
        # Summary JSON
        summary_path = os.path.join(config['base_path'], "Json", "processing_summary.json")
        processors['json_converter'].create_summary_json(processed_emails, summary_path)
        print(f"✅ Summary saved: {summary_path}")
        
        print("\n🎉 Gmail processing completed successfully!")
        
    except Exception as e:
        print(f"❌ Critical error: {e}")
        logger.error(f"Critical error in main processing: {e}")
        import traceback
        traceback.print_exc()
    
    finally:
        # Cleanup
        try:
            if 'gmail_connector' in locals():
                gmail_connector.disconnect()
            if 'processing_logger' in locals():
                processing_logger.cleanup_handlers()
        except:
            pass

if __name__ == "__main__":
    main()
