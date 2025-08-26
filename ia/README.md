# Gmail Data Extraction and Processing System - Enterprise Edition

A comprehensive, enterprise-grade Python-based system for extracting and processing Gmail data without using the Gmail API. This system provides unlimited Gmail data extraction capabilities using IMAP protocols with advanced monitoring, quality assurance, backup/recovery, and administration features.

## Features

### Core Processing Features
- **Complete Email Processing**: Extract metadata, body content, and attachments from Gmail messages
- **Multi-format Support**: Process PDF, Word, Excel, PowerPoint, RTF, CSV, images, and other file types
- **Advanced Text Extraction**: Extract text from all supported file formats including OCR for images
- **Enhanced JSON Output**: Convert all extracted content to structured JSON format with comprehensive analysis
- **Organized Storage**: Automatically organize extracted data in categorized folders with unique email IDs
- **Scalable Architecture**: Handle unlimited Gmail messages with robust error handling and retry mechanisms
- **No API Limits**: Uses IMAP protocol instead of Gmail API for unlimited access

### Enterprise Features
- **Performance Monitoring**: Real-time system performance tracking with alerts and thresholds
- **Quality Assurance**: Comprehensive data validation and quality scoring system
- **Backup & Recovery**: Automated backup system with incremental backups and point-in-time recovery
- **Security Analysis**: Advanced attachment security scanning and threat detection
- **Data Validation**: Multi-layer validation system ensuring data integrity and completeness
- **Administration Tools**: Web-based and CLI administration interface for system management
- **Parallel Processing**: Multi-threaded processing for improved performance on large datasets
- **Advanced Analytics**: Email pattern analysis, sentiment detection, and content categorization
- **Audit Logging**: Comprehensive audit trails and structured logging for compliance
- **Memory Management**: Intelligent memory usage optimization for large-scale processing

## Directory Structure

```
ia/
├── gmail_processor.py          # Main orchestration script
├── config.py                   # Configuration settings
├── requirements.txt            # Python dependencies
├── README.md                   # This file
├── Functions/                  # Core processing modules
│   ├── gmail_connector.py      # Gmail IMAP connection with advanced search
│   ├── metadata_extractor.py   # Enhanced metadata extraction with security analysis
│   ├── attachment_processor.py # Advanced attachment handling with security scanning
│   ├── text_extractor.py      # Multi-format text extraction with language detection
│   ├── json_converter.py      # Enhanced JSON conversion with quality metrics
│   ├── monitoring.py          # Performance monitoring and system health
│   ├── data_validator.py      # Data validation and quality assurance
│   └── backup_recovery.py     # Backup and recovery management
├── Archivos/                   # Document attachments storage
├── Imagenes/                   # Image attachments storage
├── Json/                       # Enhanced JSON output files with analysis
├── Text/                       # Extracted text files
├── backups/                    # System backups
├── logs/                       # System logs
├── reports/                    # Generated reports
├── quarantine/                 # Quarantined suspicious files
└── admin_tools.py             # Administration interface
```

## Installation

1. **Clone or download the system files**

2. **Install Python dependencies**:
   ```bash
   pip install -r requirements.txt
   ```

3. **Install additional system dependencies**:
   
   **For OCR (Tesseract)**:
   - Windows: Download from https://github.com/UB-Mannheim/tesseract/wiki
   - Linux: `sudo apt-get install tesseract-ocr`
   - macOS: `brew install tesseract`

4. **Set up Gmail App Password**:
   - Enable 2-factor authentication on your Gmail account
   - Generate an App Password: https://support.google.com/accounts/answer/185833
   - Use this App Password instead of your regular password

## Configuration

### Environment Variables (Recommended)

Create a `.env` file or set environment variables:

```bash
GMAIL_EMAIL=your-email@gmail.com
GMAIL_APP_PASSWORD=your-app-password
GMAIL_DEFAULT_FOLDER=INBOX
MAX_EMAILS_PER_BATCH=100
LOG_LEVEL=INFO
```

### Direct Configuration

Edit `config.py` to modify default settings:

```python
# Gmail settings
IMAP_SERVER = "imap.gmail.com"
IMAP_PORT = 993

# Processing settings
MAX_EMAILS_PER_BATCH = 100
PROCESSING_TIMEOUT = 300

# File processing
MAX_ATTACHMENT_SIZE = 50 * 1024 * 1024  # 50MB
```

## Usage

### Command Line Interface

```bash
# Basic usage
python gmail_processor.py your-email@gmail.com your-app-password

# Process specific folder
python gmail_processor.py your-email@gmail.com your-app-password --folder "Sent"

# Limit number of emails
python gmail_processor.py your-email@gmail.com your-app-password --max-emails 50

# Search criteria
python gmail_processor.py your-email@gmail.com your-app-password --criteria "FROM example@domain.com"

# Start from specific email index
python gmail_processor.py your-email@gmail.com your-app-password --start-from 100
```

### Python Script Usage

```python
from gmail_processor import GmailProcessor

# Initialize processor
processor = GmailProcessor("your-email@gmail.com", "your-app-password")

# Process emails
results = processor.process_emails(
    folder_name="INBOX",
    criteria="ALL",
    max_emails=100
)

# Print summary
processor.print_summary()
```

## Advanced Administration

### System Administration Interface

```bash
# Check system status
python admin_tools.py status

# Create full backup
python admin_tools.py backup --backup-type full

# Create incremental backup
python admin_tools.py backup --backup-type incremental

# Restore from backup
python admin_tools.py restore --backup-id backup_id_here

# System cleanup
python admin_tools.py cleanup

# Data validation
python admin_tools.py validate

# Generate comprehensive report
python admin_tools.py report --report-type comprehensive

# Start monitoring
python admin_tools.py monitor
```

### Advanced Processing Options

```python
from gmail_processor import GmailProcessor

# Initialize with monitoring
processor = GmailProcessor("email@gmail.com", "app-password")

# Process with retry mechanism
result = processor.process_with_retry("email_id", max_retries=5)

# Process with advanced filters
filters = {
    'folder': 'INBOX',
    'from_email': 'sender@example.com',
    'date_range': ('2024-01-01', '2024-12-31'),
    'has_attachments': True,
    'min_size': 1024,  # bytes
    'max_emails': 100
}
results = processor.process_emails_with_filters(filters)

# Analyze email patterns
patterns = processor.analyze_email_patterns()
print(f"Most active hours: {patterns['temporal_patterns']['most_active_hours']}")
```

### Quality Assurance and Validation

```python
from Functions.data_validator import QualityAssurance

qa = QualityAssurance()

# Run QA check on processed email
qa_result = qa.run_full_qa_check(email_data)
print(f"Quality score: {qa_result['quality_score']}")
print(f"Quality level: {qa_result['overall_quality']}")

# Get QA summary for all emails
summary = qa.get_qa_summary()
print(f"Average quality: {summary['average_quality_score']}")
```

### Backup and Recovery

```python
from Functions.backup_recovery import BackupManager, RecoveryManager

# Create backup manager
backup_mgr = BackupManager("path/to/ia")

# Create full backup
backup_result = backup_mgr.create_full_backup(include_attachments=True)
print(f"Backup created: {backup_result['backup_id']}")

# List all backups
backups = backup_mgr.list_backups()
for backup in backups:
    print(f"{backup['backup_id']}: {backup['size_mb']} MB")

# Recovery manager
recovery_mgr = RecoveryManager("path/to/ia")

# Recover corrupted email
recovery_result = recovery_mgr.recover_corrupted_email("email_unique_id")
if recovery_result['success']:
    print(f"Recovered from backup: {recovery_result['source_backup']}")
```

### Performance Monitoring

```python
from Functions.monitoring import PerformanceMonitor, ProcessingLogger

# Start performance monitoring
monitor = PerformanceMonitor()
monitor.start_monitoring(interval=5.0)

# Get performance summary
summary = monitor.get_performance_summary()
print(f"Average CPU: {summary['cpu']['average_percent']:.1f}%")
print(f"Peak memory: {summary['memory']['peak_percent']:.1f}%")

# Processing logger
logger = ProcessingLogger()
logger.log_email_processing_start("email_id", "unique_id")
logger.log_email_processing_complete("unique_id", 45.2, 3, True)

# Get processing statistics
stats = logger.get_processing_statistics()
print(f"Success rate: {stats['success_rate']:.1%}")
```

### Advanced Usage

```python
# Process specific date range
criteria = 'SINCE "01-Jan-2024" BEFORE "31-Dec-2024"'
results = processor.process_emails(criteria=criteria)

# Process only unread emails
results = processor.process_emails(criteria="UNSEEN")

# Process emails from specific sender
criteria = 'FROM "sender@example.com"'
results = processor.process_emails(criteria=criteria)
```

## Output Structure

For each processed email, the system creates:

### Folder Structure per Email
```
{unique_email_id}/
├── Json/
│   ├── email_data.json         # Complete email data
│   ├── metadata.json           # Email metadata only
│   ├── attachments.json        # Attachment information
│   ├── content.json           # Email content
│   └── statistics.json        # Processing statistics
├── Text/
│   └── extracted_text.txt     # All extracted text
├── Archivos/                  # Document attachments
└── Imagenes/                  # Image attachments
```

### JSON Schema

```json
{
  "email_info": {
    "unique_id": "email_hash",
    "processing_timestamp": "2024-01-01T12:00:00",
    "schema_version": "1.0"
  },
  "metadata": {
    "subject": "Email subject",
    "from": [{"name": "Sender Name", "email": "sender@example.com"}],
    "to": [{"name": "Recipient", "email": "recipient@example.com"}],
    "date": "2024-01-01T12:00:00",
    "has_attachments": true,
    "attachment_count": 2
  },
  "content": {
    "body": {
      "text": "Plain text content",
      "html": "HTML content"
    },
    "extracted_text": {
      "email_body": {...},
      "attachments": [...]
    }
  },
  "attachments": {
    "count": 2,
    "total_size_bytes": 1048576,
    "files": [...]
  },
  "statistics": {...}
}
```

## Supported File Formats

### Text Extraction
- **PDF**: PyPDF2, pdfplumber
- **Word**: python-docx (.docx, .doc)
- **Excel**: openpyxl (.xlsx, .xls)
- **Text**: Plain text files (.txt, .csv, .log)
- **Images**: OCR with Tesseract (.jpg, .png, .bmp, .tiff)

### Attachment Storage
- **Documents**: PDF, Word, Excel, PowerPoint, text files
- **Images**: JPEG, PNG, GIF, BMP, TIFF, WebP, SVG
- **Archives**: ZIP, RAR, 7Z, TAR
- **Other**: All other file types

## Search Criteria Examples

```python
# Date-based searches
"SINCE 01-Jan-2024"
"BEFORE 31-Dec-2024"
"ON 15-Jun-2024"

# Sender/recipient searches
'FROM "example@domain.com"'
'TO "recipient@domain.com"'
'CC "cc@domain.com"'

# Subject searches
'SUBJECT "Important"'

# Status searches
"UNSEEN"          # Unread emails
"SEEN"            # Read emails
"FLAGGED"         # Starred emails

# Size searches
"LARGER 1000000"  # Emails larger than 1MB
"SMALLER 100000"  # Emails smaller than 100KB

# Combined searches
'FROM "boss@company.com" SINCE 01-Jan-2024 SUBJECT "Report"'
```

## Error Handling

The system includes comprehensive error handling:

- **Connection errors**: Automatic retry with exponential backoff
- **Authentication errors**: Clear error messages and suggestions
- **File processing errors**: Continue processing other files
- **Memory management**: Automatic cleanup of large files
- **Logging**: Detailed logs for troubleshooting

## Performance Optimization

- **Batch processing**: Process emails in configurable batches
- **Memory management**: Efficient handling of large attachments
- **Concurrent processing**: Optional parallel processing
- **Progress tracking**: Real-time progress updates
- **Resume capability**: Start from specific email index

## Security Considerations

- **App Passwords**: Use Gmail App Passwords instead of regular passwords
- **Secure storage**: Extracted files are stored locally
- **Data privacy**: No data sent to external services
- **Temporary files**: Automatic cleanup of temporary files
- **Logging**: Option to mask sensitive information in logs

## Troubleshooting

### Common Issues

1. **Authentication Failed**
   - Ensure 2FA is enabled on Gmail
   - Use App Password, not regular password
   - Check email address format

2. **IMAP Not Enabled**
   - Enable IMAP in Gmail settings
   - Check firewall/antivirus blocking connections

3. **Missing Dependencies**
   - Install all requirements: `pip install -r requirements.txt`
   - Install Tesseract for OCR functionality

4. **Memory Issues**
   - Reduce `MAX_EMAILS_PER_BATCH` in config
   - Increase system memory or use smaller batches

5. **File Permission Errors**
   - Ensure write permissions to output directories
   - Run with appropriate user privileges

### Logging

Check the log file `gmail_processing.log` for detailed error information:

```bash
tail -f gmail_processing.log
```

## License

This project is provided as-is for educational and personal use. Please ensure compliance with Gmail's Terms of Service and applicable data protection regulations.

## Support

For issues and questions:
1. Check the troubleshooting section
2. Review log files for error details
3. Ensure all dependencies are properly installed
4. Verify Gmail account settings and permissions
