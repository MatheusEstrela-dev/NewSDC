#!/bin/bash
# ============================================================================
# SDC - Corrigir APP_KEY e Redis no App Service (Solução Rápida)
# ============================================================================
# Uso: ./corrigir-app-key.sh
# ============================================================================

set -e

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

# Variáveis
APP_SERVICE_NAME="${1:-newsdc2027}"
RESOURCE_GROUP="${2:-DEFESA_CIVIL}"

echo ""
echo -e "${CYAN}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║        🔧 Corrigir APP_KEY e Configurações do App Service    ║${NC}"
echo -e "${CYAN}╚══════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Verificar se está logado no Azure
echo -e "${YELLOW}[1/4] Verificando login no Azure...${NC}"
if ! az account show &>/dev/null; then
    echo -e "${YELLOW}⚠️  Não autenticado. Fazendo login...${NC}"
    az login
else
    ACCOUNT=$(az account show --query user.name -o tsv)
    echo -e "${GREEN}✓ Autenticado como: ${ACCOUNT}${NC}"
fi

# Gerar APP_KEY
echo ""
echo -e "${YELLOW}[2/4] Gerando APP_KEY...${NC}"
if command -v php &> /dev/null; then
    APP_KEY=$(php -r "echo 'base64:' . base64_encode(random_bytes(32));")
    echo -e "${GREEN}✓ APP_KEY gerada usando PHP${NC}"
else
    # Fallback: usar openssl
    APP_KEY="base64:$(openssl rand -base64 32)"
    echo -e "${GREEN}✓ APP_KEY gerada usando OpenSSL${NC}"
fi
echo -e "${GREEN}  APP_KEY: ${APP_KEY}${NC}"

# Configurar variáveis de ambiente
echo ""
echo -e "${YELLOW}[3/4] Configurando variáveis de ambiente no App Service...${NC}"

az webapp config appsettings set \
    --name "$APP_SERVICE_NAME" \
    --resource-group "$RESOURCE_GROUP" \
    --settings \
        APP_NAME=SDC \
        APP_ENV=production \
        APP_KEY="$APP_KEY" \
        APP_DEBUG=false \
        APP_URL="https://${APP_SERVICE_NAME}.azurewebsites.net" \
        LOG_CHANNEL=stack \
        LOG_LEVEL=error \
        CACHE_DRIVER=file \
        SESSION_DRIVER=file \
        QUEUE_CONNECTION=sync \
        WEBSITES_PORT=8000 \
        WEBSITES_ENABLE_APP_SERVICE_STORAGE=false \
    --output none

echo -e "${GREEN}✓ Variáveis de ambiente configuradas${NC}"

# Reiniciar App Service
echo ""
echo -e "${YELLOW}[4/4] Reiniciando App Service...${NC}"
az webapp restart \
    --name "$APP_SERVICE_NAME" \
    --resource-group "$RESOURCE_GROUP" \
    --output none

echo -e "${GREEN}✓ App Service reiniciado${NC}"

# Resumo
echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║        ✅ Configuração Concluída com Sucesso!               ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${CYAN}📋 Resumo das alterações:${NC}"
echo -e "  ✓ APP_KEY configurada e gerada automaticamente"
echo -e "  ✓ CACHE_DRIVER alterado para 'file' (Redis desabilitado)"
echo -e "  ✓ SESSION_DRIVER alterado para 'file'"
echo -e "  ✓ QUEUE_CONNECTION alterado para 'sync'"
echo -e "  ✓ App Service reiniciado"
echo ""
echo -e "${YELLOW}⏳ Aguarde ~30 segundos para o container reiniciar completamente${NC}"
echo ""
echo -e "${CYAN}🔍 Verificar logs:${NC}"
echo -e "   az webapp log tail --name ${APP_SERVICE_NAME} --resource-group ${RESOURCE_GROUP}"
echo ""
echo -e "${CYAN}🌐 Testar aplicação:${NC}"
echo -e "   https://${APP_SERVICE_NAME}.azurewebsites.net"
echo ""
echo -e "${YELLOW}⚠️  PRÓXIMOS PASSOS:${NC}"
echo -e "   1. Configure DB_HOST, DB_DATABASE, DB_USERNAME e DB_PASSWORD se usar MySQL"
echo -e "   2. Se tiver Redis, altere CACHE_DRIVER e SESSION_DRIVER para 'redis'"
echo -e "   3. Verifique os logs para confirmar que não há mais erros"
echo ""
echo -e "${CYAN}═══════════════════════════════════════════════════════════════${NC}"
echo ""




