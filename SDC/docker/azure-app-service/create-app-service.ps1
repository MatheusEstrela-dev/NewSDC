# ============================================================================
# SDC - Criar Azure App Service para SDC
# ============================================================================
# Uso: .\create-app-service.ps1 -ResourceGroup "sdc-rg" -AppName "sdc-app" -PlanName "sdc-plan"
# ============================================================================

param(
    [Parameter(Mandatory=$true)]
    [string]$ResourceGroup,
    
    [Parameter(Mandatory=$true)]
    [string]$AppName,
    
    [Parameter(Mandatory=$true)]
    [string]$PlanName,
    
    [string]$Location = "brazilsouth",
    [string]$Sku = "B1",
    [string]$AcrName = "apidover",
    [string]$AcrImage = "apidover.azurecr.io/sdc-dev-app:latest"
)

$ErrorActionPreference = "Stop"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "SDC - Criar Azure App Service" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 1. Verificar login no Azure
Write-Host "[1/6] Verificando login no Azure..." -ForegroundColor Yellow
if (-not (az account show 2>$null)) {
    Write-Host "Fazendo login no Azure..." -ForegroundColor Yellow
    az login
}
Write-Host "✓ Autenticado no Azure" -ForegroundColor Green

# 2. Criar Resource Group (se não existir)
Write-Host ""
Write-Host "[2/6] Verificando Resource Group..." -ForegroundColor Yellow
if (-not (az group show --name $ResourceGroup 2>$null)) {
    Write-Host "Criando Resource Group: $ResourceGroup..." -ForegroundColor Yellow
    az group create --name $ResourceGroup --location $Location
    Write-Host "✓ Resource Group criado" -ForegroundColor Green
} else {
    Write-Host "✓ Resource Group já existe" -ForegroundColor Green
}

# 3. Criar App Service Plan (se não existir)
Write-Host ""
Write-Host "[3/6] Verificando App Service Plan..." -ForegroundColor Yellow
if (-not (az appservice plan show --name $PlanName --resource-group $ResourceGroup 2>$null)) {
    Write-Host "Criando App Service Plan: $PlanName..." -ForegroundColor Yellow
    az appservice plan create `
        --name $PlanName `
        --resource-group $ResourceGroup `
        --location $Location `
        --is-linux `
        --sku $Sku
    Write-Host "✓ App Service Plan criado" -ForegroundColor Green
} else {
    Write-Host "✓ App Service Plan já existe" -ForegroundColor Green
}

# 4. Obter credenciais do ACR
Write-Host ""
Write-Host "[4/6] Obtendo credenciais do ACR..." -ForegroundColor Yellow
$AcrUsername = $AcrName
$AcrPassword = az acr credential show --name $AcrName --query "passwords[0].value" -o tsv

if (-not $AcrPassword) {
    Write-Host "✗ Erro: Não foi possível obter senha do ACR" -ForegroundColor Red
    exit 1
}
Write-Host "✓ Credenciais do ACR obtidas" -ForegroundColor Green

# 5. Criar App Service
Write-Host ""
Write-Host "[5/6] Criando App Service: $AppName..." -ForegroundColor Yellow
if (-not (az webapp show --name $AppName --resource-group $ResourceGroup 2>$null)) {
    az webapp create `
        --name $AppName `
        --resource-group $ResourceGroup `
        --plan $PlanName `
        --deployment-container-image-name $AcrImage
    
    # Configurar credenciais do ACR
    az webapp config container set `
        --name $AppName `
        --resource-group $ResourceGroup `
        --docker-custom-image-name $AcrImage `
        --docker-registry-server-url "https://${AcrName}.azurecr.io" `
        --docker-registry-server-user $AcrUsername `
        --docker-registry-server-password $AcrPassword
    
    Write-Host "✓ App Service criado" -ForegroundColor Green
} else {
    Write-Host "⚠ App Service já existe, atualizando configuração..." -ForegroundColor Yellow
    az webapp config container set `
        --name $AppName `
        --resource-group $ResourceGroup `
        --docker-custom-image-name $AcrImage `
        --docker-registry-server-url "https://${AcrName}.azurecr.io" `
        --docker-registry-server-user $AcrUsername `
        --docker-registry-server-password $AcrPassword
    Write-Host "✓ Configuração atualizada" -ForegroundColor Green
}

# 6. Configurar variáveis de ambiente
Write-Host ""
Write-Host "[6/6] Configurando variáveis de ambiente..." -ForegroundColor Yellow
az webapp config appsettings set `
    --name $AppName `
    --resource-group $ResourceGroup `
    --settings `
        WEBSITES_ENABLE_APP_SERVICE_STORAGE=false `
        WEBSITES_PORT=8000 `
        APP_ENV=production `
        APP_DEBUG=false `
        DOCKER_ENABLE_CI=true `
    --output none

Write-Host "✓ Variáveis de ambiente configuradas" -ForegroundColor Green

# Resumo
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "✅ App Service criado com sucesso!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Informações:" -ForegroundColor Blue
Write-Host "  App Service: $AppName"
Write-Host "  URL: https://${AppName}.azurewebsites.net"
Write-Host "  Resource Group: $ResourceGroup"
Write-Host "  Plan: $PlanName"
Write-Host "  Image: $AcrImage"
Write-Host ""
Write-Host "Para atualizar a imagem:" -ForegroundColor Cyan
Write-Host "  az webapp config container set \"
Write-Host "    --name $AppName \"
Write-Host "    --resource-group $ResourceGroup \"
Write-Host "    --docker-custom-image-name $AcrImage"
Write-Host ""
Write-Host "Para ver logs:" -ForegroundColor Cyan
Write-Host "  az webapp log tail --name $AppName --resource-group $ResourceGroup"
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan




