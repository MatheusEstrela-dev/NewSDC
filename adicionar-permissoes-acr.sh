#!/bin/bash

# Script para adicionar permissões AcrPush ao Service Principal no ACR

echo "🔧 Adicionando permissões AcrPush ao Service Principal..."
echo ""

# Configurações
SERVICE_PRINCIPAL_ID="74596f5b-5c73-4256-9719-b52e7f978985"
SUBSCRIPTION_ID="ef65818a-5356-4772-b849-0c793a23ec87"
RESOURCE_GROUP="DOVER"
ACR_NAME="apidover"

# Verificar se está logado no Azure
if ! az account show &> /dev/null; then
    echo "❌ Você não está logado no Azure CLI"
    echo "Execute: az login"
    exit 1
fi

echo "✅ Logado no Azure"
echo ""

# Definir subscription correta
echo "📋 Definindo subscription..."
az account set --subscription "$SUBSCRIPTION_ID"
echo "✅ Subscription: Azure for Students ($SUBSCRIPTION_ID)"
echo ""

# Adicionar role AcrPush
echo "🔐 Adicionando role AcrPush ao Service Principal..."
az role assignment create \
  --assignee "$SERVICE_PRINCIPAL_ID" \
  --role AcrPush \
  --scope "/subscriptions/$SUBSCRIPTION_ID/resourceGroups/$RESOURCE_GROUP/providers/Microsoft.ContainerRegistry/registries/$ACR_NAME"

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Permissões adicionadas com sucesso!"
    echo ""
    echo "📋 Verificando permissões..."
    az role assignment list \
      --assignee "$SERVICE_PRINCIPAL_ID" \
      --output table
    echo ""
    echo "🎉 Pronto! Aguarde 30 segundos e execute um novo build no Jenkins."
    echo ""
    echo "Jenkins: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/"
else
    echo ""
    echo "❌ Falha ao adicionar permissões"
    echo ""
    echo "💡 Tente adicionar role Contributor (mais permissiva):"
    echo ""
    echo "az role assignment create \\"
    echo "  --assignee $SERVICE_PRINCIPAL_ID \\"
    echo "  --role Contributor \\"
    echo "  --scope /subscriptions/$SUBSCRIPTION_ID/resourceGroups/$RESOURCE_GROUP/providers/Microsoft.ContainerRegistry/registries/$ACR_NAME"
fi
