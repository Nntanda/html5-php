#!/bin/bash

echo "=========================================="
echo "SACCO Management System - MySQL Setup"
echo "=========================================="
echo ""

# Check if MySQL is running
echo "Checking MySQL connection..."
if mysql -u root -e "SELECT 1" &> /dev/null; then
    echo "✓ MySQL is running"
else
    echo "⚠ MySQL connection check failed"
    echo ""
    echo "Please ensure MySQL is running and accessible."
    echo "Default connection: root@localhost (no password)"
    echo ""
    read -p "Continue anyway? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Navigate to backend directory
cd backend

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo "❌ Composer is not installed. Please install Composer first."
    echo "Visit: https://getcomposer.org/download/"
    exit 1
fi

echo "✓ Composer found"

# Install dependencies if vendor directory doesn't exist
if [ ! -d "vendor" ]; then
    echo ""
    echo "📦 Installing PHP dependencies..."
    composer install
    if [ $? -ne 0 ]; then
        echo "❌ Failed to install dependencies"
        exit 1
    fi
else
    echo "✓ Dependencies already installed"
fi

# Generate application key if not set
echo ""
echo "🔑 Generating application key..."
php artisan key:generate

# Create database
echo ""
echo "🗄️  Creating database..."
mysql -u root -e "CREATE DATABASE IF NOT EXISTS sacco_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if [ $? -eq 0 ]; then
    echo "✓ Database 'sacco_db' created successfully"
else
    echo "⚠ Database creation failed. It may already exist."
fi

# Run migrations
echo ""
echo "🗄️  Running database migrations..."
php artisan migrate --force
if [ $? -ne 0 ]; then
    echo "❌ Migration failed"
    echo ""
    echo "Troubleshooting:"
    echo "1. Check MySQL is running: mysql -u root -e 'SELECT 1;'"
    echo "2. Verify database exists: mysql -u root -e 'SHOW DATABASES;'"
    echo "3. Check .env file database credentials"
    exit 1
fi

# Seed database with initial data
echo ""
echo "🌱 Seeding database..."
php artisan db:seed --force

echo ""
echo "=========================================="
echo "✅ MySQL Setup Complete!"
echo "=========================================="
echo ""
echo "Database: sacco_db"
echo "Host: localhost:3306"
echo ""
echo "Default Login Credentials:"
echo "Admin: admin@sacco.com / password"
echo "Member: member@sacco.com / password"
echo ""
echo "Next steps:"
echo "1. Start backend: cd backend && php artisan serve"
echo "2. Start admin app: cd admin-app && npm install && npm run dev"
echo "3. Start client portal: cd client-portal && npm install && npm run dev"
echo ""

# Return to root directory
cd ..
