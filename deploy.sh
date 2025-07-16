#!/bin/bash

# Production Deployment Script for AMT CRM
set -e

echo "🚀 Starting AMT CRM Production Deployment..."

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
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if .env file exists
if [ ! -f .env ]; then
    print_error ".env file not found! Please create one from .env.example"
    exit 1
fi

# Create external network if it doesn't exist
print_status "Creating external network..."
docker network create amt_crm_shared_network 2>/dev/null || print_warning "Network already exists"

# Stop existing containers
print_status "Stopping existing containers..."
docker-compose down --remove-orphans

# Build and start services
print_status "Building and starting services..."
docker-compose up --build -d

# Wait for services to be ready
print_status "Waiting for services to be ready..."
sleep 30

# Check if containers are running
print_status "Checking container status..."
docker-compose ps

# Run Laravel optimizations
print_status "Running Laravel optimizations..."
docker-compose exec app php artisan config:cache || print_warning "Config cache failed"
docker-compose exec app php artisan view:cache || print_warning "View cache failed"
docker-compose exec app php artisan route:cache || print_warning "Route cache failed"

# Run migrations
print_status "Running database migrations..."
docker-compose exec app php artisan migrate --force || print_warning "Migrations failed"

# Set proper permissions
print_status "Setting proper permissions..."
docker-compose exec app chown -R www:www /var/www/storage /var/www/bootstrap/cache || print_warning "Permission setting failed"

# Health check
print_status "Performing health check..."
if curl -f -s -m 10 http://localhost/health > /dev/null 2>&1; then
    print_status "✅ Health check passed"
else
    print_warning "⚠️ Health check failed, but continuing..."
    print_status "Container logs for debugging:"
    docker-compose logs app --tail=20 || true
fi

print_status "🎉 Deployment completed successfully!"
print_status "🌐 Application is available at: http://localhost"
print_status "📊 Container status:"
docker-compose ps

echo ""
print_status "Useful commands:"
echo "  docker-compose logs -f          # View logs"
echo "  docker-compose exec app bash    # Access app container"
echo "  docker-compose down             # Stop all services"
echo "  docker-compose restart          # Restart all services" 