"""
Example usage of Gmail Data Extraction System
Demonstrates various ways to use the system
"""

import os
import sys
from gmail_processor import GmailProcessor
from config import load_config, GmailConfig

def example_basic_usage():
    """
    Basic usage example
    """
    print("=== Basic Usage Example ===")
    
    # Replace with your credentials
    email_address = "your-email@gmail.com"
    app_password = "your-app-password"
    
    # Create processor
    processor = GmailProcessor(email_address, app_password)
    
    # Process first 5 emails from INBOX
    results = processor.process_emails(
        folder_name="INBOX",
        criteria="ALL",
        max_emails=5
    )
    
    # Print summary
    processor.print_summary()
    
    return results

def example_advanced_usage():
    """
    Advanced usage with specific criteria
    """
    print("=== Advanced Usage Example ===")
    
    email_address = "your-email@gmail.com"
    app_password = "your-app-password"
    
    processor = GmailProcessor(email_address, app_password)
    
    # Process emails from last 30 days with attachments
    import datetime
    thirty_days_ago = (datetime.datetime.now() - datetime.timedelta(days=30)).strftime("%d-%b-%Y")
    criteria = f'SINCE "{thirty_days_ago}" HAS ATTACHMENT'
    
    results = processor.process_emails(
        folder_name="INBOX",
        criteria=criteria,
        max_emails=20
    )
    
    # Print detailed results
    print(f"\nProcessed {len(results)} emails:")
    for result in results:
        if result['success']:
            print(f"✓ {result['subject'][:50]}... - {result['attachment_count']} attachments")
        else:
            print(f"✗ Failed: {result['error']}")
    
    processor.print_summary()
    return results

def example_specific_sender():
    """
    Process emails from specific sender
    """
    print("=== Specific Sender Example ===")
    
    email_address = "your-email@gmail.com"
    app_password = "your-app-password"
    sender_email = "important-sender@company.com"
    
    processor = GmailProcessor(email_address, app_password)
    
    # Process all emails from specific sender
    criteria = f'FROM "{sender_email}"'
    
    results = processor.process_emails(
        folder_name="INBOX",
        criteria=criteria,
        max_emails=50
    )
    
    processor.print_summary()
    return results

def example_date_range():
    """
    Process emails from specific date range
    """
    print("=== Date Range Example ===")
    
    email_address = "your-email@gmail.com"
    app_password = "your-app-password"
    
    processor = GmailProcessor(email_address, app_password)
    
    # Process emails from January 2024
    criteria = 'SINCE "01-Jan-2024" BEFORE "01-Feb-2024"'
    
    results = processor.process_emails(
        folder_name="INBOX",
        criteria=criteria
    )
    
    processor.print_summary()
    return results

def example_unread_emails():
    """
    Process only unread emails
    """
    print("=== Unread Emails Example ===")
    
    email_address = "your-email@gmail.com"
    app_password = "your-app-password"
    
    processor = GmailProcessor(email_address, app_password)
    
    # Process only unread emails
    results = processor.process_emails(
        folder_name="INBOX",
        criteria="UNSEEN",
        max_emails=10
    )
    
    processor.print_summary()
    return results

def example_with_environment_config():
    """
    Example using environment configuration
    """
    print("=== Environment Config Example ===")
    
    try:
        # Load configuration from environment
        config = load_config()
        
        processor = GmailProcessor(
            config['email_address'], 
            config['password'],
            config.get('base_path')
        )
        
        results = processor.process_emails(
            folder_name=config.get('default_folder', 'INBOX'),
            max_emails=config.get('max_emails', 10)
        )
        
        processor.print_summary()
        return results
        
    except ValueError as e:
        print(f"Configuration error: {e}")
        print("Please set environment variables:")
        print("- GMAIL_EMAIL")
        print("- GMAIL_APP_PASSWORD")
        return []

def example_batch_processing():
    """
    Example of processing emails in batches
    """
    print("=== Batch Processing Example ===")
    
    email_address = "your-email@gmail.com"
    app_password = "your-app-password"
    
    processor = GmailProcessor(email_address, app_password)
    
    # Process emails in batches of 10
    batch_size = 10
    total_processed = 0
    start_from = 0
    
    while total_processed < 50:  # Process up to 50 emails total
        print(f"\nProcessing batch starting from email {start_from}")
        
        results = processor.process_emails(
            folder_name="INBOX",
            criteria="ALL",
            max_emails=batch_size,
            start_from=start_from
        )
        
        if not results:
            print("No more emails to process")
            break
        
        total_processed += len(results)
        start_from += batch_size
        
        print(f"Batch completed. Total processed: {total_processed}")
    
    processor.print_summary()

def example_error_handling():
    """
    Example with comprehensive error handling
    """
    print("=== Error Handling Example ===")
    
    email_address = "your-email@gmail.com"
    app_password = "your-app-password"
    
    try:
        processor = GmailProcessor(email_address, app_password)
        
        results = processor.process_emails(
            folder_name="INBOX",
            criteria="ALL",
            max_emails=5
        )
        
        # Analyze results
        successful = [r for r in results if r['success']]
        failed = [r for r in results if not r['success']]
        
        print(f"\nResults Summary:")
        print(f"Successful: {len(successful)}")
        print(f"Failed: {len(failed)}")
        
        if failed:
            print("\nFailed emails:")
            for result in failed:
                print(f"- Email {result['email_id']}: {result['error']}")
        
        processor.print_summary()
        
    except Exception as e:
        print(f"Processing error: {e}")
        print("Please check your credentials and internet connection")

def main():
    """
    Main function to run examples
    """
    print("Gmail Data Extraction System - Examples")
    print("=" * 50)
    
    examples = {
        '1': ('Basic Usage', example_basic_usage),
        '2': ('Advanced Usage', example_advanced_usage),
        '3': ('Specific Sender', example_specific_sender),
        '4': ('Date Range', example_date_range),
        '5': ('Unread Emails', example_unread_emails),
        '6': ('Environment Config', example_with_environment_config),
        '7': ('Batch Processing', example_batch_processing),
        '8': ('Error Handling', example_error_handling)
    }
    
    print("\nAvailable examples:")
    for key, (name, _) in examples.items():
        print(f"{key}. {name}")
    
    choice = input("\nSelect example to run (1-8): ").strip()
    
    if choice in examples:
        name, func = examples[choice]
        print(f"\nRunning: {name}")
        print("-" * 30)
        
        # Important: Update credentials before running
        print("\n⚠️  IMPORTANT: Update email credentials in the example functions before running!")
        print("Replace 'your-email@gmail.com' and 'your-app-password' with your actual credentials.")
        
        confirm = input("\nHave you updated the credentials? (y/N): ").strip().lower()
        if confirm == 'y':
            func()
        else:
            print("Please update credentials first and run again.")
    else:
        print("Invalid choice")

if __name__ == "__main__":
    main()
