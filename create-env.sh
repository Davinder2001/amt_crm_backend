#!/bin/bash

# Function to safely escape environment variable values for .env file
escape_env_value() {
    local value="$1"
    # Remove any trailing whitespace and newlines
    value=$(echo "$value" | sed 's/[[:space:]]*$//' | tr -d '\n\r')
    # Escape quotes and backslashes
    value=$(echo "$value" | sed 's/\\/\\\\/g' | sed 's/"/\\"/g')
    echo "$value"
}

# Create .env.docker file with safe defaults
cat > .env.docker << 'EOF'
# Application Configuration
APP_NAME="AMT CRM"
APP_ENV=production
APP_KEY=base64:sCEsiEt3AiO1fKmObkwjDOEdt8FmChqfjQT7Z8hpYJs=
APP_DEBUG=false
APP_URL=https://api.himmanav.com
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
APP_MAINTENANCE_DRIVER=file

# PHP Configuration
PHP_CLI_SERVER_WORKERS=4
BCRYPT_ROUNDS=12

# Logging Configuration
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=amt_crm_backend
DB_USERNAME=amt_crm_user
DB_PASSWORD=your_secure_database_password_here
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

# Session Configuration
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_TABLE=sessions

# Cache Configuration
CACHE_STORE=database
DB_CACHE_TABLE=cache

# Queue Configuration
QUEUE_CONNECTION=database
DB_QUEUE_TABLE=jobs

# Redis Configuration
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_DB=0

# Broadcasting Configuration
BROADCAST_DRIVER=log
BROADCAST_CONNECTION=log

# Mail Configuration
MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS=no-reply@himmanav.com
MAIL_FROM_NAME=AMT CRM

# AWS Configuration
AWS_DEFAULT_REGION=ap-south-1a
AWS_USE_PATH_STYLE_ENDPOINT=false

# Filesystem Configuration
FILESYSTEM_DISK=local

# Vite Configuration
VITE_APP_NAME="AMT CRM"
EOF

# Now update specific values if environment variables are provided
if [ -n "$APP_KEY" ]; then
    escaped_key=$(escape_env_value "$APP_KEY")
    sed -i "s|^APP_KEY=.*|APP_KEY=$escaped_key|" .env.docker
fi

if [ -n "$APP_URL" ]; then
    escaped_url=$(escape_env_value "$APP_URL")
    sed -i "s|^APP_URL=.*|APP_URL=$escaped_url|" .env.docker
fi

if [ -n "$DB_DATABASE" ]; then
    escaped_db=$(escape_env_value "$DB_DATABASE")
    sed -i "s|^DB_DATABASE=.*|DB_DATABASE=$escaped_db|" .env.docker
fi

if [ -n "$DB_USERNAME" ]; then
    escaped_user=$(escape_env_value "$DB_USERNAME")
    sed -i "s|^DB_USERNAME=.*|DB_USERNAME=$escaped_user|" .env.docker
fi

if [ -n "$DB_PASSWORD" ]; then
    escaped_pass=$(escape_env_value "$DB_PASSWORD")
    sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$escaped_pass|" .env.docker
fi

if [ -n "$REDIS_HOST" ]; then
    escaped_redis_host=$(escape_env_value "$REDIS_HOST")
    sed -i "s|^REDIS_HOST=.*|REDIS_HOST=$escaped_redis_host|" .env.docker
fi

if [ -n "$REDIS_PORT" ]; then
    escaped_redis_port=$(escape_env_value "$REDIS_PORT")
    sed -i "s|^REDIS_PORT=.*|REDIS_PORT=$escaped_redis_port|" .env.docker
fi

if [ -n "$REDIS_DB" ]; then
    escaped_redis_db=$(escape_env_value "$REDIS_DB")
    sed -i "s|^REDIS_DB=.*|REDIS_DB=$escaped_redis_db|" .env.docker
fi

if [ -n "$MAIL_MAILER" ]; then
    escaped_mailer=$(escape_env_value "$MAIL_MAILER")
    sed -i "s|^MAIL_MAILER=.*|MAIL_MAILER=$escaped_mailer|" .env.docker
fi

if [ -n "$MAIL_SCHEME" ]; then
    escaped_scheme=$(escape_env_value "$MAIL_SCHEME")
    sed -i "s|^MAIL_SCHEME=.*|MAIL_SCHEME=$escaped_scheme|" .env.docker
fi

if [ -n "$MAIL_HOST" ]; then
    escaped_mail_host=$(escape_env_value "$MAIL_HOST")
    sed -i "s|^MAIL_HOST=.*|MAIL_HOST=$escaped_mail_host|" .env.docker
fi

if [ -n "$MAIL_PORT" ]; then
    escaped_mail_port=$(escape_env_value "$MAIL_PORT")
    sed -i "s|^MAIL_PORT=.*|MAIL_PORT=$escaped_mail_port|" .env.docker
fi

if [ -n "$MAIL_USERNAME" ]; then
    escaped_mail_user=$(escape_env_value "$MAIL_USERNAME")
    sed -i "s|^MAIL_USERNAME=.*|MAIL_USERNAME=$escaped_mail_user|" .env.docker
fi

if [ -n "$MAIL_PASSWORD" ]; then
    escaped_mail_pass=$(escape_env_value "$MAIL_PASSWORD")
    sed -i "s|^MAIL_PASSWORD=.*|MAIL_PASSWORD=$escaped_mail_pass|" .env.docker
fi

if [ -n "$MAIL_FROM_ADDRESS" ]; then
    escaped_from_addr=$(escape_env_value "$MAIL_FROM_ADDRESS")
    sed -i "s|^MAIL_FROM_ADDRESS=.*|MAIL_FROM_ADDRESS=$escaped_from_addr|" .env.docker
fi

if [ -n "$MAIL_FROM_NAME" ]; then
    escaped_from_name=$(escape_env_value "$MAIL_FROM_NAME")
    sed -i "s|^MAIL_FROM_NAME=.*|MAIL_FROM_NAME=$escaped_from_name|" .env.docker
fi

if [ -n "$AWS_DEFAULT_REGION" ]; then
    escaped_region=$(escape_env_value "$AWS_DEFAULT_REGION")
    sed -i "s|^AWS_DEFAULT_REGION=.*|AWS_DEFAULT_REGION=$escaped_region|" .env.docker
fi

# Ensure the file ends with a newline
echo "" >> .env.docker

echo "✅ .env.docker file created successfully" 