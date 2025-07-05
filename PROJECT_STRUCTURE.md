# AMT CRM Backend - Project Structure

## Current vs Recommended Structure

### ✅ Current (Good)
```
amt_crm_backend/
├── app/                    # Laravel application logic
├── config/                 # Laravel configuration
├── database/              # Migrations, seeders, factories
├── routes/                # API and web routes
├── storage/               # Laravel storage
├── resources/             # Views and assets
├── public/                # Web-accessible files
├── bootstrap/             # Laravel bootstrap
├── tests/                 # Test files
├── infra/                 # Infrastructure configs
├── .github/               # GitHub Actions
├── Dockerfile             # Docker configuration
├── docker-compose.yml     # Docker orchestration
├── nginx.conf             # Nginx config (duplicate)
├── deploy.sh              # Deployment script
├── create-env.sh          # Environment setup
└── env-to-github-secrets.sh
```

### 🎯 Recommended (Industry Standard)
```
amt_crm_backend/
├── app/                    # Laravel application logic
├── config/                 # Laravel configuration
├── database/              # Migrations, seeders, factories
├── routes/                # API and web routes
├── storage/               # Laravel storage
├── resources/             # Views and assets
├── public/                # Web-accessible files
├── bootstrap/             # Laravel bootstrap
├── tests/                 # Test files
├── docker/                # Docker configurations
│   ├── nginx/
│   │   ├── nginx.conf
│   │   └── default.conf
│   ├── php/
│   │   └── php.ini
│   └── mysql/
│       └── my.cnf
├── scripts/               # Deployment and utility scripts
│   ├── deploy.sh
│   ├── create-env.sh
│   ├── env-to-github-secrets.sh
│   └── docker/
│       ├── build.sh
│       └── setup.sh
├── docs/                  # Documentation
│   ├── README.md
│   ├── DEPLOYMENT.md
│   ├── API.md
│   ├── DEVELOPMENT.md
│   └── docker/
│       └── SETUP.md
├── .github/               # GitHub Actions
├── Dockerfile             # Docker configuration
├── docker-compose.yml     # Docker orchestration
├── docker-entrypoint.sh   # Docker entrypoint
└── .dockerignore          # Docker ignore file
```

## Key Improvements

### 1. **Docker Configuration Consolidation**
- Move all Docker-related configs to `docker/` directory
- Separate nginx, PHP, and MySQL configurations
- Better organization for multi-service setup

### 2. **Scripts Organization**
- Group all scripts in `scripts/` directory
- Separate deployment, development, and Docker scripts
- Better maintainability and discoverability

### 3. **Documentation Structure**
- Centralized documentation in `docs/` directory
- Separate docs for different aspects (API, deployment, development)
- Better onboarding for new developers

### 4. **Remove Duplicates**
- Remove duplicate `nginx.conf` from root
- Use only the one in `infra/nginx/` or move to `docker/nginx/`

## Benefits of Recommended Structure

1. **Better Organization**: Related files are grouped together
2. **Easier Maintenance**: Clear separation of concerns
3. **Team Collaboration**: New developers can quickly understand the structure
4. **Scalability**: Easy to add new services or configurations
5. **Industry Standard**: Follows common Laravel + Docker best practices

## Migration Steps

1. Create new directory structure
2. Move files to appropriate locations
3. Update Docker Compose volume mappings
4. Update CI/CD pipeline paths
5. Update documentation references
6. Test deployment process 