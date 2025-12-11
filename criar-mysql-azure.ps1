# ============================================================================
# Script para criar MySQL no Azure e configurar App Service
# ============================================================================

$ResourceGroup = "DEFESA_CIVIL"
$AppServiceName = "newsdc2027"
$MySQLServerName = "sdc-mysql-$(Get-Random -Minimum 1000 -Maximum 9999)"
$Location = "brazilsouth"
$AdminUser = "sdcadmin"
$AdminPassword = "SDC@Senha123!"  # ⚠️ ALTERE ESTA SENHA!

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Criando MySQL no Azure" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 1. Criar MySQL Flexible Server
Write-Host "[1/4] Criando MySQL Flexible Server..." -ForegroundColor Yellow
az mysql flexible-server create `
    --resource-group $ResourceGroup `
    --name $MySQLServerName `
    --location $Location `
    --admin-user $AdminUser `
    --admin-password $AdminPassword `
    --sku-name Standard_B1ms `
    --tier Burstable `
    --version 8.0.21 `
    --storage-size 32 `
    --public-access 0.0.0.0-255.255.255.255 `
    --output none

if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Erro ao criar MySQL Server" -ForegroundColor Red
    exit 1
}

Write-Host "✅ MySQL Server criado: $MySQLServerName" -ForegroundColor Green

# 2. Obter FQDN do servidor
Write-Host ""
Write-Host "[2/4] Obtendo informações do servidor..." -ForegroundColor Yellow
$FQDN = az mysql flexible-server show `
    --resource-group $ResourceGroup `
    --name $MySQLServerName `
    --query "fullyQualifiedDomainName" -o tsv

Write-Host "✅ FQDN: $FQDN" -ForegroundColor Green

# 3. Criar banco de dados
Write-Host ""
Write-Host "[3/4] Criando banco de dados 'sdc'..." -ForegroundColor Yellow
az mysql flexible-server db create `
    --resource-group $ResourceGroup `
    --server-name $MySQLServerName `
    --database-name sdc `
    --output none

Write-Host "✅ Banco de dados 'sdc' criado" -ForegroundColor Green

# 4. Configurar App Service
Write-Host ""
Write-Host "[4/4] Configurando App Service..." -ForegroundColor Yellow
az webapp config appsettings set `
    --name $AppServiceName `
    --resource-group $ResourceGroup `
    --settings `
        "DB_CONNECTION=mysql" `
        "DB_HOST=$FQDN" `
        "DB_PORT=3306" `
        "DB_DATABASE=sdc" `
        "DB_USERNAME=$AdminUser" `
        "DB_PASSWORD=$AdminPassword" `
    --output none

Write-Host "✅ Variáveis de ambiente configuradas" -ForegroundColor Green

# 5. Reiniciar App Service
Write-Host ""
Write-Host "Reiniciando App Service..." -ForegroundColor Yellow
az webapp restart `
    --name $AppServiceName `
    --resource-group $ResourceGroup `
    --output none

Write-Host "✅ App Service reiniciado" -ForegroundColor Green

# Resumo
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "✅ MySQL configurado com sucesso!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "📋 Informações:" -ForegroundColor Blue
Write-Host "  MySQL Server: $MySQLServerName"
Write-Host "  FQDN: $FQDN"
Write-Host "  Database: sdc"
Write-Host "  Username: $AdminUser"
Write-Host "  Password: $AdminPassword"
Write-Host ""
Write-Host "⏳ Aguarde 2-3 minutos para o App Service reiniciar"
Write-Host "   O entrypoint executará migrations automaticamente"
Write-Host ""
Write-Host "🔐 IMPORTANTE: Guarde estas credenciais em local seguro!"


