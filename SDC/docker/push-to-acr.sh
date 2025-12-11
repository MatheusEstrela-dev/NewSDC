#!/bin/bash
# ============================================================================
# SDC - Script para Push de Imagens para Azure Container Registry (ACR)
# ============================================================================
# Uso: ./docker/push-to-acr.sh -n "seuacr" -g "seu-rg" -t "dev-latest"
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
RESOURCE_GROUP=""
TAG="dev-latest"
LOGIN_ONLY=false

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -n|--name)
            ACR_NAME="$2"
            shift 2
            ;;
        -g|--resource-group)
            RESOURCE_GROUP="$2"
            shift 2
            ;;
        -t|--tag)
            TAG="$2"
            shift 2
            ;;
        --login-only)
            LOGIN_ONLY=true
            shift
            ;;
        *)
            echo "Uso: $0 -n <acr-name> [-g <resource-group>] [-t <tag>] [--login-only]"
            exit 1
            ;;
    esac
done

if [ -z "$ACR_NAME" ]; then
    echo -e "${RED}Erro: Nome do ACR é obrigatório (-n)${NC}"
    exit 1
fi

ACR_LOGIN_SERVER="${ACR_NAME}.azurecr.io"

# Imagens para fazer push (formato: local_image:target_name)
IMAGES=(
    "sdc-dev-app:latest:sdc-dev-app"
)

echo -e "${CYAN}========================================${NC}"
echo -e "${CYAN}SDC - Push para Azure Container Registry${NC}"
echo -e "${CYAN}========================================${NC}"
echo ""

# 1. Login no Azure
echo -e "${YELLOW}[1/5] Verificando login no Azure...${NC}"
if ! az account show &>/dev/null; then
    echo -e "${YELLOW}Fazendo login no Azure...${NC}"
    az login
else
    echo -e "${GREEN}✓ Já autenticado no Azure${NC}"
fi

# 2. Login no ACR
echo ""
echo -e "${YELLOW}[2/5] Fazendo login no ACR: ${ACR_LOGIN_SERVER}${NC}"
if [ -n "$RESOURCE_GROUP" ]; then
    az acr login --name "$ACR_NAME" --resource-group "$RESOURCE_GROUP"
else
    az acr login --name "$ACR_NAME"
fi
echo -e "${GREEN}✓ Login no ACR realizado com sucesso${NC}"

if [ "$LOGIN_ONLY" = true ]; then
    echo ""
    echo -e "${GREEN}Login realizado. Execute o script novamente sem --login-only para fazer push.${NC}"
    exit 0
fi

# 3. Tag das imagens
echo ""
echo -e "${YELLOW}[3/5] Aplicando tags ACR nas imagens...${NC}"
for image_spec in "${IMAGES[@]}"; do
    IFS=':' read -r local_image target_name <<< "$image_spec"
    acr_image="${ACR_LOGIN_SERVER}/${target_name}:${TAG}"
    
    echo -e "  Tagging: ${local_image} -> ${acr_image}"
    
    # Verificar se a imagem existe
    if ! docker images -q "$local_image" | grep -q .; then
        echo -e "  ${YELLOW}⚠ Imagem ${local_image} não encontrada. Pulando...${NC}"
        continue
    fi
    
    docker tag "$local_image" "$acr_image"
    echo -e "  ${GREEN}✓ Tag aplicada: ${acr_image}${NC}"
done

# 4. Push das imagens
echo ""
echo -e "${YELLOW}[4/5] Fazendo push das imagens para o ACR...${NC}"
for image_spec in "${IMAGES[@]}"; do
    IFS=':' read -r local_image target_name <<< "$image_spec"
    acr_image="${ACR_LOGIN_SERVER}/${target_name}:${TAG}"
    
    echo -e "  Pushing: ${acr_image}"
    docker push "$acr_image"
    echo -e "  ${GREEN}✓ Push concluído: ${acr_image}${NC}"
done

# 5. Resumo
echo ""
echo -e "${YELLOW}[5/5] Resumo${NC}"
echo -e "${CYAN}========================================${NC}"
echo -e "${GREEN}✓ Imagens enviadas para o ACR:${NC}"
for image_spec in "${IMAGES[@]}"; do
    IFS=':' read -r local_image target_name <<< "$image_spec"
    acr_image="${ACR_LOGIN_SERVER}/${target_name}:${TAG}"
    echo -e "  - ${acr_image}"
done
echo ""
echo -e "${CYAN}Para usar as imagens no Azure:${NC}"
echo -e "  docker pull ${ACR_LOGIN_SERVER}/sdc-dev-app:${TAG}"
echo -e "${CYAN}========================================${NC}"

