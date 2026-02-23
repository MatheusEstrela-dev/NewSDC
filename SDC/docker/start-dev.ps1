# ============================================================================
# SDC - Quick Start Development Environment (PowerShell)
# Monta TODOS os containers, exceto Node (Vite roda no HOST)
# ============================================================================

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  SDC - Starting All Containers" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Set environment variables
$env:HOST_UID = "1000"
$env:HOST_GID = "1000"
$env:DOCKER_BUILDKIT = "1"
$env:COMPOSE_DOCKER_CLI_BUILD = "1"

# Navigate to docker directory
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $scriptPath

# Check Docker
Write-Host "[1/5] Checking Docker..." -ForegroundColor Yellow
try {
    $dockerVersion = docker version 2>&1
    if ($LASTEXITCODE -ne 0) {
        throw "Docker not running"
    }
    Write-Host "OK: Docker is running" -ForegroundColor Green
}
catch {
    Write-Host "ERROR: Docker is not running or not installed" -ForegroundColor Red
    Write-Host "Please start Docker Desktop and try again" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

# Create network
Write-Host ""
Write-Host "[2/5] Creating network..." -ForegroundColor Yellow
$networkExists = docker network inspect sdc_network 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "Creating network sdc_network..." -ForegroundColor Yellow
    docker network create --driver bridge --subnet 172.26.0.0/16 sdc_network | Out-Null
    Write-Host "OK: Network created" -ForegroundColor Green
}
else {
    Write-Host "OK: Network already exists" -ForegroundColor Green
}

# Start containers
Write-Host ""
Write-Host "[3/5] Starting containers..." -ForegroundColor Yellow
Write-Host "  - App (Laravel + PHP-FPM)"
Write-Host "  - Nginx (Web Server)"
Write-Host "  - MySQL (Database)"
Write-Host "  - Redis (Cache/Queue/Session)"
Write-Host "  - Mailhog (Email Testing)"
Write-Host "  - Queue (Workers)"
Write-Host "  - Prometheus (Metrics)"
Write-Host "  - Grafana (Dashboards)"
Write-Host "  - Alertmanager (Alerts)"
Write-Host "  - Node Exporter"
Write-Host "  - cAdvisor"
Write-Host "  - MySQL Exporter"
Write-Host "  - Redis Exporter"
Write-Host "  - Nginx Exporter"
Write-Host "  - Blackbox Exporter"
Write-Host ""
docker compose -f docker-compose.yml up -d

# Wait for services
Write-Host ""
Write-Host "[4/5] Waiting for services to be healthy..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

# Check status
Write-Host ""
Write-Host "[5/5] Checking container status..." -ForegroundColor Yellow
docker compose -f docker-compose.yml ps

# Summary
Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "  ALL CONTAINERS STARTED!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "APLICACAO:" -ForegroundColor Cyan
Write-Host "  App (Laravel):     http://localhost:8001" -ForegroundColor Green
Write-Host "  Nginx:             http://localhost:8082" -ForegroundColor Green
Write-Host "  Mailhog:           http://localhost:8026" -ForegroundColor Green
Write-Host ""
Write-Host "BANCO DE DADOS:" -ForegroundColor Cyan
Write-Host "  MySQL:             localhost:3307 (user: sdc, pass: secret)" -ForegroundColor Green
Write-Host "  Redis:             localhost:6380" -ForegroundColor Green
Write-Host ""
Write-Host "MONITORAMENTO:" -ForegroundColor Cyan
Write-Host "  Grafana:           http://localhost:3000 (admin/admin@123)" -ForegroundColor Green
Write-Host "  Prometheus:        http://localhost:9090" -ForegroundColor Green
Write-Host "  Alertmanager:      http://localhost:9093" -ForegroundColor Green
Write-Host "  cAdvisor:          http://localhost:8080" -ForegroundColor Green
Write-Host ""
Write-Host "IMPORTANTE - VITE DEVE SER EXECUTADO NO HOST:" -ForegroundColor Yellow
Write-Host "  1. Abra um novo terminal" -ForegroundColor White
Write-Host "  2. Navegue para: C:\Users\x24679188\Documents\GitHub\NewSDC\SDC" -ForegroundColor White
Write-Host "  3. Execute: npm run dev" -ForegroundColor White
Write-Host "  4. Vite estara em: http://localhost:5173" -ForegroundColor White
Write-Host ""
Write-Host "COMANDOS UTEIS:" -ForegroundColor Cyan
Write-Host "  Migrations:        docker compose -f docker-compose.yml exec app php artisan migrate"
Write-Host "  View logs:         docker compose -f docker-compose.yml logs -f"
Write-Host "  Stop all:          .\stop-all.bat or .\stop-all.ps1"
Write-Host "  Or use Makefile:   make dev, make logs, make shell, etc."
Write-Host ""
Read-Host "Press Enter to continue"
