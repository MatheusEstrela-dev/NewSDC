#!/bin/bash
# ============================================================================
# Script de Inicialização do Sistema de Monitoramento
# ============================================================================

set -e

DOCKER_DIR="/home/matheus/Documentos/NewSDC/SDC/docker"
cd "$DOCKER_DIR"

echo "🚀 Iniciando Sistema de Monitoramento SDC..."
echo ""

# Verificar se rede exists
if ! docker network inspect newsdc_sdc_network >/dev/null 2>&1; then
    echo "❌ Erro: Rede newsdc_sdc_network não existe!"
    echo "Execute primeiro: docker compose up -d"
    exit 1
fi

# Iniciar stack de monitoramento
echo "📊 Subindo containers de monitoramento..."
docker compose -f docker-compose.monitoring.yml up -d

echo ""
echo "⏳ Aguardando containers ficarem healthy..."
sleep 15

# Verificar status
echo ""
echo "📋 Status dos containers:"
docker compose -f docker-compose.monitoring.yml ps

echo ""
echo "✅ Sistema de Monitoramento iniciado!"
echo ""
echo "📊 Acesse as interfaces:"
echo "   - Grafana:     http://localhost:3000 (admin/admin@123)"
echo "   - Prometheus:  http://localhost:9090"
echo "   - Alertmanager: http://localhost:9093"
echo "   - cAdvisor:    http://localhost:8080"
echo ""
echo "📖 Documentação: $DOCKER_DIR/monitoring/README.md"
