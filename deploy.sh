#!/bin/bash

echo "🚀 GARB Deployment Script"

# Run migrations
echo "📊 Running migrations..."
php artisan migrate --force

# Seed admin user
echo "👤 Creating admin user..."
php artisan db:seed --class=AdminUserSeeder

# Upgrade Filament
echo "🎨 Upgrading Filament..."
php artisan filament:upgrade

# Clear caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "✅ Deployment completed successfully!"
