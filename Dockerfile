FROM php:8.2-fpm

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    curl \
    git \
    jq \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libxml2-dev \
    libonig-dev \
    libicu-dev \
    libxslt-dev \
    libcurl4-openssl-dev \
    libpq-dev \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        mbstring \
        exif \
        bcmath \
        gd \
        intl \
        xsl \
        xml \
        zip



# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application source
COPY . .


# Create .env if not present
# RUN cp .env.example .env

# Ensure entrypoint script is copied before installing Composer deps
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh


# Disable composer post-scripts and install dependencies
# RUN jq '.scripts["post-install-cmd"] = [] | .scripts["post-update-cmd"] = []' composer.json > composer.json.tmp && \
#     mv composer.json.tmp composer.json && \
#     composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Set proper permissions
# RUN chown -R www-data:www-data storage bootstrap/cache \
#     && chmod -R 775 storage bootstrap/cache
    
# Copy configuration files
COPY ./docker/nginx/default.conf /etc/nginx/sites-available/default
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini



# Expose ports
EXPOSE 80 8080

# will manage Laravel setup and Supervisor
# CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
CMD ["/usr/local/bin/entrypoint.sh"]