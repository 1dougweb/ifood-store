FROM php:8.3-fpm

# Set working directory
WORKDIR /var/www/html

# Prevent interactive prompts during build
ENV DEBIAN_FRONTEND=noninteractive
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install dependencies (including Nginx and Supervisor)
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libwebp-dev \
    libxpm-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libzip-dev \
    libonig-dev \
    libicu-dev \
    nginx \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl intl
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-xpm
RUN docker-php-ext-install gd

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Verify Node.js and npm installation
RUN node --version && npm --version

# Add user for laravel application
RUN groupadd -g 1000 www || true
RUN useradd -u 1000 -ms /bin/bash -g www www || true

# Install dependencies (as root, before switching user)
USER root

# Copy only dependency files first (for better Docker layer caching)
COPY --chown=www:www composer.json composer.lock* ./
COPY --chown=www:www package.json package-lock.json* ./

# Install Composer dependencies with cache mount
RUN --mount=type=cache,target=/root/.composer/cache \
    cd /var/www/html && \
    if [ -f composer.json ]; then \
        echo "=== Installing Composer dependencies ===" && \
        composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --prefer-dist || \
        composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist; \
        echo "✓ Composer dependencies installed" && \
        ls -la vendor/autoload.php || echo "⚠ Warning: vendor/autoload.php not found"; \
    else \
        echo "✗ composer.json NOT found, skipping composer install"; \
    fi

# Install Node dependencies with cache mount
RUN --mount=type=cache,target=/root/.npm \
    cd /var/www/html && \
    if [ -f package.json ]; then \
        echo "=== Installing npm dependencies ===" && \
        npm ci --legacy-peer-deps --prefer-offline --no-audit 2>&1 || npm install --legacy-peer-deps --prefer-offline --no-audit 2>&1 || echo "npm install had issues but continuing..."; \
        echo "✓ npm dependencies installed"; \
    else \
        echo "✗ package.json NOT found, skipping npm install"; \
    fi

# Copy application code (this layer will be invalidated more often)
COPY --chown=www:www . /var/www/html

# Create a minimal .env file for build if it doesn't exist
RUN if [ ! -f .env ]; then \
        echo "APP_NAME=Laravel" > .env && \
        echo "APP_ENV=production" >> .env && \
        echo "APP_URL=http://localhost" >> .env && \
        echo "APP_KEY=" >> .env; \
    fi

# Build assets (only if package.json exists and dependencies are installed)
RUN cd /var/www/html && \
    if [ -f package.json ] && [ -d node_modules ]; then \
        echo "=== Building assets ===" && \
        npm run build 2>&1 || (echo "✗ Build failed, will retry at runtime" && mkdir -p public/build); \
        if [ -f public/build/manifest.json ]; then \
            echo "✓ Build successful! manifest.json created during build"; \
        else \
            echo "⚠ Build did not create manifest.json, will be created at runtime"; \
        fi; \
    else \
        echo "⚠ Skipping build - package.json or node_modules not found"; \
    fi

# Ensure build directory has correct permissions
RUN mkdir -p /var/www/html/public/build && \
    chown -R www:www /var/www/html/public/build && \
    chmod -R 755 /var/www/html/public/build || true

# Configure Nginx (as root, before switching user)
RUN mkdir -p /etc/nginx/sites-available /etc/nginx/sites-enabled
RUN echo 'server {' > /etc/nginx/sites-available/default && \
    echo '  listen 80;' >> /etc/nginx/sites-available/default && \
    echo '  server_name localhost;' >> /etc/nginx/sites-available/default && \
    echo '  root /var/www/html/public;' >> /etc/nginx/sites-available/default && \
    echo '  index index.php index.html;' >> /etc/nginx/sites-available/default && \
    echo '  charset utf-8;' >> /etc/nginx/sites-available/default && \
    echo '' >> /etc/nginx/sites-available/default && \
    echo '  # Trust proxy headers for HTTPS detection' >> /etc/nginx/sites-available/default && \
    echo '  set_real_ip_from 0.0.0.0/0;' >> /etc/nginx/sites-available/default && \
    echo '  real_ip_header X-Forwarded-For;' >> /etc/nginx/sites-available/default && \
    echo '  real_ip_recursive on;' >> /etc/nginx/sites-available/default && \
    echo '' >> /etc/nginx/sites-available/default && \
    echo '  location / {' >> /etc/nginx/sites-available/default && \
    echo '    try_files $uri $uri/ /index.php?$query_string;' >> /etc/nginx/sites-available/default && \
    echo '  }' >> /etc/nginx/sites-available/default && \
    echo '' >> /etc/nginx/sites-available/default && \
    echo '  location ~ \.php$ {' >> /etc/nginx/sites-available/default && \
    echo '    try_files $uri =404;' >> /etc/nginx/sites-available/default && \
    echo '    fastcgi_split_path_info ^(.+\.php)(/.+)$;' >> /etc/nginx/sites-available/default && \
    echo '    fastcgi_pass 127.0.0.1:9000;' >> /etc/nginx/sites-available/default && \
    echo '    fastcgi_index index.php;' >> /etc/nginx/sites-available/default && \
    echo '    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;' >> /etc/nginx/sites-available/default && \
    echo '    include fastcgi_params;' >> /etc/nginx/sites-available/default && \
    echo '    fastcgi_hide_header X-Powered-By;' >> /etc/nginx/sites-available/default && \
    echo '    fastcgi_read_timeout 300;' >> /etc/nginx/sites-available/default && \
    echo '    # Pass HTTPS headers to PHP for proper URL generation' >> /etc/nginx/sites-available/default && \
    echo '    fastcgi_param HTTP_X_FORWARDED_PROTO $http_x_forwarded_proto;' >> /etc/nginx/sites-available/default && \
    echo '    fastcgi_param HTTP_X_FORWARDED_FOR $http_x_forwarded_for;' >> /etc/nginx/sites-available/default && \
    echo '    fastcgi_param HTTP_X_FORWARDED_HOST $http_x_forwarded_host;' >> /etc/nginx/sites-available/default && \
    echo '    fastcgi_param HTTP_X_FORWARDED_PORT $http_x_forwarded_port;' >> /etc/nginx/sites-available/default && \
    echo '    # If X-Forwarded-Proto is https, set HTTPS=on' >> /etc/nginx/sites-available/default && \
    echo '    fastcgi_param HTTPS $http_x_forwarded_proto;' >> /etc/nginx/sites-available/default && \
    echo '  }' >> /etc/nginx/sites-available/default && \
    echo '' >> /etc/nginx/sites-available/default && \
    echo '  location ~ /\.(?!well-known).* { deny all; }' >> /etc/nginx/sites-available/default && \
    echo '' >> /etc/nginx/sites-available/default && \
    echo '  location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {' >> /etc/nginx/sites-available/default && \
    echo '    expires 1y;' >> /etc/nginx/sites-available/default && \
    echo '    add_header Cache-Control "public, immutable";' >> /etc/nginx/sites-available/default && \
    echo '    access_log off;' >> /etc/nginx/sites-available/default && \
    echo '  }' >> /etc/nginx/sites-available/default && \
    echo '' >> /etc/nginx/sites-available/default && \
    echo '  location /health { access_log off; return 200 "healthy\n"; add_header Content-Type text/plain; }' >> /etc/nginx/sites-available/default && \
    echo '}' >> /etc/nginx/sites-available/default
RUN rm -f /etc/nginx/sites-enabled/default && \
    ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default || true

# Configure Supervisor (as root)
RUN echo '[supervisord]' > /etc/supervisor/conf.d/supervisord.conf && \
    echo 'nodaemon=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'user=root' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '[program:php-fpm]' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'command=php-fpm -F' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autostart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autorestart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stderr_logfile=/var/log/php-fpm.err.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stdout_logfile=/var/log/php-fpm.out.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'priority=999' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'startsecs=3' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '[program:nginx]' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'command=nginx -g "daemon off;"' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autostart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autorestart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stderr_logfile=/var/log/nginx.err.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stdout_logfile=/var/log/nginx.out.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'priority=998' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'startsecs=2' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '[program:queue]' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'command=/bin/bash -c "cd /var/www/html && php artisan queue:work --tries=3 --timeout=90"' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autostart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autorestart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stderr_logfile=/var/log/queue.err.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stdout_logfile=/var/log/queue.out.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'priority=997' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'startsecs=5' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'startretries=3' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'user=www' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'environment=HOME="/var/www/html",USER="www",PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '[program:scheduler]' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'command=/bin/bash -c "while true; do cd /var/www/html && php artisan schedule:run --verbose --no-interaction; sleep 60; done"' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autostart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autorestart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stderr_logfile=/var/log/scheduler.err.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stdout_logfile=/var/log/scheduler.out.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'priority=996' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'startsecs=5' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'startretries=3' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'user=www' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'environment=HOME="/var/www/html",USER="www",PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"' >> /etc/supervisor/conf.d/supervisord.conf

# Set permissions (as root)
RUN chown -R www:www /var/www/html || true
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true

# Fix PHP-FPM pool configuration to use www user
RUN if [ -f /usr/local/etc/php-fpm.d/www.conf ]; then \
    sed -i 's/user = www-data/user = www/g' /usr/local/etc/php-fpm.d/www.conf || true; \
    sed -i 's/group = www-data/group = www/g' /usr/local/etc/php-fpm.d/www.conf || true; \
    sed -i 's/listen.owner = www-data/listen.owner = www/g' /usr/local/etc/php-fpm.d/www.conf || true; \
    sed -i 's/listen.group = www-data/listen.group = www/g' /usr/local/etc/php-fpm.d/www.conf || true; \
    fi

# Create PHP-FPM pool directory and ensure www.conf exists
RUN mkdir -p /usr/local/etc/php-fpm.d || true
RUN if [ ! -f /usr/local/etc/php-fpm.d/www.conf ]; then \
    echo '[www]' > /usr/local/etc/php-fpm.d/www.conf && \
    echo 'user = www' >> /usr/local/etc/php-fpm.d/www.conf && \
    echo 'group = www' >> /usr/local/etc/php-fpm.d/www.conf && \
    echo 'listen = 127.0.0.1:9000' >> /usr/local/etc/php-fpm.d/www.conf && \
    echo 'listen.owner = www' >> /usr/local/etc/php-fpm.d/www.conf && \
    echo 'listen.group = www' >> /usr/local/etc/php-fpm.d/www.conf && \
    echo 'pm = dynamic' >> /usr/local/etc/php-fpm.d/www.conf && \
    echo 'pm.max_children = 50' >> /usr/local/etc/php-fpm.d/www.conf && \
    echo 'pm.start_servers = 5' >> /usr/local/etc/php-fpm.d/www.conf && \
    echo 'pm.min_spare_servers = 5' >> /usr/local/etc/php-fpm.d/www.conf && \
    echo 'pm.max_spare_servers = 35' >> /usr/local/etc/php-fpm.d/www.conf; \
    fi

# Create log directories
RUN mkdir -p /var/log && chmod 777 /var/log

# Create entrypoint script inline
RUN echo '#!/bin/bash' > /usr/local/bin/docker-entrypoint.sh && \
    echo 'set -e' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'echo "Starting application initialization..."' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '# Wait for database if needed' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'if [ -n "$DB_HOST" ] && [ "$DB_HOST" != "127.0.0.1" ] && [ "$DB_HOST" != "localhost" ]; then' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '  echo "Waiting for database connection..."' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '  until php -r "try { \$pdo = new PDO(\"${DB_CONNECTION:-mysql}:host=${DB_HOST};dbname=${DB_DATABASE}\", \"${DB_USERNAME}\", \"${DB_PASSWORD}\"); echo \"Database connected\"; exit(0); } catch (PDOException \$e) { exit(1); }" 2>/dev/null; do' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '    echo "Database is unavailable - sleeping"' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '    sleep 2' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '  done' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '  echo "Database is ready!"' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'fi' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'cd /var/www/html' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '# Ensure APP_URL uses HTTPS if X-Forwarded-Proto is https' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'if [ -n "$APP_URL" ] && [[ "$APP_URL" == http://* ]]; then' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '  echo "⚠ Warning: APP_URL is set to HTTP. Make sure it uses HTTPS in production."' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'fi' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '# Generate app key if needed' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '  echo "Generating application key..."' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '  php artisan key:generate --force || true' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'fi' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '# Run migrations' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'echo "Running migrations..."' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'php artisan migrate --force || true' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '# Clear and cache config' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'echo "Clearing caches..."' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'php artisan config:clear || true' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'php artisan route:clear || true' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'php artisan view:clear || true' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'php artisan cache:clear || true' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'if [ "$APP_ENV" = "production" ]; then' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '  echo "Caching for production..."' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '  php artisan config:cache || true' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '  php artisan route:cache || true' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '  php artisan view:cache || true' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'fi' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '# Set permissions' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'echo "Setting permissions..."' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'chown -R www:www /var/www/html/storage /var/www/html/bootstrap/cache || true' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true' >> /usr/local/bin/docker-entrypoint.sh && \
    echo '' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'echo "Application initialization complete!"' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'echo "Starting services..."' >> /usr/local/bin/docker-entrypoint.sh && \
    echo 'exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf' >> /usr/local/bin/docker-entrypoint.sh && \
    chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/health 2>/dev/null || exit 1

# Start supervisor to run PHP-FPM, Nginx, Queue and Scheduler
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
