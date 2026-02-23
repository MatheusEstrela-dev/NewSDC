# ============================================================================
# SDC - Stop All Docker Containers (PowerShell)
# ============================================================================

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  SDC - Stopping All Containers" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Navigate to docker directory
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $scriptPath

Write-Host "Stopping all containers..." -ForegroundColor Yellow
docker compose -f docker-compose.yml --profile tools --profile node down

Write-Host ""
Write-Host "Checking for running containers..." -ForegroundColor Yellow
docker ps --filter "name=newsdc" --format "table {{.Names}}\t{{.Status}}"

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "  All Containers Stopped!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "To remove volumes as well (WARNING: deletes data!):" -ForegroundColor Yellow
Write-Host "  docker compose -f docker-compose.yml down -v"
Write-Host ""
Write-Host "To start again:" -ForegroundColor Cyan
Write-Host "  .\start-dev.bat or .\start-dev.ps1 or make dev"
Write-Host ""
Read-Host "Press Enter to continue"
