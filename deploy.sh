#!/bin/bash

# Production Deployment Script for AMT CRM Backend
# Usage: ./deploy.sh

set -e  # Exit on any error

echo "🚀 Starting AMT CRM Backend deployment..."

# Check if we're in the right directory
if [ ! -f "docker-compose.yml" ]; then
    echo "❌ Error: docker-compose.yml not found. Please run this script from the project root."
    exit 1
fi

# Check if .env.docker exists
if [ ! -f ".env.docker" ]; then
    echo "❌ Error: .env.docker file not found. Please create it first."
    exit 1
fi

# Create necessary directories
echo "📁 Creating necessary directories..."
mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache
mkdir -p infra/certs
mkdir -p infra/vhost.d
mkdir -p infra/html

# Set proper permissions
echo "🔐 Setting proper permissions..."
chmod -R 755 storage bootstrap/cache
chmod -R 755 infra

# Copy environment file
echo "⚙️  Setting up environment..."
cp .env.docker .env

# Stop existing containers
echo "🛑 Stopping existing containers..."
docker compose down --remove-orphans || true

# Build and start containers
echo "🔨 Building and starting containers..."
docker compose up --build -d

# Wait for database to be ready
echo "⏳ Waiting for database to be ready..."
sleep 30

# Run database migrations
echo "🗄️  Running database migrations..."
docker compose exec -T app php artisan migrate --force

# Clear and cache configuration
echo "🧹 Clearing and caching configuration..."
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache

# Create storage link
echo "🔗 Creating storage link..."
docker compose exec -T app php artisan storage:link || true

# Set proper ownership
echo "👤 Setting proper ownership..."
docker compose exec -T app chown -R www:www /var/www/storage /var/www/bootstrap/cache

echo "✅ Deployment completed successfully!"
echo "🌐 Your application should be available at: https://api.himmanav.com"
echo "📊 Check container status with: docker compose ps"
echo "📝 View logs with: docker compose logs -f" 