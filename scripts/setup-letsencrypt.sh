#!/bin/bash

# Let's Encrypt SSL Certificate Setup Script
# This script properly obtains and configures Let's Encrypt certificates

set -e

echo "🔐 Setting up Let's Encrypt SSL Certificates"
echo "============================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if we're in the right directory
check_directory() {
    if [ ! -f "docker-compose.yml" ]; then
        print_error "Please run this script from the project root directory"
        exit 1
    fi
    print_status "Directory check passed ✓"
}

# Function to backup existing certificates
backup_existing_certificates() {
    print_status "Backing up existing certificates..."
    
    if [ -f "docker/nginx/ssl/fullchain.pem" ]; then
        mv docker/nginx/ssl/fullchain.pem docker/nginx/ssl/fullchain.pem.backup
        print_status "Backed up fullchain.pem"
    fi
    
    if [ -f "docker/nginx/ssl/privkey.pem" ]; then
        mv docker/nginx/ssl/privkey.pem docker/nginx/ssl/privkey.pem.backup
        print_status "Backed up privkey.pem"
    fi
    
    if [ -d "docker/nginx/ssl/live" ]; then
        mv docker/nginx/ssl/live docker/nginx/ssl/live.backup
        print_status "Backed up live directory"
    fi
}

# Function to create necessary directories
create_directories() {
    print_status "Creating necessary directories..."
    
    mkdir -p docker/nginx/ssl
    mkdir -p docker/nginx/certbot/www
    mkdir -p docker/nginx/logs
    
    print_status "Directories created ✓"
}

# Function to start nginx for ACME challenge
start_nginx_for_challenge() {
    print_status "Starting Nginx for ACME challenge..."
    
    # Stop any existing containers
    docker compose down 2>/dev/null || true
    
    # Start only nginx
    docker compose up -d nginx-proxy
    
    # Wait for nginx to be ready
    print_status "Waiting for Nginx to be ready..."
    sleep 15
    
    # Check if nginx is running
    if ! docker compose ps nginx-proxy | grep -q "Up"; then
        print_error "Nginx failed to start. Checking logs..."
        docker compose logs nginx-proxy
        exit 1
    fi
    
    print_status "Nginx is ready for ACME challenge ✓"
}

# Function to obtain Let's Encrypt certificates
obtain_certificates() {
    print_status "Obtaining Let's Encrypt certificates..."
    
    # Run certbot to obtain certificates
    if docker compose run --rm certbot; then
        print_status "Certificates obtained successfully ✓"
    else
        print_error "Failed to obtain certificates. Checking logs..."
        docker compose logs certbot
        exit 1
    fi
}

# Function to verify certificates
verify_certificates() {
    print_status "Verifying certificates..."
    
    if [ -f "docker/nginx/ssl/live/api.himmanav.com/fullchain.pem" ]; then
        print_status "Certificate files found ✓"
        
        # Check certificate details
        echo "Certificate details:"
        openssl x509 -in docker/nginx/ssl/live/api.himmanav.com/fullchain.pem -text -noout | grep -E "(Subject|Issuer|Not Before|Not After)" | head -4
        
        # Check if it's Let's Encrypt
        if openssl x509 -in docker/nginx/ssl/live/api.himmanav.com/fullchain.pem -text -noout | grep -q "Let's Encrypt"; then
            print_status "Let's Encrypt certificate verified ✓"
        else
            print_warning "Certificate may not be from Let's Encrypt"
        fi
    else
        print_error "Certificate files not found"
        exit 1
    fi
}

# Function to restart services with new certificates
restart_services() {
    print_status "Restarting services with new certificates..."
    
    # Restart all services
    docker compose down
    docker compose up -d
    
    # Wait for services to be ready
    print_status "Waiting for services to be ready..."
    sleep 30
    
    # Check service status
    print_status "Service status:"
    docker compose ps
    
    print_status "Services restarted ✓"
}

# Function to test SSL certificate
test_ssl() {
    print_status "Testing SSL certificate..."
    
    # Wait a bit more for nginx to fully load
    sleep 10
    
    # Test local connection
    if curl -k -s https://localhost/health | grep -q "healthy"; then
        print_status "Local SSL connection working ✓"
    else
        print_warning "Local SSL connection test failed"
    fi
    
    # Test domain connection (if available)
    if curl -k -s https://api.himmanav.com/health | grep -q "healthy"; then
        print_status "Domain SSL connection working ✓"
    else
        print_warning "Domain SSL connection test failed (may be expected in development)"
    fi
    
    # Show certificate details
    echo ""
    print_status "SSL Certificate Details:"
    curl -k -v https://api.himmanav.com/health 2>&1 | grep -E "(subject|issuer)" | head -2
}

# Function to cleanup
cleanup() {
    print_status "Cleaning up backup files..."
    
    # Remove backup files if certificates are working
    if [ -f "docker/nginx/ssl/live/api.himmanav.com/fullchain.pem" ]; then
        rm -f docker/nginx/ssl/fullchain.pem.backup
        rm -f docker/nginx/ssl/privkey.pem.backup
        rm -rf docker/nginx/ssl/live.backup
        print_status "Backup files cleaned up ✓"
    fi
}

# Main execution
main() {
    print_status "Starting Let's Encrypt SSL setup..."
    
    check_directory
    backup_existing_certificates
    create_directories
    start_nginx_for_challenge
    obtain_certificates
    verify_certificates
    restart_services
    test_ssl
    cleanup
    
    print_status "🎉 Let's Encrypt SSL setup completed successfully!"
    echo ""
    print_status "Your API is now available with Let's Encrypt SSL at:"
    echo "  - HTTPS: https://api.himmanav.com"
    echo "  - Health: https://api.himmanav.com/health"
    echo ""
    print_status "Certificate will auto-renew every 60 days."
}

# Run main function
main "$@" 