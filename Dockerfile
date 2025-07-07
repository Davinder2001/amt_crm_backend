# Multi-stage build for better security and smaller image
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
    nginx \
    wget \
    certbot \
    certbot-nginx \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd zip

# Create non-root user for security (do this early)
RUN addgroup -g 1000 www && \
    adduser -u 1000 -G www -s /bin/sh -D www

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Install Node.js 20.x LTS
RUN apk add --no-cache nodejs npm

# Set working directory
WORKDIR /var/www

# Copy composer files and install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

# Copy package files and install Node.js dependencies
COPY package.json package-lock.json ./
RUN npm ci

# Copy application code
COPY . .

# Build frontend assets
RUN npm run build

# Remove Node.js and npm (not needed in production)
RUN apk del nodejs npm

# Create nginx directories and set permissions (now www user exists)
RUN mkdir -p /run/nginx /var/lib/nginx/logs /var/log/nginx /etc/letsencrypt && \
    chown -R www:www /var/lib/nginx /var/log/nginx /run/nginx /etc/letsencrypt

# Copy nginx configuration
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf

# Configure PHP-FPM to run as www user
RUN sed -i 's/user = www-data/user = www/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/group = www-data/group = www/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/listen.owner = www-data/listen.owner = www/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/listen.group = www-data/listen.group = www/' /usr/local/etc/php-fpm.d/www.conf

# Copy startup script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Set correct permissions
RUN chown -R www:www /var/www && \
    chmod -R 755 /var/www/storage /var/www/bootstrap/cache && \
    chown www:www /usr/local/bin/docker-entrypoint.sh

# Switch to non-root user
USER www

# Expose ports
EXPOSE 80 443

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD wget --quiet --tries=1 --spider http://localhost/health || exit 1

# Start both nginx and PHP-FPM
ENTRYPOINT ["docker-entrypoint.sh"] 