# AMT CRM Backend - DevOps Deployment Guide

## 🚀 Overview

This guide provides **DevOps-focused** instructions for deploying the AMT CRM Backend on production infrastructure. The application is designed for HTTP-only deployment with Docker containers.

## 📋 Infrastructure Requirements

### **VPS Specifications**
- **OS**: Ubuntu 24.04 LTS
- **CPU**: 4 cores minimum
- **RAM**: 16GB minimum
- **Storage**: 80GB+ SSD
- **Network**: 100Mbps+ bandwidth

### **Software Stack**
- **Docker**: 24.0+
- **Docker Compose**: 2.0+
- **Git**: Latest version
- **SSH**: Enabled with key authentication

## 🏗️ Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Nginx Proxy   │    │  Laravel App    │    │   MySQL 8.0     │
│   (HTTP Only)   │◄──►│   (PHP 8.2)     │◄──►│   (Database)    │
│   Port 80       │    │   Port 9000     │    │   Port 3306     │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         │              ┌─────────────────┐              │
         └──────────────►│   Manual        │◄─────────────┘
                        │   Backups       │
                        │   (Folder)      │
                        └─────────────────┘
```

## 🔧 Deployment Steps

### **1. Server Preparation**

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Add user to docker group
sudo usermod -aG docker $USER
newgrp docker
```

### **2. Project Setup**

```bash
# Clone repository
git clone <your-repo-url> amt_crm_backend
cd amt_crm_backend

# Create necessary directories
mkdir -p backups
mkdir -p docker/nginx/logs
mkdir -p docker/mysql/init

# Set proper permissions
chmod +x create-env.sh
chmod +x scripts/monitor.sh
```

### **3. Environment Configuration**

```bash
# Generate environment file
./create-env.sh

# Edit environment variables
nano .env.docker
```

**Critical Environment Variables:**
```env
# Database
DB_DATABASE=amt_crm_production
DB_USERNAME=amt_crm_user
DB_PASSWORD=<strong-password>
DB_ROOT_PASSWORD=<strong-root-password>

# Application
APP_URL=http://api.himmanav.com
APP_KEY=<laravel-app-key>

# Mail (if needed)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
```

### **4. Deploy Application**

```bash
# Build and start containers
docker compose up --build -d

# Check container status
docker compose ps

# Run database migrations
docker compose exec app php artisan migrate --force

# Clear application cache
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan view:clear
```

## 🔍 Monitoring & Maintenance

### **Health Checks**

```bash
# Check system health
./scripts/monitor.sh health

# Check performance
./scripts/monitor.sh performance

# Check logs
./scripts/monitor.sh logs

# Check backup status
./scripts/monitor.sh backup

# Run all checks
./scripts/monitor.sh all
```

### **Log Management**

```bash
# View application logs
docker compose logs -f app

# View Nginx logs
docker compose logs -f nginx-proxy

# View database logs
docker compose logs -f db

# View all logs
docker compose logs -f
```

### **Manual Backup Management**

```bash
# Create manual backup
docker compose exec db mysqldump -u root -p${DB_ROOT_PASSWORD} --all-databases > backups/manual_backup_$(date +%Y%m%d_%H%M%S).sql

# Restore from backup
docker compose exec -T db mysql -u root -p${DB_ROOT_PASSWORD} < backups/backup_file.sql

# List existing backups
ls -la backups/

# Check backup directory status
./scripts/monitor.sh backup
```

## 🛠️ Troubleshooting

### **Common Issues**

#### **1. Container Won't Start**
```bash
# Check logs
docker compose logs <service-name>

# Check disk space
df -h

# Check memory
free -h

# Restart services
docker compose restart
```

#### **2. Database Connection Issues**
```bash
# Check database container
docker compose exec db mysqladmin ping -u root -p

# Check environment variables
docker compose exec app env | grep DB_

# Reset database container
docker compose down
docker volume rm amt_crm_backend_mysql_data
docker compose up -d
```

#### **3. Application Errors**
```bash
# Check Laravel logs
docker compose exec app tail -f /var/www/storage/logs/laravel.log

# Check permissions
docker compose exec app ls -la /var/www/storage

# Fix permissions
docker compose exec app chown -R www:www /var/www/storage
```

#### **4. Performance Issues**
```bash
# Check resource usage
docker stats

# Check slow queries
docker compose exec db tail -f /var/log/mysql/slow.log

# Restart with resource limits
docker compose down
docker compose up -d --scale app=1
```

## 📊 Performance Optimization

### **Current Optimizations**
- **Nginx**: Gzip compression, rate limiting, caching
- **PHP**: OPcache enabled, memory optimization
- **MySQL**: InnoDB optimization, query cache
- **Docker**: Health checks, resource monitoring

### **Scaling Considerations**
- **Vertical**: Increase VPS resources
- **Horizontal**: Add load balancer, multiple app instances
- **Database**: Master-slave replication
- **Caching**: Add Redis (if needed later)

## 🔒 Security Features

### **Network Security**
- Containers isolated in private network
- Only port 80 exposed externally
- Database only accessible from app container
- Rate limiting on API endpoints

### **Application Security**
- Non-root user execution
- Security headers in Nginx
- Input validation and sanitization
- Environment variable protection

### **Data Security**
- Encrypted database connections
- Secure file permissions
- Manual backup strategy
- Backup folder for data safety

## 🔄 Maintenance Schedule

### **Daily Tasks**
- [ ] Check system health: `./scripts/monitor.sh health`
- [ ] Review error logs: `./scripts/monitor.sh logs`
- [ ] Check backup directory: `./scripts/monitor.sh backup`

### **Weekly Tasks**
- [ ] Performance analysis: `./scripts/monitor.sh performance`
- [ ] Log rotation and cleanup
- [ ] Security updates: `sudo apt update && sudo apt upgrade`

### **Monthly Tasks**
- [ ] Full system backup
- [ ] Performance optimization review
- [ ] Security audit
- [ ] Dependency updates

## 📞 Emergency Procedures

### **Service Recovery**
```bash
# Restart all services
docker compose restart

# Restart specific service
docker compose restart <service-name>

# Rebuild and restart
docker compose down
docker compose up --build -d
```

### **Data Recovery**
```bash
# Stop services
docker compose down

# Restore from backup
docker compose up -d db
docker compose exec -T db mysql -u root -p${DB_ROOT_PASSWORD} < backups/backup_file.sql

# Restart all services
docker compose up -d
```

### **Complete Reset**
```bash
# Stop and remove everything
docker compose down -v
docker system prune -a

# Re-deploy
./create-env.sh
docker compose up --build -d
```

## 📝 Notes

- **No Redis**: Application uses database for caching
- **HTTP Only**: SSL/HTTPS not configured (as requested)
- **Manual Backups**: Backup folder available for manual database dumps
- **Monitoring**: Comprehensive health and performance monitoring
- **Security**: Production-ready security configurations

---

**Last Updated**: $(date)
**Version**: 1.0
**Status**: Production Ready ✅ 