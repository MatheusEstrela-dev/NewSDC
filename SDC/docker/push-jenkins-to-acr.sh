#!/bin/bash
# ============================================================================
# SDC - Script para Push da Imagem Jenkins para Azure Container Registry (ACR)
# ============================================================================
# Uso: ./docker/push-jenkins-to-acr.sh -n "apidover" -t "latest" --build
# ============================================================================

set -e

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
GRAY='\033[0;37m'
NC='\033[0m' # No Color

# Valores padrão
ACR_NAME="apidover"
RESOURCE_GROUP=""
TAG="latest"
BUILD_FIRST=false
LOGIN_ONLY=false
IMAGE_NAME="sdc-jenkins"
DOCKERFILE_PATH="./jenkins/Dockerfile.acr"

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
        -b|--build)
            BUILD_FIRST=true
            shift
            ;;
        --login-only)
            LOGIN_ONLY=true
            shift
            ;;
        -h|--help)
            echo "Uso: $0 [opções]"
            echo ""
            echo "Opções:"
            echo "  -n, --name NAME           Nome do ACR (padrão: apidover)"
            echo "  -g, --resource-group RG   Resource group do ACR"
            echo "  -t, --tag TAG             Tag da imagem (padrão: latest)"
            echo "  -b, --build               Build da imagem antes do push"
            echo "  --login-only              Apenas fazer login no ACR"
            echo "  -h, --help                Mostrar esta ajuda"
            exit 0
            ;;
        *)
            echo -e "${RED}Opção desconhecida: $1${NC}"
            echo "Use -h ou --help para ver as opções disponíveis"
            exit 1
            ;;
    esac
done

ACR_LOGIN_SERVER="${ACR_NAME}.azurecr.io"
LOCAL_IMAGE="${IMAGE_NAME}:${TAG}"
ACR_IMAGE="${ACR_LOGIN_SERVER}/${IMAGE_NAME}:${TAG}"
ACR_IMAGE_LATEST="${ACR_LOGIN_SERVER}/${IMAGE_NAME}:latest"

echo -e "${CYAN}========================================${NC}"
echo -e "${CYAN}SDC - Push Jenkins para Azure ACR${NC}"
echo -e "${CYAN}========================================${NC}"
echo ""
echo -e "ACR: ${ACR_LOGIN_SERVER}"
echo -e "Imagem: ${IMAGE_NAME}"
echo -e "Tag: ${TAG}"
echo ""

# 1. Login no Azure
echo -e "${YELLOW}[1/6] Verificando login no Azure...${NC}"
if ! az account show &>/dev/null; then
    echo -e "${YELLOW}Fazendo login no Azure...${NC}"
    az login
else
    echo -e "${GREEN}✓ Já autenticado no Azure${NC}"
    ACCOUNT_NAME=$(az account show --query user.name -o tsv)
    SUBSCRIPTION_NAME=$(az account show --query name -o tsv)
    echo -e "${GRAY}  Conta: ${ACCOUNT_NAME}${NC}"
    echo -e "${GRAY}  Subscription: ${SUBSCRIPTION_NAME}${NC}"
fi

# 2. Login no ACR
echo ""
echo -e "${YELLOW}[2/6] Fazendo login no ACR: ${ACR_LOGIN_SERVER}${NC}"
if [ -n "$RESOURCE_GROUP" ]; then
    if ! az acr login --name "$ACR_NAME" --resource-group "$RESOURCE_GROUP"; then
        echo -e "${RED}✗ Erro ao fazer login no ACR${NC}"
        echo -e "${YELLOW}  Dica: az acr list --output table${NC}"
        exit 1
    fi
else
    if ! az acr login --name "$ACR_NAME"; then
        echo -e "${RED}✗ Erro ao fazer login no ACR${NC}"
        echo -e "${YELLOW}  Dica: az acr list --output table${NC}"
        exit 1
    fi
fi
echo -e "${GREEN}✓ Login no ACR realizado com sucesso${NC}"

if [ "$LOGIN_ONLY" = true ]; then
    echo ""
    echo -e "${GREEN}✓ Login realizado. Execute o script novamente sem --login-only para fazer push.${NC}"
    exit 0
fi

# 3. Build da imagem (se solicitado)
if [ "$BUILD_FIRST" = true ]; then
    echo ""
    echo -e "${YELLOW}[3/6] Building imagem Jenkins...${NC}"
    echo -e "${GRAY}  Dockerfile: ${DOCKERFILE_PATH}${NC}"

    if [ ! -f "$DOCKERFILE_PATH" ]; then
        echo -e "${RED}✗ Dockerfile não encontrado: ${DOCKERFILE_PATH}${NC}"
        exit 1
    fi

    pushd ./jenkins > /dev/null
    if docker build -f Dockerfile.acr -t "${LOCAL_IMAGE}" .; then
        echo -e "${GREEN}✓ Build concluído: ${LOCAL_IMAGE}${NC}"
    else
        echo -e "${RED}✗ Erro no build da imagem${NC}"
        exit 1
    fi
    popd > /dev/null
else
    echo ""
    echo -e "${YELLOW}[3/6] Pulando build (use --build para buildar)${NC}"

    # Verificar se a imagem existe
    if ! docker images -q "$LOCAL_IMAGE" | grep -q .; then
        echo -e "${RED}✗ Imagem ${LOCAL_IMAGE} não encontrada localmente.${NC}"
        echo -e "${YELLOW}  Use --build para buildar a imagem${NC}"
        exit 1
    fi
    echo -e "${GREEN}✓ Imagem encontrada localmente: ${LOCAL_IMAGE}${NC}"
fi

# 4. Tag para ACR
echo ""
echo -e "${YELLOW}[4/6] Aplicando tags ACR...${NC}"

# Tag com versão específica
if docker tag "$LOCAL_IMAGE" "$ACR_IMAGE"; then
    echo -e "${GREEN}✓ Tag aplicada: ${ACR_IMAGE}${NC}"
else
    echo -e "${RED}✗ Erro ao aplicar tag${NC}"
    exit 1
fi

# Tag como latest (se não for latest)
if [ "$TAG" != "latest" ]; then
    if docker tag "$LOCAL_IMAGE" "$ACR_IMAGE_LATEST"; then
        echo -e "${GREEN}✓ Tag aplicada: ${ACR_IMAGE_LATEST}${NC}"
    else
        echo -e "${YELLOW}⚠ Aviso: Erro ao aplicar tag latest${NC}"
    fi
fi

# 5. Push para ACR
echo ""
echo -e "${YELLOW}[5/6] Fazendo push para o ACR...${NC}"

# Push tag específica
echo -e "${GRAY}  Pushing: ${ACR_IMAGE}${NC}"
if docker push "$ACR_IMAGE"; then
    echo -e "${GREEN}✓ Push concluído: ${ACR_IMAGE}${NC}"
else
    echo -e "${RED}✗ Erro ao fazer push${NC}"
    exit 1
fi

# Push latest
if [ "$TAG" != "latest" ]; then
    echo -e "${GRAY}  Pushing: ${ACR_IMAGE_LATEST}${NC}"
    if docker push "$ACR_IMAGE_LATEST"; then
        echo -e "${GREEN}✓ Push concluído: ${ACR_IMAGE_LATEST}${NC}"
    else
        echo -e "${YELLOW}⚠ Aviso: Erro ao fazer push da tag latest${NC}"
    fi
fi

# 6. Verificar no ACR
echo ""
echo -e "${YELLOW}[6/6] Verificando imagens no ACR...${NC}"
if TAGS=$(az acr repository show-tags --name "$ACR_NAME" --repository "$IMAGE_NAME" --output json 2>/dev/null); then
    echo -e "${GREEN}✓ Tags disponíveis no ACR:${NC}"
    echo "$TAGS" | jq -r '.[]' | while read -r t; do
        echo -e "  - ${ACR_LOGIN_SERVER}/${IMAGE_NAME}:${t}"
    done
else
    echo -e "${YELLOW}⚠ Não foi possível listar tags do ACR${NC}"
fi

# Resumo
echo ""
echo -e "${CYAN}========================================${NC}"
echo -e "${GREEN}✓ PUSH CONCLUÍDO COM SUCESSO!${NC}"
echo -e "${CYAN}========================================${NC}"
echo ""
echo -e "Imagem disponível em:"
echo -e "${CYAN}  ${ACR_IMAGE}${NC}"
if [ "$TAG" != "latest" ]; then
    echo -e "${CYAN}  ${ACR_IMAGE_LATEST}${NC}"
fi
echo ""
echo -e "${YELLOW}Para usar no Azure Container Instances:${NC}"
echo -e "${GRAY}  az container create --image ${ACR_IMAGE} ...${NC}"
echo ""
echo -e "${YELLOW}Para usar no Azure Kubernetes Service:${NC}"
echo -e "${GRAY}  kubectl set image deployment/jenkins jenkins=${ACR_IMAGE}${NC}"
echo ""
echo -e "${YELLOW}Para pull local:${NC}"
echo -e "${GRAY}  docker pull ${ACR_IMAGE}${NC}"
echo -e "${CYAN}========================================${NC}"
