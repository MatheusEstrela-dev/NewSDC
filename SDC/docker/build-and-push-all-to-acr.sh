#!/bin/bash
# ============================================================================
# SDC - Build e Push de TODAS as imagens para Azure Container Registry
# Garante que todas as imagens estejam na mesma rede bridge
# ============================================================================
# Uso: ./build-and-push-all-to-acr.sh -n apidover -t latest
# ============================================================================

set -e

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Valores padrão
ACR_NAME="apidover"
TAG="latest"
BUILD_APP=true
BUILD_JENKINS=true
PUSH_TO_ACR=true

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
        --no-app)
            BUILD_APP=false
            shift
            ;;
        --no-jenkins)
            BUILD_JENKINS=false
            shift
            ;;
        --no-push)
            PUSH_TO_ACR=false
            shift
            ;;
        *)
            echo -e "${RED}Uso: $0 -n <acr-name> [-t <tag>] [--no-app] [--no-jenkins] [--no-push]${NC}"
            echo "Exemplo: $0 -n apidover -t latest"
            exit 1
            ;;
    esac
done

ACR_LOGIN_SERVER="${ACR_NAME}.azurecr.io"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

echo -e "${CYAN}========================================${NC}"
echo -e "${CYAN}SDC - Build e Push para Azure ACR${NC}"
echo -e "${CYAN}========================================${NC}"
echo ""
echo -e "${BLUE}Configuração:${NC}"
echo -e "  ACR: ${ACR_LOGIN_SERVER}"
echo -e "  Tag: ${TAG}"
echo -e "  Build App: ${BUILD_APP}"
echo -e "  Build Jenkins: ${BUILD_JENKINS}"
echo -e "  Push to ACR: ${PUSH_TO_ACR}"
echo ""

# 1. Verificar Azure CLI
echo -e "${YELLOW}[1/6] Verificando Azure CLI...${NC}"
if ! command -v az &> /dev/null; then
    echo -e "${RED}✗ Azure CLI não encontrado${NC}"
    echo -e "${YELLOW}  Instale: https://docs.microsoft.com/cli/azure/install-azure-cli${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Azure CLI encontrado${NC}"

# 2. Login no Azure
echo ""
echo -e "${YELLOW}[2/6] Verificando login no Azure...${NC}"
if ! az account show &>/dev/null; then
    echo -e "${YELLOW}Fazendo login no Azure...${NC}"
    az login
else
    ACCOUNT=$(az account show --query name -o tsv)
    echo -e "${GREEN}✓ Autenticado como: ${ACCOUNT}${NC}"
fi

# 3. Login no ACR
echo ""
echo -e "${YELLOW}[3/6] Fazendo login no ACR: ${ACR_LOGIN_SERVER}${NC}"
if az acr login --name "$ACR_NAME" &>/dev/null; then
    echo -e "${GREEN}✓ Login no ACR realizado${NC}"
else
    echo -e "${RED}✗ Erro ao fazer login no ACR${NC}"
    echo -e "${YELLOW}  Verifique se o ACR existe: az acr list${NC}"
    exit 1
fi

# 4. Build da imagem App (Laravel)
if [ "$BUILD_APP" = true ]; then
    echo ""
    echo -e "${YELLOW}[4/6] Building imagem App (Laravel)...${NC}"
    echo -e "  Context: ${PROJECT_ROOT}"
    echo -e "  Dockerfile: docker/Dockerfile.dev"

    APP_IMAGE_LOCAL="sdc-dev-app:${TAG}"
    APP_IMAGE_ACR="${ACR_LOGIN_SERVER}/sdc-dev-app:${TAG}"

    cd "$PROJECT_ROOT"

    docker build \
        -f docker/Dockerfile.dev \
        -t "${APP_IMAGE_LOCAL}" \
        -t "${APP_IMAGE_ACR}" \
        --build-arg UID=1000 \
        --build-arg GID=1000 \
        .

    echo -e "${GREEN}✓ Build App concluído${NC}"

    # Push App para ACR
    if [ "$PUSH_TO_ACR" = true ]; then
        echo -e "${YELLOW}  Fazendo push da imagem App...${NC}"
        docker push "${APP_IMAGE_ACR}"
        echo -e "${GREEN}✓ Push App concluído: ${APP_IMAGE_ACR}${NC}"
    fi
fi

# 5. Build da imagem Jenkins
if [ "$BUILD_JENKINS" = true ]; then
    echo ""
    echo -e "${YELLOW}[5/6] Building imagem Jenkins...${NC}"
    echo -e "  Context: ${SCRIPT_DIR}/jenkins"
    echo -e "  Dockerfile: Dockerfile.acr"

    JENKINS_IMAGE_LOCAL="sdc-dev-jenkins:${TAG}"
    JENKINS_IMAGE_ACR="${ACR_LOGIN_SERVER}/sdc-dev-jenkins:${TAG}"

    cd "$SCRIPT_DIR/jenkins"

    docker build \
        -f Dockerfile.acr \
        -t "${JENKINS_IMAGE_LOCAL}" \
        -t "${JENKINS_IMAGE_ACR}" \
        --build-arg DOCKER_GID=999 \
        .

    echo -e "${GREEN}✓ Build Jenkins concluído${NC}"

    # Push Jenkins para ACR
    if [ "$PUSH_TO_ACR" = true ]; then
        echo -e "${YELLOW}  Fazendo push da imagem Jenkins...${NC}"
        docker push "${JENKINS_IMAGE_ACR}"
        echo -e "${GREEN}✓ Push Jenkins concluído: ${JENKINS_IMAGE_ACR}${NC}"
    fi
fi

# 6. Verificar imagens no ACR
echo ""
echo -e "${YELLOW}[6/6] Verificando imagens no ACR...${NC}"

if [ "$PUSH_TO_ACR" = true ]; then
    echo -e "${BLUE}Imagens disponíveis no ACR:${NC}"

    if [ "$BUILD_APP" = true ]; then
        echo -e "${CYAN}  App:${NC}"
        az acr repository show-tags --name "$ACR_NAME" --repository sdc-dev-app --output table 2>/dev/null || echo -e "    ${YELLOW}(nenhuma tag encontrada)${NC}"
    fi

    if [ "$BUILD_JENKINS" = true ]; then
        echo -e "${CYAN}  Jenkins:${NC}"
        az acr repository show-tags --name "$ACR_NAME" --repository sdc-dev-jenkins --output table 2>/dev/null || echo -e "    ${YELLOW}(nenhuma tag encontrada)${NC}"
    fi
fi

# Resumo final
echo ""
echo -e "${CYAN}========================================${NC}"
echo -e "${GREEN}✅ Build e Push concluídos!${NC}"
echo -e "${CYAN}========================================${NC}"
echo ""

if [ "$BUILD_APP" = true ]; then
    echo -e "${BLUE}Imagem App:${NC}"
    echo -e "  ${ACR_LOGIN_SERVER}/sdc-dev-app:${TAG}"
    echo ""
fi

if [ "$BUILD_JENKINS" = true ]; then
    echo -e "${BLUE}Imagem Jenkins:${NC}"
    echo -e "  ${ACR_LOGIN_SERVER}/sdc-dev-jenkins:${TAG}"
    echo ""
fi

echo -e "${CYAN}Para usar as imagens:${NC}"
echo -e "  docker pull ${ACR_LOGIN_SERVER}/sdc-dev-app:${TAG}"
echo -e "  docker pull ${ACR_LOGIN_SERVER}/sdc-dev-jenkins:${TAG}"
echo ""
echo -e "${CYAN}Para atualizar docker-compose.yml:${NC}"
echo -e "  image: ${ACR_LOGIN_SERVER}/sdc-dev-app:${TAG}"
echo -e "  image: ${ACR_LOGIN_SERVER}/sdc-dev-jenkins:${TAG}"
echo ""
echo -e "${CYAN}========================================${NC}"




