#!/bin/sh

set -e

echo "Waiting for database connection..."
DB_HOST=${DB_HOST:-db}
DB_DATABASE=${DB_DATABASE:-ead}
DB_USERNAME=${DB_USERNAME:-ead_user}
DB_PASSWORD=${DB_PASSWORD:-ead_password}

until php -r "
try {
    \$pdo = new PDO('mysql:host=${DB_HOST};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');
    echo 'Database connected';
    exit(0);
} catch (PDOException \$e) {
    exit(1);
}
" 2>/dev/null; do
    echo "Database is unavailable - sleeping"
    sleep 2
done

echo "Database is up - executing commands"

# Run migrations (only if not already run)
php artisan migrate --force || true

# Clear and cache config
php artisan config:clear || true
php artisan config:cache || true

# Clear and cache routes
php artisan route:clear || true
php artisan route:cache || true

# Clear and cache views
php artisan view:clear || true
php artisan view:cache || true

# Optimize
php artisan optimize || true

echo "Application is ready!"

exec "$@"
