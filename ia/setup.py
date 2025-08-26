"""
Setup script for Gmail Data Extraction System
"""

import os
import sys
import subprocess
import platform

def check_python_version():
    """Check if Python version is compatible"""
    if sys.version_info < (3, 7):
        print("❌ Python 3.7 or higher is required")
        print(f"Current version: {sys.version}")
        return False
    print(f"✅ Python version: {sys.version}")
    return True

def install_requirements():
    """Install Python requirements"""
    print("\n📦 Installing Python requirements...")
    try:
        subprocess.check_call([sys.executable, "-m", "pip", "install", "-r", "requirements.txt"])
        print("✅ Python requirements installed successfully")
        return True
    except subprocess.CalledProcessError as e:
        print(f"❌ Failed to install requirements: {e}")
        return False

def check_tesseract():
    """Check if Tesseract OCR is installed"""
    print("\n🔍 Checking Tesseract OCR installation...")
    try:
        result = subprocess.run(["tesseract", "--version"], 
                              capture_output=True, text=True, timeout=10)
        if result.returncode == 0:
            version = result.stdout.split('\n')[0]
            print(f"✅ Tesseract found: {version}")
            return True
        else:
            print("❌ Tesseract not found")
            return False
    except (subprocess.TimeoutExpired, FileNotFoundError):
        print("❌ Tesseract not found")
        return False

def install_tesseract_instructions():
    """Provide Tesseract installation instructions"""
    system = platform.system().lower()
    
    print("\n📋 Tesseract OCR Installation Instructions:")
    print("=" * 50)
    
    if system == "windows":
        print("Windows:")
        print("1. Download Tesseract installer from:")
        print("   https://github.com/UB-Mannheim/tesseract/wiki")
        print("2. Run the installer")
        print("3. Add Tesseract to your PATH environment variable")
        print("4. Restart your command prompt/terminal")
        
    elif system == "darwin":  # macOS
        print("macOS:")
        print("1. Install Homebrew if not already installed:")
        print("   /bin/bash -c \"$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)\"")
        print("2. Install Tesseract:")
        print("   brew install tesseract")
        
    elif system == "linux":
        print("Linux (Ubuntu/Debian):")
        print("   sudo apt-get update")
        print("   sudo apt-get install tesseract-ocr")
        print("\nLinux (CentOS/RHEL):")
        print("   sudo yum install tesseract")
        print("\nLinux (Fedora):")
        print("   sudo dnf install tesseract")
    
    else:
        print("Please install Tesseract OCR for your operating system")
        print("Visit: https://tesseract-ocr.github.io/tessdoc/Installation.html")

def create_directories():
    """Create necessary directories"""
    print("\n📁 Creating directory structure...")
    
    directories = [
        "Archivos",
        "Functions", 
        "Imagenes",
        "Json",
        "Text"
    ]
    
    for directory in directories:
        os.makedirs(directory, exist_ok=True)
        print(f"✅ Created/verified: {directory}/")
    
    return True

def create_env_template():
    """Create .env template file"""
    print("\n📝 Creating .env template...")
    
    env_template = """# Gmail Data Extraction System Configuration
# Copy this file to .env and fill in your credentials

# Gmail credentials (REQUIRED)
GMAIL_EMAIL=your-email@gmail.com
GMAIL_APP_PASSWORD=your-app-password

# Gmail settings (OPTIONAL)
GMAIL_IMAP_SERVER=imap.gmail.com
GMAIL_IMAP_PORT=993
GMAIL_DEFAULT_FOLDER=INBOX

# Processing settings (OPTIONAL)
MAX_EMAILS_PER_BATCH=100
PROCESSING_TIMEOUT=300
MAX_ATTACHMENT_SIZE=52428800

# OCR settings (OPTIONAL)
OCR_LANGUAGE=eng

# Logging settings (OPTIONAL)
LOG_LEVEL=INFO
LOG_FILE=gmail_processing.log

# Paths (OPTIONAL)
GMAIL_BASE_PATH=.
"""
    
    try:
        with open(".env.template", "w") as f:
            f.write(env_template)
        print("✅ Created .env.template file")
        print("   Copy this to .env and update with your credentials")
        return True
    except Exception as e:
        print(f"❌ Failed to create .env template: {e}")
        return False

def test_imports():
    """Test if all required modules can be imported"""
    print("\n🧪 Testing module imports...")
    
    required_modules = [
        "imaplib",
        "email", 
        "json",
        "os",
        "datetime",
        "hashlib",
        "logging"
    ]
    
    optional_modules = [
        ("PyPDF2", "PDF processing"),
        ("docx", "Word document processing"),
        ("openpyxl", "Excel processing"),
        ("PIL", "Image processing"),
        ("pytesseract", "OCR functionality"),
        ("html2text", "HTML to text conversion")
    ]
    
    # Test required modules
    for module in required_modules:
        try:
            __import__(module)
            print(f"✅ {module}")
        except ImportError:
            print(f"❌ {module} (REQUIRED)")
            return False
    
    # Test optional modules
    print("\nOptional modules:")
    for module, description in optional_modules:
        try:
            __import__(module)
            print(f"✅ {module} - {description}")
        except ImportError:
            print(f"⚠️  {module} - {description} (install for full functionality)")
    
    return True

def main():
    """Main setup function"""
    print("Gmail Data Extraction System Setup")
    print("=" * 40)
    
    success = True
    
    # Check Python version
    if not check_python_version():
        success = False
    
    # Create directories
    if not create_directories():
        success = False
    
    # Install requirements
    if not install_requirements():
        success = False
    
    # Test imports
    if not test_imports():
        success = False
    
    # Check Tesseract
    tesseract_available = check_tesseract()
    if not tesseract_available:
        install_tesseract_instructions()
    
    # Create environment template
    if not create_env_template():
        success = False
    
    # Final summary
    print("\n" + "=" * 50)
    print("SETUP SUMMARY")
    print("=" * 50)
    
    if success:
        print("✅ Core setup completed successfully!")
        
        if not tesseract_available:
            print("⚠️  Tesseract OCR not found - install for image text extraction")
        
        print("\nNext steps:")
        print("1. Copy .env.template to .env")
        print("2. Update .env with your Gmail credentials")
        print("3. Enable 2FA and create App Password for Gmail")
        print("4. Run: python example_usage.py")
        
        if not tesseract_available:
            print("5. Install Tesseract OCR for full functionality")
        
    else:
        print("❌ Setup encountered errors")
        print("Please resolve the issues above and run setup again")
    
    print("\nFor help, see README.md")

if __name__ == "__main__":
    main()
