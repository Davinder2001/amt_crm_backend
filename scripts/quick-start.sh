#!/bin/bash

# Quick Start Script for AMT CRM Backend
# This script provides a simplified deployment process

set -e

echo "🚀 AMT CRM Backend - Quick Start"
echo "================================="

# Check if .env.production exists
if [ ! -f ".env.production" ]; then
    echo "❌ .env.production file not found!"
    echo "Please copy env.production.example to .env.production and configure it."
    exit 1
fi

# Check if SSL certificates exist
if [ ! -f "docker/nginx/ssl/api.himmanav.com.crt" ] || [ ! -f "docker/nginx/ssl/api.himmanav.com.key" ]; then
    echo "⚠️  SSL certificates not found!"
    echo "Please add your SSL certificates to docker/nginx/ssl/"
    echo "Files needed:"
    echo "  - docker/nginx/ssl/api.himmanav.com.crt"
    echo "  - docker/nginx/ssl/api.himmanav.com.key"
    exit 1
fi

echo "✅ Configuration files found"

# Create necessary directories
echo "📁 Creating directories..."
mkdir -p docker/nginx/logs docker/nginx/conf.d docker/mysql/init backups storage/logs

# Build and start services
echo "🔨 Building Docker images..."
docker-compose build --no-cache

echo "🚀 Starting services..."
docker-compose up -d

echo "⏳ Waiting for services to be ready..."
sleep 30

# Check if services are healthy
if docker-compose ps | grep -q "unhealthy"; then
    echo "❌ Some services are unhealthy. Check logs:"
    docker-compose logs
    exit 1
fi

echo "✅ All services are healthy!"

# Setup Laravel
echo "⚙️  Setting up Laravel..."
docker-compose exec -T app php artisan migrate --force
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache
docker-compose exec -T app php artisan storage:link

echo "🎉 Deployment completed successfully!"
echo ""
echo "📊 Service Status:"
docker-compose ps
echo ""
echo "🌐 Your application is available at: https://api.himmanav.com"
echo ""
echo "📝 Useful commands:"
echo "  - View logs: docker-compose logs -f"
echo "  - Stop services: docker-compose down"
echo "  - Restart services: docker-compose restart"
echo "  - Health check: docker-compose exec app php artisan health:check" 