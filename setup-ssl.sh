#!/bin/bash

# SSL Certificate Setup Script for api.himmanav.com
# This script should be run on the server to obtain SSL certificates

set -e

DOMAIN="api.himmanav.com"
EMAIL="admin@himmanav.com"  # Replace with your actual email

echo "Setting up SSL certificates for $DOMAIN..."

# Check if certbot is installed
if ! command -v certbot &> /dev/null; then
    echo "Certbot is not installed. Installing..."
    apk add --no-cache certbot certbot-nginx
fi

# Create necessary directories
mkdir -p /etc/letsencrypt
mkdir -p /var/www/public/.well-known/acme-challenge

# Set proper permissions
chown -R www:www /etc/letsencrypt
chown -R www:www /var/www/public/.well-known

# Stop nginx temporarily for certificate generation
echo "Stopping nginx temporarily..."
nginx -s stop || true

# Obtain SSL certificate using standalone mode
echo "Obtaining SSL certificate from Let's Encrypt..."
certbot certonly \
    --standalone \
    --email $EMAIL \
    --agree-tos \
    --no-eff-email \
    --domains $DOMAIN \
    --non-interactive

# Set proper permissions for certificates
chown -R www:www /etc/letsencrypt/live/$DOMAIN
chown -R www:www /etc/letsencrypt/archive/$DOMAIN

echo "SSL certificates obtained successfully!"
echo "Certificate location: /etc/letsencrypt/live/$DOMAIN/"

# Test certificate
echo "Testing certificate..."
openssl x509 -in /etc/letsencrypt/live/$DOMAIN/fullchain.pem -text -noout | grep "Subject:"

# Set up automatic renewal
echo "Setting up automatic renewal..."
echo "0 12 * * * /usr/bin/certbot renew --quiet --deploy-hook 'nginx -s reload'" | crontab -

echo "SSL setup completed successfully!"
echo "You can now restart your application with HTTPS support." 