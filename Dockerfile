# ==========================================
# Stage 1: Build Frontend Assets (Vite)
# ==========================================
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# ==========================================
# Stage 2: Production PHP-FPM + Nginx
# ==========================================
FROM php:8.3-fpm-alpine

WORKDIR /var/www/html

# Install system dependencies, Nginx, and CA certificates
RUN apk add --no-cache \
    nginx \
    curl \
    bash \
    ca-certificates \
    git \
    libpq-dev \
    libzip-dev

# Install required PHP extensions for Laravel & PostgreSQL Supabase
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    zip \
    bcmath \
    opcache \
    pcntl

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application source
COPY . .

# Copy compiled frontend assets from Stage 1
COPY --from=frontend /app/public/build ./public/build

# Install production PHP dependencies
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Copy Nginx configuration and entrypoint script
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

# Configure PHP settings & allow environment variables in PHP-FPM workers
RUN echo "clear_env = no" >> /usr/local/etc/php-fpm.d/zz-docker.conf \
    && echo "memory_limit = 256M" > /usr/local/etc/php/conf.d/custom.ini \
    && echo "upload_max_filesize = 64M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size = 64M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time = 120" >> /usr/local/etc/php/conf.d/custom.ini

# Create directories and set proper permissions
RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    /var/log/nginx \
    /run/nginx \
    && chown -R www-data:www-data storage bootstrap/cache /var/log/nginx /run/nginx \
    && chmod -R 775 storage bootstrap/cache

# Expose HTTP port (Render binds to $PORT or default 80/10000)
EXPOSE 80 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
