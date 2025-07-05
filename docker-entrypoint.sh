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

# Start nginx in the background
nginx -g "daemon off;" &
NGINX_PID=$!

# Start PHP-FPM in the background
php-fpm &
PHP_FPM_PID=$!

# Wait for both processes
wait $NGINX_PID $PHP_FPM_PID 