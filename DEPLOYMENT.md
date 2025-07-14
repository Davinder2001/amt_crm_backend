# AMT CRM Deployment Guide

## Automatic Deployment Setup

### 1. VPS Configuration (Already Done)

The VPS is configured with:
- **Directory**: `/srv/`
- **Docker Compose**: `/srv/docker-compose.yml`
- **Nginx Config**: `/srv/nginx/nginx.conf`
- **Deployment Script**: `/srv/deploy.sh`

### 2. GitHub Repository Setup

#### Add GitHub Secrets:
Go to your GitHub repository → Settings → Secrets and variables → Actions

Add these secrets:
- `HOST`: `31.97.186.147`
- `USERNAME`: `root`
- `SSH_KEY`: Your private SSH key content
- `PORT`: `22`

#### Repository URLs:
Update the deployment scripts with your actual repository URLs:

**Backend Repository**: `https://github.com/your-username/amt_crm_backend.git`
**Frontend Repository**: `https://github.com/your-username/amt_crm_frontend.git`

### 3. Automatic Deployment

#### Option 1: GitHub Actions (Recommended)
1. Push the `.github/workflows/deploy.yml` file to your repository
2. Every push to `main` branch will automatically deploy
3. Manual deployment: Go to Actions → Deploy to VPS → Run workflow

#### Option 2: Manual Deployment
```bash
# SSH to your VPS
ssh -i ~/.ssh/id_ed25519 root@31.97.186.147

# Run deployment
cd /srv
./deploy.sh
```

### 4. Initial Setup

If starting fresh:
```bash
# SSH to your VPS
ssh -i ~/.ssh/id_ed25519 root@31.97.186.147

# Run initial setup
cd /srv
./setup.sh
```

### 5. Directory Structure

```
/srv/
├── docker-compose.yml          # Container orchestration
├── deploy.sh                   # Deployment script
├── setup.sh                    # Initial setup script
├── laravel-backend/            # Backend code (auto-cloned)
├── amt-crm-frontend/           # Frontend code (auto-cloned)
└── nginx/
    ├── nginx.conf             # Nginx configuration
    ├── logs/                  # Nginx logs
    └── ssl/                   # SSL certificates
```

### 6. Environment Variables

The system uses these environment variables:
- `NEXT_PUBLIC_API_BASE_URL=http://api.himmanav.com/api/v1`
- Database credentials are in docker-compose.yml

### 7. Domains

- **Frontend**: `http://himmanav.com`
- **Backend API**: `http://api.himmanav.com`

### 8. Troubleshooting

#### Check container status:
```bash
docker ps
```

#### View logs:
```bash
docker logs amt_crm_backend
docker logs amt_crm_frontend
docker logs amt_crm_nginx
```

#### Restart services:
```bash
cd /srv
docker compose restart
```

#### Rebuild containers:
```bash
cd /srv
docker compose up -d --build
``` 