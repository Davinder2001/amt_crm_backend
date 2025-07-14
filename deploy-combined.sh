#!/bin/bash

# AMT CRM Combined Deployment Script
# Deploys both Frontend (Next.js) and Backend (Laravel) with Nginx reverse proxy

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

# Check if server IP is provided
if [ -z "$1" ]; then
    print_error "Usage: $0 <server_ip> [username]"
    print_error "Example: $0 31.97.186.147 root"
    exit 1
fi

SERVER_IP=$1
USERNAME=${2:-root}
SSH_KEY="$HOME/.ssh/id_ed25519"

print_status "Starting AMT CRM Combined Deployment..."
print_status "Server: $SERVER_IP"
print_status "User: $USERNAME"
print_status "SSH Key: $SSH_KEY"

# Check if SSH key exists
if [ ! -f "$SSH_KEY" ]; then
    print_error "SSH key not found: $SSH_KEY"
    exit 1
fi

# Create deployment package
print_status "Creating deployment package..."

# Create a temporary directory for packaging
TEMP_DIR=$(mktemp -d)
PACKAGE_NAME="amt-crm-combined-$(date +%Y%m%d-%H%M%S).tar.gz"

# Copy necessary files to temp directory
print_status "Copying files to temporary directory..."

# Create directory structure
mkdir -p "$TEMP_DIR"/{frontend,backend,nginx}

# Copy backend files (current directory)
cp -r app artisan bootstrap composer.json composer.lock config database docker docker-compose.combined.yml docker-entrypoint.sh Dockerfile .env.example .gitattributes .gitignore package.json package-lock.json phpunit.xml public README.md resources routes scripts storage tests vendor vite.config.js "$TEMP_DIR/backend/"

# Copy nginx configuration
cp -r nginx "$TEMP_DIR/"

# Copy frontend files (assuming they're in a sibling directory)
if [ -d "../amt_crm_frontend" ]; then
    cp -r ../amt_crm_frontend/* "$TEMP_DIR/frontend/"
    print_success "Frontend files copied from ../amt_crm_frontend"
else
    print_warning "Frontend directory not found at ../amt_crm_frontend"
    print_warning "You'll need to add frontend files manually to the server"
fi

# Create the package
cd "$TEMP_DIR"
tar -czf "$PACKAGE_NAME" --exclude='.git' --exclude='node_modules' --exclude='.next' --exclude='.env*' --exclude='*.log' .

print_success "Deployment package created: $PACKAGE_NAME"

# Upload to server
print_status "Uploading package to server..."
scp -i "$SSH_KEY" "$PACKAGE_NAME" "$USERNAME@$SERVER_IP:/tmp/"

if [ $? -eq 0 ]; then
    print_success "Package uploaded successfully"
else
    print_error "Failed to upload package"
    exit 1
fi

# Deploy on server
print_status "Deploying on server..."

ssh -i "$SSH_KEY" "$USERNAME@$SERVER_IP" << 'EOF'
set -e

# Colors for server output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() {
    echo -e "${BLUE}[SERVER]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SERVER]${NC} $1"
}

print_error() {
    echo -e "${RED}[SERVER]${NC} $1"
}

# Find the uploaded package
PACKAGE=$(ls -t /tmp/amt-crm-combined-*.tar.gz | head -1)

if [ -z "$PACKAGE" ]; then
    print_error "No deployment package found in /tmp/"
    exit 1
fi

print_status "Found package: $PACKAGE"

# Create deployment directory
DEPLOY_DIR="/srv/amt-crm"
print_status "Creating deployment directory: $DEPLOY_DIR"
sudo mkdir -p "$DEPLOY_DIR"
sudo chown $USER:$USER "$DEPLOY_DIR"

# Extract package
print_status "Extracting package..."
cd "$DEPLOY_DIR"
tar -xzf "$PACKAGE"

# Set up environment files
print_status "Setting up environment files..."

# Backend environment
if [ -f "backend/.env.example" ]; then
    if [ ! -f "backend/.env" ]; then
        cp backend/.env.example backend/.env
        print_status "Created backend .env from example"
    fi
else
    print_error "Backend .env.example not found"
    exit 1
fi

# Frontend environment
if [ -d "frontend" ] && [ ! -f "frontend/env.docker" ]; then
    cat > frontend/env.docker << 'ENVEOF'
NODE_ENV=production
NEXT_PUBLIC_API_URL=http://api.himmanav.com
NEXT_PUBLIC_SOCKET_URL=ws://api.himmanav.com
NEXT_PUBLIC_APP_URL=http://himmanav.com
ENVEOF
    print_status "Created frontend env.docker"
fi

# Create nginx logs directory
sudo mkdir -p nginx/logs
sudo chown -R $USER:$USER nginx/

# Stop any existing containers
print_status "Stopping existing containers..."
docker-compose -f docker-compose.combined.yml down 2>/dev/null || true

# Build and start services
print_status "Building and starting services..."
docker-compose -f docker-compose.combined.yml up -d --build

# Wait for services to be ready
print_status "Waiting for services to be ready..."
sleep 30

# Check service status
print_status "Checking service status..."
docker-compose -f docker-compose.combined.yml ps

# Health checks
print_status "Performing health checks..."

# Check if containers are running
if docker ps | grep -q amt_crm_nginx; then
    print_success "Nginx container is running"
else
    print_error "Nginx container is not running"
fi

if docker ps | grep -q amt_crm_backend; then
    print_success "Backend container is running"
else
    print_error "Backend container is not running"
fi

if docker ps | grep -q amt_crm_frontend; then
    print_success "Frontend container is running"
else
    print_error "Frontend container is not running"
fi

# Clean up
print_status "Cleaning up..."
rm -f /tmp/amt-crm-combined-*.tar.gz

print_success "Deployment completed successfully!"
print_status "Access points:"
print_status "- Frontend: http://himmanav.com"
print_status "- Backend API: http://api.himmanav.com"
print_status "- Health checks:"
print_status "  - Frontend: http://himmanav.com/health"
print_status "  - Backend: http://api.himmanav.com/health"

EOF

# Clean up local temp directory
rm -rf "$TEMP_DIR"

print_success "Combined deployment completed!"
print_status "Next steps:"
print_status "1. Update your DNS records to point himmanav.com and api.himmanav.com to $SERVER_IP"
print_status "2. Configure SSL certificates if needed"
print_status "3. Update environment variables in backend/.env and frontend/env.docker"
print_status "4. Run database migrations: ssh -i $SSH_KEY $USERNAME@$SERVER_IP 'cd /srv/amt-crm && docker-compose -f docker-compose.combined.yml exec backend php artisan migrate'" 