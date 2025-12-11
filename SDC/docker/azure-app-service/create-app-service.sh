#!/bin/bash
# ============================================================================
# SDC - Criar Azure App Service para SDC
# ============================================================================
# Uso: ./create-app-service.sh -g <resource-group> -n <app-name> -p <plan-name>
# ============================================================================

set -e

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

# Valores padrão
RESOURCE_GROUP=""
APP_NAME=""
PLAN_NAME=""
LOCATION="brazilsouth"
SKU="B1"  # Basic tier
ACR_NAME="apidover"
ACR_IMAGE="apidover.azurecr.io/sdc-dev-app:latest"

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -g|--resource-group)
            RESOURCE_GROUP="$2"
            shift 2
            ;;
        -n|--app-name)
            APP_NAME="$2"
            shift 2
            ;;
        -p|--plan-name)
            PLAN_NAME="$2"
            shift 2
            ;;
        -l|--location)
            LOCATION="$2"
            shift 2
            ;;
        -s|--sku)
            SKU="$2"
            shift 2
            ;;
        *)
            echo -e "${RED}Uso: $0 -g <resource-group> -n <app-name> -p <plan-name> [opções]${NC}"
            echo "  -l, --location    Localização (padrão: brazilsouth)"
            echo "  -s, --sku         SKU do App Service Plan (padrão: B1)"
            exit 1
            ;;
    esac
done

if [ -z "$RESOURCE_GROUP" ] || [ -z "$APP_NAME" ] || [ -z "$PLAN_NAME" ]; then
    echo -e "${RED}Erro: Resource Group, App Name e Plan Name são obrigatórios${NC}"
    exit 1
fi

echo -e "${CYAN}========================================${NC}"
echo -e "${CYAN}SDC - Criar Azure App Service${NC}"
echo -e "${CYAN}========================================${NC}"
echo ""

# 1. Verificar login no Azure
echo -e "${YELLOW}[1/6] Verificando login no Azure...${NC}"
if ! az account show &>/dev/null; then
    echo -e "${YELLOW}Fazendo login no Azure...${NC}"
    az login
fi
echo -e "${GREEN}✓ Autenticado no Azure${NC}"

# 2. Criar Resource Group (se não existir)
echo ""
echo -e "${YELLOW}[2/6] Verificando Resource Group...${NC}"
if ! az group show --name "$RESOURCE_GROUP" &>/dev/null; then
    echo -e "${YELLOW}Criando Resource Group: ${RESOURCE_GROUP}...${NC}"
    az group create --name "$RESOURCE_GROUP" --location "$LOCATION"
    echo -e "${GREEN}✓ Resource Group criado${NC}"
else
    echo -e "${GREEN}✓ Resource Group já existe${NC}"
fi

# 3. Criar App Service Plan (se não existir)
echo ""
echo -e "${YELLOW}[3/6] Verificando App Service Plan...${NC}"
if ! az appservice plan show --name "$PLAN_NAME" --resource-group "$RESOURCE_GROUP" &>/dev/null; then
    echo -e "${YELLOW}Criando App Service Plan: ${PLAN_NAME}...${NC}"
    az appservice plan create \
        --name "$PLAN_NAME" \
        --resource-group "$RESOURCE_GROUP" \
        --location "$LOCATION" \
        --is-linux \
        --sku "$SKU"
    echo -e "${GREEN}✓ App Service Plan criado${NC}"
else
    echo -e "${GREEN}✓ App Service Plan já existe${NC}"
fi

# 4. Obter credenciais do ACR
echo ""
echo -e "${YELLOW}[4/6] Obtendo credenciais do ACR...${NC}"
ACR_USERNAME="$ACR_NAME"
ACR_PASSWORD=$(az acr credential show --name "$ACR_NAME" --query "passwords[0].value" -o tsv)

if [ -z "$ACR_PASSWORD" ]; then
    echo -e "${RED}✗ Erro: Não foi possível obter senha do ACR${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Credenciais do ACR obtidas${NC}"

# 5. Criar App Service
echo ""
echo -e "${YELLOW}[5/6] Criando App Service: ${APP_NAME}...${NC}"
if ! az webapp show --name "$APP_NAME" --resource-group "$RESOURCE_GROUP" &>/dev/null; then
    az webapp create \
        --name "$APP_NAME" \
        --resource-group "$RESOURCE_GROUP" \
        --plan "$PLAN_NAME" \
        --deployment-container-image-name "$ACR_IMAGE"

    # Configurar credenciais do ACR
    az webapp config container set \
        --name "$APP_NAME" \
        --resource-group "$RESOURCE_GROUP" \
        --docker-custom-image-name "$ACR_IMAGE" \
        --docker-registry-server-url "https://${ACR_NAME}.azurecr.io" \
        --docker-registry-server-user "$ACR_USERNAME" \
        --docker-registry-server-password "$ACR_PASSWORD"

    echo -e "${GREEN}✓ App Service criado${NC}"
else
    echo -e "${YELLOW}⚠ App Service já existe, atualizando configuração...${NC}"
    az webapp config container set \
        --name "$APP_NAME" \
        --resource-group "$RESOURCE_GROUP" \
        --docker-custom-image-name "$ACR_IMAGE" \
        --docker-registry-server-url "https://${ACR_NAME}.azurecr.io" \
        --docker-registry-server-user "$ACR_USERNAME" \
        --docker-registry-server-password "$ACR_PASSWORD"
    echo -e "${GREEN}✓ Configuração atualizada${NC}"
fi

# 6. Configurar variáveis de ambiente
echo ""
echo -e "${YELLOW}[6/6] Configurando variáveis de ambiente...${NC}"
az webapp config appsettings set \
    --name "$APP_NAME" \
    --resource-group "$RESOURCE_GROUP" \
    --settings \
        WEBSITES_ENABLE_APP_SERVICE_STORAGE=false \
        WEBSITES_PORT=8000 \
        APP_ENV=production \
        APP_DEBUG=false \
        DOCKER_ENABLE_CI=true \
    --output none

echo -e "${GREEN}✓ Variáveis de ambiente configuradas${NC}"

# Resumo
echo ""
echo -e "${CYAN}========================================${NC}"
echo -e "${GREEN}✅ App Service criado com sucesso!${NC}"
echo -e "${CYAN}========================================${NC}"
echo ""
echo -e "${BLUE}Informações:${NC}"
echo -e "  App Service: ${APP_NAME}"
echo -e "  URL: https://${APP_NAME}.azurewebsites.net"
echo -e "  Resource Group: ${RESOURCE_GROUP}"
echo -e "  Plan: ${PLAN_NAME}"
echo -e "  Image: ${ACR_IMAGE}"
echo ""
echo -e "${CYAN}Para atualizar a imagem:${NC}"
echo -e "  az webapp config container set \\"
echo -e "    --name ${APP_NAME} \\"
echo -e "    --resource-group ${RESOURCE_GROUP} \\"
echo -e "    --docker-custom-image-name ${ACR_IMAGE}"
echo ""
echo -e "${CYAN}Para ver logs:${NC}"
echo -e "  az webapp log tail --name ${APP_NAME} --resource-group ${RESOURCE_GROUP}"
echo ""
echo -e "${CYAN}========================================${NC}"




