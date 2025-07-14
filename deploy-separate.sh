#!/bin/bash

# AMT CRM Backend - Industry Standard Deployment Script
# HTTP-only deployment with separate database, Laravel app, and Nginx
# Follows Docker and DevOps best practices

set -euo pipefail

# Script configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SRV_DIR="/srv"
LARAVEL_DIR="$SRV_DIR/laravel-backend"
DATABASE_DIR="$SRV_DIR/database"
NGINX_DIR="$SRV_DIR/nginx"
BACKUP_DIR="$SRV_DIR/backups"
LOG_FILE="$SRV_DIR/deployment.log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Logging functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

log_step() {
    echo -e "${PURPLE}[STEP]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

# Error handling
error_handler() {
    local exit_code=$?
    local line_number=$1
    log_error "Script failed at line $line_number with exit code $exit_code"
    exit $exit_code
}

trap 'error_handler $LINENO' ERR

# Check system requirements
check_requirements() {
    log_step "Checking system requirements..."
    
    # Check if running as root
    if [[ $EUID -eq 0 ]]; then
        log_error "This script should not be run as root for security reasons"
        exit 1
    fi
    
    # Check Docker installation
    if ! command -v docker &> /dev/null; then
        log_error "Docker is not installed. Please install Docker first."
        exit 1
    fi
    
    # Check Docker Compose installation
    if ! command -v docker-compose &> /dev/null; then
        log_error "Docker Compose is not installed. Please install Docker Compose first."
        exit 1
    fi
    
    # Check Docker daemon
    if ! docker info &> /dev/null; then
        log_error "Docker daemon is not running. Please start Docker first."
        exit 1
    fi
    
    # Check available disk space (minimum 10GB)
    local available_space=$(df -BG "$SRV_DIR" | awk 'NR==2 {print $4}' | sed 's/G//')
    if [[ $available_space -lt 10 ]]; then
        log_warning "Low disk space: ${available_space}GB available. Recommended: 10GB+"
    fi
    
    # Check available memory (minimum 4GB)
    local available_memory=$(free -g | awk 'NR==2 {print $7}')
    if [[ $available_memory -lt 4 ]]; then
        log_warning "Low memory: ${available_memory}GB available. Recommended: 4GB+"
    fi
    
    log_success "System requirements check completed"
}

# Create directory structure with proper permissions
create_directories() {
    log_step "Creating directory structure..."
    
    # Create main directories
    sudo mkdir -p "$DATABASE_DIR"/{init,data,logs}
    sudo mkdir -p "$LARAVEL_DIR"
    sudo mkdir -p "$NGINX_DIR"/{conf.d,logs,ssl}
    sudo mkdir -p "$BACKUP_DIR"
    
    # Set proper ownership and permissions
    sudo chown -R $USER:$USER "$SRV_DIR"
    sudo chmod -R 755 "$SRV_DIR"
    sudo chmod -R 700 "$DATABASE_DIR"/data
    sudo chmod -R 600 "$DATABASE_DIR"/init
    
    log_success "Directory structure created with proper permissions"
}

# Setup database with security best practices
setup_database() {
    log_step "Setting up database container..."
    
    cd "$DATABASE_DIR"
    
    # Create secure .env file for database
    if [[ ! -f ".env" ]]; then
        local db_password=$(openssl rand -base64 32)
        local root_password=$(openssl rand -base64 32)
        
        cat > .env << EOF
# Database Configuration - Generated on $(date)
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=amt_crm_backend
DB_USERNAME=amt_crm_user
DB_PASSWORD=$db_password
DB_ROOT_PASSWORD=$root_password

# MySQL Configuration
MYSQL_ROOT_PASSWORD=$root_password
MYSQL_DATABASE=amt_crm_backend
MYSQL_USER=amt_crm_user
MYSQL_PASSWORD=$db_password

# Security
MYSQL_ALLOW_EMPTY_PASSWORD=no
MYSQL_DISALLOW_REMOTE_ROOT_LOGIN=yes
EOF
        log_warning "Created database .env file with secure passwords. Store these securely!"
        log_info "DB Password: $db_password"
        log_info "Root Password: $root_password"
    else
        log_info "Database .env file already exists"
    fi
    
    # Start database container
    log_info "Starting database container..."
    docker-compose up -d
    
    # Wait for database to be ready with timeout
    log_info "Waiting for database to be ready..."
    local timeout=120
    local counter=0
    
    while [[ $counter -lt $timeout ]]; do
        if docker-compose exec -T db mysqladmin ping -h localhost --silent; then
            log_success "Database is ready"
            break
        fi
        sleep 2
        counter=$((counter + 2))
    done
    
    if [[ $counter -eq $timeout ]]; then
        log_error "Database failed to start within $timeout seconds"
        docker-compose logs db
        exit 1
    fi
    
    log_success "Database setup completed"
}

# Setup Laravel backend with optimizations
setup_laravel() {
    log_step "Setting up Laravel backend..."
    
    cd "$LARAVEL_DIR"
    
    # Create secure .env file for Laravel
    if [[ ! -f ".env" ]]; then
        local app_key=$(php artisan key:generate --show 2>/dev/null || echo "base64:$(openssl rand -base64 32)")
        
        cat > .env << EOF
# Application Configuration - Generated on $(date)
APP_NAME="AMT CRM"
APP_ENV=production
APP_KEY=$app_key
APP_DEBUG=false
APP_URL=http://api.himmanav.com
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=host.docker.internal
DB_PORT=3306
DB_DATABASE=amt_crm_backend
DB_USERNAME=amt_crm_user
DB_PASSWORD=$(grep DB_PASSWORD "$DATABASE_DIR/.env" | cut -d'=' -f2)

# Logging and Cache
LOG_CHANNEL=stack
LOG_LEVEL=warning
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Security
BCRYPT_ROUNDS=12
SESSION_SECURE_COOKIE=false
SESSION_HTTP_ONLY=true

# Performance
PHP_CLI_SERVER_WORKERS=4
OPCACHE_ENABLE=1
OPCACHE_MEMORY_CONSUMPTION=128
OPCACHE_MAX_ACCELERATED_FILES=4000
EOF
        log_warning "Created Laravel .env file. Please review and customize as needed."
    else
        log_info "Laravel .env file already exists"
    fi
    
    # Start Laravel containers
    log_info "Starting Laravel containers..."
    docker-compose up -d
    
    # Wait for application to be ready
    log_info "Waiting for application to be ready..."
    local timeout=180
    local counter=0
    
    while [[ $counter -lt $timeout ]]; do
        if curl -f http://localhost:9000/health &>/dev/null; then
            log_success "Laravel application is ready"
            break
        fi
        sleep 3
        counter=$((counter + 3))
    done
    
    if [[ $counter -eq $timeout ]]; then
        log_error "Laravel application failed to start within $timeout seconds"
        docker-compose logs app
        exit 1
    fi
    
    log_success "Laravel setup completed"
}

# Setup Nginx with optimizations
setup_nginx() {
    log_step "Setting up Nginx reverse proxy..."
    
    cd "$NGINX_DIR"
    
    # Build Laravel image first
    log_info "Building Laravel Docker image..."
    cd "$LARAVEL_DIR"
    docker build -t amt-crm-backend:latest . --no-cache
    
    # Start Nginx with Laravel app
    cd "$NGINX_DIR"
    log_info "Starting Nginx and Laravel containers..."
    docker-compose up -d
    
    # Wait for services to be ready
    log_info "Waiting for services to be ready..."
    local timeout=60
    local counter=0
    
    while [[ $counter -lt $timeout ]]; do
        if curl -f http://localhost/health &>/dev/null; then
            log_success "Nginx and Laravel services are ready"
            break
        fi
        sleep 2
        counter=$((counter + 2))
    done
    
    if [[ $counter -eq $timeout ]]; then
        log_error "Services failed to start within $timeout seconds"
        docker-compose logs
        exit 1
    fi
    
    log_success "Nginx setup completed"
}

# Run post-deployment tasks
post_deployment_tasks() {
    log_step "Running post-deployment tasks..."
    
    cd "$LARAVEL_DIR"
    
    # Run database migrations
    log_info "Running database migrations..."
    docker-compose exec -T app php artisan migrate --force
    
    # Clear and cache Laravel configurations
    log_info "Optimizing Laravel for production..."
    docker-compose exec -T app php artisan config:clear
    docker-compose exec -T app php artisan route:clear
    docker-compose exec -T app php artisan view:clear
    docker-compose exec -T app php artisan cache:clear
    
    docker-compose exec -T app php artisan config:cache
    docker-compose exec -T app php artisan route:cache
    docker-compose exec -T app php artisan view:cache
    
    # Set proper permissions
    log_info "Setting proper file permissions..."
    docker-compose exec -T app chown -R www:www /var/www/storage
    docker-compose exec -T app chown -R www:www /var/www/bootstrap/cache
    docker-compose exec -T app chmod -R 775 /var/www/storage
    docker-compose exec -T app chmod -R 775 /var/www/bootstrap/cache
    
    log_success "Post-deployment tasks completed"
}

# Check services health
check_services() {
    log_step "Checking service health..."
    
    local all_healthy=true
    
    # Check database
    log_info "Checking database health..."
    if cd "$DATABASE_DIR" && docker-compose ps | grep -q "Up"; then
        log_success "Database is running"
    else
        log_error "Database is not running"
        all_healthy=false
    fi
    
    # Check Laravel application
    log_info "Checking Laravel application health..."
    if curl -f http://localhost/health &>/dev/null; then
        log_success "Laravel application is healthy"
    else
        log_error "Laravel application health check failed"
        all_healthy=false
    fi
    
    # Check Nginx
    log_info "Checking Nginx health..."
    if curl -f http://localhost/api/routes &>/dev/null; then
        log_success "Nginx is serving requests"
    else
        log_error "Nginx is not serving requests"
        all_healthy=false
    fi
    
    if [[ "$all_healthy" == true ]]; then
        log_success "All services are healthy"
    else
        log_error "Some services are not healthy"
        return 1
    fi
}

# Display deployment summary
deployment_summary() {
    log_step "Deployment Summary"
    
    echo -e "${CYAN}========================================${NC}"
    echo -e "${CYAN}           DEPLOYMENT SUMMARY           ${NC}"
    echo -e "${CYAN}========================================${NC}"
    echo -e "${GREEN}✅ Database:${NC} $DATABASE_DIR"
    echo -e "${GREEN}✅ Laravel Backend:${NC} $LARAVEL_DIR"
    echo -e "${GREEN}✅ Nginx Proxy:${NC} $NGINX_DIR"
    echo -e "${GREEN}✅ Backups:${NC} $BACKUP_DIR"
    echo -e "${GREEN}✅ Logs:${NC} $LOG_FILE"
    echo ""
    echo -e "${YELLOW}🌐 Application URL:${NC} http://api.himmanav.com"
    echo -e "${YELLOW}🔍 Health Check:${NC} http://api.himmanav.com/health"
    echo -e "${YELLOW}📊 API Routes:${NC} http://api.himmanav.com/api/routes"
    echo ""
    echo -e "${BLUE}📋 Useful Commands:${NC}"
    echo -e "  Check logs: tail -f $LOG_FILE"
    echo -e "  Database logs: cd $DATABASE_DIR && docker-compose logs -f"
    echo -e "  Laravel logs: cd $LARAVEL_DIR && docker-compose logs -f"
    echo -e "  Nginx logs: cd $NGINX_DIR && docker-compose logs -f"
    echo -e "  Check services: cd $NGINX_DIR && docker-compose ps"
    echo ""
    echo -e "${PURPLE}🔒 Security Notes:${NC}"
    echo -e "  - Database passwords are in $DATABASE_DIR/.env"
    echo -e "  - Laravel config is in $LARAVEL_DIR/.env"
    echo -e "  - Keep these files secure and backed up"
    echo -e "${CYAN}========================================${NC}"
}

# Main deployment function
main() {
    log_info "Starting AMT CRM Backend deployment (HTTP-only)"
    log_info "Deployment log: $LOG_FILE"
    
    # Initialize log file
    echo "=== AMT CRM Backend Deployment Log ===" > "$LOG_FILE"
    echo "Started at: $(date)" >> "$LOG_FILE"
    echo "=====================================" >> "$LOG_FILE"
    
    check_requirements
    create_directories
    setup_database
    setup_laravel
    setup_nginx
    post_deployment_tasks
    check_services
    deployment_summary
    
    log_success "Deployment completed successfully!"
    log_info "Check the deployment summary above for next steps"
}

# Run main function
main "$@" 