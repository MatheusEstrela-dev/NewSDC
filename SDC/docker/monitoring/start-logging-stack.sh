#!/bin/bash

###############################################################################
# Script para iniciar stack completa de monitoramento de logs
# - Promtail: Coleta logs dos containers Docker
# - Loki: Armazena e indexa logs
# - Grafana: Visualização e dashboards
###############################################################################

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

echo "========================================="
echo "🚀 Laravel Logging Stack Starter"
echo "========================================="
echo ""

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Função para imprimir mensagens coloridas
print_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Verifica se Docker está rodando
if ! docker info > /dev/null 2>&1; then
    print_error "Docker não está rodando. Por favor, inicie o Docker primeiro."
    exit 1
fi

print_info "Docker está rodando ✓"

# Verifica se Docker Compose está disponível
if ! command -v docker-compose &> /dev/null; then
    print_error "docker-compose não encontrado. Por favor, instale o Docker Compose."
    exit 1
fi

print_info "Docker Compose disponível ✓"

# Cria diretórios necessários
print_info "Criando diretórios necessários..."
mkdir -p "$SCRIPT_DIR/loki/data"
mkdir -p "$SCRIPT_DIR/grafana/data"
mkdir -p "$SCRIPT_DIR/promtail/data"

# Define permissões corretas
print_info "Configurando permissões..."
chmod -R 777 "$SCRIPT_DIR/loki/data" 2>/dev/null || true
chmod -R 777 "$SCRIPT_DIR/grafana/data" 2>/dev/null || true
chmod -R 777 "$SCRIPT_DIR/promtail/data" 2>/dev/null || true

# Verifica se arquivo de configuração do Promtail existe
if [ ! -f "$SCRIPT_DIR/promtail/promtail-config.yml" ]; then
    print_error "Arquivo promtail-config.yml não encontrado!"
    print_info "Esperado em: $SCRIPT_DIR/promtail/promtail-config.yml"
    exit 1
fi

print_info "Arquivo de configuração do Promtail encontrado ✓"

# Cria docker-compose.yml se não existir
if [ ! -f "$SCRIPT_DIR/docker-compose.logging.yml" ]; then
    print_info "Criando docker-compose.logging.yml..."
    cat > "$SCRIPT_DIR/docker-compose.logging.yml" << 'EOF'
version: '3.8'

services:
  loki:
    image: grafana/loki:latest
    container_name: loki
    ports:
      - "3100:3100"
    volumes:
      - ./loki/data:/loki
    command: -config.file=/etc/loki/local-config.yaml
    networks:
      - logging
    restart: unless-stopped

  promtail:
    image: grafana/promtail:latest
    container_name: promtail
    volumes:
      - /var/lib/docker/containers:/var/lib/docker/containers:ro
      - /var/run/docker.sock:/var/run/docker.sock
      - ./promtail/promtail-config.yml:/etc/promtail/config.yml:ro
      - ./promtail/data:/tmp
    command: -config.file=/etc/promtail/config.yml
    networks:
      - logging
    restart: unless-stopped
    depends_on:
      - loki

  grafana:
    image: grafana/grafana:latest
    container_name: grafana
    ports:
      - "3001:3000"
    volumes:
      - ./grafana/data:/var/lib/grafana
      - ./grafana/dashboards:/etc/grafana/provisioning/dashboards:ro
      - ./grafana/provisioning:/etc/grafana/provisioning:ro
    environment:
      - GF_SECURITY_ADMIN_USER=admin
      - GF_SECURITY_ADMIN_PASSWORD=admin
      - GF_USERS_ALLOW_SIGN_UP=false
      - GF_AUTH_ANONYMOUS_ENABLED=false
      - GF_INSTALL_PLUGINS=grafana-clock-panel,grafana-simple-json-datasource
    networks:
      - logging
    restart: unless-stopped
    depends_on:
      - loki

networks:
  logging:
    driver: bridge
EOF
    print_info "docker-compose.logging.yml criado ✓"
fi

# Cria configuração de datasource do Grafana
print_info "Configurando datasource do Grafana..."
mkdir -p "$SCRIPT_DIR/grafana/provisioning/datasources"
cat > "$SCRIPT_DIR/grafana/provisioning/datasources/loki.yml" << 'EOF'
apiVersion: 1

datasources:
  - name: Loki
    type: loki
    access: proxy
    url: http://loki:3100
    isDefault: true
    editable: true
EOF

# Cria configuração de dashboards do Grafana
mkdir -p "$SCRIPT_DIR/grafana/provisioning/dashboards"
cat > "$SCRIPT_DIR/grafana/provisioning/dashboards/default.yml" << 'EOF'
apiVersion: 1

providers:
  - name: 'Default'
    orgId: 1
    folder: 'Laravel'
    type: file
    disableDeletion: false
    updateIntervalSeconds: 10
    allowUiUpdates: true
    options:
      path: /etc/grafana/provisioning/dashboards
      foldersFromFilesStructure: true
EOF

print_info "Configurações do Grafana criadas ✓"

echo ""
print_info "Iniciando containers..."
echo ""

# Inicia os containers
cd "$SCRIPT_DIR"
docker-compose -f docker-compose.logging.yml up -d

echo ""
print_info "Aguardando containers iniciarem..."
sleep 5

# Verifica status dos containers
print_info "Status dos containers:"
docker-compose -f docker-compose.logging.yml ps

echo ""
echo "========================================="
echo -e "${GREEN}✅ Stack de Logging iniciada com sucesso!${NC}"
echo "========================================="
echo ""
echo "📊 Acesse os serviços:"
echo "  - Grafana:  http://localhost:3001"
echo "    Usuário: admin"
echo "    Senha: admin"
echo ""
echo "  - Loki API: http://localhost:3100"
echo ""
echo "🔍 Queries exemplo no Grafana:"
echo '  {app="laravel"} |= "error"'
echo '  {app="laravel"} | json | severity="critical"'
echo '  {app="laravel"} | json | event_name="Slow Query Detected"'
echo ""
echo "📚 Documentação completa:"
echo "  - $PROJECT_ROOT/LOGGING_SYSTEM.md"
echo "  - $PROJECT_ROOT/LOGGING_IMPROVEMENTS_SUMMARY.md"
echo ""
echo "⚙️  Para parar a stack:"
echo "  cd $SCRIPT_DIR"
echo "  docker-compose -f docker-compose.logging.yml down"
echo ""
echo "🔄 Para ver logs em tempo real:"
echo "  docker-compose -f docker-compose.logging.yml logs -f"
echo ""
