"""
Email Metadata Extraction Module
Extracts comprehensive metadata from email messages
"""

import email
import re
from typing import Dict, List, Any, Optional
from email.header import decode_header
from email.utils import parsedate_tz, mktime_tz, parseaddr
from email.message import Message
import datetime
import logging

logger = logging.getLogger(__name__)

class MetadataExtractor:
    """
    Extracts metadata from email messages
    """
    
    @staticmethod
    def decode_mime_words(text) -> str:
        """
        Decode MIME encoded words in headers with robust error handling

        Args:
            text: Text that may contain MIME encoded words (can be str, bytes, or Header object)

        Returns:
            str: Decoded text
        """
        if not text:
            return ""

        # Handle different input types robustly
        try:
            # Convert Header objects to string
            if hasattr(text, '__str__'):
                text_str = str(text)
            elif isinstance(text, bytes):
                text_str = text.decode('utf-8', errors='replace')
            else:
                text_str = str(text)
        except Exception as e:
            logger.warning(f"Error converting text to string: {e}")
            return ""

        try:
            decoded_fragments = decode_header(text_str)
            decoded_string = ""

            for fragment, encoding in decoded_fragments:
                if isinstance(fragment, bytes):
                    if encoding and encoding.lower() not in ['unknown-8bit', 'unknown']:
                        try:
                            decoded_string += fragment.decode(encoding)
                        except (UnicodeDecodeError, LookupError):
                            # Fallback to utf-8 with error handling
                            decoded_string += fragment.decode('utf-8', errors='replace')
                    else:
                        # Try common encodings for unknown or problematic encodings
                        for enc in ['utf-8', 'latin-1', 'ascii', 'cp1252']:
                            try:
                                decoded_string += fragment.decode(enc)
                                break
                            except UnicodeDecodeError:
                                continue
                        else:
                            # Last resort: decode with replacement
                            decoded_string += fragment.decode('utf-8', errors='replace')
                else:
                    decoded_string += str(fragment)

            return decoded_string.strip()

        except Exception as e:
            logger.warning(f"Error decoding MIME words: {str(e)}")
            # Return the original text as string if all else fails
            try:
                return str(text).strip()
            except:
                return ""
    
    @staticmethod
    def parse_email_addresses(address_string: str) -> List[Dict[str, str]]:
        """
        Parse email addresses from header string
        
        Args:
            address_string: String containing email addresses
            
        Returns:
            List[Dict]: List of parsed email addresses with name and email
        """
        if not address_string:
            return []
        
        addresses = []
        
        # Split by comma and parse each address
        for addr in address_string.split(','):
            addr = addr.strip()
            if addr:
                try:
                    name, email_addr = parseaddr(addr)
                    addresses.append({
                        'name': MetadataExtractor.decode_mime_words(name) if name else "",
                        'email': email_addr.strip() if email_addr else ""
                    })
                except Exception as e:
                    logger.warning(f"Error parsing address '{addr}': {str(e)}")
                    addresses.append({
                        'name': "",
                        'email': addr
                    })
        
        return addresses
    
    @staticmethod
    def parse_date(date_string: str) -> Optional[datetime.datetime]:
        """
        Parse email date string to datetime object
        
        Args:
            date_string: Date string from email header
            
        Returns:
            datetime object or None if parsing fails
        """
        if not date_string:
            return None
        
        try:
            # Parse date with timezone
            date_tuple = parsedate_tz(date_string)
            if date_tuple:
                timestamp = mktime_tz(date_tuple)
                return datetime.datetime.fromtimestamp(timestamp)
            return None
            
        except Exception as e:
            logger.warning(f"Error parsing date '{date_string}': {str(e)}")
            return None
    
    @staticmethod
    def extract_metadata(email_message: Message, unique_id: str) -> Dict[str, Any]:
        """
        Extract comprehensive metadata from email message with robust error handling

        Args:
            email_message: Email message object
            unique_id: Unique identifier for the email

        Returns:
            Dict: Comprehensive metadata dictionary
        """
        try:
            # Initialize metadata with safe defaults
            metadata = {
                'unique_id': unique_id,
                'message_id': '',
                'subject': '',
                'from': [],
                'to': [],
                'cc': [],
                'bcc': [],
                'reply_to': [],
                'date': None,
                'date_string': '',
                'received_date': None,
                'thread_topic': '',
                'thread_index': '',
                'in_reply_to': '',
                'references': '',
                'priority': '',
                'importance': '',
                'sensitivity': '',
                'content_type': 'text/plain',
                'charset': None,
                'size': 0,
                'has_attachments': False,
                'attachment_count': 0,
                'attachment_names': [],
                'is_multipart': False,
                'headers': {},
                'extraction_timestamp': datetime.datetime.now().isoformat(),
                'processing_errors': []
            }

            # Safely extract basic fields
            try:
                metadata['message_id'] = str(email_message.get('Message-ID', ''))
            except Exception as e:
                metadata['processing_errors'].append(f"Error extracting message_id: {str(e)}")

            try:
                metadata['subject'] = MetadataExtractor.decode_mime_words(email_message.get('Subject', ''))
            except Exception as e:
                metadata['processing_errors'].append(f"Error extracting subject: {str(e)}")

            try:
                metadata['from'] = MetadataExtractor.parse_email_addresses(email_message.get('From', ''))
            except Exception as e:
                metadata['processing_errors'].append(f"Error extracting from: {str(e)}")

            try:
                metadata['to'] = MetadataExtractor.parse_email_addresses(email_message.get('To', ''))
            except Exception as e:
                metadata['processing_errors'].append(f"Error extracting to: {str(e)}")

            try:
                metadata['cc'] = MetadataExtractor.parse_email_addresses(email_message.get('Cc', ''))
            except Exception as e:
                metadata['processing_errors'].append(f"Error extracting cc: {str(e)}")

            try:
                metadata['bcc'] = MetadataExtractor.parse_email_addresses(email_message.get('Bcc', ''))
            except Exception as e:
                metadata['processing_errors'].append(f"Error extracting bcc: {str(e)}")

            try:
                metadata['reply_to'] = MetadataExtractor.parse_email_addresses(email_message.get('Reply-To', ''))
            except Exception as e:
                metadata['processing_errors'].append(f"Error extracting reply_to: {str(e)}")

            try:
                metadata['date_string'] = str(email_message.get('Date', ''))
                metadata['date'] = MetadataExtractor.parse_date(metadata['date_string'])
                if metadata['date']:
                    metadata['date'] = metadata['date'].isoformat()
            except Exception as e:
                metadata['processing_errors'].append(f"Error extracting date: {str(e)}")

            try:
                metadata['thread_topic'] = MetadataExtractor.decode_mime_words(email_message.get('Thread-Topic', ''))
            except Exception as e:
                metadata['processing_errors'].append(f"Error extracting thread_topic: {str(e)}")

            try:
                metadata['thread_index'] = str(email_message.get('Thread-Index', ''))
                metadata['in_reply_to'] = str(email_message.get('In-Reply-To', ''))
                metadata['references'] = str(email_message.get('References', ''))
                metadata['priority'] = str(email_message.get('X-Priority', ''))
                metadata['importance'] = str(email_message.get('Importance', ''))
                metadata['sensitivity'] = str(email_message.get('Sensitivity', ''))
            except Exception as e:
                metadata['processing_errors'].append(f"Error extracting thread/priority info: {str(e)}")

            try:
                metadata['content_type'] = email_message.get_content_type() or 'text/plain'
                metadata['charset'] = email_message.get_content_charset()
                metadata['size'] = len(str(email_message))
                metadata['is_multipart'] = email_message.is_multipart()
            except Exception as e:
                metadata['processing_errors'].append(f"Error extracting content info: {str(e)}")

            # Extract all headers safely
            try:
                for header_name, header_value in email_message.items():
                    try:
                        # Safely decode header value
                        decoded_value = MetadataExtractor.decode_mime_words(header_value)
                        metadata['headers'][str(header_name)] = decoded_value
                    except Exception as e:
                        metadata['processing_errors'].append(f"Error extracting header {header_name}: {str(e)}")
                        # Store raw value as fallback
                        try:
                            metadata['headers'][str(header_name)] = str(header_value)
                        except:
                            metadata['headers'][str(header_name)] = "[Unable to decode]"
            except Exception as e:
                metadata['processing_errors'].append(f"Error extracting headers: {str(e)}")

            # Check for attachments safely
            try:
                if metadata['is_multipart']:
                    attachment_count = 0
                    attachment_names = []

                    for part in email_message.walk():
                        try:
                            if part.get_content_disposition() == 'attachment':
                                attachment_count += 1
                                filename = part.get_filename()
                                if filename:
                                    try:
                                        decoded_filename = MetadataExtractor.decode_mime_words(filename)
                                        attachment_names.append(decoded_filename)
                                    except Exception as e:
                                        attachment_names.append(str(filename))
                                        metadata['processing_errors'].append(f"Error decoding filename: {str(e)}")
                                else:
                                    attachment_names.append(f"unnamed_attachment_{attachment_count}")
                        except Exception as e:
                            metadata['processing_errors'].append(f"Error processing attachment part: {str(e)}")

                    metadata['has_attachments'] = attachment_count > 0
                    metadata['attachment_count'] = attachment_count
                    metadata['attachment_names'] = attachment_names
            except Exception as e:
                metadata['processing_errors'].append(f"Error checking attachments: {str(e)}")

            # Extract received headers for tracking
            try:
                received_headers = []
                for header in email_message.get_all('Received') or []:
                    try:
                        received_headers.append(str(header))
                    except Exception as e:
                        metadata['processing_errors'].append(f"Error processing received header: {str(e)}")
                metadata['received_headers'] = received_headers
            except Exception as e:
                metadata['processing_errors'].append(f"Error extracting received headers: {str(e)}")
                metadata['received_headers'] = []

            # Extract X-headers for additional metadata
            try:
                x_headers = {}
                for header_name, header_value in email_message.items():
                    try:
                        if str(header_name).startswith('X-'):
                            x_headers[str(header_name)] = MetadataExtractor.decode_mime_words(header_value)
                    except Exception as e:
                        metadata['processing_errors'].append(f"Error processing X-header {header_name}: {str(e)}")
                metadata['x_headers'] = x_headers
            except Exception as e:
                metadata['processing_errors'].append(f"Error extracting X-headers: {str(e)}")
                metadata['x_headers'] = {}

            # Extract delivery and read receipts
            try:
                metadata['delivery_receipt'] = str(email_message.get('Disposition-Notification-To', ''))
                metadata['read_receipt'] = str(email_message.get('Return-Receipt-To', ''))
            except Exception as e:
                metadata['processing_errors'].append(f"Error extracting receipt info: {str(e)}")
                metadata['delivery_receipt'] = ''
                metadata['read_receipt'] = ''

            # Extract enhanced information with error handling
            try:
                metadata['thread_info'] = MetadataExtractor.extract_email_thread_info(email_message)
            except Exception as e:
                metadata['processing_errors'].append(f"Error extracting thread info: {str(e)}")
                metadata['thread_info'] = {}

            try:
                metadata['security_info'] = MetadataExtractor.extract_security_headers(email_message)
            except Exception as e:
                metadata['processing_errors'].append(f"Error extracting security info: {str(e)}")
                metadata['security_info'] = {}

            try:
                metadata['routing_info'] = MetadataExtractor.extract_routing_info(email_message)
            except Exception as e:
                metadata['processing_errors'].append(f"Error extracting routing info: {str(e)}")
                metadata['routing_info'] = {}

            try:
                metadata['content_analysis'] = MetadataExtractor.analyze_email_content_type(email_message)
            except Exception as e:
                metadata['processing_errors'].append(f"Error analyzing content type: {str(e)}")
                metadata['content_analysis'] = {}

            try:
                metadata['priority_info'] = MetadataExtractor.extract_priority_and_importance(email_message)
            except Exception as e:
                metadata['processing_errors'].append(f"Error extracting priority info: {str(e)}")
                metadata['priority_info'] = {}

            # Add body preview
            try:
                metadata['body_preview'] = MetadataExtractor.extract_body_preview(email_message, 300)
            except Exception as e:
                metadata['processing_errors'].append(f"Error extracting body preview: {str(e)}")
                metadata['body_preview'] = ""


            # Calculate content statistics
            try:
                metadata['content_stats'] = {
                    'total_recipients': len(metadata['to']) + len(metadata['cc']) + len(metadata['bcc']),
                    'subject_length': len(metadata['subject']),
                    'has_thread_info': bool(metadata.get('thread_info', {}).get('in_reply_to') or
                                          metadata.get('thread_info', {}).get('references')),
                    'estimated_read_time': max(1, len(metadata['body_preview'].split()) // 200)  # words per minute
                }
            except Exception as e:
                metadata['processing_errors'].append(f"Error calculating content stats: {str(e)}")
                metadata['content_stats'] = {
                    'total_recipients': 0,
                    'subject_length': 0,
                    'has_thread_info': False,
                    'estimated_read_time': 1
                }

            # Legacy fields for backward compatibility
            try:
                security_info = metadata.get('security_info', {})
                metadata['authentication_results'] = security_info.get('authentication_results', '')
                metadata['dkim_signature'] = security_info.get('dkim_signature', '')
                metadata['spf_result'] = security_info.get('spf_result', '')
            except Exception as e:
                metadata['processing_errors'].append(f"Error setting legacy fields: {str(e)}")
                metadata['authentication_results'] = ''
                metadata['dkim_signature'] = ''
                metadata['spf_result'] = ''

            return metadata

        except Exception as e:
            # If everything fails, return minimal metadata
            logger.error(f"Critical error in extract_metadata: {str(e)}")
            return {
                'unique_id': unique_id,
                'message_id': '',
                'subject': '[Error extracting subject]',
                'from': [],
                'to': [],
                'cc': [],
                'bcc': [],
                'date': None,
                'content_type': 'text/plain',
                'size': 0,
                'has_attachments': False,
                'attachment_count': 0,
                'attachment_names': [],
                'is_multipart': False,
                'headers': {},
                'extraction_timestamp': datetime.datetime.now().isoformat(),
                'processing_errors': [f"Critical extraction error: {str(e)}"],
                'extraction_status': 'failed'
            }
    
    @staticmethod
    def extract_body_preview(email_message: Message, max_length: int = 200) -> str:
        """
        Extract a preview of the email body
        
        Args:
            email_message: Email message object
            max_length: Maximum length of preview
            
        Returns:
            str: Body preview
        """
        try:
            body = ""
            
            if email_message.is_multipart():
                for part in email_message.walk():
                    if part.get_content_type() == "text/plain":
                        body = part.get_payload(decode=True)
                        if isinstance(body, bytes):
                            body = body.decode('utf-8', errors='ignore')
                        break
            else:
                if email_message.get_content_type() == "text/plain":
                    body = email_message.get_payload(decode=True)
                    if isinstance(body, bytes):
                        body = body.decode('utf-8', errors='ignore')
            
            # Clean and truncate
            body = re.sub(r'\s+', ' ', body.strip())
            if len(body) > max_length:
                body = body[:max_length] + "..."
            
            return body
            
        except Exception as e:
            logger.warning(f"Error extracting body preview: {str(e)}")
            return ""

    @staticmethod
    def extract_email_thread_info(email_message: Message) -> Dict[str, Any]:
        """
        Extract email thread information

        Args:
            email_message: Email message object

        Returns:
            Dict: Thread information
        """
        thread_info = {
            'message_id': email_message.get('Message-ID', ''),
            'in_reply_to': email_message.get('In-Reply-To', ''),
            'references': [],
            'thread_topic': MetadataExtractor.decode_mime_words(email_message.get('Thread-Topic', '')),
            'thread_index': email_message.get('Thread-Index', ''),
            'is_reply': False,
            'is_forward': False
        }

        # Parse references header
        references = email_message.get('References', '')
        if references:
            # Split by whitespace and clean up
            thread_info['references'] = [ref.strip('<>') for ref in references.split() if ref.strip()]

        # Determine if it's a reply
        thread_info['is_reply'] = bool(thread_info['in_reply_to'] or thread_info['references'])

        # Check if it's a forward (basic heuristic)
        subject = email_message.get('Subject', '').lower()
        thread_info['is_forward'] = any(indicator in subject for indicator in ['fwd:', 'fw:', 'forward:'])

        return thread_info

    @staticmethod
    def extract_security_headers(email_message: Message) -> Dict[str, Any]:
        """
        Extract email security and authentication headers

        Args:
            email_message: Email message object

        Returns:
            Dict: Security information
        """
        security_info = {
            'spf_result': '',
            'dkim_signature': '',
            'dmarc_result': '',
            'authentication_results': '',
            'received_spf': '',
            'arc_authentication_results': [],
            'message_security': 'unknown'
        }

        # SPF (Sender Policy Framework)
        security_info['spf_result'] = email_message.get('Received-SPF', '')

        # DKIM (DomainKeys Identified Mail)
        security_info['dkim_signature'] = email_message.get('DKIM-Signature', '')

        # DMARC and general authentication results
        security_info['authentication_results'] = email_message.get('Authentication-Results', '')

        # ARC (Authenticated Received Chain)
        arc_results = email_message.get_all('ARC-Authentication-Results') or []
        security_info['arc_authentication_results'] = [result for result in arc_results]

        # Analyze overall security status
        auth_results = security_info['authentication_results'].lower()
        if 'pass' in auth_results and 'dkim=pass' in auth_results:
            security_info['message_security'] = 'high'
        elif 'pass' in auth_results:
            security_info['message_security'] = 'medium'
        elif 'fail' in auth_results or 'none' in auth_results:
            security_info['message_security'] = 'low'

        return security_info

    @staticmethod
    def extract_routing_info(email_message: Message) -> Dict[str, Any]:
        """
        Extract email routing and delivery information

        Args:
            email_message: Email message object

        Returns:
            Dict: Routing information
        """
        routing_info = {
            'received_headers': [],
            'delivery_path': [],
            'total_hops': 0,
            'delivery_delay': None,
            'original_sender_ip': '',
            'final_recipient': ''
        }

        # Get all Received headers
        received_headers = email_message.get_all('Received') or []
        routing_info['received_headers'] = received_headers
        routing_info['total_hops'] = len(received_headers)

        # Parse delivery path
        import re
        for received in received_headers:
            # Extract server information from Received headers
            server_match = re.search(r'from\s+([^\s]+)', received)
            if server_match:
                routing_info['delivery_path'].append(server_match.group(1))

        # Extract original sender IP (from first Received header)
        if received_headers:
            first_received = received_headers[-1]  # Last in list is first received
            ip_match = re.search(r'\[(\d+\.\d+\.\d+\.\d+)\]', first_received)
            if ip_match:
                routing_info['original_sender_ip'] = ip_match.group(1)

        return routing_info

    @staticmethod
    def analyze_email_content_type(email_message: Message) -> Dict[str, Any]:
        """
        Analyze email content structure and types

        Args:
            email_message: Email message object

        Returns:
            Dict: Content analysis
        """
        content_analysis = {
            'is_multipart': email_message.is_multipart(),
            'main_content_type': email_message.get_content_type(),
            'charset': email_message.get_content_charset(),
            'content_parts': [],
            'has_html': False,
            'has_plain_text': False,
            'has_attachments': False,
            'has_inline_images': False,
            'total_parts': 0
        }

        if email_message.is_multipart():
            for part in email_message.walk():
                part_info = {
                    'content_type': part.get_content_type(),
                    'charset': part.get_content_charset(),
                    'disposition': part.get_content_disposition(),
                    'filename': part.get_filename(),
                    'size': len(part.get_payload(decode=False)) if part.get_payload() else 0
                }

                content_analysis['content_parts'].append(part_info)
                content_analysis['total_parts'] += 1

                # Analyze content types
                if part.get_content_type() == 'text/html':
                    content_analysis['has_html'] = True
                elif part.get_content_type() == 'text/plain':
                    content_analysis['has_plain_text'] = True

                # Check for attachments and inline content
                if part.get_content_disposition() == 'attachment':
                    content_analysis['has_attachments'] = True
                elif part.get_content_disposition() == 'inline' and part.get_filename():
                    content_analysis['has_inline_images'] = True
        else:
            content_analysis['total_parts'] = 1
            if email_message.get_content_type() == 'text/html':
                content_analysis['has_html'] = True
            elif email_message.get_content_type() == 'text/plain':
                content_analysis['has_plain_text'] = True

        return content_analysis

    @staticmethod
    def extract_priority_and_importance(email_message: Message) -> Dict[str, Any]:
        """
        Extract email priority and importance indicators

        Args:
            email_message: Email message object

        Returns:
            Dict: Priority information
        """
        priority_info = {
            'priority': email_message.get('X-Priority', ''),
            'importance': email_message.get('Importance', ''),
            'sensitivity': email_message.get('Sensitivity', ''),
            'precedence': email_message.get('Precedence', ''),
            'urgency': 'normal',
            'delivery_receipt_requested': False,
            'read_receipt_requested': False
        }

        # Check for delivery and read receipts
        priority_info['delivery_receipt_requested'] = bool(
            email_message.get('Disposition-Notification-To') or
            email_message.get('Return-Receipt-To')
        )
        priority_info['read_receipt_requested'] = bool(email_message.get('Return-Receipt-To'))

        # Determine urgency level
        priority = priority_info['priority'].lower()
        importance = priority_info['importance'].lower()

        if priority in ['1', 'high'] or importance == 'high':
            priority_info['urgency'] = 'high'
        elif priority in ['5', 'low'] or importance == 'low':
            priority_info['urgency'] = 'low'
        elif priority_info['delivery_receipt_requested']:
            priority_info['urgency'] = 'medium'

        return priority_info
