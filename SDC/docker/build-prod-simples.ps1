# ============================================================================
# SDC - Build Simples para Produção
# ============================================================================
# Uso: .\build-prod-simples.ps1
# ============================================================================

$ErrorActionPreference = "Stop"

$SCRIPT_DIR = Split-Path -Parent $MyInvocation.MyCommand.Path
$PROJECT_ROOT = Split-Path -Parent $SCRIPT_DIR

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "SDC - Build Produção" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Context: $PROJECT_ROOT"
Write-Host "Dockerfile: docker/Dockerfile.prod"
Write-Host ""

Push-Location $PROJECT_ROOT

# Verificar se composer.json existe
if (-not (Test-Path "composer.json")) {
    Write-Host "❌ ERRO: composer.json não encontrado em $PROJECT_ROOT" -ForegroundColor Red
    exit 1
}

Write-Host "✅ composer.json encontrado" -ForegroundColor Green
Write-Host ""

# Build
Write-Host "🏗️  Building imagem..." -ForegroundColor Yellow
docker build `
    -f docker/Dockerfile.prod `
    -t sdc-dev-app:latest `
    -t apidover.azurecr.io/sdc-dev-app:latest `
    .

if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Build falhou!" -ForegroundColor Red
    Pop-Location
    exit 1
}

Write-Host ""
Write-Host "✅ Build concluído!" -ForegroundColor Green
Write-Host ""
Write-Host "Imagens criadas:" -ForegroundColor Blue
Write-Host "  - sdc-dev-app:latest"
Write-Host "  - apidover.azurecr.io/sdc-dev-app:latest"
Write-Host ""
Write-Host "Para fazer push:" -ForegroundColor Cyan
Write-Host "  az acr login --name apidover"
Write-Host "  docker push apidover.azurecr.io/sdc-dev-app:latest"

Pop-Location

