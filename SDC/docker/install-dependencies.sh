#!/bin/bash
set -e

echo "📦 Instalando dependências do projeto SDC..."
echo ""

# Cores para output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Função para executar comando no container
run_in_container() {
    local container=$1
    local command=$2
    echo -e "${BLUE}Executando no container ${container}:${NC} ${command}"
    docker exec -it ${container} sh -c "${command}"
}

# Verifica se os containers estão rodando
if ! docker ps | grep -q "sdc_app_dev"; then
    echo -e "${YELLOW}⚠️  Container sdc_app_dev não está rodando!${NC}"
    echo "Iniciando containers..."
    docker-compose -f docker-compose.dev.yml up -d
    echo "Aguardando containers iniciarem..."
    sleep 5
fi

echo -e "${GREEN}📦 Instalando dependências PHP (Composer)...${NC}"
run_in_container "sdc_app_dev" "composer install --no-interaction --prefer-dist --optimize-autoloader"

echo ""
echo -e "${GREEN}📦 Instalando dependências Node.js (NPM)...${NC}"
if docker ps | grep -q "sdc_node"; then
    run_in_container "sdc_node" "npm install"
else
    echo -e "${YELLOW}⚠️  Container sdc_node não está rodando. Iniciando...${NC}"
    docker-compose -f docker-compose.dev.yml up -d node
    sleep 3
    run_in_container "sdc_node" "npm install"
fi

echo ""
echo -e "${GREEN}✅ Todas as dependências foram instaladas com sucesso!${NC}"
echo ""
echo "Próximos passos:"
echo "1. Configure o arquivo .env se ainda não configurou"
echo "2. Execute as migrations: docker exec -it sdc_app_dev php artisan migrate"
echo "3. Acesse a aplicação em http://localhost"

