#!/bin/bash

# Railway Deployment Commands for GARB Project

echo "🚀 Starting GARB deployment on Railway..."

# Install dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader

# Generate application key if not exists
echo "🔑 Generating application key..."
php artisan key:generate --force

# Clear all caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Run database migrations
echo "📊 Running database migrations..."
php artisan migrate --force

# Publish Filament assets
echo "🎨 Publishing Filament assets..."
php artisan filament:upgrade

# Create admin user (only if not exists)
echo "👤 Creating admin user..."
php artisan db:seed --class=AdminUserSeeder --force

# Set permissions
echo "🔒 Setting permissions..."
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

echo "✅ GARB deployment completed successfully!"
echo "🌐 Admin panel: https://your-app.railway.app/admin"
echo "👤 Login: admin@garb.com / password123"
