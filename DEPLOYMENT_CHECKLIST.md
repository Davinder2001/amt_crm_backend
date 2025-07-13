# AMT CRM Backend - Deployment Checklist

## ✅ Pre-Deployment Verification

### 1. Directory Structure
- [x] `docker/nginx/ssl/` - SSL certificates directory
- [x] `docker/nginx/logs/` - Nginx logs directory  
- [x] `docker/nginx/conf.d/` - Additional Nginx configs
- [x] `docker/mysql/init/` - MySQL initialization scripts
- [x] `docker/php/` - PHP configuration
- [x] `backups/` - Backup storage directory

### 2. Configuration Files
- [x] `docker-compose.yml` - Main orchestration file
- [x] `Dockerfile` - Application container build
- [x] `docker/nginx/proxy.conf` - Nginx reverse proxy
- [x] `docker/php/php.ini` - PHP production settings
- [x] `docker/mysql/my.cnf` - MySQL optimization
- [x] `create-env.sh` - Environment file generator
- [x] `docker-entrypoint.sh` - Container startup script

### 3. GitHub Actions Workflow
- [x] `.github/workflows/deploy_backend.yml` - CI/CD pipeline
- [x] Updated to use `root` user for SSH
- [x] Environment variables properly configured
- [x] Deployment package creation optimized

### 4. Application Files
- [x] `app/Console/Commands/HealthCheckCommand.php` - Health check
- [x] All Laravel application files present
- [x] `composer.json` and `composer.lock` - Dependencies
- [x] `package.json` and `package-lock.json` - Frontend assets

## 🔧 VPS Setup Requirements

### 1. System Requirements
- [ ] Ubuntu 24.04 LTS
- [ ] 4 CPU cores
- [ ] 16GB RAM
- [ ] 80GB+ SSD storage
- [ ] Docker 24.0+ installed
- [ ] Docker Compose 2.0+ installed

### 2. Network Configuration
- [ ] Port 80 open (HTTP)
- [ ] Port 443 open (HTTPS)
- [ ] Port 22 open (SSH)
- [ ] Domain `api.himmanav.com` pointing to VPS IP

### 3. SSL Certificates
- [ ] SSL certificate for `api.himmanav.com`
- [ ] Certificate files in `docker/nginx/ssl/`
- [ ] Proper file permissions (600)

## 🔐 GitHub Secrets Configuration

### Required Secrets
- [ ] `PROD_HOST` = `31.97.186.147`
- [ ] `PROD_SSH_KEY` = Your private SSH key
- [ ] `PROD_PORT` = `22` (optional, defaults to 22)
- [ ] `APP_KEY` = Laravel application key
- [ ] `APP_URL` = `https://api.himmanav.com`
- [ ] `DB_DATABASE` = `amt_crm_backend`
- [ ] `DB_USERNAME` = `amt_crm_user`
- [ ] `DB_PASSWORD` = Secure database password
- [ ] `REDIS_HOST` = `127.0.0.1`
- [ ] `REDIS_PORT` = `6379`
- [ ] `REDIS_DB` = `0`
- [ ] `MAIL_*` = Email configuration
- [ ] `AWS_DEFAULT_REGION` = `ap-south-1a`

## 🚀 Deployment Steps

### 1. Initial VPS Setup
```bash
# SSH to VPS as root
ssh root@31.97.186.147

# Create deployment directories
mkdir -p /srv/laravel-backend
mkdir -p /srv/nextjs-frontend

# Set proper permissions
chmod 755 /srv/laravel-backend
chmod 755 /srv/nextjs-frontend
```

### 2. SSL Certificate Setup
```bash
# Install Certbot
apt update && apt install -y certbot

# Obtain SSL certificate
certbot certonly --standalone -d api.himmanav.com --email himmanav11@gmail.com

# Copy certificates to project directory
cp /etc/letsencrypt/live/api.himmanav.com/fullchain.pem /srv/laravel-backend/docker/nginx/ssl/api.himmanav.com.crt
cp /etc/letsencrypt/live/api.himmanav.com/privkey.pem /srv/laravel-backend/docker/nginx/ssl/api.himmanav.com.key

# Set proper permissions
chmod 600 /srv/laravel-backend/docker/nginx/ssl/api.himmanav.com.*
```

### 3. Trigger Deployment
```bash
# Push to production branch to trigger GitHub Actions
git push origin production
```

## 🔍 Post-Deployment Verification

### 1. Container Status
```bash
# Check all containers are running
docker compose ps

# Check container logs
docker compose logs -f
```

### 2. Application Health
```bash
# Test health endpoint
curl -k https://api.himmanav.com/health

# Test database connection
docker compose exec app php artisan tinker
```

### 3. SSL Certificate
```bash
# Verify SSL certificate
openssl s_client -connect api.himmanav.com:443 -servername api.himmanav.com
```

### 4. Performance Check
```bash
# Check resource usage
docker stats

# Check disk space
df -h

# Check memory usage
free -h
```

## 🛠️ Troubleshooting

### Common Issues
1. **Container won't start**: Check logs with `docker compose logs <service>`
2. **Database connection failed**: Verify environment variables
3. **SSL certificate issues**: Check certificate paths and permissions
4. **Permission denied**: Ensure proper file ownership and permissions

### Useful Commands
```bash
# Restart services
docker compose restart

# Rebuild containers
docker compose build --no-cache

# Check environment variables
docker compose exec app env | grep DB_

# View real-time logs
docker compose logs -f --tail=100
```

## 📊 Monitoring

### Health Checks
- [ ] Application health endpoint responding
- [ ] Database connection stable
- [ ] Redis connection working
- [ ] SSL certificate valid
- [ ] All containers running

### Performance Metrics
- [ ] Response times under 500ms
- [ ] Memory usage under 80%
- [ ] CPU usage under 70%
- [ ] Disk usage under 80%

## 🔄 Maintenance

### Regular Tasks
- [ ] SSL certificate renewal (every 90 days)
- [ ] Database backups (daily)
- [ ] Log rotation (weekly)
- [ ] Security updates (monthly)
- [ ] Performance monitoring (ongoing)

### Backup Strategy
- [ ] Database dumps stored in `backups/`
- [ ] Storage files backed up
- [ ] Configuration files version controlled
- [ ] Recovery procedures documented

---

**Status**: ✅ Ready for deployment
**Last Updated**: $(date)
**Next Review**: After first deployment 

---

## 1. **Install Composer on Your VM**

Run:
```bash
sudo apt update
sudo apt install composer
```

---

## 2. **Install Missing Dev Dependencies**

From your project root (`/home/suraj/spark web solution/amt_crm_backend`), run:
```bash
composer require --dev phpstan/phpstan friendsofphp/php-cs-fixer
```
This will update both `composer.json` and `composer.lock` with the required dev dependencies.

---

## 3. **Commit and Push the Changes**

Run:
```bash
<code_block_to_apply_changes_from>
```

---

## 4. **Verify CI/CD Pipeline**

- Go to your GitHub Actions tab.
- Wait for the pipeline to run.
- Ensure all steps (test, build, security) pass.

---

## 5. **Deploy on the VM**

Once the pipeline is green, you can deploy on your VM using your improved scripts:

```bash
cd /srv/laravel-backend
chmod +x deploy-separate.sh
./deploy-separate.sh
```

---

## 6. **Monitor and Verify**

- Use the monitoring script:
  ```bash
  ./scripts/monitor.sh all
  ```
- Check health endpoints:
  - `http://api.himmanav.com/health`
  - `http://api.himmanav.com/api/routes`

---

## 7. **If You Need HTTPS in the Future**

You can add SSL certificates and update your Nginx config, but for now, you are set for HTTP-only as requested.

---

### **Let me know when Composer is installed, or if you want me to run the commands for you!**
If you want, I can run each command for you one by one and guide you through any issues that come up. Just say "continue" when ready! 