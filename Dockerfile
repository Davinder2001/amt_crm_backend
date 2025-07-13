# Multi-stage build for production
FROM php:8.2-fpm-alpine AS base

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    libpng-dev \
    jpeg-dev \
    freetype-dev \
    libzip-dev \
    zip \
    git \
    unzip \
    oniguruma-dev \
    libxml2-dev \
    wget \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd zip

# Create non-root user for security
RUN addgroup -g 1000 www && \
    adduser -u 1000 -G www -s /bin/sh -D www

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy composer files and install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

# Copy package files (skip Node.js build for now)
COPY package.json package-lock.json ./

# Copy application code
COPY . .

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Set correct permissions
RUN chown -R www:www /var/www && \
    chmod -R 755 /var/www/storage /var/www/bootstrap/cache

# Production stage
FROM base AS production

# Install only production dependencies
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

# Configure PHP for production
RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory-limit.ini && \
    echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/memory-limit.ini && \
    echo "upload_max_filesize = 50M" >> /usr/local/etc/php/conf.d/memory-limit.ini && \
    echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/memory-limit.ini

# Configure PHP-FPM for production
RUN sed -i 's/user = www-data/user = www/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/group = www-data/group = www/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/listen.owner = www-data/listen.owner = www/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/listen.group = www-data/listen.group = www/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/pm.max_children = 5/pm.max_children = 10/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/pm.start_servers = 2/pm.start_servers = 3/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/pm.min_spare_servers = 1/pm.min_spare_servers = 2/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/pm.max_spare_servers = 3/pm.max_spare_servers = 4/' /usr/local/etc/php-fpm.d/www.conf

# Switch to non-root user
USER www

# Expose port
EXPOSE 9000

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD php artisan health:check || exit 1

# Start PHP-FPM with entrypoint
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"] 