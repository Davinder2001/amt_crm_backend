# GitHub Actions Workflow Documentation

## Overview

This repository uses a **multi-stage, separated workflow architecture** following industry best practices for CI/CD. Each workflow has a specific responsibility and runs independently, providing better error isolation, debugging, and maintainability.

## Workflow Structure

### 🔄 **1. Build and Test** (`build.yml`)
**Trigger:** Push to `main` or `production` branches, Pull Requests
**Purpose:** Code quality, testing, and Docker image building

**Jobs:**
- **Test:** PHP testing, code quality checks, security scanning
- **Build:** Docker image building (production branch only)
- **Security:** Vulnerability scanning with Trivy

**Features:**
- MySQL service container for testing
- PHP 8.2 with all required extensions
- Node.js 18 for frontend assets
- PHPStan static analysis
- PHP CS Fixer code style checks
- Trivy security scanning
- Docker layer caching

### 🗄️ **2. Deploy Database** (`deploy-database.yml`)
**Trigger:** After successful `Build and Test` workflow
**Purpose:** Database migrations and schema updates

**Jobs:**
- **deploy-database:** Database deployment and migrations

**Features:**
- Downloads build artifacts from previous workflow
- Creates environment file from GitHub secrets
- Starts database container
- Runs Laravel migrations
- Handles database seeding (optional)
- Preserves existing data

### 🚀 **3. Deploy Application** (`deploy-application.yml`)
**Trigger:** After successful `Deploy Database` workflow
**Purpose:** Application deployment and optimization

**Jobs:**
- **deploy-application:** Main application deployment
- **health-check:** Post-deployment health verification

**Features:**
- Downloads build artifacts from previous workflow
- Stops only app container (preserves database and nginx)
- Builds and starts application container
- Clears and rebuilds Laravel caches
- Optimizes for production
- Health checks and monitoring
- Automatic rollback on failure

### 🔄 **4. Rollback Deployment** (`rollback.yml`)
**Trigger:** Manual workflow dispatch
**Purpose:** Emergency rollback to previous versions

**Jobs:**
- **rollback:** Execute rollback to specified commit
- **notify-rollback:** Rollback status notification

**Features:**
- Manual trigger with commit specification
- Automatic backup before rollback
- Rollback to specific commit or previous commit
- Health verification after rollback
- Automatic restoration if rollback fails

### 📊 **5. Monitor Application** (`monitor.yml`)
**Trigger:** Every 5 minutes (cron) or manual dispatch
**Purpose:** Continuous health monitoring and alerting

**Jobs:**
- **health-check:** Basic application health checks
- **performance-check:** Response time monitoring (manual)
- **resource-check:** Server resource monitoring (manual)
- **alert:** Alert system for failures

**Features:**
- Automated health checks every 5 minutes
- Performance monitoring
- Server resource monitoring
- Docker container status checks
- Alert system for failures

## Workflow Chain

```
Push to Production
       ↓
Build and Test (build.yml)
       ↓ (if successful)
Deploy Database (deploy-database.yml)
       ↓ (if successful)
Deploy Application (deploy-application.yml)
       ↓
Continuous Monitoring (monitor.yml)
```

## Benefits of This Architecture

### ✅ **Separation of Concerns**
- Each workflow has a single responsibility
- Easier to debug and maintain
- Independent failure handling

### ✅ **Better Error Isolation**
- Database issues don't affect application deployment
- Build failures don't trigger deployment
- Clear failure points and recovery

### ✅ **Improved Security**
- Security scanning in build phase
- Separate database and application deployments
- Rollback capabilities

### ✅ **Enhanced Monitoring**
- Continuous health checks
- Performance monitoring
- Resource monitoring
- Automated alerting

### ✅ **Flexibility**
- Manual rollback capabilities
- Independent workflow triggering
- Custom deployment strategies

## Required GitHub Secrets

### **Deployment Secrets**
- `PROD_HOST`: Production server IP/hostname
- `PROD_SSH_KEY`: SSH private key for server access
- `PROD_PORT`: SSH port (default: 22)

### **Application Secrets**
- `APP_KEY`: Laravel application key
- `APP_URL`: Application URL
- `APP_NAME`: Application name
- `APP_ENV`: Environment (production)
- `APP_DEBUG`: Debug mode (false)

### **Database Secrets**
- `DB_CONNECTION`: Database connection type
- `DB_HOST`: Database host
- `DB_PORT`: Database port
- `DB_DATABASE`: Database name
- `DB_USERNAME`: Database username
- `DB_PASSWORD`: Database password
- `DB_ROOT_PASSWORD`: Database root password

### **Mail Secrets**
- `MAIL_MAILER`: Mail driver
- `MAIL_HOST`: Mail host
- `MAIL_PORT`: Mail port
- `MAIL_USERNAME`: Mail username
- `MAIL_PASSWORD`: Mail password
- `MAIL_FROM_ADDRESS`: From email address
- `MAIL_FROM_NAME`: From name

### **Other Configuration Secrets**
- `AWS_DEFAULT_REGION`: AWS region
- `VITE_APP_NAME`: Vite application name
- Various Laravel configuration secrets

## Usage Instructions

### **Normal Deployment**
1. Push code to `production` branch
2. Workflows will automatically trigger in sequence
3. Monitor progress in GitHub Actions tab

### **Manual Rollback**
1. Go to Actions tab
2. Select "Rollback Deployment" workflow
3. Click "Run workflow"
4. Enter rollback reason and optional commit hash
5. Click "Run workflow"

### **Manual Monitoring**
1. Go to Actions tab
2. Select "Monitor Application" workflow
3. Click "Run workflow"
4. Choose monitoring type (health, performance, resources)

### **Troubleshooting**

#### **Build Failures**
- Check test results in build workflow
- Review PHPStan and CS Fixer output
- Check security scan results

#### **Database Deployment Failures**
- Check database connectivity
- Review migration errors
- Verify database credentials

#### **Application Deployment Failures**
- Check container logs
- Verify environment variables
- Review health check results

#### **Monitoring Alerts**
- Check application health endpoints
- Review server resources
- Check Docker container status

## Best Practices

### **Code Quality**
- Always run tests before deployment
- Use static analysis tools
- Follow coding standards

### **Security**
- Regular security scans
- Keep dependencies updated
- Use secure environment variables

### **Monitoring**
- Set up proper alerting
- Monitor application performance
- Track resource usage

### **Deployment**
- Use blue-green deployment when possible
- Implement proper rollback procedures
- Test deployments in staging first

## Migration from Old Workflow

The old single workflow (`deploy_backend.yml`) has been deprecated. The new workflow structure provides:

1. **Better separation of concerns**
2. **Improved error handling**
3. **Enhanced monitoring**
4. **Rollback capabilities**
5. **Security scanning**

To migrate:
1. Ensure all required secrets are set
2. Test the new workflow structure
3. Remove the old workflow when confident

## Support

For issues with workflows:
1. Check GitHub Actions logs
2. Review workflow documentation
3. Verify secret configuration
4. Test individual workflow components 