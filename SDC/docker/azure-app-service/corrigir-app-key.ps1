# ============================================================================
# SDC - Corrigir APP_KEY e Redis no App Service (Solução Rápida)
# ============================================================================
# Uso: .\corrigir-app-key.ps1
# ============================================================================

param(
    [string]$AppServiceName = "newsdc2027",
    [string]$ResourceGroup = "DEFESA_CIVIL"
)

$ErrorActionPreference = "Stop"

Write-Host ""
Write-Host "╔══════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║        🔧 Corrigir APP_KEY e Configurações do App Service    ║" -ForegroundColor Cyan
Write-Host "╚══════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Verificar se está logado no Azure
Write-Host "[1/4] Verificando login no Azure..." -ForegroundColor Yellow
try {
    $account = az account show 2>$null | ConvertFrom-Json
    if (-not $account) {
        Write-Host "⚠️  Não autenticado. Fazendo login..." -ForegroundColor Yellow
        az login
    } else {
        Write-Host "✓ Autenticado como: $($account.user.name)" -ForegroundColor Green
    }
} catch {
    Write-Host "⚠️  Fazendo login no Azure..." -ForegroundColor Yellow
    az login
}

# Gerar APP_KEY
Write-Host ""
Write-Host "[2/4] Gerando APP_KEY..." -ForegroundColor Yellow
try {
    # Tentar usar PHP se disponível
    $appKey = php -r "echo 'base64:'.base64_encode(random_bytes(32));" 2>$null
    if (-not $appKey -or $appKey.Length -lt 10) {
        throw "PHP não disponível"
    }
    Write-Host "✓ APP_KEY gerada usando PHP" -ForegroundColor Green
} catch {
    # Fallback: gerar usando .NET
    Write-Host "  Usando método alternativo..." -ForegroundColor Gray
    $bytes = New-Object byte[] 32
    [System.Security.Cryptography.RandomNumberGenerator]::Fill($bytes)
    $appKey = "base64:" + [Convert]::ToBase64String($bytes)
    Write-Host "✓ APP_KEY gerada usando .NET" -ForegroundColor Green
}

Write-Host "  APP_KEY: $appKey" -ForegroundColor Gray

# Configurar variáveis de ambiente
Write-Host ""
Write-Host "[3/4] Configurando variáveis de ambiente no App Service..." -ForegroundColor Yellow

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

Write-Host "  Configurando ${settings.Count} variáveis..." -ForegroundColor Gray

az webapp config appsettings set `
    --name $AppServiceName `
    --resource-group $ResourceGroup `
    --settings $settings `
    --output none

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Variáveis de ambiente configuradas" -ForegroundColor Green
} else {
    Write-Host "✗ Erro ao configurar variáveis de ambiente" -ForegroundColor Red
    exit 1
}

# Reiniciar App Service
Write-Host ""
Write-Host "[4/4] Reiniciando App Service..." -ForegroundColor Yellow
az webapp restart `
    --name $AppServiceName `
    --resource-group $ResourceGroup `
    --output none

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ App Service reiniciado" -ForegroundColor Green
} else {
    Write-Host "✗ Erro ao reiniciar App Service" -ForegroundColor Red
    exit 1
}

# Resumo
Write-Host ""
Write-Host "╔══════════════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║        ✅ Configuração Concluída com Sucesso!               ║" -ForegroundColor Green
Write-Host "╚══════════════════════════════════════════════════════════════╝" -ForegroundColor Green
Write-Host ""
Write-Host "📋 Resumo das alterações:" -ForegroundColor Cyan
Write-Host "  ✓ APP_KEY configurada e gerada automaticamente"
Write-Host "  ✓ CACHE_DRIVER alterado para 'file' (Redis desabilitado)"
Write-Host "  ✓ SESSION_DRIVER alterado para 'file'"
Write-Host "  ✓ QUEUE_CONNECTION alterado para 'sync'"
Write-Host "  ✓ App Service reiniciado"
Write-Host ""
Write-Host "⏳ Aguarde ~30 segundos para o container reiniciar completamente" -ForegroundColor Yellow
Write-Host ""
Write-Host "🔍 Verificar logs:" -ForegroundColor Cyan
Write-Host "   az webapp log tail --name $AppServiceName --resource-group $ResourceGroup"
Write-Host ""
Write-Host "🌐 Testar aplicação:" -ForegroundColor Cyan
Write-Host "   https://${AppServiceName}.azurewebsites.net"
Write-Host ""
Write-Host "⚠️  PRÓXIMOS PASSOS:" -ForegroundColor Yellow
Write-Host "   1. Configure DB_HOST, DB_DATABASE, DB_USERNAME e DB_PASSWORD se usar MySQL"
Write-Host "   2. Se tiver Redis, altere CACHE_DRIVER e SESSION_DRIVER para 'redis'"
Write-Host "   3. Verifique os logs para confirmar que não há mais erros"
Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""




