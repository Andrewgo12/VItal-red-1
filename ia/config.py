"""
Configuration settings for Gmail Data Extraction System
"""

import os
from typing import Dict, Any, List

class GmailConfig:
    """
    Configuration class for Gmail processing
    """
    
    # Gmail IMAP settings
    IMAP_SERVER = "imap.gmail.com"
    IMAP_PORT = 993
    IMAP_USE_SSL = True
    
    # Processing settings
    DEFAULT_FOLDER = "INBOX"
    DEFAULT_SEARCH_CRITERIA = "ALL"
    MAX_EMAILS_PER_BATCH = 100
    PROCESSING_TIMEOUT = 300  # 5 minutes per email
    
    # File processing settings
    MAX_ATTACHMENT_SIZE = 50 * 1024 * 1024  # 50MB
    SUPPORTED_TEXT_FORMATS = [
        '.pdf', '.docx', '.doc', '.xlsx', '.xls', 
        '.txt', '.csv', '.log', '.rtf'
    ]
    SUPPORTED_IMAGE_FORMATS = [
        '.jpg', '.jpeg', '.png', '.bmp', '.tiff', 
        '.gif', '.webp', '.svg'
    ]
    
    # Text extraction settings
    OCR_LANGUAGE = 'eng'  # Tesseract language
    MAX_TEXT_LENGTH = 1000000  # 1MB of text
    HTML_TO_TEXT_OPTIONS = {
        'ignore_links': True,
        'ignore_images': True,
        'body_width': 0
    }
    
    # JSON output settings
    JSON_INDENT = 2
    JSON_ENSURE_ASCII = False
    SCHEMA_VERSION = "1.0"
    
    # Logging settings
    LOG_LEVEL = "INFO"
    LOG_FORMAT = "%(asctime)s - %(name)s - %(levelname)s - %(message)s"
    LOG_FILE = "gmail_processing.log"
    MAX_LOG_SIZE = 10 * 1024 * 1024  # 10MB
    LOG_BACKUP_COUNT = 5
    
    # Security settings
    MASK_EMAIL_IN_LOGS = True
    SECURE_DELETE_TEMP_FILES = True
    
    # Performance settings
    CONCURRENT_PROCESSING = False  # Set to True for parallel processing
    MAX_WORKERS = 4
    MEMORY_LIMIT_MB = 1024  # 1GB
    
    # Retry settings
    MAX_RETRIES = 3
    RETRY_DELAY = 5  # seconds
    EXPONENTIAL_BACKOFF = True
    
    @classmethod
    def from_env(cls) -> Dict[str, Any]:
        """
        Load configuration from environment variables
        
        Returns:
            Dict: Configuration dictionary
        """
        config = {}
        
        # Gmail settings
        config['email_address'] = os.getenv('GMAIL_EMAIL')
        config['password'] = os.getenv('GMAIL_PASSWORD') or os.getenv('GMAIL_APP_PASSWORD')
        config['imap_server'] = os.getenv('GMAIL_IMAP_SERVER', cls.IMAP_SERVER)
        config['imap_port'] = int(os.getenv('GMAIL_IMAP_PORT', cls.IMAP_PORT))
        
        # Processing settings
        config['default_folder'] = os.getenv('GMAIL_DEFAULT_FOLDER', cls.DEFAULT_FOLDER)
        config['max_emails'] = int(os.getenv('MAX_EMAILS_PER_BATCH', cls.MAX_EMAILS_PER_BATCH))
        config['processing_timeout'] = int(os.getenv('PROCESSING_TIMEOUT', cls.PROCESSING_TIMEOUT))
        
        # File settings
        config['max_attachment_size'] = int(os.getenv('MAX_ATTACHMENT_SIZE', cls.MAX_ATTACHMENT_SIZE))
        config['ocr_language'] = os.getenv('OCR_LANGUAGE', cls.OCR_LANGUAGE)
        
        # Paths
        config['base_path'] = os.getenv('GMAIL_BASE_PATH', os.getcwd())
        
        # Logging
        config['log_level'] = os.getenv('LOG_LEVEL', cls.LOG_LEVEL)
        config['log_file'] = os.getenv('LOG_FILE', cls.LOG_FILE)
        
        return config
    
    @classmethod
    def validate_config(cls, config: Dict[str, Any]) -> List[str]:
        """
        Validate configuration settings
        
        Args:
            config: Configuration dictionary
            
        Returns:
            List[str]: List of validation errors
        """
        errors = []
        
        # Required settings
        if not config.get('email_address'):
            errors.append("Gmail email address is required")
        
        if not config.get('password'):
            errors.append("Gmail app password is required")
        
        # Validate email format
        if config.get('email_address'):
            import re
            email_pattern = r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$'
            if not re.match(email_pattern, config['email_address']):
                errors.append("Invalid email address format")
        
        # Validate numeric settings
        numeric_settings = [
            ('imap_port', 1, 65535),
            ('max_emails', 1, 10000),
            ('processing_timeout', 10, 3600),
            ('max_attachment_size', 1024, 1024*1024*1024)  # 1KB to 1GB
        ]
        
        for setting, min_val, max_val in numeric_settings:
            if setting in config:
                try:
                    value = int(config[setting])
                    if not (min_val <= value <= max_val):
                        errors.append(f"{setting} must be between {min_val} and {max_val}")
                except ValueError:
                    errors.append(f"{setting} must be a valid integer")
        
        # Validate paths
        if config.get('base_path'):
            if not os.path.exists(config['base_path']):
                errors.append(f"Base path does not exist: {config['base_path']}")
        
        return errors

# Default configuration instance
default_config = GmailConfig()

# Environment-based configuration
def load_config() -> Dict[str, Any]:
    """
    Load configuration from environment or defaults
    
    Returns:
        Dict: Complete configuration
    """
    config = GmailConfig.from_env()
    
    # Validate configuration
    errors = GmailConfig.validate_config(config)
    if errors:
        raise ValueError(f"Configuration errors: {', '.join(errors)}")
    
    return config

# Configuration for different environments
DEVELOPMENT_CONFIG = {
    'log_level': 'DEBUG',
    'max_emails': 10,
    'concurrent_processing': False
}

PRODUCTION_CONFIG = {
    'log_level': 'INFO',
    'max_emails': 1000,
    'concurrent_processing': True,
    'max_workers': 8
}

TESTING_CONFIG = {
    'log_level': 'WARNING',
    'max_emails': 5,
    'processing_timeout': 60
}

class AdvancedConfig:
    """
    Advanced configuration options for the Gmail processing system
    """

    # Monitoring and performance settings
    ENABLE_PERFORMANCE_MONITORING = True
    MONITORING_INTERVAL = 5.0  # seconds
    PERFORMANCE_ALERT_THRESHOLDS = {
        'cpu_percent': 80.0,
        'memory_percent': 85.0,
        'disk_usage_percent': 90.0,
        'processing_time_per_email': 60.0
    }

    # Quality assurance settings
    ENABLE_QA_CHECKS = True
    QA_QUALITY_THRESHOLDS = {
        'min_text_extraction_rate': 0.8,
        'max_error_rate': 0.1,
        'min_metadata_completeness': 0.9,
        'min_attachment_success_rate': 0.95
    }

    # Backup and recovery settings
    ENABLE_AUTO_BACKUP = True
    BACKUP_INTERVAL_HOURS = 24
    BACKUP_RETENTION_DAYS = 30
    BACKUP_RETENTION_COUNT = 10
    INCREMENTAL_BACKUP_ENABLED = True

    # Security settings
    ENABLE_SECURITY_ANALYSIS = True
    QUARANTINE_HIGH_RISK_ATTACHMENTS = True
    QUARANTINE_PATH = "quarantine"
    VIRUS_SCAN_ENABLED = False  # Requires external antivirus

    # Advanced text processing
    ENABLE_LANGUAGE_DETECTION = True
    ENABLE_SENTIMENT_ANALYSIS = False  # Requires additional libraries
    ENABLE_TOPIC_EXTRACTION = False   # Requires additional libraries
    OCR_CONFIDENCE_THRESHOLD = 0.6

    # Database settings
    ENABLE_DATABASE_STORAGE = False  # Store metadata in database
    DATABASE_TYPE = 'sqlite'  # sqlite, postgresql, mysql
    DATABASE_PATH = 'gmail_data.db'

    # API and webhook settings
    ENABLE_WEBHOOKS = False
    WEBHOOK_ENDPOINTS = []
    API_RATE_LIMITING = True
    API_RATE_LIMIT_PER_MINUTE = 60

    # Parallel processing settings
    ENABLE_PARALLEL_PROCESSING = False
    MAX_WORKER_THREADS = 4
    THREAD_POOL_SIZE = 8
    PROCESS_POOL_SIZE = 2

    # Memory management
    MAX_MEMORY_USAGE_MB = 2048
    MEMORY_CLEANUP_INTERVAL = 300  # seconds
    LARGE_FILE_THRESHOLD_MB = 50
    STREAM_LARGE_FILES = True

    # Caching settings
    ENABLE_CACHING = True
    CACHE_TYPE = 'memory'  # memory, redis, file
    CACHE_TTL_SECONDS = 3600
    CACHE_MAX_SIZE_MB = 512

    # Notification settings
    ENABLE_NOTIFICATIONS = False
    NOTIFICATION_CHANNELS = ['email', 'slack', 'webhook']
    NOTIFICATION_EVENTS = ['processing_complete', 'error_occurred', 'backup_created']

    # Advanced logging
    STRUCTURED_LOGGING = True
    LOG_ROTATION_SIZE_MB = 100
    LOG_RETENTION_DAYS = 30
    ENABLE_AUDIT_LOGGING = True

    @classmethod
    def get_advanced_config(cls) -> Dict[str, Any]:
        """
        Get all advanced configuration options

        Returns:
            Dict: Advanced configuration dictionary
        """
        config = {}

        # Get all class attributes that are configuration options
        for attr_name in dir(cls):
            if not attr_name.startswith('_') and not callable(getattr(cls, attr_name)):
                if attr_name not in ['get_advanced_config']:
                    config[attr_name.lower()] = getattr(cls, attr_name)

        return config

    @classmethod
    def update_from_env(cls, config: Dict[str, Any]) -> Dict[str, Any]:
        """
        Update advanced configuration from environment variables

        Args:
            config: Base configuration dictionary

        Returns:
            Dict: Updated configuration
        """
        # Performance monitoring
        config['enable_performance_monitoring'] = os.getenv('ENABLE_PERFORMANCE_MONITORING', 'true').lower() == 'true'
        config['monitoring_interval'] = float(os.getenv('MONITORING_INTERVAL', cls.MONITORING_INTERVAL))

        # Quality assurance
        config['enable_qa_checks'] = os.getenv('ENABLE_QA_CHECKS', 'true').lower() == 'true'

        # Backup settings
        config['enable_auto_backup'] = os.getenv('ENABLE_AUTO_BACKUP', 'true').lower() == 'true'
        config['backup_interval_hours'] = int(os.getenv('BACKUP_INTERVAL_HOURS', cls.BACKUP_INTERVAL_HOURS))
        config['backup_retention_days'] = int(os.getenv('BACKUP_RETENTION_DAYS', cls.BACKUP_RETENTION_DAYS))

        # Security settings
        config['enable_security_analysis'] = os.getenv('ENABLE_SECURITY_ANALYSIS', 'true').lower() == 'true'
        config['quarantine_high_risk_attachments'] = os.getenv('QUARANTINE_HIGH_RISK_ATTACHMENTS', 'true').lower() == 'true'

        # Parallel processing
        config['enable_parallel_processing'] = os.getenv('ENABLE_PARALLEL_PROCESSING', 'false').lower() == 'true'
        config['max_worker_threads'] = int(os.getenv('MAX_WORKER_THREADS', cls.MAX_WORKER_THREADS))

        # Memory management
        config['max_memory_usage_mb'] = int(os.getenv('MAX_MEMORY_USAGE_MB', cls.MAX_MEMORY_USAGE_MB))

        return config

def load_complete_config() -> Dict[str, Any]:
    """
    Load complete configuration including advanced options

    Returns:
        Dict: Complete configuration
    """
    # Load base configuration
    base_config = load_config()

    # Load advanced configuration
    advanced_config = AdvancedConfig.get_advanced_config()

    # Update from environment
    advanced_config = AdvancedConfig.update_from_env(advanced_config)

    # Merge configurations
    complete_config = {**base_config, **advanced_config}

    return complete_config
