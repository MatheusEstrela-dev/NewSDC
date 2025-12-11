#!/bin/bash
# ============================================================================
# SDC - Verificar Status do Pipeline Jenkins
# ============================================================================
# Uso: ./verificar-pipeline.sh
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
echo -e "${CYAN}SDC - Verificação do Pipeline Jenkins${NC}"
echo -e "${CYAN}========================================${NC}"
echo ""

JENKINS_URL="https://jenkinssdc.azurewebsites.net"
JOB_PATH="job/SDC/job/build-and-deploy"
APP_URL="https://newsdc2027.azurewebsites.net/login"

# 1. Verificar se Jenkins está acessível
echo -e "${BLUE}[1/6] Verificando acesso ao Jenkins...${NC}"
if curl -f -s "${JENKINS_URL}/login" &>/dev/null; then
    echo -e "${GREEN}✓${NC} Jenkins está acessível em ${JENKINS_URL}"
else
    echo -e "${RED}✗${NC} Jenkins não está acessível"
    exit 1
fi
echo ""

# 2. Verificar se o job existe
echo -e "${BLUE}[2/6] Verificando se o job existe...${NC}"
JOB_URL="${JENKINS_URL}/${JOB_PATH}"
if curl -f -s "${JOB_URL}" &>/dev/null; then
    echo -e "${GREEN}✓${NC} Job encontrado: ${JOB_PATH}"
else
    echo -e "${YELLOW}⚠${NC} Job não encontrado. Verifique se o caminho está correto."
    echo -e "${CYAN}  URL esperada: ${JOB_URL}${NC}"
fi
echo ""

# 3. Verificar último build
echo -e "${BLUE}[3/6] Verificando último build...${NC}"
LAST_BUILD_URL="${JOB_URL}/lastBuild/api/json"
if curl -f -s "${LAST_BUILD_URL}" &>/dev/null; then
    BUILD_INFO=$(curl -s "${LAST_BUILD_URL}")
    BUILD_NUMBER=$(echo "$BUILD_INFO" | grep -o '"number":[0-9]*' | head -1 | cut -d: -f2)
    BUILD_RESULT=$(echo "$BUILD_INFO" | grep -o '"result":"[^"]*"' | cut -d'"' -f4)
    BUILD_TIMESTAMP=$(echo "$BUILD_INFO" | grep -o '"timestamp":[0-9]*' | cut -d: -f2)
    
    if [ -n "$BUILD_NUMBER" ]; then
        echo -e "${CYAN}  Build #${BUILD_NUMBER}${NC}"
        
        if [ "$BUILD_RESULT" = "SUCCESS" ]; then
            echo -e "${GREEN}✓${NC} Status: SUCESSO"
        elif [ "$BUILD_RESULT" = "FAILURE" ]; then
            echo -e "${RED}✗${NC} Status: FALHOU"
            echo -e "${YELLOW}  Verifique os logs em: ${JOB_URL}/${BUILD_NUMBER}/console${NC}"
        elif [ "$BUILD_RESULT" = "null" ]; then
            echo -e "${YELLOW}⚠${NC} Status: EM EXECUÇÃO"
        else
            echo -e "${YELLOW}⚠${NC} Status: ${BUILD_RESULT}"
        fi
        
        if [ -n "$BUILD_TIMESTAMP" ]; then
            BUILD_DATE=$(date -d "@$((BUILD_TIMESTAMP / 1000))" 2>/dev/null || echo "N/A")
            echo -e "${CYAN}  Data: ${BUILD_DATE}${NC}"
        fi
    else
        echo -e "${YELLOW}⚠${NC} Nenhum build encontrado"
    fi
else
    echo -e "${YELLOW}⚠${NC} Não foi possível obter informações do build"
fi
echo ""

# 4. Verificar webhook do GitHub
echo -e "${BLUE}[4/6] Verificando webhook do GitHub...${NC}"
WEBHOOK_URL="${JENKINS_URL}/github-webhook/"
if curl -f -s "${WEBHOOK_URL}" &>/dev/null; then
    echo -e "${GREEN}✓${NC} Endpoint do webhook está acessível"
    echo -e "${CYAN}  URL: ${WEBHOOK_URL}${NC}"
    echo -e "${YELLOW}  ⚠ Configure no GitHub:${NC}"
    echo -e "${CYAN}     https://github.com/MatheusEstrela-dev/NewSDC/settings/hooks${NC}"
else
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "${WEBHOOK_URL}")
    if [ "$HTTP_CODE" = "405" ] || [ "$HTTP_CODE" = "403" ]; then
        echo -e "${GREEN}✓${NC} Endpoint do webhook existe (código HTTP: ${HTTP_CODE})"
    else
        echo -e "${YELLOW}⚠${NC} Endpoint do webhook retornou código HTTP: ${HTTP_CODE}"
    fi
fi
echo ""

# 5. Verificar App Service
echo -e "${BLUE}[5/6] Verificando App Service...${NC}"
if curl -f -s "${APP_URL}" &>/dev/null; then
    echo -e "${GREEN}✓${NC} App Service está respondendo"
    echo -e "${CYAN}  URL: ${APP_URL}${NC}"
    
    # Verificar se o texto está presente (busca simples)
    if curl -s "${APP_URL}" | grep -q "CI/CD Test - Deploy Automático"; then
        echo -e "${GREEN}✓${NC} Texto 'CI/CD Test - Deploy Automático' encontrado na página"
    else
        echo -e "${YELLOW}⚠${NC} Texto 'CI/CD Test - Deploy Automático' NÃO encontrado"
        echo -e "${CYAN}  Isso pode significar:${NC}"
        echo -e "${CYAN}    - Deploy ainda não aconteceu${NC}"
        echo -e "${CYAN}    - Build falhou antes do deploy${NC}"
        echo -e "${CYAN}    - Cache do navegador (tente Ctrl+F5)${NC}"
    fi
else
    echo -e "${RED}✗${NC} App Service não está respondendo"
fi
echo ""

# 6. Resumo e próximos passos
echo -e "${CYAN}========================================${NC}"
echo -e "${CYAN}Resumo${NC}"
echo -e "${CYAN}========================================${NC}"
echo ""
echo -e "${CYAN}URLs importantes:${NC}"
echo -e "  Jenkins: ${JENKINS_URL}"
echo -e "  Job: ${JOB_URL}"
echo -e "  App Service: ${APP_URL}"
echo -e "  Webhook: ${WEBHOOK_URL}"
echo ""
echo -e "${CYAN}Próximos passos:${NC}"
echo -e "  1. Verifique os logs do último build em: ${JOB_URL}/lastBuild/console"
echo -e "  2. Configure o webhook no GitHub se ainda não estiver configurado"
echo -e "  3. Execute um build manual se necessário: ${JOB_URL}/build?delay=0sec"
echo -e "  4. Aguarde o deploy completar (5-10 minutos)"
echo -e "  5. Verifique a tela de login: ${APP_URL}"
echo ""
echo -e "${CYAN}========================================${NC}"




