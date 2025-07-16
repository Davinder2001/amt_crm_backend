# Multi-stage build for production
# Stage 1: Node.js build stage
FROM node:22-alpine AS node-builder

WORKDIR /app

# Copy package files first for better caching
COPY package*.json ./

# Install dependencies
RUN npm ci --only=production --no-optional --legacy-peer-deps

# Copy source files
COPY . .

# Build frontend assets
RUN npm run build

# Stage 2: Composer dependencies stage
FROM composer:2.7 AS composer

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

# Stage 3: Production stage
FROM php:8.2-fpm-alpine AS production

# Install system dependencies
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
    certbot \
    certbot-nginx \
    bash \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apk del --no-cache \
        libpng-dev \
        jpeg-dev \
        freetype-dev \
        libzip-dev \
        oniguruma-dev \
        libxml2-dev

# Create non-root user
RUN addgroup -g 1000 www && \
    adduser -u 1000 -G www -s /bin/sh -D www

# Set working directory
WORKDIR /var/www

# Copy composer from composer stage
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Copy PHP dependencies from composer stage
COPY --from=composer /app/vendor ./vendor

# Copy application code
COPY . .

# Copy built assets from node-builder stage
COPY --from=node-builder /app/public/build ./public/build

# Create necessary directories and set permissions
RUN mkdir -p \
    /run/nginx \
    /var/lib/nginx/logs \
    /var/log/nginx \
    /etc/letsencrypt \
    /var/cache/nginx/client_temp \
    /var/www/bootstrap/cache \
    /var/www/storage/logs \
    /var/www/storage/framework/cache \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    && chown -R www:www \
        /var/lib/nginx \
        /var/log/nginx \
        /run/nginx \
        /etc/letsencrypt \
        /var/cache/nginx \
        /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache \
    && chmod -R 777 /var/www/storage/framework

# Copy nginx configuration
COPY nginx/nginx.combined.conf /etc/nginx/nginx.conf

# Configure PHP-FPM
RUN sed -i 's/user = www-data/user = www/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/group = www-data/group = www/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/listen.owner = www-data/listen.owner = www/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/listen.group = www-data/listen.group = www/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/pm.max_children = 5/pm.max_children = 10/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/pm.start_servers = 2/pm.start_servers = 3/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/pm.min_spare_servers = 1/pm.min_spare_servers = 2/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/pm.max_spare_servers = 3/pm.max_spare_servers = 4/' /usr/local/etc/php-fpm.d/www.conf

# PHP configuration
RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory-limit.ini && \
    echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/memory-limit.ini && \
    echo "upload_max_filesize = 50M" >> /usr/local/etc/php/conf.d/memory-limit.ini && \
    echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/memory-limit.ini && \
    echo "opcache.enable = 1" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.memory_consumption = 128" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.interned_strings_buffer = 8" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.max_accelerated_files = 4000" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.revalidate_freq = 2" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.fast_shutdown = 1" >> /usr/local/etc/php/conf.d/opcache.ini

# Expose ports
EXPOSE 80 443 9000

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD ps aux | grep php-fpm | grep -v grep || exit 1

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Switch to non-root user
USER www

# Entrypoint and command
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"] 