# SACCO Management System - Quick Setup Script
# This script will set up everything automatically

Write-Host "========================================" -ForegroundColor Green
Write-Host "SACCO Management System - Quick Setup" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""

# Step 1: Backend Setup
Write-Host "Step 1: Setting up Backend..." -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path "backend/.env")) {
    Write-Host "  Creating backend .env file..." -ForegroundColor Yellow
    Copy-Item backend/.env.example backend/.env
    Write-Host "  ✓ Created backend/.env" -ForegroundColor Green
} else {
    Write-Host "  ✓ Backend .env already exists" -ForegroundColor Green
}

Write-Host "  Installing backend dependencies..." -ForegroundColor Yellow
Set-Location backend
composer install --no-interaction
if ($LASTEXITCODE -eq 0) {
    Write-Host "  ✓ Backend dependencies installed" -ForegroundColor Green
} else {
    Write-Host "  ✗ Failed to install backend dependencies" -ForegroundColor Red
    Set-Location ..
    exit 1
}

Write-Host "  Generating application key..." -ForegroundColor Yellow
php artisan key:generate --force
Write-Host "  ✓ Application key generated" -ForegroundColor Green

Write-Host "  Running database migrations..." -ForegroundColor Yellow
php artisan migrate --force
if ($LASTEXITCODE -eq 0) {
    Write-Host "  ✓ Database migrations completed" -ForegroundColor Green
} else {
    Write-Host "  ✗ Database migrations failed" -ForegroundColor Red
    Write-Host "  Please check your database configuration in backend/.env" -ForegroundColor Yellow
    Set-Location ..
    exit 1
}

Write-Host "  Seeding database with demo data..." -ForegroundColor Yellow
php artisan db:seed --force
Write-Host "  ✓ Database seeded" -ForegroundColor Green

Set-Location ..

# Step 2: Admin App Setup
Write-Host "`nStep 2: Setting up Admin App..." -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path "admin-app/.env")) {
    Write-Host "  Creating admin-app .env file..." -ForegroundColor Yellow
    "VITE_API_URL=http://localhost:8000/api" | Out-File -FilePath "admin-app/.env" -Encoding UTF8
    Write-Host "  ✓ Created admin-app/.env" -ForegroundColor Green
} else {
    Write-Host "  ✓ Admin app .env already exists" -ForegroundColor Green
}

Write-Host "  Installing admin app dependencies..." -ForegroundColor Yellow
Set-Location admin-app
npm install
if ($LASTEXITCODE -eq 0) {
    Write-Host "  ✓ Admin app dependencies installed" -ForegroundColor Green
} else {
    Write-Host "  ✗ Failed to install admin app dependencies" -ForegroundColor Red
    Set-Location ..
    exit 1
}
Set-Location ..

# Step 3: Client Portal Setup
Write-Host "`nStep 3: Setting up Client Portal..." -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path "client-portal/.env")) {
    Write-Host "  Creating client-portal .env file..." -ForegroundColor Yellow
    "VITE_API_URL=http://localhost:8000/api" | Out-File -FilePath "client-portal/.env" -Encoding UTF8
    Write-Host "  ✓ Created client-portal/.env" -ForegroundColor Green
} else {
    Write-Host "  ✓ Client portal .env already exists" -ForegroundColor Green
}

Write-Host "  Installing client portal dependencies..." -ForegroundColor Yellow
Set-Location client-portal
npm install
if ($LASTEXITCODE -eq 0) {
    Write-Host "  ✓ Client portal dependencies installed" -ForegroundColor Green
} else {
    Write-Host "  ✗ Failed to install client portal dependencies" -ForegroundColor Red
    Set-Location ..
    exit 1
}
Set-Location ..

# Summary
Write-Host "`n========================================" -ForegroundColor Green
Write-Host "✅ Setup Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Demo Accounts Created:" -ForegroundColor Cyan
Write-Host "  Admin:  admin@sacco.com / password" -ForegroundColor White
Write-Host "  Member: member@sacco.com / password" -ForegroundColor White
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Cyan
Write-Host "  1. Start all services: .\start-all-services.ps1" -ForegroundColor White
Write-Host "  2. Open Admin App: http://localhost:5173" -ForegroundColor White
Write-Host "  3. Open Client Portal: http://localhost:5174" -ForegroundColor White
Write-Host ""
Write-Host "Or start services manually:" -ForegroundColor Cyan
Write-Host "  Terminal 1: cd backend && php artisan serve" -ForegroundColor Gray
Write-Host "  Terminal 2: cd admin-app && npm run dev" -ForegroundColor Gray
Write-Host "  Terminal 3: cd client-portal && npm run dev" -ForegroundColor Gray
Write-Host ""
