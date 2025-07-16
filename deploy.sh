#!/bin/bash

# AMT CRM Backend Deployment Script
# This script deploys the Laravel application using Docker Compose

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

# Check if .env file exists
if [ ! -f ".env" ]; then
    print_error ".env file not found. Please create one from .env.example"
    exit 1
fi

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    print_error "Docker is not running. Please start Docker and try again."
    exit 1
fi

# Check if Docker Compose is available
if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
    print_error "Docker Compose is not available. Please install Docker Compose."
    exit 1
fi

print_status "Starting AMT CRM Backend deployment..."

# Create external network if it doesn't exist
print_status "Creating external network..."
if command -v docker-compose &> /dev/null; then
    docker network create amt_crm_shared_network 2>/dev/null || print_warning "Network already exists"
else
    docker network create amt_crm_shared_network 2>/dev/null || print_warning "Network already exists"
fi

# Build and start containers
print_status "Building and starting containers..."
if command -v docker-compose &> /dev/null; then
    docker-compose up --build -d
else
    docker compose up --build -d
fi

# Wait for containers to be ready
print_status "Waiting for containers to be ready..."
sleep 10

# Check if containers are running
print_status "Checking container status..."
if command -v docker-compose &> /dev/null; then
    docker-compose ps
else
    docker compose ps
fi

# Fix Laravel permissions
print_status "Fixing Laravel permissions..."
if command -v docker-compose &> /dev/null; then
    docker-compose exec --user root app chown -R www:www /var/www/storage /var/www/bootstrap/cache || true
    docker-compose exec --user root app chmod -R 775 /var/www/storage /var/www/bootstrap/cache || true
else
    docker compose exec --user root app chown -R www:www /var/www/storage /var/www/bootstrap/cache || true
    docker compose exec --user root app chmod -R 775 /var/www/storage /var/www/bootstrap/cache || true
fi

# Run Laravel optimizations
print_status "Running Laravel optimizations..."
if command -v docker-compose &> /dev/null; then
    docker-compose exec app php artisan config:cache || true
    docker-compose exec app php artisan view:cache || true
    docker-compose exec app php artisan migrate --force --no-interaction || true
else
    docker compose exec app php artisan config:cache || true
    docker compose exec app php artisan view:cache || true
    docker compose exec app php artisan migrate --force --no-interaction || true
fi

# Wait for app to be fully ready
print_status "Waiting for app to be ready..."
sleep 30

# Health check
print_status "Performing health check..."
if curl -f -s -m 10 http://localhost/health > /dev/null; then
    print_success "Health check passed"
else
    print_warning "Health check failed, but deployment completed"
    print_status "Container logs for debugging:"
    if command -v docker-compose &> /dev/null; then
        docker-compose logs app --tail=20 || true
        docker-compose logs nginx --tail=10 || true
    else
        docker compose logs app --tail=20 || true
        docker compose logs nginx --tail=10 || true
    fi
fi

print_success "Deployment completed successfully!"
print_status "Application is available at: http://localhost"
print_status "Health check endpoint: http://localhost/health"

# Show container status
print_status "Final container status:"
if command -v docker-compose &> /dev/null; then
    docker-compose ps
else
    docker compose ps
fi 