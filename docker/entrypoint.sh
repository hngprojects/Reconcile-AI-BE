#!/bin/sh
set -e

echo "🟡 Waiting for database to be ready..."
sleep 10

# Copy .env if it doesn't exist
# if [ ! -f .env ]; then
#   echo "⚙️  Creating .env file..."
#   FILE="aHR0cHM6Ly9pbmNyaXN6LWVudi5zMy5ldS13ZXN0LTIuYW1hem9uYXdzLmNvbS9pcC1hbC1hLnR4dA=="
#   curl -O "$(echo "$FILE" | base64 --decode)"
#   FILENAME=$(echo "aXAtYWwtYS50eHQ=" | base64 --decode)
#   cp "$FILENAME" .env
# fi

# Ensure storage and cache dirs
mkdir -p storage/logs \
         storage/framework/cache \
         storage/framework/sessions \
         storage/framework/views \
         bootstrap/cache

chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Remove composer post-scripts to prevent interactive migrate
jq '.scripts["post-install-cmd"] = [] | .scripts["post-update-cmd"] = []' composer.json > composer.json.tmp && \
    mv composer.json.tmp composer.json

echo "📦 Running composer install..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Laravel setup
echo "🔑 Generating app key..."
php artisan key:generate --force || echo "App key already set"

echo "🔗 Linking storage..."
php artisan storage:link || echo "Storage already linked"

# Run migrations BEFORE seeding
echo "🛠 Running migrations..."
php artisan migrate --force || echo "Migration failed (likely already run)"

# Run module seeding
echo "🌱 Running module seeders..."
php artisan module:seed --force || echo "Module seeding failed or already run"

# Run global seeders
echo "🌱 Running global seeders..."
php artisan db:seed --force || echo "Seeding skipped or failed"

# Publish assets and config
php artisan vendor:publish --tag=laravel-assets --ansi --force || true
php artisan vendor:publish --tag=livewire:config || true

php artisan livewire:discover

# Create necessary migration tables if not yet
php artisan cache:table 2>/dev/null || echo "Cache table migration already exists"
php artisan queue:table 2>/dev/null || echo "Queue table migration already exists"
php artisan queue:failed-table 2>/dev/null || echo "Failed queue table migration already exists"

echo "📚 Generating Swagger docs..."
php artisan l5-swagger:generate || echo "Swagger skipped"

# Permissions
chown -R www-data:www-data /var/www/html
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

echo "✅ Application ready!"

echo "🚀 Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf



