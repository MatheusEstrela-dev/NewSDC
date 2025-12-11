# ============================================================================
# SDC - Deploy Rápido para Azure App Service
# ============================================================================
# Uso: .\deploy-rapido.ps1
# ============================================================================

param(
    [string]$AppServiceName = "newsdc2027",
    [string]$ResourceGroup = "DEFESA_CIVIL",
    [string]$AcrName = "apidover"
)

$ErrorActionPreference = "Stop"

$AcrImage = "${AcrName}.azurecr.io/sdc-dev-app:latest"
$AppUrl = "https://${AppServiceName}.azurewebsites.net"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "SDC - Deploy Rápido" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Configuração:" -ForegroundColor Blue
Write-Host "  App Service: $AppServiceName"
Write-Host "  Resource Group: $ResourceGroup"
Write-Host "  ACR Image: $AcrImage"
Write-Host ""

# 1. Verificar login no Azure
Write-Host "[1/4] Verificando login no Azure..." -ForegroundColor Yellow
if (-not (az account show 2>$null)) {
    Write-Host "Fazendo login no Azure..." -ForegroundColor Yellow
    az login
}
Write-Host "✓ Autenticado no Azure" -ForegroundColor Green

# 2. Login no ACR
Write-Host ""
Write-Host "[2/4] Fazendo login no ACR..." -ForegroundColor Yellow
az acr login --name $AcrName
Write-Host "✓ Login no ACR realizado" -ForegroundColor Green

# 3. Verificar e fazer push da imagem
Write-Host ""
Write-Host "[3/4] Verificando imagem local..." -ForegroundColor Yellow
$localImage = docker images --format "{{.Repository}}:{{.Tag}}" | Select-String "^sdc-dev-app:latest$"

if ($localImage) {
    Write-Host "✓ Imagem local encontrada" -ForegroundColor Green
    
    # Tag para ACR
    Write-Host "  Aplicando tag ACR..." -ForegroundColor Yellow
    docker tag sdc-dev-app:latest $AcrImage
    
    # Push para ACR
    Write-Host "  Fazendo push para ACR..." -ForegroundColor Yellow
    docker push $AcrImage
    Write-Host "✓ Push concluído" -ForegroundColor Green
} else {
    Write-Host "⚠ Imagem local não encontrada" -ForegroundColor Yellow
    Write-Host "  Usando imagem existente no ACR" -ForegroundColor Yellow
}

# 4. Atualizar App Service
Write-Host ""
Write-Host "[4/4] Atualizando App Service..." -ForegroundColor Yellow

# Atualizar imagem
az webapp config container set `
    --name $AppServiceName `
    --resource-group $ResourceGroup `
    --docker-custom-image-name $AcrImage `
    --output none

Write-Host "✓ Configuração atualizada" -ForegroundColor Green

# Reiniciar App Service
Write-Host "  Reiniciando App Service..." -ForegroundColor Yellow
az webapp restart `
    --name $AppServiceName `
    --resource-group $ResourceGroup `
    --output none

Write-Host "✓ App Service reiniciado" -ForegroundColor Green

# Aguardar alguns segundos
Write-Host "  Aguardando aplicação iniciar..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

# Verificar status
Write-Host "  Verificando saúde da aplicação..." -ForegroundColor Yellow
$maxAttempts = 12
$attempt = 0
$success = $false

while ($attempt -lt $maxAttempts -and -not $success) {
    $attempt++
    try {
        $response = Invoke-WebRequest -Uri "${AppUrl}/health" -Method Get -UseBasicParsing -TimeoutSec 5 -ErrorAction Stop
        Write-Host "✓ Aplicação está respondendo!" -ForegroundColor Green
        $success = $true
    } catch {
        Write-Host "  Tentativa $attempt/$maxAttempts : Aguardando..." -ForegroundColor Yellow
        Start-Sleep -Seconds 5
    }
}

# Resumo
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "✅ Deploy concluído!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Informações:" -ForegroundColor Blue
Write-Host "  App Service: $AppServiceName"
Write-Host "  URL: $AppUrl"
Write-Host "  Image: $AcrImage"
Write-Host ""
Write-Host "Para ver logs:" -ForegroundColor Cyan
Write-Host "  az webapp log tail --name $AppServiceName --resource-group $ResourceGroup"
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan




