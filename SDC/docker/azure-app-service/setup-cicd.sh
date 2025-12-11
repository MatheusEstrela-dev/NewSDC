#!/bin/bash
# ============================================================================
# SDC - Setup completo do CI/CD
# ============================================================================
# Este script automatiza a configuração do CI/CD
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
echo -e "${CYAN}SDC - Setup CI/CD Automático${NC}"
echo -e "${CYAN}========================================${NC}"
echo ""

# Verificar se está no diretório correto
if [ ! -f "../../docker-compose.jenkins-dev.yml" ]; then
    echo -e "${RED}✗ Execute este script de: SDC/docker/azure-app-service/${NC}"
    exit 1
fi

# 1. Verificar se .env.jenkins existe
echo -e "${BLUE}[1/6] Verificando arquivo de credenciais...${NC}"
if [ -f "../../.env.jenkins" ]; then
    echo -e "${GREEN}✓${NC} Arquivo .env.jenkins encontrado"

    # Verificar se as credenciais estão preenchidas
    if grep -q "your-service-principal-app-id" "../../.env.jenkins"; then
        echo -e "${YELLOW}⚠${NC} Credenciais ainda não foram preenchidas"
        echo -e "${YELLOW}  Edite o arquivo: SDC/docker/.env.jenkins${NC}"
        echo -e "${YELLOW}  Use .env.jenkins.example como referência${NC}"
        exit 1
    fi

    echo -e "${GREEN}✓${NC} Credenciais parecem estar configuradas"
else
    echo -e "${YELLOW}⚠${NC} Arquivo .env.jenkins não encontrado"
    echo -e "${YELLOW}  Copie .env.jenkins.example para .env.jenkins${NC}"
    echo -e "${YELLOW}  cp SDC/docker/.env.jenkins.example SDC/docker/.env.jenkins${NC}"
    exit 1
fi
echo ""

# 2. Verificar autenticação Azure
echo -e "${BLUE}[2/6] Verificando autenticação Azure...${NC}"
if az account show &>/dev/null; then
    ACCOUNT=$(az account show --query name -o tsv)
    echo -e "${GREEN}✓${NC} Autenticado no Azure: ${ACCOUNT}"
else
    echo -e "${RED}✗${NC} Não autenticado no Azure"
    echo -e "${YELLOW}  Execute: az login${NC}"
    exit 1
fi
echo ""

# 3. Verificar ACR
echo -e "${BLUE}[3/6] Verificando acesso ao ACR...${NC}"
ACR_NAME="${ACR_NAME:-apidover}"
if az acr show --name "$ACR_NAME" &>/dev/null; then
    echo -e "${GREEN}✓${NC} ACR ${ACR_NAME} encontrado"

    # Testar login
    if az acr login --name "$ACR_NAME" &>/dev/null; then
        echo -e "${GREEN}✓${NC} Login no ACR realizado com sucesso"
    else
        echo -e "${RED}✗${NC} Falha no login do ACR"
        exit 1
    fi
else
    echo -e "${RED}✗${NC} ACR ${ACR_NAME} não encontrado"
    exit 1
fi
echo ""

# 4. Criar rede Docker se não existir
echo -e "${BLUE}[4/6] Verificando rede Docker...${NC}"
if docker network ls --format "{{.Name}}" | grep -q "sdc-dev_sdc_network"; then
    echo -e "${GREEN}✓${NC} Rede sdc-dev_sdc_network já existe"
else
    echo -e "${YELLOW}⚠${NC} Criando rede sdc-dev_sdc_network"
    docker network create sdc-dev_sdc_network
    echo -e "${GREEN}✓${NC} Rede criada"
fi
echo ""

# 5. Iniciar Jenkins
echo -e "${BLUE}[5/6] Iniciando Jenkins...${NC}"
cd ../..
if docker ps --format "{{.Names}}" | grep -q "sdc_jenkins_dev"; then
    echo -e "${YELLOW}⚠${NC} Jenkins já está rodando"
    echo -e "${CYAN}  Reiniciando...${NC}"
    docker-compose -f docker-compose.jenkins-dev.yml --env-file .env.jenkins restart
else
    echo -e "${CYAN}  Subindo container...${NC}"
    docker-compose -f docker-compose.jenkins-dev.yml --env-file .env.jenkins up -d
fi

echo -e "${CYAN}  Aguardando Jenkins iniciar...${NC}"
RETRIES=30
COUNT=0
until curl -f -s http://localhost:8080/login > /dev/null 2>&1; do
    sleep 2
    COUNT=$((COUNT+1))
    if [ $COUNT -ge $RETRIES ]; then
        echo -e "${RED}✗${NC} Timeout aguardando Jenkins"
        exit 1
    fi
    echo -n "."
done
echo ""
echo -e "${GREEN}✓${NC} Jenkins está rodando"
echo ""

# 6. Informações de acesso
echo -e "${BLUE}[6/6] Configuração concluída!${NC}"
echo ""
echo -e "${CYAN}========================================${NC}"
echo -e "${GREEN}✅ CI/CD Setup Completo!${NC}"
echo -e "${CYAN}========================================${NC}"
echo ""
echo -e "${CYAN}Acesso ao Jenkins:${NC}"
echo -e "  URL: ${GREEN}http://localhost:8080${NC}"
echo -e "  Usuário: ${GREEN}admin${NC}"
echo -e "  Senha: ${GREEN}admin123${NC}"
echo ""
echo -e "${CYAN}Próximos passos:${NC}"
echo -e "  1. Acesse Jenkins e verifique as credenciais"
echo -e "  2. Configure o webhook no GitHub:"
echo -e "     ${YELLOW}https://github.com/SEU_USUARIO/New_SDC/settings/hooks${NC}"
echo -e "  3. Use a URL: ${GREEN}http://localhost:8080/github-webhook/${NC}"
echo -e "     ${YELLOW}(ou use ngrok se Jenkins for local)${NC}"
echo -e "  4. Faça um commit de teste"
echo ""
echo -e "${CYAN}Para expor Jenkins com ngrok:${NC}"
echo -e "  ${YELLOW}ngrok http 8080${NC}"
echo ""
echo -e "${CYAN}Documentação:${NC}"
echo -e "  - GUIA_CONFIGURACAO_WEBHOOK.md"
echo -e "  - Doc/SETUP_CI_CD_RESUMO.md"
echo -e "  - Doc/GITHUB_WEBHOOK_JENKINS.md"
echo ""
echo -e "${CYAN}========================================${NC}"
