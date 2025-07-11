#!/bin/bash

# SSL Setup Script for AMT CRM Backend
# This script sets up SSL certificates for api.himmanav.com

set -e

echo "🔐 Setting up SSL certificates for api.himmanav.com..."

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "❌ This script must be run as root"
    exit 1
fi

# Update system
echo "📦 Updating system packages..."
apt update

# Install certbot
echo "🔧 Installing Certbot..."
apt install -y certbot

# Create SSL directory
echo "📁 Creating SSL directory..."
mkdir -p /srv/laravel-backend/docker/nginx/ssl

# Stop nginx if running to free port 80
echo "🛑 Stopping any existing web servers..."
systemctl stop nginx 2>/dev/null || true
docker compose -f /srv/laravel-backend/docker-compose.yml down 2>/dev/null || true

# Obtain SSL certificate
echo "🎫 Obtaining SSL certificate from Let's Encrypt..."
certbot certonly --standalone \
    -d api.himmanav.com \
    --email himmanav11@gmail.com \
    --agree-tos \
    --non-interactive

# Copy certificates to project directory
echo "📋 Copying certificates to project directory..."
cp /etc/letsencrypt/live/api.himmanav.com/fullchain.pem /srv/laravel-backend/docker/nginx/ssl/api.himmanav.com.crt
cp /etc/letsencrypt/live/api.himmanav.com/privkey.pem /srv/laravel-backend/docker/nginx/ssl/api.himmanav.com.key

# Set proper permissions
echo "🔒 Setting proper permissions..."
chmod 600 /srv/laravel-backend/docker/nginx/ssl/api.himmanav.com.*
chown root:root /srv/laravel-backend/docker/nginx/ssl/api.himmanav.com.*

# Create renewal script
echo "🔄 Creating certificate renewal script..."
cat > /srv/laravel-backend/renew-ssl.sh << 'EOF'
#!/bin/bash
# SSL Certificate Renewal Script

set -e

echo "🔄 Renewing SSL certificates..."

# Renew certificates
certbot renew --quiet

# Copy renewed certificates
cp /etc/letsencrypt/live/api.himmanav.com/fullchain.pem /srv/laravel-backend/docker/nginx/ssl/api.himmanav.com.crt
cp /etc/letsencrypt/live/api.himmanav.com/privkey.pem /srv/laravel-backend/docker/nginx/ssl/api.himmanav.com.key

# Set permissions
chmod 600 /srv/laravel-backend/docker/nginx/ssl/api.himmanav.com.*
chown root:root /srv/laravel-backend/docker/nginx/ssl/api.himmanav.com.*

# Reload nginx container
cd /srv/laravel-backend
docker compose exec nginx-proxy nginx -s reload

echo "✅ SSL certificates renewed successfully!"
EOF

chmod +x /srv/laravel-backend/renew-ssl.sh

# Add to crontab for automatic renewal
echo "⏰ Setting up automatic renewal..."
(crontab -l 2>/dev/null; echo "0 12 * * * /srv/laravel-backend/renew-ssl.sh") | crontab -

echo "✅ SSL setup completed successfully!"
echo ""
echo "📋 Next steps:"
echo "1. Update your domain DNS to point api.himmanav.com to 31.97.186.147"
echo "2. Deploy the updated Docker setup with: docker compose up -d"
echo "3. Test HTTPS access: https://api.himmanav.com/health"
echo ""
echo "🔧 SSL certificates will auto-renew every 90 days" 