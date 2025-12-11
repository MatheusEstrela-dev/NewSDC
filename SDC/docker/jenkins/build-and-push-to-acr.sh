#!/bin/bash
# ============================================================================
# Script para build e push da imagem Jenkins para Azure Container Registry
# ============================================================================
# Uso: ./build-and-push-to-acr.sh -n apidover -t latest
# ============================================================================

set -e

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Valores padrão
ACR_NAME=""
TAG="latest"
IMAGE_NAME="sdc-jenkins"

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -n|--name)
            ACR_NAME="$2"
            shift 2
            ;;
        -t|--tag)
            TAG="$2"
            shift 2
            ;;
        -i|--image)
            IMAGE_NAME="$2"
            shift 2
            ;;
        *)
            echo -e "${RED}Uso: $0 -n <acr-name> [-t <tag>] [-i <image-name>]${NC}"
            echo "Exemplo: $0 -n apidover -t latest"
            exit 1
            ;;
    esac
done

if [ -z "$ACR_NAME" ]; then
    echo -e "${RED}Erro: Nome do ACR é obrigatório (-n)${NC}"
    exit 1
fi

ACR_LOGIN_SERVER="${ACR_NAME}.azurecr.io"
ACR_IMAGE="${ACR_LOGIN_SERVER}/${IMAGE_NAME}:${TAG}"

echo -e "${CYAN}========================================${NC}"
echo -e "${CYAN}Build e Push Jenkins para Azure ACR${NC}"
echo -e "${CYAN}========================================${NC}"
echo ""

# 1. Verificar se está no diretório correto
if [ ! -f "Dockerfile" ]; then
    echo -e "${RED}Erro: Dockerfile não encontrado${NC}"
    echo "Execute este script do diretório docker/jenkins/"
    exit 1
fi

# 2. Login no Azure
echo -e "${YELLOW}[1/4] Verificando login no Azure...${NC}"
if ! az account show &>/dev/null; then
    echo -e "${YELLOW}Fazendo login no Azure...${NC}"
    az login
else
    echo -e "${GREEN}✓ Já autenticado no Azure${NC}"
fi

# 3. Login no ACR
echo ""
echo -e "${YELLOW}[2/4] Fazendo login no ACR: ${ACR_LOGIN_SERVER}${NC}"
az acr login --name "$ACR_NAME"
echo -e "${GREEN}✓ Login no ACR realizado${NC}"

# 4. Build da imagem
echo ""
echo -e "${YELLOW}[3/4] Building imagem Jenkins...${NC}"
echo -e "  Image: ${ACR_IMAGE}"
echo -e "  Context: $(pwd)"

docker build \
    -t "${IMAGE_NAME}:${TAG}" \
    -t "${ACR_IMAGE}" \
    -f Dockerfile \
    .

echo -e "${GREEN}✓ Build concluído${NC}"

# 5. Push para ACR
echo ""
echo -e "${YELLOW}[4/4] Fazendo push para ACR...${NC}"
docker push "${ACR_IMAGE}"

echo ""
echo -e "${CYAN}========================================${NC}"
echo -e "${GREEN}✅ Imagem enviada com sucesso!${NC}"
echo -e "${CYAN}========================================${NC}"
echo ""
echo -e "Imagem: ${ACR_IMAGE}"
echo ""
echo -e "${CYAN}Para usar a imagem:${NC}"
echo -e "  docker pull ${ACR_IMAGE}"
echo ""
echo -e "${CYAN}Para deploy no Azure Container Instances:${NC}"
echo -e "  az container create \\"
echo -e "    --resource-group <resource-group> \\"
echo -e "    --name sdc-jenkins \\"
echo -e "    --image ${ACR_IMAGE} \\"
echo -e "    --registry-login-server ${ACR_LOGIN_SERVER} \\"
echo -e "    --registry-username ${ACR_NAME} \\"
echo -e "    --registry-password \$(az acr credential show --name ${ACR_NAME} --query 'passwords[0].value' -o tsv) \\"
echo -e "    --cpu 4 --memory 8 \\"
echo -e "    --ports 8080 50000"
echo -e "${CYAN}========================================${NC}"




