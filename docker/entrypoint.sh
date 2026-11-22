#!/bin/sh
set -e

# Default PORT to 10000 or 80 if not set
PORT="${PORT:-80}"
echo "Configuring Nginx to listen on port ${PORT}..."
sed -i "s/__PORT__/${PORT}/g" /etc/nginx/nginx.conf

# Wait for database if configured
if [ -n "$DB_HOST" ]; then
    echo "Configured database host: $DB_HOST"
fi

# Run database migrations if RUN_MIGRATIONS=true
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force || echo "Migration command returned an error or is already up to date."
fi

# Optimize Laravel configuration and caches for production
echo "Optimizing Laravel configuration and caches..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Ensure permissions are correct on storage and bootstrap/cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Start PHP-FPM in background
echo "Starting PHP-FPM..."
php-fpm -D

# Start Nginx in foreground
echo "Starting Nginx web server on port ${PORT}..."
exec nginx -g "daemon off;"
