#!/bin/bash
# ============================================================================
# SDC - Deploy Rápido para Azure App Service
# ============================================================================
# Uso: ./deploy-rapido.sh
# ============================================================================

set -e

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

# Configurações
APP_SERVICE_NAME="${AZURE_APP_SERVICE_NAME:-newsdc2027}"
RESOURCE_GROUP="${AZURE_RESOURCE_GROUP:-DEFESA_CIVIL}"
ACR_NAME="${ACR_NAME:-apidover}"
ACR_IMAGE="${ACR_NAME}.azurecr.io/sdc-dev-app:latest"

echo -e "${CYAN}========================================${NC}"
echo -e "${CYAN}SDC - Deploy Rápido${NC}"
echo -e "${CYAN}========================================${NC}"
echo ""
echo -e "${BLUE}Configuração:${NC}"
echo -e "  App Service: ${APP_SERVICE_NAME}"
echo -e "  Resource Group: ${RESOURCE_GROUP}"
echo -e "  ACR Image: ${ACR_IMAGE}"
echo ""

# 1. Verificar login no Azure
echo -e "${YELLOW}[1/4] Verificando login no Azure...${NC}"
if ! az account show &>/dev/null; then
    echo -e "${YELLOW}Fazendo login no Azure...${NC}"
    az login
fi
echo -e "${GREEN}✓ Autenticado no Azure${NC}"

# 2. Login no ACR
echo ""
echo -e "${YELLOW}[2/4] Fazendo login no ACR...${NC}"
az acr login --name "$ACR_NAME"
echo -e "${GREEN}✓ Login no ACR realizado${NC}"

# 3. Verificar se a imagem existe localmente
echo ""
echo -e "${YELLOW}[3/4] Verificando imagem local...${NC}"
if docker images --format "{{.Repository}}:{{.Tag}}" | grep -q "^sdc-dev-app:latest$"; then
    echo -e "${GREEN}✓ Imagem local encontrada${NC}"
    
    # Tag para ACR
    echo -e "${YELLOW}  Aplicando tag ACR...${NC}"
    docker tag sdc-dev-app:latest "$ACR_IMAGE"
    
    # Push para ACR
    echo -e "${YELLOW}  Fazendo push para ACR...${NC}"
    docker push "$ACR_IMAGE"
    echo -e "${GREEN}✓ Push concluído${NC}"
else
    echo -e "${YELLOW}⚠ Imagem local não encontrada${NC}"
    echo -e "${YELLOW}  Usando imagem existente no ACR${NC}"
fi

# 4. Atualizar App Service
echo ""
echo -e "${YELLOW}[4/4] Atualizando App Service...${NC}"

# Atualizar imagem
az webapp config container set \
    --name "$APP_SERVICE_NAME" \
    --resource-group "$RESOURCE_GROUP" \
    --docker-custom-image-name "$ACR_IMAGE" \
    --output none

echo -e "${GREEN}✓ Configuração atualizada${NC}"

# Reiniciar App Service
echo -e "${YELLOW}  Reiniciando App Service...${NC}"
az webapp restart \
    --name "$APP_SERVICE_NAME" \
    --resource-group "$RESOURCE_GROUP" \
    --output none

echo -e "${GREEN}✓ App Service reiniciado${NC}"

# Aguardar alguns segundos
echo -e "${YELLOW}  Aguardando aplicação iniciar...${NC}"
sleep 10

# Verificar status
APP_URL="https://${APP_SERVICE_NAME}.azurewebsites.net"
echo -e "${YELLOW}  Verificando saúde da aplicação...${NC}"

for i in {1..12}; do
    if curl -f -s "${APP_URL}/health" &>/dev/null; then
        echo -e "${GREEN}✓ Aplicação está respondendo!${NC}"
        break
    fi
    echo -e "${YELLOW}  Tentativa $i/12: Aguardando...${NC}"
    sleep 5
done

# Resumo
echo ""
echo -e "${CYAN}========================================${NC}"
echo -e "${GREEN}✅ Deploy concluído!${NC}"
echo -e "${CYAN}========================================${NC}"
echo ""
echo -e "${BLUE}Informações:${NC}"
echo -e "  App Service: ${APP_SERVICE_NAME}"
echo -e "  URL: ${APP_URL}"
echo -e "  Image: ${ACR_IMAGE}"
echo ""
echo -e "${CYAN}Para ver logs:${NC}"
echo -e "  az webapp log tail --name ${APP_SERVICE_NAME} --resource-group ${RESOURCE_GROUP}"
echo ""
echo -e "${CYAN}========================================${NC}"




