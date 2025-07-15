#!/bin/bash

# Setup Shared Network for AMT CRM
# This script creates a shared external network and connects existing containers

set -e

echo "🌐 Setting up shared network for AMT CRM..."

# Create the shared external network
echo "📡 Creating shared external network..."
docker network create amt_crm_shared_network 2>/dev/null || echo "✅ Network already exists"

# Check if frontend container exists and connect it to the shared network
if docker ps -a --format '{{.Names}}' | grep -q '^amt_crm_frontend$'; then
    echo "🔗 Connecting frontend container to shared network..."
    docker network connect amt_crm_shared_network amt_crm_frontend 2>/dev/null || echo "✅ Frontend already connected"
else
    echo "⚠️  Frontend container not found"
fi

# Check if backend containers exist and connect them
if docker ps -a --format '{{.Names}}' | grep -q '^amt_crm_app$'; then
    echo "🔗 Connecting backend app to shared network..."
    docker network connect amt_crm_shared_network amt_crm_app 2>/dev/null || echo "✅ Backend app already connected"
fi

if docker ps -a --format '{{.Names}}' | grep -q '^amt_crm_nginx$'; then
    echo "🔗 Connecting nginx to shared network..."
    docker network connect amt_crm_shared_network amt_crm_nginx 2>/dev/null || echo "✅ Nginx already connected"
fi

if docker ps -a --format '{{.Names}}' | grep -q '^amt_crm_db$'; then
    echo "🔗 Connecting database to shared network..."
    docker network connect amt_crm_shared_network amt_crm_db 2>/dev/null || echo "✅ Database already connected"
fi

# Show network information
echo "📊 Network information:"
docker network inspect amt_crm_shared_network --format '{{range .Containers}}{{.Name}} {{end}}' 2>/dev/null || echo "No containers connected yet"

echo "✅ Shared network setup complete!"
echo "💡 Make sure your frontend deployment also uses the 'amt_crm_shared_network' external network" 