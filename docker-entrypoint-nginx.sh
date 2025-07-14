#!/bin/bash
set -e

# Create nginx cache directories if they don't exist
mkdir -p /var/cache/nginx
mkdir -p /var/cache/nginx/client_temp
mkdir -p /var/cache/nginx/fastcgi_temp
mkdir -p /var/cache/nginx/proxy_temp
mkdir -p /var/cache/nginx/scgi_temp
mkdir -p /var/cache/nginx/uwsgi_temp

# Set proper permissions
chown -R nginx:nginx /var/cache/nginx

# Execute the original command (nginx)
exec "$@" 