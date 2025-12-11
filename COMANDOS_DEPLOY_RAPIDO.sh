#!/bin/bash
# ============================================================================
# Deploy Rápido para ACR e Azure App Service
# ============================================================================

set -e

echo "🚀 Deploy SDC para Azure"
echo ""

# Variáveis
ACR_NAME="apidover"
APP_SERVICE="newsdc2027"
RESOURCE_GROUP="DEFESA_CIVIL"

# 1. Build (se necessário)
echo "📦 Build da imagem..."
cd SDC
docker build -f docker/Dockerfile.prod -t sdc-dev-app:prod .

# 2. Tag para ACR
echo "🏷️  Tag para ACR..."
docker tag sdc-dev-app:prod ${ACR_NAME}.azurecr.io/sdc-dev-app:latest
docker tag sdc-dev-app:prod ${ACR_NAME}.azurecr.io/sdc-dev-app:$(date +%Y%m%d-%H%M%S)

# 3. Login e Push
echo "📤 Push para ACR..."
az acr login --name ${ACR_NAME}
docker push ${ACR_NAME}.azurecr.io/sdc-dev-app:latest
docker push ${ACR_NAME}.azurecr.io/sdc-dev-app:$(date +%Y%m%d-%H%M%S)

# 4. Gerar APP_KEY
echo "🔑 Gerando APP_KEY..."
APP_KEY=$(docker run --rm php:8.3-cli php -r "echo 'base64:' . base64_encode(random_bytes(32)) . PHP_EOL;")

# 5. Configurar App Service
echo "⚙️  Configurando App Service..."
az webapp config appsettings set \
  --name ${APP_SERVICE} \
  --resource-group ${RESOURCE_GROUP} \
  --settings \
    APP_KEY="$APP_KEY" \
    WEBSITES_PORT="8000" \
    APP_NAME="SDC" \
    APP_ENV="production" \
    APP_DEBUG="false" \
    APP_URL="https://${APP_SERVICE}.azurewebsites.net" \
    DB_CONNECTION="sqlite" \
    WEBSITES_ENABLE_APP_SERVICE_STORAGE="true"

# 6. Restart
echo "🔄 Reiniciando App Service..."
az webapp restart --name ${APP_SERVICE} --resource-group ${RESOURCE_GROUP}

echo ""
echo "✅ Deploy concluído!"
echo "🌐 URL: https://${APP_SERVICE}.azurewebsites.net"
echo ""
echo "📋 Próximos passos:"
echo "   1. Aguardar ~30 segundos"
echo "   2. Verificar: curl https://${APP_SERVICE}.azurewebsites.net/"
echo "   3. Ver logs: az webapp log tail --name ${APP_SERVICE} --resource-group ${RESOURCE_GROUP}"
