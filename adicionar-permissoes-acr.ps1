# Script PowerShell para adicionar permissões AcrPush ao Service Principal no ACR
# Execute este script no Azure Cloud Shell ou PowerShell com Azure CLI instalado

Write-Host "🔧 Adicionando permissões AcrPush ao Service Principal..." -ForegroundColor Cyan
Write-Host ""

# Configurações
$SERVICE_PRINCIPAL_ID = "74596f5b-5c73-4256-9719-b52e7f978985"
$SUBSCRIPTION_ID = "ef65818a-5356-4772-b849-0c793a23ec87"
$RESOURCE_GROUP = "DOVER"
$ACR_NAME = "APIDOVER"  # Nome do ACR (case-insensitive, mas mantendo maiúsculas para consistência)

# Verificar se está logado no Azure
try {
    $account = az account show 2>&1 | ConvertFrom-Json
    if (-not $account) {
        throw "Não logado"
    }
    Write-Host "✅ Logado no Azure" -ForegroundColor Green
    Write-Host "   Subscription: $($account.name)" -ForegroundColor Gray
    Write-Host "   ID: $($account.id)" -ForegroundColor Gray
    Write-Host ""
} catch {
    Write-Host "❌ Você não está logado no Azure CLI" -ForegroundColor Red
    Write-Host "Execute: az login" -ForegroundColor Yellow
    exit 1
}

# Definir subscription correta
Write-Host "📋 Definindo subscription..." -ForegroundColor Cyan
az account set --subscription $SUBSCRIPTION_ID
Write-Host "✅ Subscription: Azure for Students ($SUBSCRIPTION_ID)" -ForegroundColor Green
Write-Host ""

# Verificar se o ACR existe
Write-Host "🔍 Verificando se o ACR existe..." -ForegroundColor Cyan
try {
    $acr = az acr show --name $ACR_NAME --resource-group $RESOURCE_GROUP 2>&1 | ConvertFrom-Json
    Write-Host "✅ ACR encontrado: $($acr.name)" -ForegroundColor Green
    Write-Host "   Login Server: $($acr.loginServer)" -ForegroundColor Gray
    Write-Host ""
} catch {
    Write-Host "❌ ACR '$ACR_NAME' não encontrado no resource group '$RESOURCE_GROUP'" -ForegroundColor Red
    Write-Host "Verifique o nome do ACR e resource group" -ForegroundColor Yellow
    exit 1
}

# Adicionar role AcrPush
Write-Host "🔐 Adicionando role AcrPush ao Service Principal..." -ForegroundColor Cyan
$scope = "/subscriptions/$SUBSCRIPTION_ID/resourceGroups/$RESOURCE_GROUP/providers/Microsoft.ContainerRegistry/registries/$ACR_NAME"

try {
    $result = az role assignment create `
        --assignee $SERVICE_PRINCIPAL_ID `
        --role AcrPush `
        --scope $scope 2>&1
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host ""
        Write-Host "✅ Permissões adicionadas com sucesso!" -ForegroundColor Green
        Write-Host ""
        
        Write-Host "📋 Verificando permissões..." -ForegroundColor Cyan
        az role assignment list `
            --assignee $SERVICE_PRINCIPAL_ID `
            --scope $scope `
            --output table
        Write-Host ""
        
        Write-Host "🎉 Pronto! Aguarde 30-60 segundos para propagação e execute um novo build no Jenkins." -ForegroundColor Green
        Write-Host ""
        Write-Host "Jenkins: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/" -ForegroundColor Cyan
        Write-Host ""
        Write-Host "💡 Dica: Se ainda falhar, reinicie o Jenkins:" -ForegroundColor Yellow
        Write-Host "   az webapp restart --name jenkinssdc --resource-group DEFESA_CIVIL" -ForegroundColor Gray
    } else {
        # Verificar se a permissão já existe
        if ($result -match "already exists" -or $result -match "RoleAssignmentExists") {
            Write-Host ""
            Write-Host "⚠️  Permissão já existe!" -ForegroundColor Yellow
            Write-Host ""
            Write-Host "📋 Verificando permissões atuais..." -ForegroundColor Cyan
            az role assignment list `
                --assignee $SERVICE_PRINCIPAL_ID `
                --scope $scope `
                --output table
            Write-Host ""
            Write-Host "✅ Permissão já configurada. Se ainda houver erro, aguarde propagação ou reinicie o Jenkins." -ForegroundColor Green
        } else {
            throw $result
        }
    }
} catch {
    Write-Host ""
    Write-Host "❌ Falha ao adicionar permissões" -ForegroundColor Red
    Write-Host "Erro: $_" -ForegroundColor Red
    Write-Host ""
    Write-Host "💡 Tente adicionar role Contributor (mais permissiva):" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "az role assignment create \`" -ForegroundColor Gray
    Write-Host "  --assignee $SERVICE_PRINCIPAL_ID \`" -ForegroundColor Gray
    Write-Host "  --role Contributor \`" -ForegroundColor Gray
    Write-Host "  --scope $scope" -ForegroundColor Gray
    Write-Host ""
    exit 1
}

Write-Host ""
Write-Host "📊 Resumo:" -ForegroundColor Cyan
Write-Host "   Service Principal: $SERVICE_PRINCIPAL_ID" -ForegroundColor Gray
Write-Host "   ACR: $ACR_NAME" -ForegroundColor Gray
Write-Host "   Resource Group: $RESOURCE_GROUP" -ForegroundColor Gray
Write-Host "   Role: AcrPush" -ForegroundColor Gray
Write-Host ""



