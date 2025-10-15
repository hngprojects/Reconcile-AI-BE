FROM php:8.2-fpm-alpine

# Install system dependencies and build tools
RUN apk add --no-cache \
    nginx \
    postgresql-client \
    postgresql-dev \
    redis \
    supervisor \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    autoconf \
    g++ \
    make \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_pgsql \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del autoconf g++ make

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Copy configuration files
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Create non-root user and set permissions
RUN addgroup -g 1000 appuser && adduser -u 1000 -G appuser -s /bin/sh -D appuser \
    && chown -R appuser:appuser /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Create log directories
RUN mkdir -p /var/log/supervisor /var/log/nginx

EXPOSE 80

ENTRYPOINT []
CMD ["sh", "-c", "echo 'Starting Reconcile AI Backend...' && until pg_isready -h db -p 5432 -U reconxi -d reconxi 2>/dev/null; do echo 'Database unavailable - sleeping'; sleep 2; done && echo 'Database ready!' && until redis-cli -h redis -p 6379 ping 2>/dev/null; do echo 'Redis unavailable - sleeping'; sleep 2; done && echo 'Redis ready!' && php artisan config:cache && php artisan migrate --force && supervisord -c /etc/supervisor/conf.d/supervisord.conf"]