Write-Host "Setting up SACCO Admin Application..." -ForegroundColor Green

Set-Location admin-app

Write-Host "Installing dependencies..." -ForegroundColor Yellow
npm install

Write-Host "Creating .env file..." -ForegroundColor Yellow
if (-not (Test-Path .env)) {
  Copy-Item .env.example .env
  Write-Host ".env file created. Please update it with your API URL if needed." -ForegroundColor Cyan
}

Write-Host ""
Write-Host "✅ Admin app setup complete!" -ForegroundColor Green
Write-Host ""
Write-Host "To start the development server, run:" -ForegroundColor Cyan
Write-Host "  cd admin-app" -ForegroundColor White
Write-Host "  npm run dev" -ForegroundColor White
Write-Host ""
Write-Host "The app will be available at http://localhost:5173" -ForegroundColor Cyan
