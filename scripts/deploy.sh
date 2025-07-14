#!/bin/bash

# AMT CRM Backend Deployment Script for Hostinger VPS
# This script deploys the Laravel application with Docker

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="amt_crm_backend"
DOMAIN="api.himmanav.com"
SSL_EMAIL="himmanav11@gmail.com"

# Functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root
check_root() {
    if [[ $EUID -eq 0 ]]; then
        log_error "This script should not be run as root"
        exit 1
    fi
}

# Check system requirements
check_requirements() {
    log_info "Checking system requirements..."
    
    # Check Docker
    if ! command -v docker &> /dev/null; then
        log_error "Docker is not installed"
        exit 1
    fi
    
    # Check Docker Compose
    if ! command -v docker-compose &> /dev/null; then
        log_error "Docker Compose is not installed"
        exit 1
    fi
    
    # Check available memory (minimum 4GB)
    MEMORY_KB=$(grep MemTotal /proc/meminfo | awk '{print $2}')
    MEMORY_GB=$((MEMORY_KB / 1024 / 1024))
    
    if [ $MEMORY_GB -lt 4 ]; then
        log_warning "System has less than 4GB RAM ($MEMORY_GB GB). Performance may be affected."
    fi
    
    log_success "System requirements check passed"
}

# Setup SSL certificates
setup_ssl() {
    log_info "Setting up SSL certificates..."
    
    # Create SSL directory
    mkdir -p docker/nginx/ssl
    
    # Check if certificates exist
    if [ ! -f "docker/nginx/ssl/${DOMAIN}.crt" ] || [ ! -f "docker/nginx/ssl/${DOMAIN}.key" ]; then
        log_warning "SSL certificates not found. Please add them to docker/nginx/ssl/"
        log_info "You can obtain free certificates from Let's Encrypt:"
        log_info "sudo certbot certonly --standalone -d ${DOMAIN} --email ${SSL_EMAIL}"
        log_info "Then copy the certificates to docker/nginx/ssl/"
    else
        log_success "SSL certificates found"
    fi
}

# Create necessary directories
create_directories() {
    log_info "Creating necessary directories..."
    
    mkdir -p docker/nginx/logs
    mkdir -p docker/nginx/conf.d
    mkdir -p docker/mysql/init
    mkdir -p backups
    mkdir -p storage/logs
    
    log_success "Directories created"
}

# Generate environment file
setup_environment() {
    log_info "Setting up environment configuration..."
    
    if [ ! -f ".env.production" ]; then
        if [ -f "env.production.example" ]; then
            cp env.production.example .env.production
            log_warning "Please edit .env.production with your production values"
        else
            log_error "Environment template not found"
            exit 1
        fi
    fi
    
    # Generate APP_KEY if not set
    if ! grep -q "APP_KEY=base64:" .env.production; then
        log_info "Generating application key..."
        docker run --rm -v $(pwd):/app -w /app php:8.2-cli-alpine sh -c "
            apk add --no-cache openssl
            echo 'APP_KEY=base64:'$(openssl rand -base64 32) >> .env.production
        "
    fi
    
    log_success "Environment setup completed"
}

# Build and deploy
deploy() {
    log_info "Starting deployment..."
    
    # Stop existing containers
    log_info "Stopping existing containers..."
    docker-compose down --remove-orphans
    
    # Build images
    log_info "Building Docker images..."
    docker-compose build --no-cache
    
    # Start services
    log_info "Starting services..."
    docker-compose up -d
    
    # Wait for services to be healthy
    log_info "Waiting for services to be healthy..."
    sleep 30
    
    # Check service health
    if docker-compose ps | grep -q "unhealthy"; then
        log_error "Some services are unhealthy. Check logs:"
        docker-compose logs
        exit 1
    fi
    
    log_success "All services are healthy"
}

# Run Laravel setup
setup_laravel() {
    log_info "Setting up Laravel application..."
    
    # Wait for database to be ready
    log_info "Waiting for database to be ready..."
    sleep 10
    
    # Run migrations
    log_info "Running database migrations..."
    docker-compose exec -T app php artisan migrate --force
    
    # Clear and cache config
    log_info "Caching configuration..."
    docker-compose exec -T app php artisan config:cache
    docker-compose exec -T app php artisan route:cache
    docker-compose exec -T app php artisan view:cache
    
    # Create storage link
    log_info "Creating storage link..."
    docker-compose exec -T app php artisan storage:link
    
    # Set permissions
    log_info "Setting file permissions..."
    docker-compose exec -T app chown -R www:www /var/www/storage /var/www/bootstrap/cache
    
    log_success "Laravel setup completed"
}

# Setup monitoring
setup_monitoring() {
    log_info "Setting up monitoring..."
    
    # Create logrotate configuration
    sudo tee /etc/logrotate.d/amt-crm > /dev/null <<EOF
/var/lib/docker/volumes/amt_crm_backend_docker_nginx_logs/_data/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 nginx nginx
    postrotate
        docker-compose -f /home/\$USER/amt_crm_backend/docker-compose.yml restart nginx-proxy
    endscript
}
EOF
    
    log_success "Monitoring setup completed"
}

# Setup backup cron job
setup_backups() {
    log_info "Setting up automated backups..."
    
    # Create backup script
    cat > scripts/backup.sh << 'EOF'
#!/bin/bash
cd /home/$USER/amt_crm_backend
docker-compose run --rm backup
EOF
    
    chmod +x scripts/backup.sh
    
    # Add to crontab (daily at 2 AM)
    (crontab -l 2>/dev/null; echo "0 2 * * * /home/$USER/amt_crm_backend/scripts/backup.sh") | crontab -
    
    log_success "Backup automation setup completed"
}

# Main deployment function
main() {
    log_info "Starting AMT CRM Backend deployment..."
    
    check_root
    check_requirements
    create_directories
    setup_environment
    setup_ssl
    deploy
    setup_laravel
    setup_monitoring
    setup_backups
    
    log_success "Deployment completed successfully!"
    log_info "Your application should be available at: https://${DOMAIN}"
    log_info "To check logs: docker-compose logs -f"
    log_info "To stop services: docker-compose down"
    log_info "To restart services: docker-compose restart"
}

# Run main function
main "$@" 