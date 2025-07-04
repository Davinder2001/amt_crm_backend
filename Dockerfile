# Multi-stage build for better security and smaller image
FROM php:8.2-fpm-alpine AS base

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    libpng-dev \
    libjpeg-dev \
    libfreetype-dev \
    libzip-dev \
    zip \
    git \
    unzip \
    oniguruma-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd zip

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
RUN npm ci --only=production

# Copy application code
COPY . .

# Build frontend assets
RUN npm run build

# Remove Node.js and npm (not needed in production)
RUN apk del nodejs npm

# Create non-root user for security
RUN addgroup -g 1000 www && \
    adduser -u 1000 -G www -s /bin/sh -D www

# Set correct permissions
RUN chown -R www:www /var/www && \
    chmod -R 755 /var/www/storage /var/www/bootstrap/cache

# Switch to non-root user
USER www

# Expose port
EXPOSE 9000

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD php artisan --version || exit 1

# Start PHP-FPM
CMD ["php-fpm"] 