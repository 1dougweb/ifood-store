#!/bin/sh

set -e

echo "Starting application initialization..."

# Wait for database if DB_HOST is set
if [ -n "$DB_HOST" ] && [ "$DB_HOST" != "127.0.0.1" ] && [ "$DB_HOST" != "localhost" ]; then
    echo "Waiting for database connection..."
    until php -r "
    try {
        \$pdo = new PDO('${DB_CONNECTION:-mysql}:host=${DB_HOST};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');
        echo 'Database connected';
        exit(0);
    } catch (PDOException \$e) {
        exit(1);
    }
    " 2>/dev/null; do
        echo "Database is unavailable - sleeping"
        sleep 2
    done
    echo "Database is ready!"
fi

# Generate application key if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "Generating application key..."
    php artisan key:generate --force || true
fi

# Run migrations
echo "Running migrations..."
php artisan migrate --force || true

# Clear caches
echo "Clearing caches..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan cache:clear || true

# Cache for production
if [ "$APP_ENV" = "production" ]; then
    echo "Caching for production..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Set permissions
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true

echo "Application initialization complete!"
echo "Starting services..."

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
