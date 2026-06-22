# Multi-stage build for optimized Cloud Run deployment
FROM composer:2.7 AS composer-build
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

FROM node:20-alpine AS node-build
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM php:8.2-fpm-alpine

# Copy Composer from official image
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    postgresql-client \
    libpq \
    libpng \
    libjpeg-turbo \
    libwebp \
    freetype \
    libzip \
    icu \
    oniguruma \
    && apk add --no-cache --virtual .build-deps \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    postgresql-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip intl opcache \
    && apk del .build-deps

# Install PHP extensions for production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "memory_limit=512M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time=500" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "upload_max_filesize=50M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size=50M" >> /usr/local/etc/php/conf.d/custom.ini

# Set working directory
WORKDIR /var/www

# Copy application files
COPY --chown=www-data:www-data . .

# Copy vendor from composer build
COPY --from=composer-build --chown=www-data:www-data /app/vendor ./vendor

# Copy built assets from node build
COPY --from=node-build --chown=www-data:www-data /app/public/build ./public/build

# Generate optimized autoloader
RUN composer dump-autoload --optimize --no-dev

# Copy nginx and supervisor configurations
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh /start.sh
COPY docker/queue-worker.sh /docker/queue-worker.sh

# Set permissions
RUN chmod +x /start.sh /docker/queue-worker.sh \
    && mkdir -p /var/www/storage/logs \
    && mkdir -p /var/www/storage/framework/{cache,sessions,views} \
    && mkdir -p /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Create supervisor and nginx directories
RUN mkdir -p /var/log/supervisor /run/nginx /var/tmp/nginx \
    && mkdir -p /var/tmp/nginx/{client_body,proxy,fastcgi,uwsgi,scgi} \
    && chown -R www-data:www-data /var/tmp/nginx \
    && chmod -R 755 /var/tmp/nginx

# Expose port for Cloud Run
EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD wget --no-verbose --tries=1 --spider http://localhost:8080/health || exit 1

# Start application
CMD ["/start.sh"]
