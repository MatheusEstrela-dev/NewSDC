#!/bin/bash
# ============================================================================
# SDC - Verificar se o CI/CD está funcionando corretamente
# ============================================================================
# Uso: ./verificar-cicd.sh
# ============================================================================

set -e

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${CYAN}========================================${NC}"
echo -e "${CYAN}SDC - Verificação de CI/CD${NC}"
echo -e "${CYAN}========================================${NC}"
echo ""

ERRORS=0
WARNINGS=0

# Função para verificar comando
check_command() {
    if command -v "$1" &> /dev/null; then
        echo -e "${GREEN}✓${NC} $1 encontrado"
        return 0
    else
        echo -e "${RED}✗${NC} $1 não encontrado"
        ((ERRORS++))
        return 1
    fi
}

# Função para verificar variável de ambiente
check_env() {
    if [ -n "${!1}" ]; then
        echo -e "${GREEN}✓${NC} $1 está configurado"
        return 0
    else
        echo -e "${YELLOW}⚠${NC} $1 não está configurado"
        ((WARNINGS++))
        return 1
    fi
}

# 1. Verificar ferramentas necessárias
echo -e "${BLUE}[1/8] Verificando ferramentas...${NC}"
check_command "docker"
check_command "docker-compose"
check_command "az"
check_command "git"
check_command "curl"
echo ""

# 2. Verificar login no Azure
echo -e "${BLUE}[2/8] Verificando autenticação Azure...${NC}"
if az account show &>/dev/null; then
    ACCOUNT=$(az account show --query name -o tsv)
    echo -e "${GREEN}✓${NC} Autenticado no Azure como: ${ACCOUNT}"
else
    echo -e "${RED}✗${NC} Não autenticado no Azure"
    echo -e "${YELLOW}  Execute: az login${NC}"
    ((ERRORS++))
fi
echo ""

# 3. Verificar login no ACR
echo -e "${BLUE}[3/8] Verificando acesso ao ACR...${NC}"
ACR_NAME="${ACR_NAME:-apidover}"
if az acr login --name "$ACR_NAME" &>/dev/null; then
    echo -e "${GREEN}✓${NC} Acesso ao ACR ${ACR_NAME}.azurecr.io"

    # Verificar se as imagens existem
    if az acr repository list --name "$ACR_NAME" --output tsv | grep -q "sdc-dev-app"; then
        echo -e "${GREEN}✓${NC} Imagem sdc-dev-app encontrada no ACR"
    else
        echo -e "${YELLOW}⚠${NC} Imagem sdc-dev-app não encontrada no ACR"
        ((WARNINGS++))
    fi

    if az acr repository list --name "$ACR_NAME" --output tsv | grep -q "sdc-dev-jenkins"; then
        echo -e "${GREEN}✓${NC} Imagem sdc-dev-jenkins encontrada no ACR"
    else
        echo -e "${YELLOW}⚠${NC} Imagem sdc-dev-jenkins não encontrada no ACR"
        ((WARNINGS++))
    fi
else
    echo -e "${RED}✗${NC} Não foi possível fazer login no ACR"
    ((ERRORS++))
fi
echo ""

# 4. Verificar App Service (se configurado)
echo -e "${BLUE}[4/8] Verificando Azure App Service...${NC}"
APP_SERVICE_NAME="${AZURE_APP_SERVICE_NAME:-}"
RESOURCE_GROUP="${AZURE_RESOURCE_GROUP:-}"

if [ -n "$APP_SERVICE_NAME" ] && [ -n "$RESOURCE_GROUP" ]; then
    if az webapp show --name "$APP_SERVICE_NAME" --resource-group "$RESOURCE_GROUP" &>/dev/null; then
        echo -e "${GREEN}✓${NC} App Service ${APP_SERVICE_NAME} encontrado"

        # Verificar status
        STATE=$(az webapp show --name "$APP_SERVICE_NAME" --resource-group "$RESOURCE_GROUP" --query state -o tsv)
        if [ "$STATE" = "Running" ]; then
            echo -e "${GREEN}✓${NC} App Service está rodando"

            # Verificar URL
            URL="https://${APP_SERVICE_NAME}.azurewebsites.net"
            if curl -f -s "${URL}/health" &>/dev/null; then
                echo -e "${GREEN}✓${NC} App Service respondendo em ${URL}"
            else
                echo -e "${YELLOW}⚠${NC} App Service não está respondendo corretamente"
                ((WARNINGS++))
            fi
        else
            echo -e "${YELLOW}⚠${NC} App Service está em estado: ${STATE}"
            ((WARNINGS++))
        fi
    else
        echo -e "${YELLOW}⚠${NC} App Service ${APP_SERVICE_NAME} não encontrado"
        ((WARNINGS++))
    fi
else
    echo -e "${YELLOW}⚠${NC} App Service não configurado (AZURE_APP_SERVICE_NAME e AZURE_RESOURCE_GROUP)"
    ((WARNINGS++))
fi
echo ""

# 5. Verificar Jenkins
echo -e "${BLUE}[5/8] Verificando Jenkins...${NC}"
JENKINS_URL="${JENKINS_URL:-http://localhost:8090}"

if curl -f -s "${JENKINS_URL}/login" &>/dev/null; then
    echo -e "${GREEN}✓${NC} Jenkins está acessível em ${JENKINS_URL}"

    # Verificar se o pipeline está configurado
    if [ -f "Jenkinsfile" ]; then
        echo -e "${GREEN}✓${NC} Jenkinsfile encontrado"
    else
        echo -e "${YELLOW}⚠${NC} Jenkinsfile não encontrado"
        ((WARNINGS++))
    fi
else
    echo -e "${YELLOW}⚠${NC} Jenkins não está acessível em ${JENKINS_URL}"
    echo -e "${YELLOW}  Verifique se o container está rodando: docker ps | grep jenkins${NC}"
    ((WARNINGS++))
fi
echo ""

# 6. Verificar Docker Compose
echo -e "${BLUE}[6/8] Verificando Docker Compose...${NC}"
if [ -f "docker/docker-compose.yml" ]; then
    echo -e "${GREEN}✓${NC} docker-compose.yml encontrado"

    # Verificar se os containers estão rodando
    if docker ps --format "{{.Names}}" | grep -q "sdc"; then
        echo -e "${GREEN}✓${NC} Containers SDC estão rodando"
        docker ps --format "table {{.Names}}\t{{.Status}}" --filter "name=sdc"
    else
        echo -e "${YELLOW}⚠${NC} Nenhum container SDC está rodando"
        ((WARNINGS++))
    fi
else
    echo -e "${RED}✗${NC} docker-compose.yml não encontrado"
    ((ERRORS++))
fi
echo ""

# 7. Verificar rede Docker
echo -e "${BLUE}[7/8] Verificando rede Docker...${NC}"
if docker network ls --format "{{.Name}}" | grep -q "sdc"; then
    echo -e "${GREEN}✓${NC} Rede SDC encontrada"

    NETWORK_NAME=$(docker network ls --format "{{.Name}}" | grep "sdc" | head -1)
    CONTAINERS=$(docker network inspect "$NETWORK_NAME" --format '{{range .Containers}}{{.Name}} {{end}}')
    echo -e "${CYAN}  Containers na rede: ${CONTAINERS}${NC}"
else
    echo -e "${YELLOW}⚠${NC} Rede SDC não encontrada"
    ((WARNINGS++))
fi
echo ""

# 8. Verificar variáveis de ambiente
echo -e "${BLUE}[8/8] Verificando variáveis de ambiente...${NC}"
check_env "ACR_NAME"
check_env "AZURE_APP_SERVICE_NAME"
check_env "AZURE_RESOURCE_GROUP"
check_env "AZURE_CLIENT_ID"
check_env "AZURE_CLIENT_SECRET"
check_env "AZURE_TENANT_ID"
echo ""

# Resumo
echo -e "${CYAN}========================================${NC}"
if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}✅ CI/CD está configurado corretamente!${NC}"
    exit 0
elif [ $ERRORS -eq 0 ]; then
    echo -e "${YELLOW}⚠ CI/CD está funcionando com ${WARNINGS} avisos${NC}"
    exit 0
else
    echo -e "${RED}✗ CI/CD tem ${ERRORS} erro(s) e ${WARNINGS} aviso(s)${NC}"
    echo ""
    echo -e "${CYAN}Próximos passos:${NC}"
    echo -e "  1. Corrija os erros acima"
    echo -e "  2. Configure as variáveis de ambiente necessárias"
    echo -e "  3. Execute este script novamente"
    exit 1
fi
echo -e "${CYAN}========================================${NC}"




