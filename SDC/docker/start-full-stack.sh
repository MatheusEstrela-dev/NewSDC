#!/bin/bash
# ============================================================================
# Script para iniciar SDC completo: aplicacao + monitoramento
# ============================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "============================================================================"
echo "SDC - Iniciando Stack Completo"
echo "============================================================================"
echo ""

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

print_status() {
    echo -e "${GREEN}[OK]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_warning "Parando containers existentes..."
docker compose down --remove-orphans 2>/dev/null || true
docker compose -f docker-compose.monitoring.yml down --remove-orphans 2>/dev/null || true
echo ""

print_status "Iniciando aplicacao principal (SDC + Swoole)..."
docker compose up -d
echo ""

print_warning "Aguardando containers principais ficarem prontos..."
sleep 10

if ! docker compose ps | grep -q "newsdc_app"; then
    print_error "Erro: container newsdc_app nao esta rodando."
    exit 1
fi

print_status "Containers principais prontos."
echo ""

print_status "Iniciando stack de monitoramento..."
docker compose -f docker-compose.monitoring.yml up -d
echo ""

print_warning "Aguardando stack de monitoramento..."
sleep 15

echo ""
echo "============================================================================"
echo "SDC Stack Completo INICIADO"
echo "============================================================================"
echo ""
echo "APLICACAO PRINCIPAL:"
echo "   - Aplicacao:    http://localhost:18001"
echo "   - PHPMyAdmin:   http://localhost:8083 (profile: tools)"
echo "   - Mailhog:      http://localhost:8026"
echo ""
echo "MONITORAMENTO:"
echo "   - Prometheus:   http://localhost:9090"
echo "   - Grafana:      http://localhost:3000 (admin/admin@123)"
echo "   - Alertmanager: http://localhost:9093"
echo ""
echo "EXPORTERS:"
echo "   - Node Exporter:    http://localhost:9100/metrics"
echo "   - cAdvisor:         http://localhost:8080"
echo "   - Redis Exporter:   http://localhost:9121/metrics"
echo "   - Blackbox:         http://localhost:9115"
echo ""
echo "COMANDOS UTEIS:"
echo "   - Logs aplicacao:     docker compose logs -f"
echo "   - Logs monitoramento: docker compose -f docker-compose.monitoring.yml logs -f"
echo "   - Parar tudo:         docker compose down && docker compose -f docker-compose.monitoring.yml down"
echo "   - Status:             docker compose ps && docker compose -f docker-compose.monitoring.yml ps"
echo ""
echo "============================================================================"
