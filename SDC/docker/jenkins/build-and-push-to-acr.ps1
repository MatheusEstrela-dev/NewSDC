# ============================================================================
# Script para build e push da imagem Jenkins para Azure Container Registry (Windows)
# ============================================================================
# Uso: .\build-and-push-to-acr.ps1 -AcrName "apidover" -Tag "latest"
# ============================================================================

param(
    [Parameter(Mandatory = $true)]
    [string]$AcrName,

    [Parameter(Mandatory = $false)]
    [string]$Tag = "latest",

    [Parameter(Mandatory = $false)]
    [string]$ImageName = "sdc-jenkins"
)

$ErrorActionPreference = "Stop"

$ACR_LOGIN_SERVER = "${AcrName}.azurecr.io"
$ACR_IMAGE = "${ACR_LOGIN_SERVER}/${ImageName}:${Tag}"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Build e Push Jenkins para Azure ACR" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 1. Verificar se está no diretório correto
if (-not (Test-Path "Dockerfile")) {
    Write-Host "Erro: Dockerfile não encontrado" -ForegroundColor Red
    Write-Host "Execute este script do diretório docker/jenkins/"
    exit 1
}

# 2. Login no Azure
Write-Host "[1/4] Verificando login no Azure..." -ForegroundColor Yellow
try {
    $azAccount = az account show 2>$null
    if (-not $azAccount) {
        Write-Host "Fazendo login no Azure..." -ForegroundColor Yellow
        az login
    }
    else {
        Write-Host "✓ Já autenticado no Azure" -ForegroundColor Green
    }
}
catch {
    Write-Host "Erro ao verificar login no Azure. Instale o Azure CLI." -ForegroundColor Red
    exit 1
}

# 3. Login no ACR
Write-Host ""
Write-Host "[2/4] Fazendo login no ACR: $ACR_LOGIN_SERVER" -ForegroundColor Yellow
az acr login --name $AcrName
Write-Host "✓ Login no ACR realizado" -ForegroundColor Green

# 4. Build da imagem
Write-Host ""
Write-Host "[3/4] Building imagem Jenkins..." -ForegroundColor Yellow
Write-Host "  Image: $ACR_IMAGE"
Write-Host "  Context: $(Get-Location)"

docker build `
    -t "${ImageName}:${Tag}" `
    -t "${ACR_IMAGE}" `
    -f Dockerfile `
    .

Write-Host "✓ Build concluído" -ForegroundColor Green

# 5. Push para ACR
Write-Host ""
Write-Host "[4/4] Fazendo push para ACR..." -ForegroundColor Yellow
docker push $ACR_IMAGE

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "✅ Imagem enviada com sucesso!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Imagem: $ACR_IMAGE"
Write-Host ""
Write-Host "Para usar a imagem:" -ForegroundColor Cyan
Write-Host "  docker pull $ACR_IMAGE"
Write-Host ""
Write-Host "Para deploy no Azure Container Instances:" -ForegroundColor Cyan
Write-Host "  az container create \"
Write-Host "    --resource-group <resource-group> \"
Write-Host "    --name sdc-jenkins \"
Write-Host "    --image $ACR_IMAGE \"
Write-Host "    --registry-login-server $ACR_LOGIN_SERVER \"
Write-Host "    --registry-username $AcrName \"
Write-Host "    --registry-password `$(az acr credential show --name $AcrName --query 'passwords[0].value' -o tsv) \"
Write-Host "    --cpu 4 --memory 8 \"
Write-Host "    --ports 8080 50000"
Write-Host "========================================" -ForegroundColor Cyan




