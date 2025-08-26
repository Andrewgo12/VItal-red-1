#!/usr/bin/env python3
"""
Enhanced Setup and Configuration Script for Vital Red IA System
Comprehensive installation, configuration, and testing
"""

import os
import sys
import json
import logging
import subprocess
import argparse
from pathlib import Path
from typing import Dict, List, Any

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

class VitalRedEnhancedSetup:
    """Enhanced setup and configuration manager"""
    
    def __init__(self):
        self.base_path = Path(__file__).parent
        self.config_file = self.base_path / 'config.json'
        self.requirements_file = self.base_path / 'requirements.txt'
        
    def check_python_version(self) -> bool:
        """Check if Python version is compatible"""
        try:
            version = sys.version_info
            if version.major < 3 or (version.major == 3 and version.minor < 8):
                logger.error("Python 3.8 or higher is required")
                return False
            
            logger.info(f"Python version {version.major}.{version.minor}.{version.micro} is compatible")
            return True
            
        except Exception as e:
            logger.error(f"Error checking Python version: {e}")
            return False
    
    def install_dependencies(self) -> bool:
        """Install required Python packages"""
        try:
            logger.info("Installing Python dependencies...")
            
            # Create requirements.txt if it doesn't exist
            if not self.requirements_file.exists():
                self.create_requirements_file()
            
            # Install packages
            result = subprocess.run([
                sys.executable, '-m', 'pip', 'install', '-r', str(self.requirements_file)
            ], capture_output=True, text=True)
            
            if result.returncode == 0:
                logger.info("Dependencies installed successfully")
                return True
            else:
                logger.error(f"Error installing dependencies: {result.stderr}")
                return False
                
        except Exception as e:
            logger.error(f"Error installing dependencies: {e}")
            return False
    
    def create_requirements_file(self) -> None:
        """Create requirements.txt file with necessary packages"""
        requirements = [
            "imaplib2>=3.6",
            "email-validator>=1.3.0",
            "python-docx>=0.8.11",
            "PyPDF2>=3.0.1",
            "Pillow>=9.0.0",
            "python-magic>=0.4.27",
            "requests>=2.28.0",
            "beautifulsoup4>=4.11.0",
            "lxml>=4.9.0",
            "spacy>=3.4.0",
            "numpy>=1.21.0",
            "pandas>=1.5.0",
            "scikit-learn>=1.1.0",
            "nltk>=3.7",
            "textblob>=0.17.1",
            "python-dateutil>=2.8.2",
            "schedule>=1.2.0",
            "psutil>=5.9.0",
            "cryptography>=3.4.8",
            "keyring>=23.0.0"
        ]
        
        with open(self.requirements_file, 'w') as f:
            f.write('\n'.join(requirements))
        
        logger.info(f"Created requirements.txt with {len(requirements)} packages")
    
    def setup_directories(self) -> bool:
        """Create necessary directories"""
        try:
            directories = [
                'logs',
                'output',
                'output/professional_records',
                'output/single_processing',
                'temp',
                'attachments',
                'config'
            ]
            
            for directory in directories:
                dir_path = self.base_path / directory
                dir_path.mkdir(parents=True, exist_ok=True)
                logger.info(f"Created directory: {directory}")
            
            return True
            
        except Exception as e:
            logger.error(f"Error creating directories: {e}")
            return False
    
    def create_config_template(self) -> bool:
        """Create configuration template"""
        try:
            config_template = {
                "gmail": {
                    "email": "",
                    "app_password": "",
                    "imap_server": "imap.gmail.com",
                    "imap_port": 993
                },
                "laravel": {
                    "api_url": "http://localhost:8000/api",
                    "api_token": ""
                },
                "processing": {
                    "check_interval_minutes": 5,
                    "max_emails_per_check": 50,
                    "medical_keywords_threshold": 2,
                    "enable_real_time_notifications": True
                },
                "ai": {
                    "gemini_api_keys": [],
                    "enable_enhanced_analysis": True,
                    "confidence_threshold": 0.6
                },
                "security": {
                    "encrypt_sensitive_data": True,
                    "log_level": "INFO",
                    "max_log_size_mb": 100
                },
                "notifications": {
                    "email_enabled": True,
                    "smtp_server": "",
                    "smtp_port": 587,
                    "smtp_username": "",
                    "smtp_password": "",
                    "admin_email": ""
                }
            }
            
            config_file = self.base_path / 'config' / 'config_template.json'
            with open(config_file, 'w') as f:
                json.dump(config_template, f, indent=4)
            
            logger.info(f"Created configuration template: {config_file}")
            return True
            
        except Exception as e:
            logger.error(f"Error creating config template: {e}")
            return False
    
    def run_system_tests(self) -> Dict[str, bool]:
        """Run comprehensive system tests"""
        try:
            logger.info("Running system tests...")
            
            test_results = {}
            
            # Test 1: Import all modules
            try:
                sys.path.append(str(self.base_path / 'Functions'))
                
                modules_to_test = [
                    'gmail_connector',
                    'metadata_extractor',
                    'attachment_processor',
                    'text_extractor',
                    'json_converter',
                    'gmail_to_medical_transformer',
                    'medical_email_filter',
                    'enhanced_medical_analyzer',
                    'medical_priority_classifier'
                ]
                
                for module_name in modules_to_test:
                    try:
                        __import__(module_name)
                        test_results[f"import_{module_name}"] = True
                    except Exception as e:
                        logger.error(f"Failed to import {module_name}: {e}")
                        test_results[f"import_{module_name}"] = False
                
            except Exception as e:
                logger.error(f"Module import tests failed: {e}")
            
            # Test 2: Text extraction
            try:
                from text_extractor import TextExtractor
                extractor = TextExtractor(str(self.base_path))
                
                # Test with sample text
                test_text = "Test medical text with patient information"
                result = extractor.extract_text_from_string(test_text)
                test_results["text_extraction"] = bool(result)
                
            except Exception as e:
                logger.error(f"Text extraction test failed: {e}")
                test_results["text_extraction"] = False
            
            # Test 3: Medical analysis
            try:
                from enhanced_medical_analyzer import EnhancedMedicalAnalyzer
                analyzer = EnhancedMedicalAnalyzer()
                
                test_text = "Paciente de 45 años con dolor torácico y disnea"
                result = analyzer.analyze_medical_text(test_text)
                test_results["medical_analysis"] = not result.get('error')
                
            except Exception as e:
                logger.error(f"Medical analysis test failed: {e}")
                test_results["medical_analysis"] = False
            
            # Test 4: Priority classification
            try:
                from medical_priority_classifier import MedicalPriorityClassifier
                classifier = MedicalPriorityClassifier()
                
                test_data = {
                    'diagnostico_principal': 'Dolor torácico agudo',
                    'motivo_consulta': 'Paciente con dolor precordial intenso'
                }
                result = classifier.classify_priority(test_data)
                test_results["priority_classification"] = not result.get('error')
                
            except Exception as e:
                logger.error(f"Priority classification test failed: {e}")
                test_results["priority_classification"] = False
            
            # Summary
            passed_tests = sum(test_results.values())
            total_tests = len(test_results)
            
            logger.info(f"System tests completed: {passed_tests}/{total_tests} passed")
            
            if passed_tests == total_tests:
                logger.info("All system tests passed!")
            else:
                logger.warning("Some system tests failed. Check logs for details.")
            
            return test_results
            
        except Exception as e:
            logger.error(f"Error running system tests: {e}")
            return {}
    
    def create_service_scripts(self) -> bool:
        """Create service management scripts"""
        try:
            # Windows batch script
            batch_script = """@echo off
echo Starting Vital Red Gmail Monitor Service...
cd /d "%~dp0"
python gmail_monitor_service.py start
pause
"""
            
            with open(self.base_path / 'start_service.bat', 'w') as f:
                f.write(batch_script)
            
            # Linux shell script
            shell_script = """#!/bin/bash
echo "Starting Vital Red Gmail Monitor Service..."
cd "$(dirname "$0")"
python3 gmail_monitor_service.py start
"""
            
            with open(self.base_path / 'start_service.sh', 'w') as f:
                f.write(shell_script)
            
            # Make shell script executable
            os.chmod(self.base_path / 'start_service.sh', 0o755)
            
            logger.info("Service management scripts created")
            return True
            
        except Exception as e:
            logger.error(f"Error creating service scripts: {e}")
            return False
    
    def full_setup(self) -> bool:
        """Run complete setup process"""
        try:
            logger.info("Starting Vital Red IA System enhanced setup...")
            
            # Step 1: Check Python version
            if not self.check_python_version():
                return False
            
            # Step 2: Create directories
            if not self.setup_directories():
                return False
            
            # Step 3: Install dependencies
            if not self.install_dependencies():
                return False
            
            # Step 4: Create configuration template
            if not self.create_config_template():
                return False
            
            # Step 5: Create service scripts
            if not self.create_service_scripts():
                return False
            
            # Step 6: Run system tests
            test_results = self.run_system_tests()
            
            logger.info("Enhanced setup completed successfully!")
            logger.info("Next steps:")
            logger.info("1. Configure your settings in config/config_template.json")
            logger.info("2. Set up environment variables for sensitive data")
            logger.info("3. Test the system with: python enhanced_setup.py test")
            logger.info("4. Start the service with: python gmail_monitor_service.py start")
            
            return True
            
        except Exception as e:
            logger.error(f"Setup failed: {e}")
            return False

def main():
    """Main entry point"""
    parser = argparse.ArgumentParser(description='Vital Red IA System Enhanced Setup')
    parser.add_argument('action', choices=['install', 'test', 'config'], 
                       help='Action to perform')
    
    args = parser.parse_args()
    
    setup = VitalRedEnhancedSetup()
    
    if args.action == 'install':
        success = setup.full_setup()
        sys.exit(0 if success else 1)
        
    elif args.action == 'test':
        test_results = setup.run_system_tests()
        passed = sum(test_results.values())
        total = len(test_results)
        
        print(f"\nTest Results: {passed}/{total} passed")
        for test_name, result in test_results.items():
            status = "PASS" if result else "FAIL"
            print(f"  {test_name}: {status}")
        
        sys.exit(0 if passed == total else 1)
        
    elif args.action == 'config':
        setup.create_config_template()
        print("Configuration template created in config/config_template.json")
        sys.exit(0)

if __name__ == "__main__":
    main()
