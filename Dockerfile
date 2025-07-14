# Multi-stage build for production
FROM php:8.2-fpm-alpine AS base

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
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd zip

RUN addgroup -g 1000 www && \
    adduser -u 1000 -G www -s /bin/sh -D www

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

COPY . .

# Install Node.js 22.17.0 and npm
RUN apk add --no-cache curl && \
    curl -fsSL https://unofficial-builds.nodejs.org/download/release/v22.17.0/node-v22.17.0-linux-x64-musl.tar.xz -o node.tar.xz && \
    tar -xJf node.tar.xz -C /usr/local --strip-components=1 && \
    rm node.tar.xz && \
    ln -sf /usr/local/bin/node /usr/bin/node && \
    ln -sf /usr/local/bin/npm /usr/bin/npm

# Build frontend assets
RUN npm run build

# Remove Node.js and npm (not needed in production)
RUN rm -rf /usr/local/bin/node /usr/local/bin/npm /usr/local/lib/node_modules /usr/bin/node /usr/bin/npm

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

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

RUN mkdir -p /var/www/bootstrap/cache && \
    chown -R www:www /var/www && \
    chmod -R 755 /var/www/storage && \
    chmod -R 775 /var/www/bootstrap/cache && \
    chmod -R 777 /var/www/bootstrap/cache

FROM base AS production

# Expose ports
EXPOSE 80 443

RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory-limit.ini && \
    echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/memory-limit.ini && \
    echo "upload_max_filesize = 50M" >> /usr/local/etc/php/conf.d/memory-limit.ini && \
    echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/memory-limit.ini

RUN sed -i 's/user = www-data/user = www/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/group = www-data/group = www/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/listen.owner = www-data/listen.owner = www/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/listen.group = www-data/listen.group = www/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/pm.max_children = 5/pm.max_children = 10/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/pm.start_servers = 2/pm.start_servers = 3/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/pm.min_spare_servers = 1/pm.min_spare_servers = 2/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/pm.max_spare_servers = 3/pm.max_spare_servers = 4/' /usr/local/etc/php-fpm.d/www.conf

EXPOSE 9000

HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD ps aux | grep php-fpm | grep -v grep || exit 1

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"] 