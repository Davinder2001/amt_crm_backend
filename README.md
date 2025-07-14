# AMT CRM Backend - Production Deployment

A modern, scalable Laravel backend application deployed with Docker following industry best practices for HTTP-only production environments.

## 🏗️ Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Nginx Proxy   │    │  Laravel App    │    │   MySQL 8.0     │
│   (HTTP Only)   │◄──►│   (PHP 8.2)     │◄──►│   (Database)    │
│   Port 80       │    │   Port 9000     │    │   Port 3306     │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         │              ┌─────────────────┐              │
         └──────────────►│   Monitoring    │◄─────────────┘
                        │   & Backups     │
                        │   (Automated)   │
                        └─────────────────┘
```

## ✨ Features

### 🚀 **Performance Optimized**
- **Nginx**: Gzip compression, rate limiting, caching
- **PHP**: OPcache enabled, memory optimization
- **MySQL**: InnoDB optimization, query cache
- **Docker**: Health checks, resource monitoring

### 🔒 **Security Hardened**
- Non-root user execution
- Security headers in Nginx
- Rate limiting on API endpoints
- Input validation and sanitization
- Environment variable protection

### 📊 **Production Ready**
- Comprehensive health monitoring
- Automated backup system
- Log aggregation and analysis
- Resource usage monitoring
- Error tracking and alerting

### 🔧 **Maintainable**
- Modular Docker architecture
- Separate database and application
- Industry-standard directory structure
- Comprehensive documentation
- Automated deployment scripts

## 📋 Requirements

### **System Requirements**
- **OS**: Ubuntu 20.04+ / CentOS 8+ / Debian 11+
- **CPU**: 4 cores minimum
- **RAM**: 8GB minimum (16GB recommended)
- **Storage**: 50GB+ SSD
- **Network**: 100Mbps+ bandwidth

### **Software Requirements**
- **Docker**: 20.10+
- **Docker Compose**: 2.0+
- **Git**: Latest version
- **SSH**: Enabled with key authentication

## 🚀 Quick Start

### 1. **Clone Repository**
```bash
git clone <repository-url> amt_crm_backend
cd amt_crm_backend
```

### 2. **Run Deployment Script**
```bash
chmod +x deploy-separate.sh
./deploy-separate.sh
```

### 3. **Verify Deployment**
```bash
# Check all services
./scripts/monitor.sh all

# Check specific service
./scripts/monitor.sh health
```

## 📁 Directory Structure

```
/srv/
├── laravel-backend/          # Laravel application
│   ├── app/                 # Application code
│   ├── config/              # Laravel configuration
│   ├── database/            # Migrations and seeders
│   ├── docker-compose.yml   # Laravel container
│   └── .env                 # Laravel environment
├── database/                # MySQL database
│   ├── docker-compose.yml   # Database container
│   ├── init/                # Database initialization
│   └── .env                 # Database environment
├── nginx/                   # Nginx reverse proxy
│   ├── docker-compose.yml   # Nginx container
│   ├── nginx.conf           # Nginx configuration
│   ├── conf.d/              # Server configurations
│   └── logs/                # Nginx logs
└── backups/                 # Database backups
```

## 🔧 Configuration

### **Environment Variables**

#### **Laravel (.env)**
```env
APP_NAME="AMT CRM"
APP_ENV=production
APP_KEY=base64:your-app-key
APP_DEBUG=false
APP_URL=http://api.himmanav.com

DB_CONNECTION=mysql
DB_HOST=host.docker.internal
DB_PORT=3306
DB_DATABASE=amt_crm_backend
DB_USERNAME=amt_crm_user
DB_PASSWORD=secure_password
```

#### **Database (.env)**
```env
MYSQL_ROOT_PASSWORD=secure_root_password
MYSQL_DATABASE=amt_crm_backend
MYSQL_USER=amt_crm_user
MYSQL_PASSWORD=secure_password
```

### **Nginx Configuration**

The Nginx configuration includes:
- **Rate Limiting**: API endpoints protected
- **Security Headers**: XSS protection, content type validation
- **Caching**: Static files and API responses
- **Compression**: Gzip for better performance
- **CORS**: Proper CORS headers for API

## 📊 Monitoring

### **Health Checks**
```bash
# Check all services
./scripts/monitor.sh all

# Check specific components
./scripts/monitor.sh containers    # Container status
./scripts/monitor.sh health        # Application health
./scripts/monitor.sh resources     # System resources
./scripts/monitor.sh database      # Database performance
./scripts/monitor.sh logs          # Error logs
./scripts/monitor.sh security      # Security status
./scripts/monitor.sh performance   # Performance metrics
```

### **Monitoring Endpoints**
- **Health Check**: `http://api.himmanav.com/health`
- **API Routes**: `http://api.himmanav.com/api/routes`
- **Application Status**: `http://api.himmanav.com/api/status`

### **Log Files**
- **Application Logs**: `/srv/laravel-backend/storage/logs/`
- **Nginx Logs**: `/srv/nginx/logs/`
- **Database Logs**: `/srv/database/logs/`
- **Monitoring Logs**: `/srv/monitoring.log`

## 🔄 Maintenance

### **Regular Tasks**

#### **Daily**
```bash
# Check system health
./scripts/monitor.sh health

# Review error logs
./scripts/monitor.sh logs

# Check resource usage
./scripts/monitor.sh resources
```

#### **Weekly**
```bash
# Performance analysis
./scripts/monitor.sh performance

# Security audit
./scripts/monitor.sh security

# Generate monitoring report
./scripts/monitor.sh report
```

#### **Monthly**
```bash
# Full system backup
docker exec amt_crm_db mysqldump -u root -p --all-databases > /srv/backups/full_backup_$(date +%Y%m%d).sql

# Clean up old logs
find /srv -name "*.log" -mtime +30 -delete

# Update system packages
sudo apt update && sudo apt upgrade -y
```

### **Backup Strategy**
- **Database**: Daily automated backups
- **Application**: Version controlled
- **Configuration**: Backed up to secure location
- **Logs**: Rotated and archived

## 🛠️ Troubleshooting

### **Common Issues**

#### **Container Won't Start**
```bash
# Check logs
docker-compose logs <service-name>

# Check disk space
df -h

# Check memory
free -h

# Restart services
docker-compose restart
```

#### **Database Connection Issues**
```bash
# Check database container
docker exec amt_crm_db mysqladmin ping -u root -p

# Check environment variables
docker exec amt_crm_app env | grep DB_

# Reset database container
docker-compose down
docker volume rm amt_crm_backend_mysql_data
docker-compose up -d
```

#### **Application Errors**
```bash
# Check Laravel logs
docker exec amt_crm_app tail -f /var/www/storage/logs/laravel.log

# Check permissions
docker exec amt_crm_app ls -la /var/www/storage

# Fix permissions
docker exec amt_crm_app chown -R www:www /var/www/storage
```

#### **Performance Issues**
```bash
# Check resource usage
docker stats

# Check slow queries
docker exec amt_crm_db tail -f /var/log/mysql/slow.log

# Restart with resource limits
docker-compose down
docker-compose up -d --scale app=1
```

## 🔒 Security Considerations

### **HTTP-Only Deployment**
- **No SSL/HTTPS**: Configured for HTTP-only as requested
- **Internal Network**: All containers in private Docker network
- **Firewall**: Only port 80 exposed externally
- **Rate Limiting**: API endpoints protected against abuse

### **Security Features**
- **Non-root Users**: All containers run as non-root
- **Security Headers**: XSS protection, content type validation
- **Input Validation**: All user inputs validated and sanitized
- **Environment Protection**: Sensitive data in environment variables

### **Access Control**
- **Database**: Only accessible from application container
- **File System**: Proper permissions and ownership
- **Network**: Isolated Docker network
- **API**: Rate limited and validated

## 📈 Scaling

### **Vertical Scaling**
- Increase VPS resources (CPU, RAM, Storage)
- Optimize PHP and MySQL configurations
- Enable additional caching layers

### **Horizontal Scaling**
- Add load balancer
- Deploy multiple application instances
- Implement database replication
- Use Redis for session storage

### **Performance Optimization**
- Enable OPcache for PHP
- Configure MySQL query cache
- Implement CDN for static assets
- Use Redis for caching

## 📞 Support

### **Documentation**
- **API Documentation**: `/docs/api.md`
- **Deployment Guide**: `/docs/deployment.md`
- **Development Guide**: `/docs/development.md`

### **Monitoring**
- **Health Checks**: Automated monitoring
- **Alerting**: Email/Slack notifications
- **Logging**: Centralized log management
- **Metrics**: Performance tracking

### **Maintenance**
- **Backups**: Automated backup system
- **Updates**: Security and feature updates
- **Monitoring**: 24/7 system monitoring
- **Support**: Technical support available

---

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

---

**Last Updated**: $(date)
**Version**: 2.0
**Status**: Production Ready ✅
