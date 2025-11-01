#!/bin/bash

# Simple deployment script for taskmanager
# Place this in /var/www/html/taskmanager/deploy.sh
# Make executable with: chmod +x deploy.sh

echo "🚀 Starting deployment..."

# Check if we're in the right directory
if [ ! -f "index.php" ]; then
    echo "❌ Error: Not in taskmanager directory"
    exit 1
fi

# Backup current state
echo "📦 Creating backup..."
cp -r . ../taskmanager-backup-$(date +%Y%m%d-%H%M%S) 2>/dev/null || true

# Pull latest changes
echo "📥 Pulling latest changes from GitHub..."
git fetch origin
git pull origin main

if [ $? -ne 0 ]; then
    echo "❌ Git pull failed! Check for conflicts."
    exit 1
fi

# Set proper permissions
echo "🔐 Setting permissions..."
sudo chown -R www-data:www-data .
sudo chmod -R 755 .

# Optional: Run database setup if schema changed
# echo "🗄️  Checking database setup..."
# php -f setup.php

echo "✅ Deployment completed successfully!"
echo "🌐 Visit: http://jtuominen.net/taskmanager/"