#!/bin/bash

set -e

echo "Setting up Laravel application in Docker..."

# Copy .env if it doesn't exist
if [ ! -f .env ]; then
    echo "Copying .env.example to .env..."
    cp .env.example .env
fi

# Start containers
echo "Starting containers..."
docker-compose up -d

# Wait for database
echo "Waiting for database..."
sleep 10

# Install PHP dependencies
echo "Installing PHP dependencies..."
docker-compose exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader

# Generate application key
echo "Generating application key..."
docker-compose exec -T app php artisan key:generate --force

# Run migrations
echo "Running migrations..."
docker-compose exec -T app php artisan migrate --force

# Seed database (optional)
read -p "Do you want to seed the database? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Seeding database..."
    docker-compose exec -T app php artisan db:seed --force
fi

# Install Node dependencies and build assets
echo "Installing Node dependencies..."
docker-compose exec -T node npm ci

echo "Building assets..."
docker-compose exec -T node npm run build

# Set permissions
echo "Setting permissions..."
docker-compose exec -T app chown -R www-data:www-data /var/www/html/storage
docker-compose exec -T app chown -R www-data:www-data /var/www/html/bootstrap/cache
docker-compose exec -T app chmod -R 775 /var/www/html/storage
docker-compose exec -T app chmod -R 775 /var/www/html/bootstrap/cache

# Clear caches
echo "Clearing caches..."
docker-compose exec -T app php artisan config:clear
docker-compose exec -T app php artisan cache:clear
docker-compose exec -T app php artisan route:clear
docker-compose exec -T app php artisan view:clear

# Cache for production
echo "Caching for production..."
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache

echo "Setup complete!"
echo "Application is available at http://localhost"
