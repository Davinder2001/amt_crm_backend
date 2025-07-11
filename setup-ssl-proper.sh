#!/bin/bash

# Comprehensive SSL Setup Script for AMT CRM Backend
# This script sets up SSL certificates for api.himmanav.com

set -e

echo "🔐 Setting up SSL certificates for AMT CRM Backend..."

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "❌ This script must be run as root"
    exit 1
fi

# Configuration
DOMAIN="api.himmanav.com"
EMAIL="himmanav11@gmail.com"
VPS_IP="31.97.186.147"
PROJECT_DIR="/srv/laravel-backend"
SSL_DIR="$PROJECT_DIR/docker/nginx/ssl"

echo "📋 Configuration:"
echo "   Domain: $DOMAIN"
echo "   Email: $EMAIL"
echo "   VPS IP: $VPS_IP"
echo "   Project Directory: $PROJECT_DIR"
echo ""

# Update system
echo "📦 Updating system packages..."
apt update

# Install required packages
echo "🔧 Installing required packages..."
apt install -y certbot openssl

# Create SSL directory
echo "📁 Creating SSL directory..."
mkdir -p "$SSL_DIR"

# Check if domain is pointing to correct IP
echo "🌐 Checking DNS configuration..."
CURRENT_IP=$(nslookup $DOMAIN | grep -A1 "Name:" | tail -1 | awk '{print $2}' | tr -d '\n')

if [ "$CURRENT_IP" != "$VPS_IP" ]; then
    echo "⚠️  WARNING: Domain $DOMAIN is pointing to $CURRENT_IP instead of $VPS_IP"
    echo "   Please update your DNS settings to point $DOMAIN to $VPS_IP"
    echo ""
    echo "📝 DNS Update Required:"
    echo "   Type: A"
    echo "   Name: api"
    echo "   Value: $VPS_IP"
    echo "   TTL: 300"
    echo ""
    echo "🔄 Creating self-signed certificate for testing..."
    
    # Create self-signed certificate for testing
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout "$SSL_DIR/$DOMAIN.key" \
        -out "$SSL_DIR/$DOMAIN.crt" \
        -subj "/C=IN/ST=Delhi/L=Delhi/O=AMT CRM/OU=IT/CN=$DOMAIN/emailAddress=$EMAIL"
    
    echo "✅ Self-signed certificate created for testing"
    echo "   Certificate: $SSL_DIR/$DOMAIN.crt"
    echo "   Private Key: $SSL_DIR/$DOMAIN.key"
    
else
    echo "✅ Domain $DOMAIN is correctly pointing to $VPS_IP"
    echo ""
    
    # Stop any existing web servers to free port 80
    echo "🛑 Stopping existing web servers..."
    systemctl stop nginx 2>/dev/null || true
    docker compose -f "$PROJECT_DIR/docker-compose.yml" down 2>/dev/null || true
    
    # Obtain SSL certificate from Let's Encrypt
    echo "🎫 Obtaining SSL certificate from Let's Encrypt..."
    certbot certonly --standalone \
        -d $DOMAIN \
        --email $EMAIL \
        --agree-tos \
        --non-interactive
    
    # Copy certificates to project directory
    echo "📋 Copying certificates to project directory..."
    cp "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" "$SSL_DIR/$DOMAIN.crt"
    cp "/etc/letsencrypt/live/$DOMAIN/privkey.pem" "$SSL_DIR/$DOMAIN.key"
    
    echo "✅ Let's Encrypt certificate obtained successfully"
fi

# Set proper permissions
echo "🔒 Setting proper permissions..."
chmod 600 "$SSL_DIR/$DOMAIN."*
chown root:root "$SSL_DIR/$DOMAIN."*

# Create renewal script
echo "🔄 Creating certificate renewal script..."
cat > "$PROJECT_DIR/renew-ssl.sh" << 'EOF'
#!/bin/bash
# SSL Certificate Renewal Script

set -e

echo "🔄 Renewing SSL certificates..."

DOMAIN="api.himmanav.com"
PROJECT_DIR="/srv/laravel-backend"
SSL_DIR="$PROJECT_DIR/docker/nginx/ssl"

# Renew certificates
certbot renew --quiet

# Copy renewed certificates
cp "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" "$SSL_DIR/$DOMAIN.crt"
cp "/etc/letsencrypt/live/$DOMAIN/privkey.pem" "$SSL_DIR/$DOMAIN.key"

# Set permissions
chmod 600 "$SSL_DIR/$DOMAIN."*
chown root:root "$SSL_DIR/$DOMAIN."*

# Reload nginx container
cd "$PROJECT_DIR"
docker compose exec nginx-proxy nginx -s reload

echo "✅ SSL certificates renewed successfully!"
EOF

chmod +x "$PROJECT_DIR/renew-ssl.sh"

# Add to crontab for automatic renewal (only for Let's Encrypt certificates)
if [ "$CURRENT_IP" = "$VPS_IP" ]; then
    echo "⏰ Setting up automatic renewal..."
    (crontab -l 2>/dev/null; echo "0 12 * * * $PROJECT_DIR/renew-ssl.sh") | crontab -
    echo "✅ Automatic renewal scheduled for daily at 12:00 PM"
fi

# Create test script
echo "🧪 Creating SSL test script..."
cat > "$PROJECT_DIR/test-ssl.sh" << 'EOF'
#!/bin/bash
# SSL Test Script

echo "🔍 Testing SSL configuration..."

DOMAIN="api.himmanav.com"
PROJECT_DIR="/srv/laravel-backend"

# Test certificate validity
echo "📋 Certificate Information:"
openssl x509 -in "$PROJECT_DIR/docker/nginx/ssl/$DOMAIN.crt" -text -noout | grep -E "(Subject:|Issuer:|Not After)"

# Test HTTPS connection (if domain is accessible)
echo ""
echo "🌐 Testing HTTPS connection..."
curl -I --connect-timeout 10 "https://$DOMAIN/health" 2>/dev/null || echo "⚠️  HTTPS connection test failed (domain may not be accessible yet)"

echo ""
echo "✅ SSL test completed!"
EOF

chmod +x "$PROJECT_DIR/test-ssl.sh"

echo ""
echo "✅ SSL setup completed successfully!"
echo ""
echo "📋 Summary:"
echo "   Certificate: $SSL_DIR/$DOMAIN.crt"
echo "   Private Key: $SSL_DIR/$DOMAIN.key"
echo "   Renewal Script: $PROJECT_DIR/renew-ssl.sh"
echo "   Test Script: $PROJECT_DIR/test-ssl.sh"
echo ""
echo "🚀 Next steps:"
echo "1. Deploy the updated Docker setup"
echo "2. Test HTTPS access: https://$DOMAIN/health"
echo "3. Run SSL test: $PROJECT_DIR/test-ssl.sh"
echo ""
if [ "$CURRENT_IP" != "$VPS_IP" ]; then
    echo "⚠️  IMPORTANT: Update your DNS settings to point $DOMAIN to $VPS_IP"
    echo "   Then run: $PROJECT_DIR/renew-ssl.sh"
fi
echo ""
echo "🔧 SSL certificates will auto-renew every 90 days (if using Let's Encrypt)" 