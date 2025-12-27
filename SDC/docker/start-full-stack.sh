#!/bin/bash
# ============================================================================
# Script para iniciar SDC completo: Aplicação + Monitoramento
# ============================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "============================================================================"
echo "SDC - Iniciando Stack Completo"
echo "============================================================================"
echo ""

# Cores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Função para print colorido
print_status() {
    echo -e "${GREEN}[✓]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[⚠]${NC} $1"
}

print_error() {
    echo -e "${RED}[✗]${NC} $1"
}

# 1. Parar tudo primeiro
print_warning "Parando containers existentes..."
docker compose down --remove-orphans 2>/dev/null || true
docker compose -f docker-compose.monitoring.yml down --remove-orphans 2>/dev/null || true
echo ""

# 2. Iniciar aplicação principal
print_status "Iniciando aplicação principal (SDC)..."
docker compose up -d
echo ""

# 3. Aguardar containers principais ficarem healthy
print_warning "Aguardando containers principais ficarem prontos..."
sleep 10

# Verificar se containers principais estão rodando
if ! docker compose ps | grep -q "newsdc_app"; then
    print_error "Erro: Container newsdc_app não está rodando!"
    exit 1
fi

print_status "Containers principais prontos!"
echo ""

# 4. Iniciar stack de monitoramento
print_status "Iniciando stack de monitoramento..."
docker compose -f docker-compose.monitoring.yml up -d
echo ""

# 5. Aguardar monitoramento ficar pronto
print_warning "Aguardando stack de monitoramento..."
sleep 15

echo ""
echo "============================================================================"
echo "✅ SDC Stack Completo INICIADO!"
echo "============================================================================"
echo ""
echo "📋 APLICAÇÃO PRINCIPAL:"
echo "   • Aplicação:    http://localhost:8082"
echo "   • PHPMyAdmin:   http://localhost:8083 (profile: tools)"
echo "   • Mailhog:      http://localhost:8026"
echo ""
echo "📊 MONITORAMENTO:"
echo "   • Prometheus:   http://localhost:9090"
echo "   • Grafana:      http://localhost:3000 (admin/admin@123)"
echo "   • Alertmanager: http://localhost:9093"
echo ""
echo "📈 EXPORTERS:"
echo "   • Node Exporter:    http://localhost:9100/metrics"
echo "   • cAdvisor:         http://localhost:8080"
echo "   • MySQL Exporter:   http://localhost:9104/metrics"
echo "   • Redis Exporter:   http://localhost:9121/metrics"
echo "   • Nginx Exporter:   http://localhost:9113/metrics"
echo "   • Blackbox:         http://localhost:9115"
echo ""
echo "🔧 COMANDOS ÚTEIS:"
echo "   • Logs aplicação:     docker compose logs -f"
echo "   • Logs monitoramento: docker compose -f docker-compose.monitoring.yml logs -f"
echo "   • Parar tudo:         docker compose down && docker compose -f docker-compose.monitoring.yml down"
echo "   • Status:             docker compose ps && docker compose -f docker-compose.monitoring.yml ps"
echo ""
echo "============================================================================"
