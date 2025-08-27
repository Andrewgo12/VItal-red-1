#!/bin/bash

# Vital Red Installation Script
# This script automates the installation process for Vital Red system

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to check PHP version
check_php_version() {
    if command_exists php; then
        PHP_VERSION=$(php -r "echo PHP_VERSION;" 2>/dev/null)
        PHP_MAJOR=$(echo $PHP_VERSION | cut -d. -f1)
        PHP_MINOR=$(echo $PHP_VERSION | cut -d. -f2)
        
        if [ "$PHP_MAJOR" -gt 8 ] || ([ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -ge 2 ]); then
            print_success "PHP $PHP_VERSION detected"
            return 0
        else
            print_error "PHP 8.2 or higher required. Found: $PHP_VERSION"
            return 1
        fi
    else
        print_error "PHP not found"
        return 1
    fi
}

# Function to check Node.js version
check_node_version() {
    if command_exists node; then
        NODE_VERSION=$(node --version | sed 's/v//')
        NODE_MAJOR=$(echo $NODE_VERSION | cut -d. -f1)
        
        if [ "$NODE_MAJOR" -ge 18 ]; then
            print_success "Node.js $NODE_VERSION detected"
            return 0
        else
            print_error "Node.js 18 or higher required. Found: $NODE_VERSION"
            return 1
        fi
    else
        print_error "Node.js not found"
        return 1
    fi
}

# Function to check system requirements
check_requirements() {
    print_status "Checking system requirements..."
    
    local requirements_met=true
    
    # Check PHP
    if ! check_php_version; then
        requirements_met=false
    fi
    
    # Check Composer
    if command_exists composer; then
        COMPOSER_VERSION=$(composer --version 2>/dev/null | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)
        print_success "Composer $COMPOSER_VERSION detected"
    else
        print_error "Composer not found"
        requirements_met=false
    fi
    
    # Check Node.js
    if ! check_node_version; then
        requirements_met=false
    fi
    
    # Check npm
    if command_exists npm; then
        NPM_VERSION=$(npm --version)
        print_success "npm $NPM_VERSION detected"
    else
        print_error "npm not found"
        requirements_met=false
    fi
    
    # Check MySQL
    if command_exists mysql; then
        print_success "MySQL detected"
    else
        print_warning "MySQL not detected. Make sure you have a MySQL server available."
    fi
    
    if [ "$requirements_met" = false ]; then
        print_error "Some requirements are not met. Please install missing dependencies."
        exit 1
    fi
    
    print_success "All requirements met!"
}

# Function to install PHP dependencies
install_php_dependencies() {
    print_status "Installing PHP dependencies..."
    
    if [ ! -f "composer.json" ]; then
        print_error "composer.json not found. Are you in the correct directory?"
        exit 1
    fi
    
    composer install --no-dev --optimize-autoloader
    print_success "PHP dependencies installed"
}

# Function to install Node.js dependencies
install_node_dependencies() {
    print_status "Installing Node.js dependencies..."
    
    if [ ! -f "package.json" ]; then
        print_error "package.json not found. Are you in the correct directory?"
        exit 1
    fi
    
    npm install
    print_success "Node.js dependencies installed"
}

# Function to setup environment
setup_environment() {
    print_status "Setting up environment..."
    
    if [ ! -f ".env" ]; then
        if [ -f ".env.example" ]; then
            cp .env.example .env
            print_success "Environment file created from .env.example"
        else
            print_error ".env.example not found"
            exit 1
        fi
    else
        print_warning ".env file already exists, skipping..."
    fi
}

# Function to setup database
setup_database() {
    print_status "Setting up database..."
    
    # Prompt for database configuration
    echo ""
    echo "Please provide database configuration:"
    read -p "Database name (default: vital_red): " DB_NAME
    DB_NAME=${DB_NAME:-vital_red}
    
    read -p "Database username (default: root): " DB_USER
    DB_USER=${DB_USER:-root}
    
    read -s -p "Database password: " DB_PASS
    echo ""
    
    read -p "Database host (default: 127.0.0.1): " DB_HOST
    DB_HOST=${DB_HOST:-127.0.0.1}
    
    read -p "Database port (default: 3306): " DB_PORT
    DB_PORT=${DB_PORT:-3306}
    
    # Update .env file
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" .env
    sed -i "s/DB_USERNAME=.*/DB_USERNAME=$DB_USER/" .env
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASS/" .env
    sed -i "s/DB_HOST=.*/DB_HOST=$DB_HOST/" .env
    sed -i "s/DB_PORT=.*/DB_PORT=$DB_PORT/" .env
    
    print_success "Database configuration updated"
}

# Function to run Laravel setup
run_laravel_setup() {
    print_status "Running Laravel setup..."
    
    # Run the custom setup command
    php artisan vitalred:setup --force
    
    print_success "Laravel setup completed"
}

# Function to build assets
build_assets() {
    print_status "Building frontend assets..."
    
    npm run build
    
    print_success "Assets built successfully"
}

# Function to set permissions (Linux/Mac only)
set_permissions() {
    if [[ "$OSTYPE" != "msys" && "$OSTYPE" != "win32" ]]; then
        print_status "Setting file permissions..."
        
        chmod -R 755 storage bootstrap/cache
        
        if [ -d "public" ]; then
            chmod -R 755 public
        fi
        
        print_success "Permissions set"
    fi
}

# Function to display final information
display_final_info() {
    echo ""
    echo "=============================================="
    print_success "🏥 Vital Red Installation Complete!"
    echo "=============================================="
    echo ""
    echo "🔐 Default Login Credentials:"
    echo "   Administrator: admin@vitalred.com / admin123"
    echo "   Doctor Demo:   medico@vitalred.com / medico123"
    echo ""
    echo "🚀 To start the development server:"
    echo "   php artisan serve"
    echo ""
    echo "🌐 Then visit: http://localhost:8000"
    echo ""
    print_warning "⚠️  IMPORTANT: Change default passwords before production use!"
    echo ""
    echo "📚 For more information, see README.md"
    echo "=============================================="
}

# Main installation function
main() {
    echo ""
    echo "=============================================="
    echo "🏥 Vital Red System Installation"
    echo "=============================================="
    echo ""
    
    # Check if we're in the right directory
    if [ ! -f "artisan" ]; then
        print_error "Laravel artisan file not found. Please run this script from the project root directory."
        exit 1
    fi
    
    # Check requirements
    check_requirements
    
    # Install dependencies
    install_php_dependencies
    install_node_dependencies
    
    # Setup environment
    setup_environment
    
    # Setup database
    setup_database
    
    # Run Laravel setup
    run_laravel_setup
    
    # Build assets
    build_assets
    
    # Set permissions
    set_permissions
    
    # Display final information
    display_final_info
}

# Run main function
main "$@"
