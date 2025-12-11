# ============================================================================
# SDC - Deploy Completo: Corrigir + Build + Push + Deploy + Reiniciar
# ============================================================================
# Uso: .\deploy-completo.ps1
# ============================================================================

param(
    [string]$AppServiceName = "newsdc2027",
    [string]$ResourceGroup = "DEFESA_CIVIL",
    [string]$AcrName = "apidover",
    [string]$Tag = "latest"
)

$ErrorActionPreference = "Stop"

$ACR_LOGIN_SERVER = "${AcrName}.azurecr.io"
$ACR_IMAGE = "${ACR_LOGIN_SERVER}/sdc-dev-app:${Tag}"
$SCRIPT_DIR = Split-Path -Parent $MyInvocation.MyCommand.Path
$PROJECT_ROOT = Split-Path -Parent $SCRIPT_DIR

Write-Host ""
Write-Host "╔══════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║        🚀 Deploy Completo SDC para Azure App Service         ║" -ForegroundColor Cyan
Write-Host "╚══════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""
Write-Host "Configuração:" -ForegroundColor Blue
Write-Host "  App Service: $AppServiceName"
Write-Host "  Resource Group: $ResourceGroup"
Write-Host "  ACR: $ACR_LOGIN_SERVER"
Write-Host "  Image: $ACR_IMAGE"
Write-Host ""

# ============================================================================
# 1. Verificar pré-requisitos
# ============================================================================
Write-Host "[1/7] Verificando pré-requisitos..." -ForegroundColor Yellow

if (-not (Get-Command az -ErrorAction SilentlyContinue)) {
    Write-Host "✗ Azure CLI não encontrado" -ForegroundColor Red
    exit 1
}
Write-Host "  ✓ Azure CLI" -ForegroundColor Green

if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    Write-Host "✗ Docker não encontrado" -ForegroundColor Red
    exit 1
}
Write-Host "  ✓ Docker" -ForegroundColor Green

# ============================================================================
# 2. Login no Azure
# ============================================================================
Write-Host ""
Write-Host "[2/7] Verificando login no Azure..." -ForegroundColor Yellow
$accountCheck = az account show --query user.name -o tsv
if ($LASTEXITCODE -eq 0 -and $accountCheck) {
    Write-Host "  ✓ Autenticado como: $accountCheck" -ForegroundColor Green
} else {
    Write-Host "  Fazendo login no Azure..." -ForegroundColor Yellow
    az login
}

# ============================================================================
# 3. Corrigir APP_KEY e variáveis de ambiente
# ============================================================================
Write-Host ""
Write-Host "[3/7] Corrigindo APP_KEY e variáveis de ambiente..." -ForegroundColor Yellow

# Gerar APP_KEY
$appKey = $null
if (Get-Command php -ErrorAction SilentlyContinue) {
    $phpResult = php -r "echo 'base64:'.base64_encode(random_bytes(32));" 2>$null
    if ($phpResult -and $phpResult.Length -gt 10) {
        $appKey = $phpResult
    }
}

if (-not $appKey) {
    $bytes = New-Object byte[] 32
    [System.Security.Cryptography.RandomNumberGenerator]::Fill($bytes)
    $appKey = "base64:" + [Convert]::ToBase64String($bytes)
}

Write-Host "  APP_KEY gerada" -ForegroundColor Gray

# Configurar variáveis
$settings = @(
    "APP_NAME=SDC",
    "APP_ENV=production",
    "APP_KEY=$appKey",
    "APP_DEBUG=false",
    "APP_URL=https://${AppServiceName}.azurewebsites.net",
    "LOG_CHANNEL=stack",
    "LOG_LEVEL=error",
    "CACHE_DRIVER=file",
    "SESSION_DRIVER=file",
    "QUEUE_CONNECTION=sync",
    "WEBSITES_PORT=8000",
    "WEBSITES_ENABLE_APP_SERVICE_STORAGE=false"
)

az webapp config appsettings set `
    --name $AppServiceName `
    --resource-group $ResourceGroup `
    --settings $settings `
    --output none | Out-Null

Write-Host "  ✓ Variáveis de ambiente configuradas" -ForegroundColor Green

# ============================================================================
# 4. Login no ACR
# ============================================================================
Write-Host ""
Write-Host "[4/7] Fazendo login no ACR..." -ForegroundColor Yellow
az acr login --name $AcrName | Out-Null
if ($LASTEXITCODE -ne 0) {
    Write-Host "✗ Erro ao fazer login no ACR" -ForegroundColor Red
    exit 1
}
Write-Host "  ✓ Login no ACR realizado" -ForegroundColor Green

# ============================================================================
# 5. Build da imagem
# ============================================================================
Write-Host ""
Write-Host "[5/7] Building imagem Docker..." -ForegroundColor Yellow
Write-Host "  Context: $PROJECT_ROOT"
Write-Host "  Dockerfile: docker/Dockerfile.prod"
Write-Host "  Image: $ACR_IMAGE"

Push-Location $PROJECT_ROOT

if (-not (Test-Path "composer.json")) {
    Write-Host "✗ ERRO: composer.json não encontrado" -ForegroundColor Red
    Pop-Location
    exit 1
}

docker build `
    -f docker/Dockerfile.prod `
    -t "sdc-dev-app:${Tag}" `
    -t $ACR_IMAGE `
    .

if ($LASTEXITCODE -ne 0) {
    Write-Host "✗ Erro no build da imagem" -ForegroundColor Red
    Pop-Location
    exit 1
}

Write-Host "  ✓ Build concluído" -ForegroundColor Green

# ============================================================================
# 6. Push para ACR
# ============================================================================
Write-Host ""
Write-Host "[6/7] Fazendo push para ACR..." -ForegroundColor Yellow
docker push $ACR_IMAGE

if ($LASTEXITCODE -ne 0) {
    Write-Host "✗ Erro no push da imagem" -ForegroundColor Red
    Pop-Location
    exit 1
}

Write-Host "  ✓ Push concluído: $ACR_IMAGE" -ForegroundColor Green

Pop-Location

# ============================================================================
# 7. Atualizar App Service e reiniciar
# ============================================================================
Write-Host ""
Write-Host "[7/7] Atualizando App Service e reiniciando..." -ForegroundColor Yellow

$ACR_USERNAME = $AcrName
$ACR_PASSWORD = az acr credential show --name $AcrName --query "passwords[0].value" -o tsv

az webapp config container set `
    --name $AppServiceName `
    --resource-group $ResourceGroup `
    --docker-custom-image-name $ACR_IMAGE `
    --docker-registry-server-url "https://${ACR_LOGIN_SERVER}" `
    --docker-registry-server-user $ACR_USERNAME `
    --docker-registry-server-password $ACR_PASSWORD `
    --output none | Out-Null

if ($LASTEXITCODE -ne 0) {
    Write-Host "✗ Erro ao atualizar configuração do container" -ForegroundColor Red
    exit 1
}

Write-Host "  ✓ Imagem atualizada no App Service" -ForegroundColor Green

az webapp restart `
    --name $AppServiceName `
    --resource-group $ResourceGroup `
    --output none | Out-Null

Write-Host "  ✓ App Service reiniciado" -ForegroundColor Green

# ============================================================================
# Resumo
# ============================================================================
Write-Host ""
Write-Host "╔══════════════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║        ✅ Deploy Concluído com Sucesso!                      ║" -ForegroundColor Green
Write-Host "╚══════════════════════════════════════════════════════════════╝" -ForegroundColor Green
Write-Host ""
Write-Host "📋 Resumo:" -ForegroundColor Cyan
Write-Host "  ✓ APP_KEY configurada"
Write-Host "  ✓ Variáveis de ambiente configuradas"
Write-Host "  ✓ Imagem Docker buildada"
Write-Host "  ✓ Imagem enviada para ACR: $ACR_IMAGE"
Write-Host "  ✓ App Service atualizado e reiniciado"
Write-Host ""
Write-Host "⏳ Aguarde ~60 segundos para o container reiniciar completamente" -ForegroundColor Yellow
Write-Host ""
Write-Host "🔍 Verificar logs:" -ForegroundColor Cyan
Write-Host "   az webapp log tail --name $AppServiceName --resource-group $ResourceGroup"
Write-Host ""
Write-Host "🌐 Testar aplicação:" -ForegroundColor Cyan
Write-Host "   https://${AppServiceName}.azurewebsites.net"
Write-Host "   https://${AppServiceName}.azurewebsites.net/login"
Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""
