# SACCO Management System - Backend Setup Script (PowerShell)
# This script initializes the Laravel backend

Write-Host "Setting up SACCO Management System Backend..." -ForegroundColor Green

# Check if composer is installed
try {
    $composerVersion = composer --version 2>&1
    Write-Host "Composer found: $composerVersion" -ForegroundColor Green
} catch {
    Write-Host "Error: Composer is not installed. Please install Composer first." -ForegroundColor Red
    Write-Host "Visit: https://getcomposer.org/download/" -ForegroundColor Yellow
    exit 1
}

# Check if PHP is installed
try {
    $phpVersion = php --version 2>&1
    Write-Host "PHP found: $($phpVersion[0])" -ForegroundColor Green
} catch {
    Write-Host "Error: PHP is not installed. Please install PHP 8.1 or higher." -ForegroundColor Red
    exit 1
}

# Create Laravel project
Write-Host "`nCreating Laravel project..." -ForegroundColor Cyan
composer create-project laravel/laravel backend

# Navigate to backend directory
Set-Location backend

# Install Laravel Sanctum for API authentication
Write-Host "`nInstalling Laravel Sanctum..." -ForegroundColor Cyan
composer require laravel/sanctum

# Publish Sanctum configuration
Write-Host "`nPublishing Sanctum configuration..." -ForegroundColor Cyan
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Create .env.example
Write-Host "`nCreating .env.example..." -ForegroundColor Cyan
Copy-Item .env .env.example

# Update .env.example with SACCO-specific settings
$envContent = @"

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
MAIL_FROM_NAME="`${APP_NAME}"

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
"@

Add-Content -Path .env.example -Value $envContent

Write-Host "`nBackend setup complete!" -ForegroundColor Green
Write-Host "`nNext steps:" -ForegroundColor Yellow
Write-Host "1. Configure your database in backend\.env"
Write-Host "2. Run: cd backend; php artisan migrate"
Write-Host "3. Start the server: php artisan serve"
