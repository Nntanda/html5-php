#!/bin/bash

# SACCO Management System - Backend Setup Script
# This script initializes the Laravel backend

echo "Setting up SACCO Management System Backend..."

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo "Error: Composer is not installed. Please install Composer first."
    echo "Visit: https://getcomposer.org/download/"
    exit 1
fi

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "Error: PHP is not installed. Please install PHP 8.1 or higher."
    exit 1
fi

# Create Laravel project
echo "Creating Laravel project..."
composer create-project laravel/laravel backend

# Navigate to backend directory
cd backend

# Install Laravel Sanctum for API authentication
echo "Installing Laravel Sanctum..."
composer require laravel/sanctum

# Publish Sanctum configuration
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Create .env.example
echo "Creating .env.example..."
cp .env .env.example

# Update .env.example with SACCO-specific settings
cat >> .env.example << 'EOF'

# SACCO Configuration
APP_NAME="SACCO Management System"
APP_ENV=local
APP_DEBUG=true

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sacco_db
DB_USERNAME=root
DB_PASSWORD=

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@sacco.local"
MAIL_FROM_NAME="${APP_NAME}"

# SMS Configuration (Africa's Talking or Twilio)
SMS_PROVIDER=africastalking
SMS_API_KEY=
SMS_USERNAME=
SMS_SENDER_ID=SACCO

# Queue Configuration
QUEUE_CONNECTION=database

# Session Configuration
SESSION_DRIVER=database
SESSION_LIFETIME=120

# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:3001
EOF

echo "Backend setup complete!"
echo ""
echo "Next steps:"
echo "1. Configure your database in backend/.env"
echo "2. Run: cd backend && php artisan migrate"
echo "3. Start the server: php artisan serve"
