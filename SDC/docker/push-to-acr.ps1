# ============================================================================
# SDC - Script para Push de Imagens para Azure Container Registry (ACR)
# ============================================================================
# Uso: .\docker\push-to-acr.ps1 -AcrName "seuacr" -ResourceGroup "seu-rg"
# ============================================================================

param(
    [Parameter(Mandatory=$true)]
    [string]$AcrName,
    
    [Parameter(Mandatory=$false)]
    [string]$ResourceGroup = "",
    
    [Parameter(Mandatory=$false)]
    [string]$Tag = "dev-latest",
    
    [Parameter(Mandatory=$false)]
    [switch]$LoginOnly
)

$ErrorActionPreference = "Stop"

# Configuração
$ACR_LOGIN_SERVER = "${AcrName}.azurecr.io"
$IMAGES = @(
    @{Name="sdc-dev-app"; Tag="latest"; Target="sdc-dev-app"}
)

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "SDC - Push para Azure Container Registry" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 1. Login no Azure (se necessário)
Write-Host "[1/5] Verificando login no Azure..." -ForegroundColor Yellow
try {
    $azAccount = az account show 2>$null
    if (-not $azAccount) {
        Write-Host "Fazendo login no Azure..." -ForegroundColor Yellow
        az login
    } else {
        Write-Host "✓ Já autenticado no Azure" -ForegroundColor Green
    }
} catch {
    Write-Host "Erro ao verificar login no Azure. Instale o Azure CLI." -ForegroundColor Red
    exit 1
}

# 2. Login no ACR
Write-Host ""
Write-Host "[2/5] Fazendo login no ACR: $ACR_LOGIN_SERVER" -ForegroundColor Yellow
try {
    if ($ResourceGroup) {
        az acr login --name $AcrName --resource-group $ResourceGroup
    } else {
        az acr login --name $AcrName
    }
    Write-Host "✓ Login no ACR realizado com sucesso" -ForegroundColor Green
} catch {
    Write-Host "Erro ao fazer login no ACR. Verifique o nome do registro." -ForegroundColor Red
    exit 1
}

if ($LoginOnly) {
    Write-Host ""
    Write-Host "Login realizado. Use o script novamente sem -LoginOnly para fazer push." -ForegroundColor Green
    exit 0
}

# 3. Tag das imagens
Write-Host ""
Write-Host "[3/5] Aplicando tags ACR nas imagens..." -ForegroundColor Yellow
foreach ($image in $IMAGES) {
    $localImage = "${($image.Name)}:${($image.Tag)}"
    $acrImage = "${ACR_LOGIN_SERVER}/${($image.Target)}:${Tag}"
    
    Write-Host "  Tagging: $localImage -> $acrImage" -ForegroundColor Gray
    
    # Verificar se a imagem local existe
    $imageExists = docker images -q $localImage
    if (-not $imageExists) {
        Write-Host "  ⚠ Imagem $localImage não encontrada. Pulando..." -ForegroundColor Yellow
        continue
    }
    
    docker tag $localImage $acrImage
    if ($LASTEXITCODE -eq 0) {
        Write-Host "  ✓ Tag aplicada: $acrImage" -ForegroundColor Green
    } else {
        Write-Host "  ✗ Erro ao aplicar tag" -ForegroundColor Red
        exit 1
    }
}

# 4. Push das imagens
Write-Host ""
Write-Host "[4/5] Fazendo push das imagens para o ACR..." -ForegroundColor Yellow
foreach ($image in $IMAGES) {
    $acrImage = "${ACR_LOGIN_SERVER}/${($image.Target)}:${Tag}"
    
    Write-Host "  Pushing: $acrImage" -ForegroundColor Gray
    docker push $acrImage
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "  ✓ Push concluído: $acrImage" -ForegroundColor Green
    } else {
        Write-Host "  ✗ Erro ao fazer push" -ForegroundColor Red
        exit 1
    }
}

# 5. Resumo
Write-Host ""
Write-Host "[5/5] Resumo" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "✓ Imagens enviadas para o ACR:" -ForegroundColor Green
foreach ($image in $IMAGES) {
    $acrImage = "${ACR_LOGIN_SERVER}/${($image.Target)}:${Tag}"
    Write-Host "  - $acrImage" -ForegroundColor White
}
Write-Host ""
Write-Host "Para usar as imagens no Azure:" -ForegroundColor Cyan
Write-Host "  docker pull ${ACR_LOGIN_SERVER}/sdc-dev-app:${Tag}" -ForegroundColor Gray
Write-Host "========================================" -ForegroundColor Cyan




