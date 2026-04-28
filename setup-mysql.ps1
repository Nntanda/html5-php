# SACCO Management System - MySQL Setup (PowerShell)

Write-Host "==========================================" -ForegroundColor Green
Write-Host "SACCO Management System - MySQL Setup" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""

# Check if MySQL is running
Write-Host "Checking MySQL connection..." -ForegroundColor Cyan
$mysqlRunning = $false
try {
    $result = mysql -u root -e "SELECT 1" 2>&1
    if ($LASTEXITCODE -eq 0) {
        $mysqlRunning = $true
        Write-Host "✓ MySQL is running" -ForegroundColor Green
    }
} catch {
    Write-Host "⚠ MySQL connection check failed" -ForegroundColor Yellow
}

if (-not $mysqlRunning) {
    Write-Host ""
    Write-Host "Please ensure MySQL is running and accessible." -ForegroundColor Yellow
    Write-Host "Default connection: root@localhost (no password)" -ForegroundColor Yellow
    Write-Host ""
    $continue = Read-Host "Continue anyway? (y/n)"
    if ($continue -ne "y") {
        exit 1
    }
}

# Navigate to backend directory
Set-Location backend

# Check if composer is installed
$composerExists = Get-Command composer -ErrorAction SilentlyContinue
if (-not $composerExists) {
    Write-Host "❌ Composer is not installed. Please install Composer first." -ForegroundColor Red
    Write-Host "Visit: https://getcomposer.org/download/" -ForegroundColor Yellow
    exit 1
}

Write-Host "✓ Composer found" -ForegroundColor Green

# Install dependencies if vendor directory doesn't exist
if (-not (Test-Path "vendor")) {
    Write-Host ""
    Write-Host "📦 Installing PHP dependencies..." -ForegroundColor Cyan
    composer install
    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ Failed to install dependencies" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "✓ Dependencies already installed" -ForegroundColor Green
}

# Generate application key if not set
Write-Host ""
Write-Host "🔑 Generating application key..." -ForegroundColor Cyan
php artisan key:generate

# Create database
Write-Host ""
Write-Host "🗄️  Creating database..." -ForegroundColor Cyan
mysql -u root -e "CREATE DATABASE IF NOT EXISTS sacco_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Database 'sacco_db' created successfully" -ForegroundColor Green
} else {
    Write-Host "⚠ Database creation failed. It may already exist." -ForegroundColor Yellow
}

# Run migrations
Write-Host ""
Write-Host "🗄️  Running database migrations..." -ForegroundColor Cyan
php artisan migrate --force
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Migration failed" -ForegroundColor Red
    Write-Host ""
    Write-Host "Troubleshooting:" -ForegroundColor Yellow
    Write-Host "1. Check MySQL is running: mysql -u root -e 'SELECT 1;'" -ForegroundColor Yellow
    Write-Host "2. Verify database exists: mysql -u root -e 'SHOW DATABASES;'" -ForegroundColor Yellow
    Write-Host "3. Check .env file database credentials" -ForegroundColor Yellow
    exit 1
}

# Seed database with initial data
Write-Host ""
Write-Host "🌱 Seeding database..." -ForegroundColor Cyan
php artisan db:seed --force

Write-Host ""
Write-Host "==========================================" -ForegroundColor Green
Write-Host "✅ MySQL Setup Complete!" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Database: sacco_db" -ForegroundColor Yellow
Write-Host "Host: localhost:3306" -ForegroundColor Yellow
Write-Host ""
Write-Host "Default Login Credentials:" -ForegroundColor Cyan
Write-Host "Admin: admin@sacco.com / password" -ForegroundColor White
Write-Host "Member: member@sacco.com / password" -ForegroundColor White
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. Start backend: cd backend && php artisan serve" -ForegroundColor White
Write-Host "2. Start admin app: cd admin-app && npm install && npm run dev" -ForegroundColor White
Write-Host "3. Start client portal: cd client-portal && npm install && npm run dev" -ForegroundColor White
Write-Host ""

# Return to root directory
Set-Location ..
