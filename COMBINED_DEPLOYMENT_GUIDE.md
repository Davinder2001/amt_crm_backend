
# AMT CRM Combined Frontend + Backend Deployment Guide

This guide covers deploying both the AMT CRM Frontend (Next.js) and Backend (Laravel) on the same VM using a single Nginx reverse proxy.

## 🏗️ Architecture

```
Browser
    ↓
Nginx (Reverse Proxy) - Port 80
    ↓
├── Frontend (Next.js) - Port 3000 (internal)
├── Backend (Laravel) - Port 8000 (internal)
└── MySQL Database - Port 3306 (internal)
```

**Domain Routing:**
- `himmanav.com` → Frontend (Next.js) + Backend API (`/api`)
- All requests to `/api/*` are routed to Laravel backend
- All other requests are routed to Next.js frontend

## 📁 Directory Structure

```
/srv/amt-crm/
├── docker-compose.combined.yml
├── frontend/
│   ├── Dockerfile
│   ├── env.docker
│   └── [Next.js files]
├── backend/
│   ├── Dockerfile
│   ├── .env
│   └── [Laravel files]
└── nginx/
    ├── nginx.combined.conf
    ├── logs/
    └── ssl/
```

## 🚀 Quick Deployment

### Option 1: Automatic Deployment
```bash
# Push to production branch
git push origin production
```

### Option 2: Manual Deployment
```bash
# Deploy both frontend and backend
./deploy-combined.sh 31.97.186.147 root
```

### Option 3: Step-by-Step Deployment
```bash
# 1. Build frontend
npm run build

# 2. Create deployment package
tar -czf amt-crm-combined.tar.gz \
  --exclude='.git' \
  --exclude='node_modules' \
  --exclude='.next' \
  --exclude='.env*' \
  --exclude='*.log' \
  .

# 3. Upload and deploy
scp amt-crm-combined.tar.gz root@31.97.186.147:/tmp/
ssh root@31.97.186.147
cd /srv/amt-crm
# Follow the deployment steps in deploy-combined.sh
```

## 🔧 Configuration Files

### 1. Docker Compose (`docker-compose.combined.yml`)
```yaml
version: '3.8'

services:
  # Frontend Application (Next.js)
  frontend:
    build:
      context: ./frontend
      dockerfile: Dockerfile
    container_name: amt_crm_frontend
    restart: unless-stopped
    env_file:
      - ./frontend/env.docker
    expose:
      - "3000"
    networks:
      - amt_network
    volumes:
      - ./frontend/.next:/app/.next
      - ./frontend/public:/app/public

  # Backend Application (Laravel)
  backend:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: amt_crm_backend
    restart: unless-stopped
    env_file:
      - .env
    expose:
      - "8000"
    networks:
      - amt_network
    volumes:
      - ./storage:/var/www/html/storage
      - ./public:/var/www/html/public
    depends_on:
      - mysql

  # MySQL Database
  mysql:
    image: mysql:8.0
    container_name: amt_crm_mysql
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_USER: ${DB_USERNAME}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
      - ./docker/mysql/init:/docker-entrypoint-initdb.d
    networks:
      - amt_network

  # Main Nginx Reverse Proxy
  nginx:
    image: nginx:alpine
    container_name: amt_crm_nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx/nginx.combined.conf:/etc/nginx/nginx.conf:ro
      - ./nginx/logs:/var/log/nginx
      - ./nginx/ssl:/etc/nginx/ssl
      - ./public:/var/www/html/public
    depends_on:
      - frontend
      - backend
    networks:
      - amt_network

volumes:
  mysql_data:

networks:
  amt_network:
    driver: bridge
```

### 2. Frontend Environment (`frontend/env.docker`)
```bash
NODE_ENV=production
NEXT_PUBLIC_API_URL=http://himmanav.com/api
NEXT_PUBLIC_SOCKET_URL=ws://himmanav.com
NEXT_PUBLIC_APP_URL=http://himmanav.com
```

### 3. Backend Environment (`backend/.env`)
```bash
APP_NAME="AMT CRM Backend"
APP_ENV=production
APP_KEY=base64:your-app-key-here
APP_DEBUG=false
APP_URL=http://himmanav.com

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=your-database
DB_USERNAME=your-username
DB_PASSWORD=your-password
```

### 4. Nginx Configuration (`nginx/nginx.combined.conf`)
- Routes `himmanav.com` to frontend container
- Routes `/api/*` requests to backend container
- Includes rate limiting and security headers
- Handles static file caching
- Serves Laravel storage and upload files directly

## 📊 Monitoring & Management

### Check All Services
```bash
# All containers
docker ps

# Combined services
cd /srv/amt-crm
docker-compose -f docker-compose.combined.yml ps

# Individual service logs
docker-compose -f docker-compose.combined.yml logs frontend
docker-compose -f docker-compose.combined.yml logs backend
docker-compose -f docker-compose.combined.yml logs nginx
```

### Health Checks
```bash
# Frontend health
curl http://himmanav.com/health

# Backend health
curl http://api.himmanav.com/health

# Direct container health
curl http://localhost:3000/api/health
curl http://localhost:8000/health
```

### Restart Services
```bash
# Restart all services
cd /srv/amt-crm
docker-compose -f docker-compose.combined.yml restart

# Restart specific service
docker-compose -f docker-compose.combined.yml restart frontend
docker-compose -f docker-compose.combined.yml restart backend
docker-compose -f docker-compose.combined.yml restart nginx
```

## 🔄 Backend Integration

### 1. Add Your Laravel Backend
```bash
# SSH to server
ssh -i ~/.ssh/id_ed25519 root@31.97.186.147

# Navigate to backend directory
cd /srv/amt-crm/backend

# Add your Laravel code here
# You can git clone, copy files, or use your deployment method
```

### 2. Update Backend Configuration
```bash
# Edit backend environment
nano /srv/amt-crm/backend/.env

# Update database settings, app key, etc.
```

### 3. Rebuild Backend
```bash
cd /srv/amt-crm
docker-compose -f docker-compose.combined.yml up -d --build backend
```

## 🐛 Troubleshooting

### Port Conflicts
```bash
# Check port usage
sudo netstat -tulpn | grep :80

# Stop conflicting services
sudo systemctl stop apache2
sudo systemctl stop nginx
```

### Container Issues
```bash
# Check container logs
docker logs amt_crm_frontend
docker logs amt_crm_backend
docker logs amt_crm_nginx

# Rebuild specific service
docker-compose -f docker-compose.combined.yml up -d --build frontend
```

### Network Issues
```bash
# Check Docker networks
docker network ls
docker network inspect amt_network

# Test inter-container communication
docker exec amt_crm_frontend ping backend
docker exec amt_crm_backend ping frontend
```

## 📈 Performance Optimization

### Resource Limits
```yaml
services:
  frontend:
    deploy:
      resources:
        limits:
          memory: 512M
          cpus: '0.5'

  backend:
    deploy:
      resources:
        limits:
          memory: 1G
          cpus: '1.0'
```

### Caching
- Frontend: Static assets cached via Nginx
- Backend: Redis for session and cache storage
- Database: Query optimization and indexing

## 🔒 Security

### Firewall Rules
```bash
# Allow necessary ports
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
```

### SSL/TLS (Optional)
```bash
# Add SSL certificates
mkdir -p /srv/amt-crm/nginx/ssl
# Add your SSL certificates here
# Update nginx configuration for HTTPS
```

## 📞 Support Commands

```bash
# Quick status check
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

# Resource usage
docker stats

# Clean up unused resources
docker system prune -f

# Backup entire setup
tar -czf backup-$(date +%Y%m%d).tar.gz /srv/amt-crm

# View real-time logs
docker-compose -f docker-compose.combined.yml logs -f
```

## 🎯 Benefits of Combined Setup

1. **Single Point of Management**: One Docker Compose file manages both services
2. **Shared Network**: Easy inter-service communication
3. **Unified Logging**: All logs in one place
4. **Simplified Deployment**: Deploy both services together
5. **Resource Efficiency**: Shared resources and optimized networking
6. **Easy Scaling**: Scale both services together or individually

## 🔗 Access Points

- **Frontend**: http://himmanav.com
- **Backend API**: http://api.himmanav.com
- **Frontend Health**: http://himmanav.com/health
- **Backend Health**: http://api.himmanav.com/health

## 🚨 Important Notes

1. **DNS Configuration**: Make sure your DNS records point `himmanav.com` and `api.himmanav.com` to your server IP
2. **Environment Variables**: Update the environment files with your actual values
3. **Database**: The MySQL container will be created automatically, but you may need to run migrations
4. **SSL**: For production, consider adding SSL certificates
5. **Backup**: Regular backups of the database and application files are recommended

## 📋 Pre-deployment Checklist

- [ ] DNS records configured for both domains
- [ ] Environment variables updated
- [ ] Database credentials configured
- [ ] SSL certificates ready (if using HTTPS)
- [ ] Firewall rules configured
- [ ] Server has sufficient resources
- [ ] Backup strategy in place 