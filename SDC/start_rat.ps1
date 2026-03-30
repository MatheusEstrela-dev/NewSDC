# RAT Validation - Start Servers
# Inicia Laravel e Vite para validacao full-stack

$ProjectPath = "c:\Users\x16073201\NewSDC\SDC"
Set-Location $ProjectPath

Write-Host "`nRAT Full-Stack Validation" -ForegroundColor Cyan
Write-Host "==========================`n" -ForegroundColor Cyan

# Limpar cache
Write-Host "[1] Cleaning cache..." -ForegroundColor Yellow
php artisan cache:clear 2>&1 | Out-Null
php artisan config:clear 2>&1 | Out-Null
php artisan route:clear 2>&1 | Out-Null
php artisan view:clear 2>&1 | Out-Null
Write-Host "OK - Cache cleared`n" -ForegroundColor Green

# Migrations
Write-Host "[2] Running migrations..." -ForegroundColor Yellow
php artisan migrate --force 2>&1 | Out-Null
Write-Host "OK - Migrations done`n" -ForegroundColor Green

# Storage link
Write-Host "[3] Creating storage link..." -ForegroundColor Yellow
if (-not (Test-Path "public\storage")) {
    php artisan storage:link 2>&1 | Out-Null
}
Write-Host "OK - Storage linked`n" -ForegroundColor Green

# Start Laravel
Write-Host "[4] Starting Laravel server..." -ForegroundColor Yellow
Write-Host "URL: http://localhost:8001`n" -ForegroundColor Cyan

php artisan serve --host=localhost --port=8001
