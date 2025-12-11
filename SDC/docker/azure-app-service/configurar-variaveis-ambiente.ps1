# ============================================================================
# SDC - Configurar Variáveis de Ambiente no App Service
# ============================================================================
# Uso: .\configurar-variaveis-ambiente.ps1
# ============================================================================

param(
    [string]$AppServiceName = "newsdc2027",
    [string]$ResourceGroup = "DEFESA_CIVIL"
)

$ErrorActionPreference = "Stop"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Configurando Variáveis de Ambiente" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Gerar APP_KEY se não existir
Write-Host "[1/3] Gerando APP_KEY..." -ForegroundColor Yellow
$appKey = php -r "echo 'base64:'.base64_encode(random_bytes(32));" 2>$null
if (-not $appKey) {
    # Fallback: gerar manualmente
    $bytes = New-Object byte[] 32
    [System.Security.Cryptography.RandomNumberGenerator]::Fill($bytes)
    $appKey = "base64:" + [Convert]::ToBase64String($bytes)
}
Write-Host "✓ APP_KEY gerada" -ForegroundColor Green

# Configurar variáveis de ambiente essenciais
Write-Host ""
Write-Host "[2/3] Configurando variáveis de ambiente..." -ForegroundColor Yellow

$appSettings = @{
    "APP_NAME" = "SDC"
    "APP_ENV" = "production"
    "APP_KEY" = $appKey
    "APP_DEBUG" = "false"
    "APP_URL" = "https://${AppServiceName}.azurewebsites.net"
    "LOG_CHANNEL" = "stack"
    "LOG_LEVEL" = "error"
    
    # Database (ajustar conforme necessário)
    "DB_CONNECTION" = "mysql"
    "DB_HOST" = "localhost"  # Ajustar para seu banco
    "DB_PORT" = "3306"
    "DB_DATABASE" = "sdc"
    "DB_USERNAME" = "sdc"
    "DB_PASSWORD" = ""  # Configurar senha
    
    # Cache/Session (usar file em vez de Redis se não houver Redis)
    "CACHE_DRIVER" = "file"
    "SESSION_DRIVER" = "file"
    "QUEUE_CONNECTION" = "sync"
    
    # Desabilitar Redis temporariamente
    "REDIS_HOST" = ""
    "REDIS_PORT" = ""
    "REDIS_PASSWORD" = ""
}

# Aplicar configurações
$settingsArray = @()
foreach ($key in $appSettings.Keys) {
    $settingsArray += "${key}=$($appSettings[$key])"
}

az webapp config appsettings set `
    --name $AppServiceName `
    --resource-group $ResourceGroup `
    --settings $settingsArray `
    --output none

Write-Host "✓ Variáveis de ambiente configuradas" -ForegroundColor Green

# Reiniciar App Service
Write-Host ""
Write-Host "[3/3] Reiniciando App Service..." -ForegroundColor Yellow
az webapp restart `
    --name $AppServiceName `
    --resource-group $ResourceGroup `
    --output none

Write-Host "✓ App Service reiniciado" -ForegroundColor Green

# Resumo
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "✅ Configuração concluída!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Variáveis configuradas:" -ForegroundColor Blue
Write-Host "  APP_KEY: $appKey"
Write-Host "  APP_ENV: production"
Write-Host "  CACHE_DRIVER: file (Redis desabilitado)"
Write-Host ""
Write-Host "⚠️  IMPORTANTE:" -ForegroundColor Yellow
Write-Host "  - Configure DB_HOST, DB_DATABASE, DB_USERNAME e DB_PASSWORD"
Write-Host "  - Se tiver Redis, altere CACHE_DRIVER e SESSION_DRIVER para 'redis'"
Write-Host ""
Write-Host "Para editar variáveis:" -ForegroundColor Cyan
Write-Host "  az webapp config appsettings list --name $AppServiceName --resource-group $ResourceGroup"
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan




