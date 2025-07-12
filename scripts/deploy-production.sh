#!/bin/bash

# Production Deployment Script for AMT CRM Backend
# This script handles deployment with proper SSL certificate management

set -e

echo "🚀 AMT CRM Production Deployment"
echo "================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check prerequisites
check_prerequisites() {
    print_status "Checking prerequisites..."
    
    # Check if Docker is running
    if ! docker info > /dev/null 2>&1; then
        print_error "Docker is not running. Please start Docker and try again."
        exit 1
    fi
    
    # Check if docker-compose is available
    if ! command -v docker-compose > /dev/null 2>&1; then
        print_error "docker-compose is not installed. Please install it and try again."
        exit 1
    fi
    
    # Check if .env file exists
    if [ ! -f ".env" ]; then
        print_error ".env file not found. Please create it from .env.example"
        exit 1
    fi
    
    print_status "Prerequisites check passed ✓"
}

# Function to setup SSL certificates
setup_ssl_certificates() {
    print_status "Setting up SSL certificates..."
    
    # Create necessary directories
    mkdir -p docker/nginx/ssl/live/api.himmanav.com
    mkdir -p docker/nginx/certbot/www
    
    # Check if certificates already exist
    if [ -f "docker/nginx/ssl/live/api.himmanav.com/fullchain.pem" ]; then
        print_warning "SSL certificates already exist. Skipping certificate generation."
        return 0
    fi
    
    # Start nginx for ACME challenge
    print_status "Starting Nginx for ACME challenge..."
    docker compose up -d nginx-proxy
    
    # Wait for nginx to be ready
    print_status "Waiting for Nginx to be ready..."
    sleep 15
    
    # Run certbot to obtain certificates
    print_status "Obtaining Let's Encrypt certificates..."
    if docker compose run --rm certbot; then
        print_status "Certificates obtained successfully ✓"
    else
        print_error "Failed to obtain certificates. Please check the logs."
        exit 1
    fi
    
    # Reload nginx to use new certificates
    print_status "Reloading Nginx with new certificates..."
    docker compose exec nginx-proxy nginx -s reload
}

# Function to deploy application
deploy_application() {
    print_status "Deploying application..."
    
    # Stop existing containers
    print_status "Stopping existing containers..."
    docker compose down --remove-orphans
    
    # Build and start containers
    print_status "Building and starting containers..."
    docker compose up --build -d
    
    # Wait for services to be ready
    print_status "Waiting for services to be ready..."
    sleep 30
    
    # Check if app container is running
    if ! docker compose ps app | grep -q "Up"; then
        print_error "App container is not running. Checking logs..."
        docker compose logs app
        exit 1
    fi
    
    print_status "Application deployed successfully ✓"
}

# Function to run post-deployment tasks
post_deployment_tasks() {
    print_status "Running post-deployment tasks..."
    
    # Run migrations
    print_status "Running database migrations..."
    docker compose exec -T app php artisan migrate --force
    
    # Clear caches
    print_status "Clearing application caches..."
    docker compose exec -T app php artisan config:clear
    docker compose exec -T app php artisan route:clear
    docker compose exec -T app php artisan view:clear
    docker compose exec -T app php artisan cache:clear
    
    # Optimize for production
    print_status "Optimizing for production..."
    docker compose exec -T app php artisan config:cache
    docker compose exec -T app php artisan route:cache
    docker compose exec -T app php artisan view:cache
    
    print_status "Post-deployment tasks completed ✓"
}

# Function to verify deployment
verify_deployment() {
    print_status "Verifying deployment..."
    
    # Check container status
    print_status "Checking container status..."
    docker compose ps
    
    # Test health endpoint
    print_status "Testing health endpoint..."
    if curl -k -s https://localhost/health | grep -q "healthy"; then
        print_status "Health endpoint working ✓"
    else
        print_warning "Health endpoint test failed"
    fi
    
    # Test SSL certificate
    print_status "Testing SSL certificate..."
    if curl -k -s https://localhost/health > /dev/null; then
        print_status "SSL connection working ✓"
    else
        print_warning "SSL connection test failed"
    fi
    
    # Check application logs
    print_status "Checking application logs..."
    docker compose logs --tail=20 app
    
    print_status "Deployment verification completed ✓"
}

# Function to show deployment status
show_status() {
    print_status "Current deployment status:"
    echo ""
    docker compose ps
    echo ""
    print_status "Recent logs:"
    docker compose logs --tail=10
}

# Function to cleanup
cleanup() {
    print_status "Cleaning up..."
    
    # Remove unused images
    docker image prune -f
    
    # Remove unused volumes
    docker volume prune -f
    
    print_status "Cleanup completed ✓"
}

# Main deployment function
main_deployment() {
    print_status "Starting production deployment..."
    
    check_prerequisites
    setup_ssl_certificates
    deploy_application
    post_deployment_tasks
    verify_deployment
    cleanup
    
    print_status "🎉 Production deployment completed successfully!"
    echo ""
    print_status "Your application is now available at:"
    echo "  - HTTPS: https://api.himmanav.com"
    echo "  - Health: https://api.himmanav.com/health"
    echo ""
    print_status "Useful commands:"
    echo "  - View logs: docker compose logs -f"
    echo "  - Check status: docker compose ps"
    echo "  - Restart services: docker compose restart"
    echo "  - Renew SSL: ./scripts/ssl-setup.sh renew"
}

# Script argument handling
case "${1:-deploy}" in
    "deploy")
        main_deployment
        ;;
    "ssl")
        setup_ssl_certificates
        ;;
    "status")
        show_status
        ;;
    "logs")
        docker compose logs -f
        ;;
    "restart")
        docker compose restart
        ;;
    "stop")
        docker compose down
        ;;
    "cleanup")
        cleanup
        ;;
    "help"|*)
        echo "Usage: $0 {deploy|ssl|status|logs|restart|stop|cleanup|help}"
        echo ""
        echo "Commands:"
        echo "  deploy   - Full production deployment (default)"
        echo "  ssl      - Setup SSL certificates only"
        echo "  status   - Show deployment status"
        echo "  logs     - Show application logs"
        echo "  restart  - Restart all services"
        echo "  stop     - Stop all services"
        echo "  cleanup  - Clean up unused Docker resources"
        echo "  help     - Show this help message"
        echo ""
        echo "Note: This script is for production deployment."
        echo "Make sure your domain is pointing to this server before running."
        ;;
esac 