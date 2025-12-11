#!/bin/bash
# ============================================================================
# SDC - Configurar Webhook do GitHub para CI/CD Automático
# ============================================================================
# Uso: ./configurar-webhook-github.sh -r <repo> -t <token> -j <jenkins-url>
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
GITHUB_REPO=""
GITHUB_TOKEN=""
JENKINS_URL="http://localhost:8090"
WEBHOOK_SECRET=""

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -r|--repo)
            GITHUB_REPO="$2"
            shift 2
            ;;
        -t|--token)
            GITHUB_TOKEN="$2"
            shift 2
            ;;
        -j|--jenkins-url)
            JENKINS_URL="$2"
            shift 2
            ;;
        -s|--secret)
            WEBHOOK_SECRET="$2"
            shift 2
            ;;
        *)
            echo -e "${RED}Uso: $0 -r <owner/repo> -t <github-token> [-j <jenkins-url>] [-s <webhook-secret>]${NC}"
            echo ""
            echo "Exemplo:"
            echo "  $0 -r seu-usuario/New_SDC -t ghp_xxxxxxxxxxxx -j http://localhost:8090"
            exit 1
            ;;
    esac
done

if [ -z "$GITHUB_REPO" ] || [ -z "$GITHUB_TOKEN" ]; then
    echo -e "${RED}Erro: Repositório GitHub e Token são obrigatórios${NC}"
    echo ""
    echo "Para criar um token:"
    echo "  1. GitHub → Settings → Developer settings → Personal access tokens"
    echo "  2. Generate new token (classic)"
    echo "  3. Selecione: repo, admin:repo_hook"
    exit 1
fi

# Gerar secret se não fornecido
if [ -z "$WEBHOOK_SECRET" ]; then
    WEBHOOK_SECRET=$(openssl rand -hex 32 2>/dev/null || head -c 32 /dev/urandom | xxd -p -c 32)
    echo -e "${YELLOW}⚠ Secret gerado automaticamente. Guarde este valor:${NC}"
    echo -e "${CYAN}${WEBHOOK_SECRET}${NC}"
    echo ""
fi

WEBHOOK_URL="${JENKINS_URL}/github-webhook/"

echo -e "${CYAN}========================================${NC}"
echo -e "${CYAN}SDC - Configurar Webhook GitHub${NC}"
echo -e "${CYAN}========================================${NC}"
echo ""
echo -e "${BLUE}Configuração:${NC}"
echo -e "  Repositório: ${GITHUB_REPO}"
echo -e "  Jenkins URL: ${JENKINS_URL}"
echo -e "  Webhook URL: ${WEBHOOK_URL}"
echo ""

# Verificar se curl está disponível
if ! command -v curl &> /dev/null; then
    echo -e "${RED}✗ curl não encontrado${NC}"
    exit 1
fi

# Verificar se jq está disponível (opcional, mas recomendado)
if ! command -v jq &> /dev/null; then
    echo -e "${YELLOW}⚠ jq não encontrado. Instale para melhor formatação de JSON${NC}"
    JQ_CMD="cat"
else
    JQ_CMD="jq"
fi

# Verificar se Jenkins está acessível
echo -e "${YELLOW}[1/4] Verificando acesso ao Jenkins...${NC}"
if curl -f -s "${JENKINS_URL}/login" &>/dev/null; then
    echo -e "${GREEN}✓ Jenkins está acessível${NC}"
else
    echo -e "${RED}✗ Jenkins não está acessível em ${JENKINS_URL}${NC}"
    echo -e "${YELLOW}  Verifique se o container está rodando: docker ps | grep jenkins${NC}"
    exit 1
fi

# Verificar se o webhook já existe
echo ""
echo -e "${YELLOW}[2/4] Verificando webhooks existentes...${NC}"
EXISTING_WEBHOOKS=$(curl -s -H "Authorization: token ${GITHUB_TOKEN}" \
    "https://api.github.com/repos/${GITHUB_REPO}/hooks" | $JQ_CMD -r '.[] | select(.config.url | contains("github-webhook")) | .id' || echo "")

if [ -n "$EXISTING_WEBHOOKS" ]; then
    echo -e "${YELLOW}⚠ Webhook já existe. Removendo webhooks antigos...${NC}"
    for hook_id in $EXISTING_WEBHOOKS; do
        curl -s -X DELETE -H "Authorization: token ${GITHUB_TOKEN}" \
            "https://api.github.com/repos/${GITHUB_REPO}/hooks/${hook_id}" > /dev/null
        echo -e "${GREEN}✓ Webhook ${hook_id} removido${NC}"
    done
fi

# Criar novo webhook
echo ""
echo -e "${YELLOW}[3/4] Criando webhook no GitHub...${NC}"

WEBHOOK_PAYLOAD=$(cat <<EOF
{
  "name": "web",
  "active": true,
  "events": ["push", "pull_request"],
  "config": {
    "url": "${WEBHOOK_URL}",
    "content_type": "application/json",
    "insecure_ssl": "0",
    "secret": "${WEBHOOK_SECRET}"
  }
}
EOF
)

RESPONSE=$(curl -s -w "\n%{http_code}" -X POST \
    -H "Authorization: token ${GITHUB_TOKEN}" \
    -H "Accept: application/vnd.github.v3+json" \
    -H "Content-Type: application/json" \
    -d "${WEBHOOK_PAYLOAD}" \
    "https://api.github.com/repos/${GITHUB_REPO}/hooks")

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

if [ "$HTTP_CODE" = "201" ]; then
    WEBHOOK_ID=$(echo "$BODY" | $JQ_CMD -r '.id // empty')
    echo -e "${GREEN}✓ Webhook criado com sucesso!${NC}"
    echo -e "${CYAN}  Webhook ID: ${WEBHOOK_ID}${NC}"
elif [ "$HTTP_CODE" = "422" ]; then
    echo -e "${YELLOW}⚠ Webhook pode já existir ou URL inválida${NC}"
    echo -e "${YELLOW}  Verifique manualmente no GitHub${NC}"
else
    echo -e "${RED}✗ Erro ao criar webhook (HTTP ${HTTP_CODE})${NC}"
    echo -e "${RED}  Resposta: ${BODY}${NC}"
    exit 1
fi

# Testar webhook
echo ""
echo -e "${YELLOW}[4/4] Testando webhook...${NC}"
if [ -n "$WEBHOOK_ID" ]; then
    TEST_RESPONSE=$(curl -s -w "\n%{http_code}" -X POST \
        -H "Authorization: token ${GITHUB_TOKEN}" \
        -H "Accept: application/vnd.github.v3+json" \
        "https://api.github.com/repos/${GITHUB_REPO}/hooks/${WEBHOOK_ID}/tests")
    
    TEST_HTTP_CODE=$(echo "$TEST_RESPONSE" | tail -n1)
    
    if [ "$TEST_HTTP_CODE" = "204" ]; then
        echo -e "${GREEN}✓ Webhook testado com sucesso!${NC}"
    else
        echo -e "${YELLOW}⚠ Não foi possível testar o webhook automaticamente${NC}"
        echo -e "${YELLOW}  Teste manualmente fazendo um push no GitHub${NC}"
    fi
fi

# Resumo
echo ""
echo -e "${CYAN}========================================${NC}"
echo -e "${GREEN}✅ Webhook configurado com sucesso!${NC}"
echo -e "${CYAN}========================================${NC}"
echo ""
echo -e "${BLUE}Informações importantes:${NC}"
echo -e "  Webhook URL: ${WEBHOOK_URL}"
echo -e "  Secret: ${WEBHOOK_SECRET}"
echo ""
echo -e "${CYAN}Próximos passos:${NC}"
echo -e "  1. Configure o secret no Jenkins:"
echo -e "     Manage Jenkins → Configure System → GitHub → Advanced"
echo -e "     Shared secret: ${WEBHOOK_SECRET}"
echo ""
echo -e "  2. No Jenkins, configure o job:"
echo -e "     Build Triggers → ✅ GitHub hook trigger for GITScm polling"
echo ""
echo -e "  3. Teste fazendo um push:"
echo -e "     git commit --allow-empty -m 'test: Trigger CI/CD'"
echo -e "     git push origin main"
echo ""
echo -e "  4. Verifique no Jenkins se o build iniciou automaticamente"
echo ""
echo -e "${CYAN}========================================${NC}"




