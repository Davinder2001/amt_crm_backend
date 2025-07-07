#!/bin/sh

# Function to handle shutdown gracefully
shutdown() {
    echo "Shutting down..."
    kill $NGINX_PID $PHP_FPM_PID
    wait $NGINX_PID $PHP_FPM_PID
    exit 0
}

# Trap signals for graceful shutdown
trap shutdown SIGTERM SIGINT

# Check if SSL certificates exist
if [ ! -f /etc/letsencrypt/live/api.himmanav.com/fullchain.pem ]; then
    echo "SSL certificates not found. Setting up development configuration..."
    
    # Create a temporary nginx config without SSL for initial setup
    cat > /etc/nginx/conf.d/default.conf << 'EOF'
server {
    listen 80;
    server_name api.himmanav.com;
    root /var/www/public;
    index index.php index.html index.htm;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/javascript;

    # Handle Laravel routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Handle PHP files
    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ /\.ht {
        deny all;
    }

    # Cache static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|pdf|txt)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Handle Laravel storage links
    location /storage {
        alias /var/www/storage/app/public;
        try_files $uri $uri/ =404;
    }

    # Health check endpoint
    location /health {
        access_log off;
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }
    
    # Let's Encrypt challenge
    location /.well-known/acme-challenge/ {
        root /var/www/public;
    }
}
EOF
else
    echo "SSL certificates found. Using production configuration..."
    # Copy the production nginx config with SSL
    cp /var/www/infra/nginx/default.conf /etc/nginx/conf.d/default.conf
fi

# Start nginx in the background
nginx -g "daemon off;" &
NGINX_PID=$!

# Start PHP-FPM in the background
php-fpm &
PHP_FPM_PID=$!

# Wait for both processes
wait $NGINX_PID $PHP_FPM_PID 