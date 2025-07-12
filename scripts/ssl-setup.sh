#!/bin/bash

# SSL Certificate Setup and Renewal Script for AMT CRM Backend
# This script helps manage Let's Encrypt certificates

set -e

echo "🔐 AMT CRM SSL Certificate Management"
echo "====================================="

# Function to check if certificates exist
check_certificates() {
    if [ -f "docker/nginx/ssl/live/api.himmanav.com/fullchain.pem" ]; then
        echo "✅ Let's Encrypt certificates found"
        return 0
    else
        echo "❌ Let's Encrypt certificates not found"
        return 1
    fi
}

# Function to obtain initial certificates
obtain_certificates() {
    echo "📜 Obtaining Let's Encrypt certificates..."
    
    # Start nginx for ACME challenge
    docker compose up -d nginx-proxy
    
    # Wait for nginx to be ready
    echo "⏳ Waiting for Nginx to be ready..."
    sleep 10
    
    # Run certbot to obtain certificates
    docker compose run --rm certbot
    
    # Reload nginx to use new certificates
    docker compose exec nginx-proxy nginx -s reload
    
    echo "✅ Certificates obtained successfully!"
}

# Function to renew certificates
renew_certificates() {
    echo "🔄 Renewing Let's Encrypt certificates..."
    
    # Run certbot renewal
    docker compose run --rm certbot renew --webroot -w /var/www/certbot
    
    # Reload nginx to use renewed certificates
    docker compose exec nginx-proxy nginx -s reload
    
    echo "✅ Certificates renewed successfully!"
}

# Function to check certificate status
check_certificate_status() {
    echo "🔍 Checking certificate status..."
    
    if check_certificates; then
        echo "📋 Certificate details:"
        docker compose exec nginx-proxy openssl x509 -in /etc/letsencrypt/live/api.himmanav.com/fullchain.pem -text -noout | grep -E "(Subject:|Issuer:|Not Before|Not After)"
    else
        echo "❌ No certificates found"
    fi
}

# Function to test SSL connection
test_ssl() {
    echo "🧪 Testing SSL connection..."
    
    # Test local connection
    if curl -k -s https://localhost/health > /dev/null; then
        echo "✅ Local SSL connection working"
    else
        echo "❌ Local SSL connection failed"
    fi
    
    # Test domain connection (if available)
    if curl -k -s https://api.himmanav.com/health > /dev/null; then
        echo "✅ Domain SSL connection working"
    else
        echo "⚠️  Domain SSL connection failed (may be expected in development)"
    fi
}

# Main script logic
case "${1:-help}" in
    "setup")
        obtain_certificates
        ;;
    "renew")
        renew_certificates
        ;;
    "status")
        check_certificate_status
        ;;
    "test")
        test_ssl
        ;;
    "check")
        check_certificates
        ;;
    "help"|*)
        echo "Usage: $0 {setup|renew|status|test|check|help}"
        echo ""
        echo "Commands:"
        echo "  setup   - Obtain initial Let's Encrypt certificates"
        echo "  renew   - Renew existing certificates"
        echo "  status  - Check certificate status and details"
        echo "  test    - Test SSL connections"
        echo "  check   - Check if certificates exist"
        echo "  help    - Show this help message"
        echo ""
        echo "Note: This script is for development/testing. In production,"
        echo "certificates should be managed by the CI/CD pipeline."
        ;;
esac 