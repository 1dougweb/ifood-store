# Multi-stage build for production
FROM node:20-alpine AS node-builder

WORKDIR /var/www/html

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm ci --only=production=false

# Copy application files
COPY . .

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
ARG APP_KEY
ARG DB_CONNECTION=mysql
ARG DB_HOST
ARG DB_DATABASE
ARG DB_USERNAME
ARG DB_PASSWORD

# Install PHP dependencies (production)
RUN if [ "$APP_ENV" = "production" ]; then \
        composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev; \
    else \
        composer install --no-interaction --prefer-dist --optimize-autoloader; \
    fi

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Create startup script
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

# Copy Nginx configuration
RUN mkdir -p /etc/nginx/http.d
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf

# Copy PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Install supervisor and curl
RUN apk add --no-cache supervisor curl

# Create supervisor directories
RUN mkdir -p /etc/supervisor/conf.d /var/log/supervisor

# Copy supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/health 2>/dev/null || exit 1

# Start supervisor via startup script
CMD ["/start.sh"]
