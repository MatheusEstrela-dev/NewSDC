#!/bin/bash
# ============================================================================
# Script para deploy do Jenkins no Azure Container Instances (ACI)
# ============================================================================
# Uso: ./deploy-to-azure.sh -g meu-rg -n apidover -i sdc-jenkins -t latest
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
ACR_NAME=""
IMAGE_NAME="sdc-jenkins"
TAG="latest"
CONTAINER_NAME="sdc-jenkins"
CPU="4"
MEMORY="8"
LOCATION="brazilsouth"

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -g|--resource-group)
            RESOURCE_GROUP="$2"
            shift 2
            ;;
        -n|--acr-name)
            ACR_NAME="$2"
            shift 2
            ;;
        -i|--image)
            IMAGE_NAME="$2"
            shift 2
            ;;
        -t|--tag)
            TAG="$2"
            shift 2
            ;;
        -c|--container-name)
            CONTAINER_NAME="$2"
            shift 2
            ;;
        --cpu)
            CPU="$2"
            shift 2
            ;;
        --memory)
            MEMORY="$2"
            shift 2
            ;;
        -l|--location)
            LOCATION="$2"
            shift 2
            ;;
        *)
            echo -e "${RED}Uso: $0 -g <resource-group> -n <acr-name> [opções]${NC}"
            echo "Opções:"
            echo "  -i, --image          Nome da imagem (padrão: sdc-jenkins)"
            echo "  -t, --tag           Tag da imagem (padrão: latest)"
            echo "  -c, --container-name Nome do container (padrão: sdc-jenkins)"
            echo "  --cpu               CPUs (padrão: 4)"
            echo "  --memory            Memória em GB (padrão: 8)"
            echo "  -l, --location      Região Azure (padrão: brazilsouth)"
            exit 1
            ;;
    esac
done

if [ -z "$RESOURCE_GROUP" ] || [ -z "$ACR_NAME" ]; then
    echo -e "${RED}Erro: Resource Group e ACR Name são obrigatórios${NC}"
    exit 1
fi

ACR_LOGIN_SERVER="${ACR_NAME}.azurecr.io"
ACR_IMAGE="${ACR_LOGIN_SERVER}/${IMAGE_NAME}:${TAG}"

echo -e "${CYAN}========================================${NC}"
echo -e "${CYAN}Deploy Jenkins no Azure Container Instances${NC}"
echo -e "${CYAN}========================================${NC}"
echo ""

# 1. Verificar login no Azure
echo -e "${YELLOW}[1/5] Verificando login no Azure...${NC}"
if ! az account show &>/dev/null; then
    echo -e "${YELLOW}Fazendo login no Azure...${NC}"
    az login
else
    echo -e "${GREEN}✓ Já autenticado no Azure${NC}"
fi

# 2. Verificar se resource group existe
echo ""
echo -e "${YELLOW}[2/5] Verificando Resource Group...${NC}"
if ! az group show --name "$RESOURCE_GROUP" &>/dev/null; then
    echo -e "${YELLOW}Criando Resource Group: ${RESOURCE_GROUP}${NC}"
    az group create --name "$RESOURCE_GROUP" --location "$LOCATION"
    echo -e "${GREEN}✓ Resource Group criado${NC}"
else
    echo -e "${GREEN}✓ Resource Group existe${NC}"
fi

# 3. Obter credenciais do ACR
echo ""
echo -e "${YELLOW}[3/5] Obtendo credenciais do ACR...${NC}"
ACR_USERNAME="$ACR_NAME"
ACR_PASSWORD=$(az acr credential show --name "$ACR_NAME" --query "passwords[0].value" -o tsv)

if [ -z "$ACR_PASSWORD" ]; then
    echo -e "${RED}Erro: Não foi possível obter senha do ACR${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Credenciais obtidas${NC}"

# 4. Verificar se container já existe
echo ""
echo -e "${YELLOW}[4/5] Verificando container existente...${NC}"
if az container show --resource-group "$RESOURCE_GROUP" --name "$CONTAINER_NAME" &>/dev/null; then
    echo -e "${YELLOW}Container já existe. Removendo...${NC}"
    az container delete --resource-group "$RESOURCE_GROUP" --name "$CONTAINER_NAME" --yes
    echo -e "${GREEN}✓ Container removido${NC}"
fi

# 5. Criar container no ACI
echo ""
echo -e "${YELLOW}[5/5] Criando container no Azure Container Instances...${NC}"
echo -e "  Resource Group: ${RESOURCE_GROUP}"
echo -e "  Container Name: ${CONTAINER_NAME}"
echo -e "  Image: ${ACR_IMAGE}"
echo -e "  CPU: ${CPU}"
echo -e "  Memory: ${MEMORY}GB"
echo -e "  Location: ${LOCATION}"

az container create \
    --resource-group "$RESOURCE_GROUP" \
    --name "$CONTAINER_NAME" \
    --image "$ACR_IMAGE" \
    --registry-login-server "$ACR_LOGIN_SERVER" \
    --registry-username "$ACR_USERNAME" \
    --registry-password "$ACR_PASSWORD" \
    --cpu "$CPU" \
    --memory "${MEMORY}Gi" \
    --location "$LOCATION" \
    --ports 8080 50000 \
    --dns-name-label "${CONTAINER_NAME}-$(date +%s)" \
    --environment-variables \
        JAVA_OPTS="-Xms512m -Xmx6g -Djava.awt.headless=true" \
        JENKINS_OPTS="--prefix=/" \
    --secure-environment-variables \
        JENKINS_ADMIN_USER="${JENKINS_ADMIN_USER:-admin}" \
        JENKINS_ADMIN_PASSWORD="${JENKINS_ADMIN_PASSWORD:-admin123}"

echo ""
echo -e "${GREEN}✓ Container criado com sucesso!${NC}"

# 6. Obter informações do container
echo ""
echo -e "${CYAN}========================================${NC}"
echo -e "${GREEN}✅ Deploy concluído!${NC}"
echo -e "${CYAN}========================================${NC}"
echo ""

FQDN=$(az container show --resource-group "$RESOURCE_GROUP" --name "$CONTAINER_NAME" --query "ipAddress.fqdn" -o tsv)
IP_ADDRESS=$(az container show --resource-group "$RESOURCE_GROUP" --name "$CONTAINER_NAME" --query "ipAddress.ip" -o tsv)
STATE=$(az container show --resource-group "$RESOURCE_GROUP" --name "$CONTAINER_NAME" --query "containers[0].instanceView.currentState.state" -o tsv)

echo -e "Container Name: ${CONTAINER_NAME}"
echo -e "FQDN: ${FQDN}"
echo -e "IP Address: ${IP_ADDRESS}"
echo -e "State: ${STATE}"
echo ""
echo -e "${CYAN}Acesse o Jenkins em:${NC}"
echo -e "  http://${FQDN}:8080"
echo -e "  http://${IP_ADDRESS}:8080"
echo ""
echo -e "${CYAN}Comandos úteis:${NC}"
echo -e "  # Ver logs"
echo -e "  az container logs --resource-group ${RESOURCE_GROUP} --name ${CONTAINER_NAME}"
echo ""
echo -e "  # Ver status"
echo -e "  az container show --resource-group ${RESOURCE_GROUP} --name ${CONTAINER_NAME} --query containers[0].instanceView.currentState"
echo ""
echo -e "  # Parar container"
echo -e "  az container stop --resource-group ${RESOURCE_GROUP} --name ${CONTAINER_NAME}"
echo ""
echo -e "  # Iniciar container"
echo -e "  az container start --resource-group ${RESOURCE_GROUP} --name ${CONTAINER_NAME}"
echo ""
echo -e "  # Remover container"
echo -e "  az container delete --resource-group ${RESOURCE_GROUP} --name ${CONTAINER_NAME} --yes"
echo -e "${CYAN}========================================${NC}"




