"""
Batch Processing System for Large-Scale Email Processing
Handles 1,000+ emails efficiently with memory management and progress tracking
"""

import os
import sys
import time
import logging
import threading
import queue
import gc
from datetime import datetime
from typing import List, Dict, Any, Optional, Callable
from concurrent.futures import ThreadPoolExecutor, as_completed
import psutil

logger = logging.getLogger(__name__)

class BatchProcessor:
    """
    High-performance batch processor for large-scale email processing
    """
    
    def __init__(
        self, 
        base_path: str,
        batch_size: int = 50,
        max_workers: int = 4,
        memory_limit_mb: int = 2048,
        progress_callback: Optional[Callable] = None
    ):
        """
        Initialize batch processor
        
        Args:
            base_path: Base path for processing
            batch_size: Number of emails to process in each batch
            max_workers: Maximum number of worker threads
            memory_limit_mb: Memory limit in MB
            progress_callback: Optional callback for progress updates
        """
        self.base_path = base_path
        self.batch_size = batch_size
        self.max_workers = max_workers
        self.memory_limit_mb = memory_limit_mb
        self.progress_callback = progress_callback
        
        # Processing statistics
        self.stats = {
            'total_emails': 0,
            'processed_emails': 0,
            'successful_emails': 0,
            'failed_emails': 0,
            'start_time': None,
            'end_time': None,
            'processing_time': 0,
            'average_time_per_email': 0,
            'memory_usage_mb': 0,
            'peak_memory_mb': 0,
            'batches_processed': 0,
            'current_batch': 0
        }
        
        # Thread-safe queue for results
        self.results_queue = queue.Queue()
        self.error_queue = queue.Queue()
        
        # Memory monitoring
        self.memory_monitor = MemoryMonitor(self.memory_limit_mb)
        
    def process_email_batch(
        self,
        email_ids: List[str],
        gmail_connector,
        processors: Dict[str, Any],
        processing_function: Callable
    ) -> List[Dict[str, Any]]:
        """
        Process a batch of emails with optimized performance
        
        Args:
            email_ids: List of email IDs to process
            gmail_connector: Gmail connection object
            processors: Dictionary of processor objects
            processing_function: Function to process individual emails
            
        Returns:
            List[Dict]: Processing results
        """
        
        self.stats['total_emails'] = len(email_ids)
        self.stats['start_time'] = time.time()
        
        logger.info(f"Starting batch processing of {len(email_ids)} emails")
        logger.info(f"Batch size: {self.batch_size}, Workers: {self.max_workers}")
        
        # Split emails into batches
        batches = self._create_batches(email_ids)
        self.stats['batches_processed'] = len(batches)
        
        all_results = []
        
        try:
            # Process batches sequentially to manage memory
            for batch_num, batch in enumerate(batches, 1):
                self.stats['current_batch'] = batch_num
                
                logger.info(f"Processing batch {batch_num}/{len(batches)} ({len(batch)} emails)")
                
                # Check memory before processing batch
                if not self.memory_monitor.check_memory_available():
                    logger.warning("Memory limit approaching, forcing garbage collection")
                    gc.collect()
                    
                    if not self.memory_monitor.check_memory_available():
                        logger.error("Insufficient memory to continue processing")
                        break
                
                # Process batch with threading
                batch_results = self._process_batch_threaded(
                    batch, gmail_connector, processors, processing_function
                )
                
                all_results.extend(batch_results)
                
                # Update statistics
                self._update_batch_stats(batch_results)
                
                # Progress callback
                if self.progress_callback:
                    progress = (batch_num / len(batches)) * 100
                    self.progress_callback(progress, self.stats)
                
                # Memory cleanup between batches
                gc.collect()
                
                # Brief pause to allow system recovery
                time.sleep(0.1)
        
        except Exception as e:
            logger.error(f"Critical error in batch processing: {str(e)}")
            
        finally:
            self.stats['end_time'] = time.time()
            self.stats['processing_time'] = self.stats['end_time'] - self.stats['start_time']
            
            if self.stats['processed_emails'] > 0:
                self.stats['average_time_per_email'] = (
                    self.stats['processing_time'] / self.stats['processed_emails']
                )
            
            self._log_final_statistics()
        
        return all_results
    
    def _create_batches(self, email_ids: List[str]) -> List[List[str]]:
        """Create batches from email IDs"""
        batches = []
        for i in range(0, len(email_ids), self.batch_size):
            batch = email_ids[i:i + self.batch_size]
            batches.append(batch)
        return batches
    
    def _process_batch_threaded(
        self,
        batch: List[str],
        gmail_connector,
        processors: Dict[str, Any],
        processing_function: Callable
    ) -> List[Dict[str, Any]]:
        """Process a batch using thread pool"""
        
        batch_results = []
        
        with ThreadPoolExecutor(max_workers=self.max_workers) as executor:
            # Submit all emails in batch
            future_to_email = {
                executor.submit(
                    self._process_single_email_safe,
                    email_id,
                    gmail_connector,
                    processors,
                    processing_function
                ): email_id for email_id in batch
            }
            
            # Collect results as they complete
            for future in as_completed(future_to_email):
                email_id = future_to_email[future]
                try:
                    result = future.result(timeout=300)  # 5 minute timeout per email
                    batch_results.append(result)
                except Exception as e:
                    logger.error(f"Error processing email {email_id}: {str(e)}")
                    batch_results.append({
                        'unique_id': f"error_{email_id}",
                        'success': False,
                        'error': str(e),
                        'email_id': email_id
                    })
        
        return batch_results
    
    def _process_single_email_safe(
        self,
        email_id: str,
        gmail_connector,
        processors: Dict[str, Any],
        processing_function: Callable
    ) -> Dict[str, Any]:
        """Safely process a single email with error handling"""
        
        try:
            # Fetch email with timeout
            email_msg = gmail_connector.fetch_email(email_id)
            if not email_msg:
                return {
                    'unique_id': f"fetch_error_{email_id}",
                    'success': False,
                    'error': 'Failed to fetch email',
                    'email_id': email_id
                }
            
            # Generate unique ID
            unique_id = f"email_{email_id}_{int(time.time() * 1000)}"
            
            # Process email
            result = processing_function(email_msg, unique_id, processors, logger)
            result['email_id'] = email_id
            
            return result
            
        except Exception as e:
            logger.error(f"Error in safe email processing for {email_id}: {str(e)}")
            return {
                'unique_id': f"process_error_{email_id}",
                'success': False,
                'error': str(e),
                'email_id': email_id
            }
    
    def _update_batch_stats(self, batch_results: List[Dict[str, Any]]):
        """Update processing statistics"""
        
        for result in batch_results:
            self.stats['processed_emails'] += 1
            
            if result.get('success', False):
                self.stats['successful_emails'] += 1
            else:
                self.stats['failed_emails'] += 1
        
        # Update memory usage
        current_memory = psutil.Process().memory_info().rss / 1024 / 1024
        self.stats['memory_usage_mb'] = current_memory
        
        if current_memory > self.stats['peak_memory_mb']:
            self.stats['peak_memory_mb'] = current_memory
    
    def _log_final_statistics(self):
        """Log final processing statistics"""
        
        logger.info("=" * 60)
        logger.info("BATCH PROCESSING COMPLETED")
        logger.info("=" * 60)
        logger.info(f"Total emails: {self.stats['total_emails']}")
        logger.info(f"Processed: {self.stats['processed_emails']}")
        logger.info(f"Successful: {self.stats['successful_emails']}")
        logger.info(f"Failed: {self.stats['failed_emails']}")
        logger.info(f"Success rate: {(self.stats['successful_emails']/max(1,self.stats['processed_emails'])*100):.1f}%")
        logger.info(f"Total time: {self.stats['processing_time']:.2f} seconds")
        logger.info(f"Average time per email: {self.stats['average_time_per_email']:.2f} seconds")
        logger.info(f"Peak memory usage: {self.stats['peak_memory_mb']:.1f} MB")
        logger.info(f"Batches processed: {self.stats['batches_processed']}")
        logger.info("=" * 60)
    
    def get_statistics(self) -> Dict[str, Any]:
        """Get current processing statistics"""
        return self.stats.copy()


class MemoryMonitor:
    """Monitor and manage memory usage during processing"""
    
    def __init__(self, limit_mb: int):
        self.limit_mb = limit_mb
        self.warning_threshold = limit_mb * 0.8  # 80% warning
        self.critical_threshold = limit_mb * 0.9  # 90% critical
    
    def get_current_memory_mb(self) -> float:
        """Get current memory usage in MB"""
        return psutil.Process().memory_info().rss / 1024 / 1024
    
    def check_memory_available(self) -> bool:
        """Check if sufficient memory is available"""
        current_memory = self.get_current_memory_mb()
        
        if current_memory > self.critical_threshold:
            logger.warning(f"Memory usage critical: {current_memory:.1f}MB / {self.limit_mb}MB")
            return False
        elif current_memory > self.warning_threshold:
            logger.warning(f"Memory usage high: {current_memory:.1f}MB / {self.limit_mb}MB")
        
        return True
    
    def force_cleanup(self):
        """Force memory cleanup"""
        logger.info("Forcing memory cleanup...")
        gc.collect()
        
        # Additional cleanup for large objects
        import sys
        if hasattr(sys, '_clear_type_cache'):
            sys._clear_type_cache()


class ProgressTracker:
    """Track and report processing progress"""
    
    def __init__(self, total_items: int):
        self.total_items = total_items
        self.processed_items = 0
        self.start_time = time.time()
        self.last_update = self.start_time
        
    def update(self, processed_count: int):
        """Update progress"""
        self.processed_items = processed_count
        current_time = time.time()
        
        # Only log every 10 seconds or at significant milestones
        if (current_time - self.last_update) > 10 or processed_count % 100 == 0:
            self._log_progress()
            self.last_update = current_time
    
    def _log_progress(self):
        """Log current progress"""
        elapsed_time = time.time() - self.start_time
        progress_percent = (self.processed_items / self.total_items) * 100
        
        if self.processed_items > 0:
            avg_time_per_item = elapsed_time / self.processed_items
            remaining_items = self.total_items - self.processed_items
            estimated_remaining_time = remaining_items * avg_time_per_item
            
            logger.info(
                f"Progress: {self.processed_items}/{self.total_items} "
                f"({progress_percent:.1f}%) - "
                f"ETA: {estimated_remaining_time/60:.1f} minutes"
            )
