#!/bin/sh

# Wait for database to be ready
echo "Waiting for database connection..."
until php artisan tinker --execute="DB::connection()->getPdo();" 2>/dev/null; do
    echo "Database not ready, waiting..."
    sleep 5
done

# Ensure bootstrap/cache directory exists and has proper permissions
mkdir -p bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Set proper permissions (only if running as root)
if [ "$(id -u)" = "0" ]; then
    chown -R www:www storage bootstrap/cache
else
    # If not running as root, ensure the current user can write to these directories
    chmod -R 775 storage bootstrap/cache
fi

# Generate autoloader and run composer scripts
echo "Setting up Laravel application..."
composer dump-autoload --optimize --no-dev
composer run-scripts post-autoload-dump --no-dev

# Clear any existing cached files
rm -rf bootstrap/cache/* 2>/dev/null || true

# Run migrations
echo "Running database migrations..."
php artisan migrate --force

# Clear and rebuild caches
echo "Rebuilding application caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches for production (skip route cache due to compatibility issues)
php artisan config:cache
# php artisan route:cache  # Skipped due to Laravel compatibility issues
php artisan view:cache

echo "Laravel application setup complete!"

# Execute the main command
exec "$@" 