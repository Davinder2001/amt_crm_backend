# AMT CRM Backend - Hostinger VPS Deployment Guide

## 🚀 Overview

This guide provides step-by-step instructions for deploying the AMT CRM Backend on a Hostinger VPS with Docker, following industry-standard best practices for security, scalability, and maintainability.

## 📋 Prerequisites

### VPS Requirements
- **OS**: Ubuntu 24.04 LTS
- **CPU**: 4 cores (KVM 4)
- **RAM**: 16GB
- **Storage**: 80GB+ SSD
- **Domain**: himmanav.com (managed in Route 53)

### Software Requirements
- Docker 24.0+
- Docker Compose 2.0+
- Git
- SSH access to VPS

## 🏗️ Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Nginx Proxy   │    │  Laravel App    │    │   MySQL 8.0     │
│   (SSL/TLS)     │◄──►│   (PHP 8.2)     │◄──►│   (Database)    │
│   Port 80/443   │    │   Port 9000     │    │   Port 3306     │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         │              ┌─────────────────┐              │
         └──────────────►│   Redis 7.0     │◄─────────────┘
                        │   (Cache)       │
                        │   Port 6379     │
                        └─────────────────┘
```

## 🔧 Installation Steps

### 1. Initial Server Setup

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install essential packages
sudo apt install -y curl wget git unzip software-properties-common

# Add user to docker group (if not already done)
sudo usermod -aG docker $USER
newgrp docker
```

### 2. Install Docker and Docker Compose

```bash
# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Verify installation
docker --version
docker-compose --version
```

### 3. Clone and Setup Project

```bash
# Clone the repository
git clone <your-repo-url> amt_crm_backend
cd amt_crm_backend

# Make deployment script executable
chmod +x scripts/deploy.sh
```

### 4. Configure Environment

```bash
# Copy environment template
cp env.production.example .env.production

# Edit environment file with your values
nano .env.production
```

**Required Environment Variables:**
```env
# Database
DB_DATABASE=amt_crm_production
DB_USERNAME=amt_crm_user
DB_PASSWORD=<strong-password>
DB_ROOT_PASSWORD=<strong-root-password>

# Mail (Gmail example)
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=your-email@gmail.com

# Application
APP_URL=https://api.himmanav.com
```

### 5. Setup SSL Certificates

```bash
# Install Certbot
sudo apt install -y certbot

# Obtain SSL certificate
sudo certbot certonly --standalone -d api.himmanav.com --email himmanav11@gmail.com

# Copy certificates to project directory
sudo cp /etc/letsencrypt/live/api.himmanav.com/fullchain.pem docker/nginx/ssl/api.himmanav.com.crt
sudo cp /etc/letsencrypt/live/api.himmanav.com/privkey.pem docker/nginx/ssl/api.himmanav.com.key

# Set proper permissions
sudo chown $USER:$USER docker/nginx/ssl/api.himmanav.com.*
chmod 600 docker/nginx/ssl/api.himmanav.com.*
```

### 6. Deploy Application

```bash
# Run deployment script
./scripts/deploy.sh
```

## 🔍 Verification

### Check Service Status
```bash
# Check all containers
docker-compose ps

# Check logs
docker-compose logs -f

# Test health endpoint
curl -k https://api.himmanav.com/health
```

### Test Database Connection
```bash
# Connect to database
docker-compose exec db mysql -u root -p

# Check Laravel logs
docker-compose exec app tail -f /var/www/storage/logs/laravel.log
```

## 🔧 Configuration Details

### Nginx Configuration
- **Location**: `docker/nginx/proxy.conf`
- **Features**: 
  - SSL/TLS termination
  - Rate limiting
  - Gzip compression
  - Security headers
  - Static file caching

### PHP Configuration
- **Location**: `docker/php/php.ini`
- **Optimizations**:
  - OPcache enabled
  - Memory limit: 512M
  - Upload max: 50M
  - Session security

### MySQL Configuration
- **Location**: `docker/mysql/my.cnf`
- **Optimizations**:
  - InnoDB buffer pool: 4GB
  - Query cache: 128M
  - Binary logging enabled
  - Slow query logging

## 📊 Monitoring and Maintenance

### Automated Backups
- **Location**: `backups/` directory
- **Schedule**: Daily at 2 AM
- **Retention**: 7 days
- **Contents**: Database dump + storage files

### Log Management
- **Nginx logs**: `docker/nginx/logs/`
- **Laravel logs**: `storage/logs/`
- **MySQL logs**: Inside container
- **Rotation**: Daily with compression

### Health Checks
```bash
# Manual health check
docker-compose exec app php artisan health:check

# Check resource usage
docker stats

# Monitor disk usage
df -h
```

## 🔒 Security Features

### Network Security
- Containers isolated in private network
- Only necessary ports exposed
- Database only accessible from app container
- Redis only accessible from app container

### Application Security
- Non-root user execution
- SSL/TLS encryption
- Security headers
- Rate limiting
- Input validation

### Data Security
- Encrypted database connections
- Secure file permissions
- Regular backups
- Environment variable protection

## 🚀 Scaling Considerations

### Vertical Scaling (Current Setup)
- **CPU**: 4 cores allocated
- **RAM**: 16GB available
- **Storage**: SSD for performance

### Horizontal Scaling (Future)
- Load balancer for multiple app instances
- Database replication
- Redis clustering
- CDN for static assets

## 🛠️ Troubleshooting

### Common Issues

#### 1. Container Won't Start
```bash
# Check logs
docker-compose logs <service-name>

# Check disk space
df -h

# Check memory
free -h
```

#### 2. Database Connection Issues
```bash
# Check database container
docker-compose exec db mysqladmin ping -u root -p

# Check environment variables
docker-compose exec app env | grep DB_
```

#### 3. SSL Certificate Issues
```bash
# Renew certificates
sudo certbot renew

# Copy new certificates
sudo cp /etc/letsencrypt/live/api.himmanav.com/fullchain.pem docker/nginx/ssl/api.himmanav.com.crt
sudo cp /etc/letsencrypt/live/api.himmanav.com/privkey.pem docker/nginx/ssl/api.himmanav.com.key

# Restart nginx
docker-compose restart nginx-proxy
```

#### 4. Performance Issues
```bash
# Check resource usage
docker stats

# Check slow queries
docker-compose exec db tail -f /var/log/mysql/slow.log

# Check PHP-FPM status
docker-compose exec app php-fpm -t
```

## 📈 Performance Optimization

### Current Optimizations
- **PHP**: OPcache, optimized settings
- **MySQL**: InnoDB buffer pool, query cache
- **Nginx**: Gzip, caching, keepalive
- **Redis**: Memory optimization, persistence

### Monitoring Tools
```bash
# Install monitoring tools
sudo apt install -y htop iotop nethogs

# Monitor system resources
htop
iotop
nethogs
```

## 🔄 Updates and Maintenance

### Application Updates
```bash
# Pull latest code
git pull origin production

# Rebuild and restart
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate --force
```

### System Updates
```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Update Docker
sudo apt update
sudo apt install docker-ce docker-ce-cli containerd.io
```

## 📞 Support

For issues or questions:
1. Check logs: `docker-compose logs -f`
2. Verify configuration files
3. Test individual services
4. Check system resources
5. Review this documentation

## 📝 Notes

- Keep SSL certificates updated
- Monitor disk space regularly
- Review logs for errors
- Test backups periodically
- Update dependencies regularly 