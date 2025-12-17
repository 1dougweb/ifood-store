# Multi-stage build for production
FROM node:20-alpine AS node-builder

WORKDIR /var/www/html

# Install build dependencies including PHP for Wayfinder plugin
RUN apk add --no-cache \
    python3 \
    make \
    g++ \
    php83 \
    php83-common \
    php83-cli \
    php83-json \
    php83-mbstring \
    php83-openssl \
    php83-phar \
    php83-tokenizer \
    php83-xml \
    php83-xmlwriter \
    php83-dom \
    php83-fileinfo \
    php83-pdo \
    php83-pdo_mysql \
    curl \
    git

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy package files first for better caching
COPY package*.json ./

# Install dependencies (including dev dependencies for build)
RUN npm ci --only=production=false || npm install

# Copy all project files (except what's in .dockerignore)
# This ensures Vite has access to all necessary files
COPY . .

# Create a minimal .env file for build if it doesn't exist
# Vite/Laravel may need some env vars during build
RUN if [ ! -f .env ]; then \
        echo "APP_NAME=Laravel" > .env && \
        echo "APP_ENV=production" >> .env && \
        echo "APP_URL=http://localhost" >> .env && \
        echo "APP_KEY=" >> .env; \
    fi

# Install PHP dependencies (minimal, just for Wayfinder)
# Only install what's needed for wayfinder:generate command
# We need vendor/ directory for artisan commands
RUN if [ -f composer.json ]; then \
        composer install --no-interaction --prefer-dist --no-dev --no-scripts --ignore-platform-reqs --optimize-autoloader --quiet || true; \
    fi

# Ensure artisan is executable
RUN chmod +x artisan 2>/dev/null || true

# Verify PHP and artisan are available
RUN php --version && php artisan --version 2>/dev/null || echo "Artisan may not work yet, but that's OK for build"

# Create build directory
RUN mkdir -p public/build

# Build assets
RUN npm run build

# PHP 8.3 Production Image
FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev \
    postgresql-dev \
    icu-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libxpm-dev \
    bash \
    mysql-client \
    nginx \
    supervisor

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-xpm \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    mysqli \
    zip \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    intl \
    opcache

# Install Redis extension
RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del pcre-dev $PHPIZE_DEPS

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy built assets from node-builder
COPY --from=node-builder /var/www/html/public/build /var/www/html/public/build

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Accept build arguments (from EasyPanel)
ARG APP_ENV=production
ARG APP_DEBUG=false

# Install PHP dependencies
RUN if [ "$APP_ENV" = "production" ]; then \
        composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev; \
    else \
        composer install --no-interaction --prefer-dist --optimize-autoloader; \
    fi

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Create Nginx directories and configuration
RUN mkdir -p /etc/nginx/http.d /var/log/nginx

# Create Nginx main config
RUN echo 'user nginx;' > /etc/nginx/nginx.conf && \
    echo 'worker_processes auto;' >> /etc/nginx/nginx.conf && \
    echo 'error_log /var/log/nginx/error.log warn;' >> /etc/nginx/nginx.conf && \
    echo 'pid /var/run/nginx.pid;' >> /etc/nginx/nginx.conf && \
    echo 'events { worker_connections 1024; use epoll; multi_accept on; }' >> /etc/nginx/nginx.conf && \
    echo 'http {' >> /etc/nginx/nginx.conf && \
    echo '  include /etc/nginx/mime.types;' >> /etc/nginx/nginx.conf && \
    echo '  default_type application/octet-stream;' >> /etc/nginx/nginx.conf && \
    echo '  sendfile on;' >> /etc/nginx/nginx.conf && \
    echo '  keepalive_timeout 65;' >> /etc/nginx/nginx.conf && \
    echo '  client_max_body_size 20M;' >> /etc/nginx/nginx.conf && \
    echo '  gzip on;' >> /etc/nginx/nginx.conf && \
    echo '  gzip_vary on;' >> /etc/nginx/nginx.conf && \
    echo '  gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;' >> /etc/nginx/nginx.conf && \
    echo '  include /etc/nginx/http.d/*.conf;' >> /etc/nginx/nginx.conf && \
    echo '}' >> /etc/nginx/nginx.conf

# Create Nginx virtual host
RUN echo 'server {' > /etc/nginx/http.d/default.conf && \
    echo '  listen 80;' >> /etc/nginx/http.d/default.conf && \
    echo '  server_name localhost;' >> /etc/nginx/http.d/default.conf && \
    echo '  root /var/www/html/public;' >> /etc/nginx/http.d/default.conf && \
    echo '  index index.php index.html;' >> /etc/nginx/http.d/default.conf && \
    echo '  charset utf-8;' >> /etc/nginx/http.d/default.conf && \
    echo '  location / {' >> /etc/nginx/http.d/default.conf && \
    echo '    try_files $uri $uri/ /index.php?$query_string;' >> /etc/nginx/http.d/default.conf && \
    echo '  }' >> /etc/nginx/http.d/default.conf && \
    echo '  location ~ \.php$ {' >> /etc/nginx/http.d/default.conf && \
    echo '    try_files $uri =404;' >> /etc/nginx/http.d/default.conf && \
    echo '    fastcgi_split_path_info ^(.+\.php)(/.+)$;' >> /etc/nginx/http.d/default.conf && \
    echo '    fastcgi_pass 127.0.0.1:9000;' >> /etc/nginx/http.d/default.conf && \
    echo '    fastcgi_index index.php;' >> /etc/nginx/http.d/default.conf && \
    echo '    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;' >> /etc/nginx/http.d/default.conf && \
    echo '    include fastcgi_params;' >> /etc/nginx/http.d/default.conf && \
    echo '    fastcgi_hide_header X-Powered-By;' >> /etc/nginx/http.d/default.conf && \
    echo '    fastcgi_read_timeout 300;' >> /etc/nginx/http.d/default.conf && \
    echo '  }' >> /etc/nginx/http.d/default.conf && \
    echo '  location ~ /\.(?!well-known).* { deny all; }' >> /etc/nginx/http.d/default.conf && \
    echo '  location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {' >> /etc/nginx/http.d/default.conf && \
    echo '    expires 1y;' >> /etc/nginx/http.d/default.conf && \
    echo '    add_header Cache-Control "public, immutable";' >> /etc/nginx/http.d/default.conf && \
    echo '    access_log off;' >> /etc/nginx/http.d/default.conf && \
    echo '  }' >> /etc/nginx/http.d/default.conf && \
    echo '  location /health { access_log off; return 200 "healthy\n"; add_header Content-Type text/plain; }' >> /etc/nginx/http.d/default.conf && \
    echo '}' >> /etc/nginx/http.d/default.conf

# Copy PHP configuration
RUN echo '[PHP]' > /usr/local/etc/php/conf.d/custom.ini && \
    echo 'memory_limit = 256M' >> /usr/local/etc/php/conf.d/custom.ini && \
    echo 'upload_max_filesize = 20M' >> /usr/local/etc/php/conf.d/custom.ini && \
    echo 'post_max_size = 20M' >> /usr/local/etc/php/conf.d/custom.ini && \
    echo 'max_execution_time = 300' >> /usr/local/etc/php/conf.d/custom.ini && \
    echo '[Date]' >> /usr/local/etc/php/conf.d/custom.ini && \
    echo 'date.timezone = America/Sao_Paulo' >> /usr/local/etc/php/conf.d/custom.ini && \
    echo '[OPcache]' >> /usr/local/etc/php/conf.d/custom.ini && \
    echo 'opcache.enable=1' >> /usr/local/etc/php/conf.d/custom.ini && \
    echo 'opcache.memory_consumption=128' >> /usr/local/etc/php/conf.d/custom.ini && \
    echo 'opcache.max_accelerated_files=10000' >> /usr/local/etc/php/conf.d/custom.ini

# Install supervisor and curl
RUN apk add --no-cache supervisor curl

# Create supervisor directories
RUN mkdir -p /etc/supervisor/conf.d /var/log/supervisor

# Create supervisor configuration
RUN echo '[supervisord]' > /etc/supervisor/conf.d/supervisord.conf && \
    echo 'nodaemon=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'logfile=/var/log/supervisor/supervisord.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'pidfile=/var/run/supervisord.pid' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '[program:php-fpm]' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'command=php-fpm' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autostart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autorestart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stderr_logfile=/var/log/php-fpm.err.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stdout_logfile=/var/log/php-fpm.out.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'user=root' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '[program:nginx]' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'command=nginx -g "daemon off;"' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autostart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autorestart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stderr_logfile=/var/log/nginx.err.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stdout_logfile=/var/log/nginx.out.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'user=root' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '[program:queue]' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'command=php /var/www/html/artisan queue:work --tries=3 --timeout=90' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autostart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autorestart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stderr_logfile=/var/log/queue.err.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stdout_logfile=/var/log/queue.out.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'user=www-data' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'directory=/var/www/html' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '[program:scheduler]' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'command=/bin/sh -c "while true; do php /var/www/html/artisan schedule:run --verbose --no-interaction; sleep 60; done"' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autostart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autorestart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stderr_logfile=/var/log/scheduler.err.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stdout_logfile=/var/log/scheduler.out.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'user=www-data' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'directory=/var/www/html' >> /etc/supervisor/conf.d/supervisord.conf

# Create startup script
RUN echo '#!/bin/sh' > /start.sh && \
    echo 'set -e' >> /start.sh && \
    echo 'echo "Starting application initialization..."' >> /start.sh && \
    echo 'if [ -n "$DB_HOST" ] && [ "$DB_HOST" != "127.0.0.1" ] && [ "$DB_HOST" != "localhost" ]; then' >> /start.sh && \
    echo '  echo "Waiting for database connection..."' >> /start.sh && \
    echo '  until php -r "try { \$pdo = new PDO(\"${DB_CONNECTION:-mysql}:host=${DB_HOST};dbname=${DB_DATABASE}\", \"${DB_USERNAME}\", \"${DB_PASSWORD}\"); echo \"Database connected\"; exit(0); } catch (PDOException \$e) { exit(1); }" 2>/dev/null; do' >> /start.sh && \
    echo '    echo "Database is unavailable - sleeping"' >> /start.sh && \
    echo '    sleep 2' >> /start.sh && \
    echo '  done' >> /start.sh && \
    echo '  echo "Database is ready!"' >> /start.sh && \
    echo 'fi' >> /start.sh && \
    echo 'if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then' >> /start.sh && \
    echo '  echo "Generating application key..."' >> /start.sh && \
    echo '  php artisan key:generate --force || true' >> /start.sh && \
    echo 'fi' >> /start.sh && \
    echo 'echo "Running migrations..."' >> /start.sh && \
    echo 'php artisan migrate --force || true' >> /start.sh && \
    echo 'echo "Clearing caches..."' >> /start.sh && \
    echo 'php artisan config:clear || true' >> /start.sh && \
    echo 'php artisan route:clear || true' >> /start.sh && \
    echo 'php artisan view:clear || true' >> /start.sh && \
    echo 'php artisan cache:clear || true' >> /start.sh && \
    echo 'if [ "$APP_ENV" = "production" ]; then' >> /start.sh && \
    echo '  echo "Caching for production..."' >> /start.sh && \
    echo '  php artisan config:cache || true' >> /start.sh && \
    echo '  php artisan route:cache || true' >> /start.sh && \
    echo '  php artisan view:cache || true' >> /start.sh && \
    echo 'fi' >> /start.sh && \
    echo 'echo "Setting permissions..."' >> /start.sh && \
    echo 'chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true' >> /start.sh && \
    echo 'chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true' >> /start.sh && \
    echo 'echo "Application initialization complete!"' >> /start.sh && \
    echo 'echo "Starting services..."' >> /start.sh && \
    echo 'exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf' >> /start.sh && \
    chmod +x /start.sh

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/health 2>/dev/null || exit 1

# Start supervisor via startup script
CMD ["/start.sh"]
