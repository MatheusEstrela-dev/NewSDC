#!/bin/bash
# ============================================================================
# SDC - Build com Mock Data + Push para Azure Container Registry
# Constrói a imagem de produção com seeders inclusos e faz push ao ACR
# ============================================================================
# Uso: ./build-seed-push-acr.sh [-n apidover] [-t latest] [--no-push]
# ============================================================================

set -e

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BLUE='\033[0;34m'
NC='\033[0m'

# Valores padrão
ACR_NAME="apidover"
TAG="latest"
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
        --no-push)
            PUSH_TO_ACR=false
            shift
            ;;
        -h|--help)
            echo "Uso: $0 [-n <acr-name>] [-t <tag>] [--no-push]"
            echo ""
            echo "Opções:"
            echo "  -n, --name    Nome do ACR (padrão: apidover)"
            echo "  -t, --tag     Tag da imagem (padrão: latest)"
            echo "  --no-push     Apenas build, sem push ao ACR"
            echo ""
            echo "O que faz:"
            echo "  1. Builda a imagem de produção (Dockerfile.prod)"
            echo "  2. A imagem inclui seeders com dados mock:"
            echo "     - 7 roles (super-admin a user)"
            echo "     - 8 órgãos (CEDEC, REDECs, COMPDECs)"
            echo "     - 30+ usuários com hierarquias diversas"
            echo "     - 15 RATs com status variados"
            echo "  3. Faz push ao Azure Container Registry"
            echo ""
            echo "Ao iniciar o container, o entrypoint.prod.sh executa:"
            echo "  php artisan migrate --force"
            echo "  php artisan db:seed --force --class=DatabaseSeeder"
            exit 0
            ;;
        *)
            echo -e "${RED}Argumento desconhecido: $1${NC}"
            echo "Use --help para ver as opções"
            exit 1
            ;;
    esac
done

ACR_LOGIN_SERVER="${ACR_NAME}.azurecr.io"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

echo -e "${CYAN}============================================${NC}"
echo -e "${CYAN}  SDC - Build + Mock Data + Push ACR${NC}"
echo -e "${CYAN}============================================${NC}"
echo ""
echo -e "${BLUE}Configuração:${NC}"
echo -e "  ACR:        ${ACR_LOGIN_SERVER}"
echo -e "  Tag:        ${TAG}"
echo -e "  Push:       ${PUSH_TO_ACR}"
echo -e "  Dockerfile: docker/Dockerfile.prod"
echo ""
echo -e "${BLUE}Mock Data incluído:${NC}"
echo -e "  Roles:     7 (super-admin, admin, manager, analyst, operator, viewer, user)"
echo -e "  Órgãos:    8 (1 CEDEC + 3 REDECs + 4 COMPDECs)"
echo -e "  Usuários:  30+ (todas as hierarquias, diversos órgãos)"
echo -e "  RATs:      15 (em_andamento, rascunho, finalizado)"
echo ""

# 1. Verificar Azure CLI
echo -e "${YELLOW}[1/5] Verificando Azure CLI...${NC}"
if ! command -v az &> /dev/null; then
    echo -e "${RED}Azure CLI não encontrado${NC}"
    echo -e "${YELLOW}  Instale: https://docs.microsoft.com/cli/azure/install-azure-cli${NC}"
    exit 1
fi
echo -e "${GREEN}  Azure CLI encontrado${NC}"

# 2. Login no Azure e ACR
echo ""
echo -e "${YELLOW}[2/5] Autenticando no Azure e ACR...${NC}"
if ! az account show &>/dev/null; then
    az login
fi
ACCOUNT=$(az account show --query name -o tsv)
echo -e "${GREEN}  Conta: ${ACCOUNT}${NC}"

if [ "$PUSH_TO_ACR" = true ]; then
    az acr login --name "$ACR_NAME" &>/dev/null || {
        echo -e "${RED}  Erro ao fazer login no ACR: ${ACR_NAME}${NC}"
        exit 1
    }
    echo -e "${GREEN}  ACR: login OK${NC}"
fi

# 3. Build da imagem de produção
echo ""
echo -e "${YELLOW}[3/5] Building imagem de produção...${NC}"
echo -e "  Context: ${PROJECT_ROOT}"

IMAGE_LOCAL="sdc-app:${TAG}"
IMAGE_ACR="${ACR_LOGIN_SERVER}/sdc-app:${TAG}"
IMAGE_ACR_DATED="${ACR_LOGIN_SERVER}/sdc-app:$(date +%Y%m%d-%H%M%S)"

cd "$PROJECT_ROOT"

docker build \
    -f docker/Dockerfile.prod \
    -t "${IMAGE_LOCAL}" \
    -t "${IMAGE_ACR}" \
    -t "${IMAGE_ACR_DATED}" \
    .

echo -e "${GREEN}  Build concluído${NC}"

# 4. Push ao ACR
if [ "$PUSH_TO_ACR" = true ]; then
    echo ""
    echo -e "${YELLOW}[4/5] Fazendo push ao ACR...${NC}"
    docker push "${IMAGE_ACR}"
    echo -e "${GREEN}  Push: ${IMAGE_ACR}${NC}"
    docker push "${IMAGE_ACR_DATED}"
    echo -e "${GREEN}  Push: ${IMAGE_ACR_DATED}${NC}"
else
    echo ""
    echo -e "${YELLOW}[4/5] Push desabilitado (--no-push)${NC}"
fi

# 5. Verificar
echo ""
echo -e "${YELLOW}[5/5] Verificando...${NC}"
if [ "$PUSH_TO_ACR" = true ]; then
    echo -e "${BLUE}Tags no ACR (sdc-app):${NC}"
    az acr repository show-tags --name "$ACR_NAME" --repository sdc-app --output table 2>/dev/null || \
        echo -e "  ${YELLOW}(repositório não encontrado - primeiro push?)${NC}"
fi

# Resumo
echo ""
echo -e "${CYAN}============================================${NC}"
echo -e "${GREEN}  Build e Push concluídos!${NC}"
echo -e "${CYAN}============================================${NC}"
echo ""
echo -e "${BLUE}Imagem:${NC} ${IMAGE_ACR}"
echo ""
echo -e "${BLUE}Para usar:${NC}"
echo -e "  docker pull ${IMAGE_ACR}"
echo -e "  docker run -p 8000:8000 --env-file .env ${IMAGE_ACR}"
echo ""
echo -e "${BLUE}O container inicia com:${NC}"
echo -e "  1. Migrations automáticas"
echo -e "  2. Seeders com dados mock (idempotentes)"
echo -e "  3. Admin: admin@defesa.mg.gov.br / password"
echo ""
echo -e "${CYAN}============================================${NC}"
