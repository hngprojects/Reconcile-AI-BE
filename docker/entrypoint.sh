#!/bin/bash

set -e

echo "🟡 Starting Reconcile AI Backend..."

# Wait for database
echo "⏳ Waiting for database..."
until pg_isready -h db -p 5432 -U reconxi -d reconxi 2>/dev/null; do
  echo "Database is unavailable - sleeping"
  sleep 2
done
echo "✅ Database is ready!"

# Wait for Redis
echo "⏳ Waiting for Redis..."
until redis-cli -h redis -p 6379 ping 2>/dev/null; do
  echo "Redis is unavailable - sleeping"
  sleep 2
done
echo "✅ Redis is ready!"

# Ensure .env file exists
if [ ! -f .env ]; then
  echo "❌ .env file not found!"
  exit 1
fi

# Laravel setup
echo "🔑 Setting up Laravel..."
php artisan key:generate --force || true
php artisan migrate --force || true

echo "🚀 Starting services..."
supervisord -c /etc/supervisor/conf.d/supervisord.conf