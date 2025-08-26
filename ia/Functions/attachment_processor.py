"""
Attachment Processing Module
Handles extraction and organization of email attachments
"""

import email
import os
import mimetypes
from typing import Dict, List, Any, Optional, Tuple
from email.header import decode_header
from email.message import Message
import logging
import hashlib
import shutil
import datetime

logger = logging.getLogger(__name__)

class AttachmentProcessor:
    """
    Processes and organizes email attachments
    """
    
    def __init__(self, base_path: str):
        """
        Initialize attachment processor
        
        Args:
            base_path: Base path for the ia folder
        """
        self.base_path = base_path
        self.archivos_path = os.path.join(base_path, "Archivos")
        self.imagenes_path = os.path.join(base_path, "Imagenes")
        
        # Ensure directories exist
        os.makedirs(self.archivos_path, exist_ok=True)
        os.makedirs(self.imagenes_path, exist_ok=True)
    
    @staticmethod
    def decode_filename(filename: str) -> str:
        """
        Decode MIME encoded filename
        
        Args:
            filename: Encoded filename
            
        Returns:
            str: Decoded filename
        """
        if not filename:
            return "unnamed_attachment"
        
        try:
            decoded_fragments = decode_header(filename)
            decoded_filename = ""
            
            for fragment, encoding in decoded_fragments:
                if isinstance(fragment, bytes):
                    if encoding:
                        decoded_filename += fragment.decode(encoding)
                    else:
                        decoded_filename += fragment.decode('utf-8', errors='ignore')
                else:
                    decoded_filename += fragment
            
            # Clean filename for filesystem
            decoded_filename = decoded_filename.strip()
            # Remove invalid characters for Windows/Unix filesystems
            invalid_chars = '<>:"/\\|?*'
            for char in invalid_chars:
                decoded_filename = decoded_filename.replace(char, '_')
            
            return decoded_filename if decoded_filename else "unnamed_attachment"
            
        except Exception as e:
            logger.warning(f"Error decoding filename: {str(e)}")
            return "unnamed_attachment"
    
    @staticmethod
    def get_file_category(filename: str, content_type: str) -> str:
        """
        Determine file category based on filename and content type
        
        Args:
            filename: File name
            content_type: MIME content type
            
        Returns:
            str: Category ('image', 'document', 'archive', 'other')
        """
        # Image types
        image_extensions = {'.jpg', '.jpeg', '.png', '.gif', '.bmp', '.tiff', '.tif', '.webp', '.svg'}
        image_mimes = {'image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/tiff', 'image/webp', 'image/svg+xml'}
        
        # Document types
        document_extensions = {'.pdf', '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx', '.txt', '.rtf', '.odt', '.ods', '.odp'}
        document_mimes = {
            'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain', 'application/rtf'
        }
        
        # Archive types
        archive_extensions = {'.zip', '.rar', '.7z', '.tar', '.gz', '.bz2', '.xz'}
        archive_mimes = {'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed', 'application/x-tar'}
        
        # Check by extension
        if filename:
            ext = os.path.splitext(filename.lower())[1]
            if ext in image_extensions:
                return 'image'
            elif ext in document_extensions:
                return 'document'
            elif ext in archive_extensions:
                return 'archive'
        
        # Check by MIME type
        if content_type:
            content_type = content_type.lower()
            if any(mime in content_type for mime in image_mimes):
                return 'image'
            elif any(mime in content_type for mime in document_mimes):
                return 'document'
            elif any(mime in content_type for mime in archive_mimes):
                return 'archive'
        
        return 'other'
    
    def create_email_folder(self, unique_id: str, category: str) -> str:
        """
        Create folder for email attachments
        
        Args:
            unique_id: Unique email identifier
            category: File category
            
        Returns:
            str: Path to created folder
        """
        if category == 'image':
            base_folder = self.imagenes_path
        else:
            base_folder = self.archivos_path
        
        email_folder = os.path.join(base_folder, unique_id)
        os.makedirs(email_folder, exist_ok=True)
        
        return email_folder
    
    def save_attachment(self, part: Message, unique_id: str, attachment_index: int) -> Dict[str, Any]:
        """
        Save individual attachment
        
        Args:
            part: Email part containing attachment
            unique_id: Unique email identifier
            attachment_index: Index of attachment in email
            
        Returns:
            Dict: Attachment information
        """
        try:
            # Get filename
            filename = part.get_filename()
            if filename:
                filename = self.decode_filename(filename)
            else:
                # Generate filename based on content type
                content_type = part.get_content_type()
                ext = mimetypes.guess_extension(content_type) or '.bin'
                filename = f"attachment_{attachment_index}{ext}"
            
            # Get content type
            content_type = part.get_content_type()
            
            # Determine category
            category = self.get_file_category(filename, content_type)
            
            # Create email folder
            email_folder = self.create_email_folder(unique_id, category)
            
            # Ensure unique filename
            base_name, ext = os.path.splitext(filename)
            counter = 1
            final_filename = filename
            while os.path.exists(os.path.join(email_folder, final_filename)):
                final_filename = f"{base_name}_{counter}{ext}"
                counter += 1
            
            # Full file path
            file_path = os.path.join(email_folder, final_filename)
            
            # Get attachment data
            attachment_data = part.get_payload(decode=True)
            
            # Save file
            with open(file_path, 'wb') as f:
                f.write(attachment_data)
            
            # Calculate file hash for integrity
            file_hash = hashlib.md5(attachment_data).hexdigest()
            
            # Get file size
            file_size = len(attachment_data)
            
            # Perform security analysis
            security_analysis = self.analyze_attachment_security(file_path, content_type)

            # Extract file metadata
            file_metadata = self.extract_attachment_metadata(file_path)

            attachment_info = {
                'original_filename': filename,
                'saved_filename': final_filename,
                'file_path': file_path,
                'relative_path': os.path.relpath(file_path, self.base_path),
                'content_type': content_type,
                'category': category,
                'size_bytes': file_size,
                'size_human': self.format_file_size(file_size),
                'md5_hash': file_hash,
                'attachment_index': attachment_index,
                'saved_successfully': True,
                'security_analysis': security_analysis,
                'file_metadata': file_metadata,
                'processing_timestamp': datetime.datetime.now().isoformat()
            }
            
            logger.info(f"Saved attachment: {final_filename} ({self.format_file_size(file_size)})")
            return attachment_info
            
        except Exception as e:
            logger.error(f"Error saving attachment {attachment_index}: {str(e)}")
            return {
                'original_filename': filename if 'filename' in locals() else 'unknown',
                'saved_filename': None,
                'file_path': None,
                'relative_path': None,
                'content_type': part.get_content_type(),
                'category': 'error',
                'size_bytes': 0,
                'size_human': '0 B',
                'md5_hash': None,
                'attachment_index': attachment_index,
                'saved_successfully': False,
                'error': str(e)
            }
    
    @staticmethod
    def format_file_size(size_bytes: int) -> str:
        """
        Format file size in human readable format
        
        Args:
            size_bytes: Size in bytes
            
        Returns:
            str: Formatted size
        """
        if size_bytes == 0:
            return "0 B"
        
        size_names = ["B", "KB", "MB", "GB", "TB"]
        i = 0
        size = float(size_bytes)
        
        while size >= 1024.0 and i < len(size_names) - 1:
            size /= 1024.0
            i += 1
        
        return f"{size:.1f} {size_names[i]}"
    
    def process_email_attachments(self, email_message: Message, unique_id: str) -> List[Dict[str, Any]]:
        """
        Process all attachments in an email
        
        Args:
            email_message: Email message object
            unique_id: Unique email identifier
            
        Returns:
            List[Dict]: List of attachment information
        """
        attachments = []
        
        if not email_message.is_multipart():
            return attachments
        
        attachment_index = 0
        
        for part in email_message.walk():
            # Skip multipart containers
            if part.get_content_maintype() == 'multipart':
                continue
            
            # Check if it's an attachment
            if part.get_content_disposition() == 'attachment':
                attachment_info = self.save_attachment(part, unique_id, attachment_index)
                attachments.append(attachment_info)
                attachment_index += 1
            
            # Also check for inline attachments (like embedded images)
            elif part.get_content_disposition() == 'inline' and part.get_filename():
                attachment_info = self.save_attachment(part, unique_id, attachment_index)
                attachment_info['disposition'] = 'inline'
                attachments.append(attachment_info)
                attachment_index += 1
        
        logger.info(f"Processed {len(attachments)} attachments for email {unique_id}")
        return attachments

    def analyze_attachment_security(self, file_path: str, content_type: str) -> Dict[str, Any]:
        """
        Analyze attachment for potential security risks

        Args:
            file_path: Path to attachment file
            content_type: MIME content type

        Returns:
            Dict: Security analysis results
        """
        security_analysis = {
            'risk_level': 'low',
            'potential_threats': [],
            'file_signature_valid': True,
            'suspicious_extensions': False,
            'encrypted': False,
            'macro_enabled': False
        }

        if not os.path.exists(file_path):
            return security_analysis

        filename = os.path.basename(file_path)
        ext = os.path.splitext(filename.lower())[1]

        # Check for suspicious extensions
        high_risk_extensions = {
            '.exe', '.bat', '.cmd', '.com', '.pif', '.scr', '.vbs', '.js',
            '.jar', '.app', '.deb', '.pkg', '.dmg', '.msi'
        }

        medium_risk_extensions = {
            '.docm', '.xlsm', '.pptm', '.dotm', '.xltm', '.potm', '.ppam',
            '.xlam', '.docx', '.xlsx', '.pptx'  # Can contain macros
        }

        if ext in high_risk_extensions:
            security_analysis['risk_level'] = 'high'
            security_analysis['potential_threats'].append('executable_file')
            security_analysis['suspicious_extensions'] = True
        elif ext in medium_risk_extensions:
            security_analysis['risk_level'] = 'medium'
            security_analysis['potential_threats'].append('macro_capable')

        # Check file signature (magic bytes)
        try:
            with open(file_path, 'rb') as f:
                header = f.read(16)

            # Common file signatures
            signatures = {
                b'\x50\x4B\x03\x04': 'zip',  # ZIP/Office files
                b'\x25\x50\x44\x46': 'pdf',  # PDF
                b'\xFF\xD8\xFF': 'jpeg',     # JPEG
                b'\x89\x50\x4E\x47': 'png',  # PNG
                b'\xD0\xCF\x11\xE0': 'ole',  # MS Office (old format)
            }

            detected_type = None
            for sig, file_type in signatures.items():
                if header.startswith(sig):
                    detected_type = file_type
                    break

            # Verify file signature matches extension
            if detected_type:
                expected_extensions = {
                    'zip': ['.zip', '.docx', '.xlsx', '.pptx'],
                    'pdf': ['.pdf'],
                    'jpeg': ['.jpg', '.jpeg'],
                    'png': ['.png'],
                    'ole': ['.doc', '.xls', '.ppt']
                }

                if ext not in expected_extensions.get(detected_type, []):
                    security_analysis['file_signature_valid'] = False
                    security_analysis['potential_threats'].append('signature_mismatch')
                    security_analysis['risk_level'] = 'high'

        except Exception as e:
            logger.warning(f"Error analyzing file signature: {str(e)}")

        # Check for password-protected/encrypted files
        if ext in ['.zip', '.rar', '.7z']:
            security_analysis['encrypted'] = self._check_archive_encryption(file_path)
        elif ext in ['.pdf']:
            security_analysis['encrypted'] = self._check_pdf_encryption(file_path)

        return security_analysis

    def _check_archive_encryption(self, file_path: str) -> bool:
        """Check if archive is password protected"""
        try:
            import zipfile
            if file_path.endswith('.zip'):
                with zipfile.ZipFile(file_path, 'r') as zip_file:
                    for info in zip_file.infolist():
                        if info.flag_bits & 0x1:  # Encrypted flag
                            return True
            return False
        except:
            return False

    def _check_pdf_encryption(self, file_path: str) -> bool:
        """Check if PDF is encrypted"""
        try:
            import PyPDF2
            with open(file_path, 'rb') as f:
                pdf_reader = PyPDF2.PdfReader(f)
                return pdf_reader.is_encrypted
        except:
            return False

    def extract_attachment_metadata(self, file_path: str) -> Dict[str, Any]:
        """
        Extract detailed metadata from attachment file

        Args:
            file_path: Path to attachment file

        Returns:
            Dict: File metadata
        """
        metadata = {
            'file_stats': {},
            'creation_time': None,
            'modification_time': None,
            'access_time': None,
            'permissions': '',
            'owner': '',
            'file_type_details': {},
            'embedded_metadata': {}
        }

        if not os.path.exists(file_path):
            return metadata

        try:
            # Basic file statistics
            stat_info = os.stat(file_path)
            metadata['file_stats'] = {
                'size_bytes': stat_info.st_size,
                'creation_time': datetime.datetime.fromtimestamp(stat_info.st_ctime).isoformat(),
                'modification_time': datetime.datetime.fromtimestamp(stat_info.st_mtime).isoformat(),
                'access_time': datetime.datetime.fromtimestamp(stat_info.st_atime).isoformat(),
                'permissions': oct(stat_info.st_mode)[-3:],
                'inode': stat_info.st_ino
            }

            # Extract embedded metadata based on file type
            ext = os.path.splitext(file_path.lower())[1]

            if ext == '.pdf':
                metadata['embedded_metadata'] = self._extract_pdf_metadata(file_path)
            elif ext in ['.jpg', '.jpeg', '.png', '.tiff']:
                metadata['embedded_metadata'] = self._extract_image_metadata(file_path)
            elif ext in ['.docx', '.xlsx', '.pptx']:
                metadata['embedded_metadata'] = self._extract_office_metadata(file_path)

        except Exception as e:
            logger.warning(f"Error extracting file metadata: {str(e)}")

        return metadata

    def _extract_pdf_metadata(self, file_path: str) -> Dict[str, Any]:
        """Extract PDF metadata"""
        try:
            import PyPDF2
            with open(file_path, 'rb') as f:
                pdf_reader = PyPDF2.PdfReader(f)
                if pdf_reader.metadata:
                    return {
                        'title': pdf_reader.metadata.get('/Title', ''),
                        'author': pdf_reader.metadata.get('/Author', ''),
                        'subject': pdf_reader.metadata.get('/Subject', ''),
                        'creator': pdf_reader.metadata.get('/Creator', ''),
                        'producer': pdf_reader.metadata.get('/Producer', ''),
                        'creation_date': pdf_reader.metadata.get('/CreationDate', ''),
                        'modification_date': pdf_reader.metadata.get('/ModDate', ''),
                        'pages': len(pdf_reader.pages)
                    }
        except:
            pass
        return {}

    def _extract_image_metadata(self, file_path: str) -> Dict[str, Any]:
        """Extract image EXIF metadata"""
        try:
            from PIL import Image
            from PIL.ExifTags import TAGS

            with Image.open(file_path) as img:
                metadata = {
                    'format': img.format,
                    'mode': img.mode,
                    'size': img.size,
                    'exif': {}
                }

                exif_data = img._getexif()
                if exif_data:
                    for tag_id, value in exif_data.items():
                        tag = TAGS.get(tag_id, tag_id)
                        metadata['exif'][tag] = str(value)

                return metadata
        except:
            pass
        return {}

    def _extract_office_metadata(self, file_path: str) -> Dict[str, Any]:
        """Extract Office document metadata"""
        try:
            if file_path.endswith('.docx'):
                import docx
                doc = docx.Document(file_path)
                props = doc.core_properties
                return {
                    'title': props.title or '',
                    'author': props.author or '',
                    'subject': props.subject or '',
                    'created': props.created.isoformat() if props.created else '',
                    'modified': props.modified.isoformat() if props.modified else '',
                    'last_modified_by': props.last_modified_by or '',
                    'revision': props.revision or '',
                    'category': props.category or ''
                }
        except:
            pass
        return {}
