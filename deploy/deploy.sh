#!/bin/bash

# Vital Red Production Deployment Script
# This script handles deployment to production environment

set -e

# Configuration
PROJECT_NAME="vital-red"
PROJECT_PATH="/var/www/$PROJECT_NAME"
BACKUP_PATH="/var/backups/$PROJECT_NAME"
NGINX_CONFIG="/etc/nginx/sites-available/$PROJECT_NAME"
SUPERVISOR_CONFIG="/etc/supervisor/conf.d/$PROJECT_NAME.conf"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

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

# Check if running as root
check_root() {
    if [ "$EUID" -ne 0 ]; then
        print_error "This script must be run as root"
        exit 1
    fi
}

# Create backup
create_backup() {
    print_status "Creating backup..."
    
    TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    BACKUP_DIR="$BACKUP_PATH/$TIMESTAMP"
    
    mkdir -p "$BACKUP_DIR"
    
    # Backup application files
    if [ -d "$PROJECT_PATH" ]; then
        tar -czf "$BACKUP_DIR/app_backup.tar.gz" -C "$PROJECT_PATH" .
        print_success "Application backup created"
    fi
    
    # Backup database
    if [ -f "$PROJECT_PATH/.env" ]; then
        DB_NAME=$(grep "DB_DATABASE=" "$PROJECT_PATH/.env" | cut -d'=' -f2)
        DB_USER=$(grep "DB_USERNAME=" "$PROJECT_PATH/.env" | cut -d'=' -f2)
        DB_PASS=$(grep "DB_PASSWORD=" "$PROJECT_PATH/.env" | cut -d'=' -f2)
        
        if [ ! -z "$DB_NAME" ]; then
            mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/database_backup.sql"
            print_success "Database backup created"
        fi
    fi
    
    print_success "Backup completed: $BACKUP_DIR"
}

# Install system dependencies
install_dependencies() {
    print_status "Installing system dependencies..."
    
    apt update
    apt install -y nginx mysql-server redis-server supervisor curl git unzip
    
    # Install PHP 8.2
    apt install -y software-properties-common
    add-apt-repository ppa:ondrej/php -y
    apt update
    apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-redis php8.2-xml php8.2-curl php8.2-mbstring php8.2-zip php8.2-gd php8.2-intl
    
    # Install Composer
    if ! command -v composer &> /dev/null; then
        curl -sS https://getcomposer.org/installer | php
        mv composer.phar /usr/local/bin/composer
        chmod +x /usr/local/bin/composer
    fi
    
    # Install Node.js
    if ! command -v node &> /dev/null; then
        curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
        apt install -y nodejs
    fi
    
    print_success "Dependencies installed"
}

# Setup project directory
setup_project() {
    print_status "Setting up project directory..."
    
    # Create project directory
    mkdir -p "$PROJECT_PATH"
    
    # Set ownership
    chown -R www-data:www-data "$PROJECT_PATH"
    
    print_success "Project directory setup completed"
}

# Deploy application
deploy_application() {
    print_status "Deploying application..."
    
    cd "$PROJECT_PATH"
    
    # Install PHP dependencies
    sudo -u www-data composer install --no-dev --optimize-autoloader
    
    # Install Node.js dependencies and build assets
    sudo -u www-data npm install
    sudo -u www-data npm run build
    
    # Set permissions
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R 755 storage bootstrap/cache
    
    # Create storage link
    sudo -u www-data php artisan storage:link
    
    # Cache configuration
    sudo -u www-data php artisan config:cache
    sudo -u www-data php artisan route:cache
    sudo -u www-data php artisan view:cache
    
    print_success "Application deployed"
}

# Setup database
setup_database() {
    print_status "Setting up database..."
    
    cd "$PROJECT_PATH"
    
    # Run migrations
    sudo -u www-data php artisan migrate --force
    
    # Run seeders (only if database is empty)
    USER_COUNT=$(sudo -u www-data php artisan tinker --execute="echo App\Models\User::count();")
    if [ "$USER_COUNT" -eq 0 ]; then
        sudo -u www-data php artisan db:seed --force
        print_success "Database seeded with initial data"
    fi
    
    print_success "Database setup completed"
}

# Configure Nginx
configure_nginx() {
    print_status "Configuring Nginx..."
    
    # Copy Nginx configuration
    cp "$PROJECT_PATH/deploy/nginx.conf" "$NGINX_CONFIG"
    
    # Enable site
    ln -sf "$NGINX_CONFIG" /etc/nginx/sites-enabled/
    
    # Remove default site
    rm -f /etc/nginx/sites-enabled/default
    
    # Test configuration
    nginx -t
    
    # Restart Nginx
    systemctl restart nginx
    systemctl enable nginx
    
    print_success "Nginx configured"
}

# Configure Supervisor
configure_supervisor() {
    print_status "Configuring Supervisor..."
    
    # Copy Supervisor configuration
    cp "$PROJECT_PATH/deploy/supervisor-vitalred.conf" "$SUPERVISOR_CONFIG"
    
    # Update Supervisor
    supervisorctl reread
    supervisorctl update
    supervisorctl start vitalred:*
    
    print_success "Supervisor configured"
}

# Configure services
configure_services() {
    print_status "Configuring services..."
    
    # Enable and start Redis
    systemctl enable redis-server
    systemctl start redis-server
    
    # Enable and start MySQL
    systemctl enable mysql
    systemctl start mysql
    
    # Configure PHP-FPM
    systemctl enable php8.2-fpm
    systemctl start php8.2-fpm
    
    print_success "Services configured"
}

# Setup SSL (Let's Encrypt)
setup_ssl() {
    print_status "Setting up SSL certificate..."
    
    # Install Certbot
    apt install -y certbot python3-certbot-nginx
    
    # Get domain from user
    read -p "Enter your domain name (e.g., vitalred.com): " DOMAIN
    
    if [ ! -z "$DOMAIN" ]; then
        # Update Nginx configuration with domain
        sed -i "s/vitalred.com/$DOMAIN/g" "$NGINX_CONFIG"
        
        # Reload Nginx
        systemctl reload nginx
        
        # Get SSL certificate
        certbot --nginx -d "$DOMAIN" -d "www.$DOMAIN" --non-interactive --agree-tos --email admin@"$DOMAIN"
        
        print_success "SSL certificate configured"
    else
        print_warning "Domain not provided, skipping SSL setup"
    fi
}

# Setup monitoring
setup_monitoring() {
    print_status "Setting up monitoring..."
    
    # Create log rotation
    cat > /etc/logrotate.d/vitalred << EOF
$PROJECT_PATH/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        supervisorctl restart vitalred:*
    endscript
}
EOF
    
    # Setup cron for Laravel scheduler
    (crontab -l 2>/dev/null; echo "* * * * * cd $PROJECT_PATH && php artisan schedule:run >> /dev/null 2>&1") | crontab -
    
    print_success "Monitoring setup completed"
}

# Display final information
display_final_info() {
    echo ""
    echo "=============================================="
    print_success "🏥 Vital Red Production Deployment Complete!"
    echo "=============================================="
    echo ""
    echo "📁 Project Path: $PROJECT_PATH"
    echo "🔧 Nginx Config: $NGINX_CONFIG"
    echo "⚙️  Supervisor Config: $SUPERVISOR_CONFIG"
    echo ""
    echo "🔐 Default Login Credentials:"
    echo "   Administrator: admin@vitalred.com / admin123"
    echo "   Doctor Demo:   medico@vitalred.com / medico123"
    echo ""
    print_warning "⚠️  IMPORTANT: Change default passwords immediately!"
    echo ""
    echo "📊 Useful Commands:"
    echo "   Check workers: supervisorctl status vitalred:*"
    echo "   Restart workers: supervisorctl restart vitalred:*"
    echo "   Check logs: tail -f $PROJECT_PATH/storage/logs/laravel.log"
    echo "   Check Nginx: systemctl status nginx"
    echo ""
    echo "=============================================="
}

# Main deployment function
main() {
    echo ""
    echo "=============================================="
    echo "🏥 Vital Red Production Deployment"
    echo "=============================================="
    echo ""
    
    # Check root privileges
    check_root
    
    # Create backup if project exists
    if [ -d "$PROJECT_PATH" ]; then
        create_backup
    fi
    
    # Install dependencies
    install_dependencies
    
    # Setup project
    setup_project
    
    # Deploy application
    deploy_application
    
    # Setup database
    setup_database
    
    # Configure services
    configure_services
    configure_nginx
    configure_supervisor
    
    # Setup SSL (optional)
    read -p "Do you want to setup SSL certificate? (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        setup_ssl
    fi
    
    # Setup monitoring
    setup_monitoring
    
    # Display final information
    display_final_info
}

# Run main function
main "$@"
