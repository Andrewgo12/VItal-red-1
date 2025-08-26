"""
Gmail Connector Module
Handles IMAP connection to Gmail without using Gmail API
Provides unlimited Gmail data extraction capabilities
"""

import imaplib
import email
import ssl
import logging
from typing import List, Dict, Any, Optional, Tuple
from email.header import decode_header
from email.utils import parsedate_tz, mktime_tz
from email.message import Message
import datetime
import hashlib
import os

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class GmailConnector:
    """
    Gmail IMAP connector for unlimited email extraction
    """
    
    def __init__(self, email_address: str, password: str, imap_server: str = "imap.gmail.com", port: int = 993):
        """
        Initialize Gmail connector
        
        Args:
            email_address: Gmail email address
            password: App password (not regular password)
            imap_server: IMAP server address
            port: IMAP port (993 for SSL)
        """
        self.email_address = email_address
        self.password = password
        self.imap_server = imap_server
        self.port = port
        self.connection = None
        
    def connect(self) -> bool:
        """
        Establish IMAP connection to Gmail
        
        Returns:
            bool: True if connection successful, False otherwise
        """
        try:
            # Create SSL context
            context = ssl.create_default_context()
            
            # Connect to server
            self.connection = imaplib.IMAP4_SSL(self.imap_server, self.port, ssl_context=context)
            
            # Login
            self.connection.login(self.email_address, self.password)
            
            logger.info(f"Successfully connected to Gmail for {self.email_address}")
            return True
            
        except Exception as e:
            logger.error(f"Failed to connect to Gmail: {str(e)}")
            return False
    
    def disconnect(self):
        """
        Close IMAP connection
        """
        if self.connection:
            try:
                self.connection.close()
                self.connection.logout()
                logger.info("Disconnected from Gmail")
            except Exception as e:
                logger.error(f"Error disconnecting: {str(e)}")
    
    def list_folders(self) -> List[str]:
        """
        List all available folders/labels
        
        Returns:
            List[str]: List of folder names
        """
        if not self.connection:
            logger.error("No active connection")
            return []
        
        try:
            status, folders = self.connection.list()
            folder_list = []
            
            for folder in folders:
                # Decode folder name
                folder_name = folder.decode().split('"')[-2]
                folder_list.append(folder_name)
            
            return folder_list
            
        except Exception as e:
            logger.error(f"Error listing folders: {str(e)}")
            return []
    
    def select_folder(self, folder_name: str = "INBOX") -> bool:
        """
        Select a folder to work with
        
        Args:
            folder_name: Name of the folder to select
            
        Returns:
            bool: True if successful, False otherwise
        """
        if not self.connection:
            logger.error("No active connection")
            return False
        
        try:
            status, messages = self.connection.select(folder_name)
            if status == 'OK':
                logger.info(f"Selected folder: {folder_name}")
                return True
            else:
                logger.error(f"Failed to select folder: {folder_name}")
                return False
                
        except Exception as e:
            logger.error(f"Error selecting folder: {str(e)}")
            return False
    
    def search_emails(self, criteria: str = "ALL", folder: str = "INBOX") -> List[str]:
        """
        Search for emails based on criteria

        Args:
            criteria: Search criteria (e.g., "ALL", "UNSEEN", "FROM example@gmail.com")
            folder: Email folder to search in (default: "INBOX")

        Returns:
            List[str]: List of email UIDs
        """
        if not self.connection:
            logger.error("No active connection")
            return []

        try:
            # Select the folder first
            status, messages = self.connection.select(folder)
            if status != 'OK':
                logger.error(f"Failed to select folder: {folder}")
                return []

            # Search for emails
            status, messages = self.connection.search(None, criteria)
            if status == 'OK':
                email_ids = messages[0].split()
                return [uid.decode() for uid in email_ids]
            else:
                logger.error(f"Search failed with criteria: {criteria}")
                return []

        except Exception as e:
            logger.error(f"Error searching emails: {str(e)}")
            return []
    
    def fetch_email(self, email_id: str) -> Optional[Message]:
        """
        Fetch a specific email by ID
        
        Args:
            email_id: Email UID
            
        Returns:
            EmailMessage object or None if failed
        """
        if not self.connection:
            logger.error("No active connection")
            return None
        
        try:
            status, msg_data = self.connection.fetch(email_id, '(RFC822)')
            if status == 'OK':
                email_body = msg_data[0][1]
                email_message = email.message_from_bytes(email_body)
                return email_message
            else:
                logger.error(f"Failed to fetch email {email_id}")
                return None
                
        except Exception as e:
            logger.error(f"Error fetching email {email_id}: {str(e)}")
            return None
    
    def generate_unique_id(self, email_message: Message) -> str:
        """
        Generate unique ID for email message
        
        Args:
            email_message: Email message object
            
        Returns:
            str: Unique ID for the email
        """
        # Combine message-id, subject, and date for uniqueness
        message_id = email_message.get('Message-ID', '')
        subject = email_message.get('Subject', '')
        date = email_message.get('Date', '')
        
        # Create hash from combined data
        combined = f"{message_id}{subject}{date}"
        unique_id = hashlib.md5(combined.encode()).hexdigest()
        
        return unique_id
    
    def get_email_count(self, folder_name: str = "INBOX") -> int:
        """
        Get total number of emails in folder
        
        Args:
            folder_name: Folder to count emails in
            
        Returns:
            int: Number of emails
        """
        if not self.select_folder(folder_name):
            return 0
        
        try:
            status, messages = self.connection.search(None, "ALL")
            if status == 'OK':
                return len(messages[0].split())
            return 0
            
        except Exception as e:
            logger.error(f"Error counting emails: {str(e)}")
            return 0

    def __enter__(self):
        """Context manager entry"""
        if self.connect():
            return self
        else:
            raise ConnectionError("Failed to connect to Gmail")
    
    def __exit__(self, exc_type, exc_val, exc_tb):
        """Context manager exit"""
        self.disconnect()

    def get_folder_status(self, folder_name: str = "INBOX") -> Dict[str, Any]:
        """
        Get detailed folder status and statistics

        Args:
            folder_name: Folder to analyze

        Returns:
            Dict: Folder status information
        """
        if not self.select_folder(folder_name):
            return {}

        try:
            # Get folder status
            status, data = self.connection.status(folder_name, '(MESSAGES RECENT UIDNEXT UIDVALIDITY UNSEEN)')

            folder_info = {
                'folder_name': folder_name,
                'total_messages': 0,
                'recent_messages': 0,
                'unseen_messages': 0,
                'uid_next': 0,
                'uid_validity': 0
            }

            if status == 'OK' and data:
                # Parse status response
                status_str = data[0].decode()
                import re

                messages_match = re.search(r'MESSAGES (\d+)', status_str)
                if messages_match:
                    folder_info['total_messages'] = int(messages_match.group(1))

                recent_match = re.search(r'RECENT (\d+)', status_str)
                if recent_match:
                    folder_info['recent_messages'] = int(recent_match.group(1))

                unseen_match = re.search(r'UNSEEN (\d+)', status_str)
                if unseen_match:
                    folder_info['unseen_messages'] = int(unseen_match.group(1))

                uidnext_match = re.search(r'UIDNEXT (\d+)', status_str)
                if uidnext_match:
                    folder_info['uid_next'] = int(uidnext_match.group(1))

                uidvalidity_match = re.search(r'UIDVALIDITY (\d+)', status_str)
                if uidvalidity_match:
                    folder_info['uid_validity'] = int(uidvalidity_match.group(1))

            return folder_info

        except Exception as e:
            logger.error(f"Error getting folder status: {str(e)}")
            return {}

    def get_email_flags(self, email_id: str) -> List[str]:
        """
        Get flags for a specific email

        Args:
            email_id: Email UID

        Returns:
            List[str]: List of email flags
        """
        if not self.connection:
            return []

        try:
            status, data = self.connection.fetch(email_id, '(FLAGS)')
            if status == 'OK' and data:
                flags_str = data[0].decode()
                import re
                flags_match = re.search(r'FLAGS \(([^)]*)\)', flags_str)
                if flags_match:
                    flags = flags_match.group(1).split()
                    return [flag.strip('\\') for flag in flags]
            return []

        except Exception as e:
            logger.error(f"Error getting email flags: {str(e)}")
            return []

    def mark_as_read(self, email_id: str) -> bool:
        """
        Mark email as read

        Args:
            email_id: Email UID

        Returns:
            bool: True if successful
        """
        if not self.connection:
            return False

        try:
            self.connection.store(email_id, '+FLAGS', '\\Seen')
            return True
        except Exception as e:
            logger.error(f"Error marking email as read: {str(e)}")
            return False

    def get_email_size(self, email_id: str) -> int:
        """
        Get email size in bytes

        Args:
            email_id: Email UID

        Returns:
            int: Email size in bytes
        """
        if not self.connection:
            return 0

        try:
            status, data = self.connection.fetch(email_id, '(RFC822.SIZE)')
            if status == 'OK' and data:
                size_str = data[0].decode()
                import re
                size_match = re.search(r'RFC822\.SIZE (\d+)', size_str)
                if size_match:
                    return int(size_match.group(1))
            return 0

        except Exception as e:
            logger.error(f"Error getting email size: {str(e)}")
            return 0

    def search_emails_advanced(self, criteria: Dict[str, Any]) -> List[str]:
        """
        Advanced email search with multiple criteria

        Args:
            criteria: Dictionary with search parameters
                     - from_email: sender email
                     - to_email: recipient email
                     - subject: subject contains
                     - since_date: date since (YYYY-MM-DD)
                     - before_date: date before (YYYY-MM-DD)
                     - has_attachment: boolean
                     - is_unread: boolean
                     - larger_than: size in bytes
                     - smaller_than: size in bytes

        Returns:
            List[str]: List of email UIDs
        """
        search_parts = []

        if criteria.get('from_email'):
            search_parts.append(f'FROM "{criteria["from_email"]}"')

        if criteria.get('to_email'):
            search_parts.append(f'TO "{criteria["to_email"]}"')

        if criteria.get('subject'):
            search_parts.append(f'SUBJECT "{criteria["subject"]}"')

        if criteria.get('since_date'):
            # Convert YYYY-MM-DD to DD-Mon-YYYY
            from datetime import datetime
            date_obj = datetime.strptime(criteria['since_date'], '%Y-%m-%d')
            formatted_date = date_obj.strftime('%d-%b-%Y')
            search_parts.append(f'SINCE "{formatted_date}"')

        if criteria.get('before_date'):
            from datetime import datetime
            date_obj = datetime.strptime(criteria['before_date'], '%Y-%m-%d')
            formatted_date = date_obj.strftime('%d-%b-%Y')
            search_parts.append(f'BEFORE "{formatted_date}"')

        if criteria.get('has_attachment'):
            search_parts.append('HAS ATTACHMENT')

        if criteria.get('is_unread'):
            search_parts.append('UNSEEN')

        if criteria.get('larger_than'):
            search_parts.append(f'LARGER {criteria["larger_than"]}')

        if criteria.get('smaller_than'):
            search_parts.append(f'SMALLER {criteria["smaller_than"]}')

        # Combine all criteria
        search_criteria = ' '.join(search_parts) if search_parts else 'ALL'

        return self.search_emails(search_criteria)
