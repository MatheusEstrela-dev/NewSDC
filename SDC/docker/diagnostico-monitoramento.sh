#!/bin/bash
echo "=== DIAGNÓSTICO DO SISTEMA DE MONITORAMENTO ==="
echo ""
echo "1️⃣ Verificando containers em execução..."
docker ps --filter "name=newsdc" --format "table {{.Names}}\t{{.Status}}" | grep -E "prometheus|grafana|alertmanager|exporter|cadvisor" || echo "❌ Nenhum container de monitoramento rodando"

echo ""
echo "2️⃣ Testando portas..."
echo -n "Prometheus (9090): "
nc -z localhost 9090 && echo "✅ UP" || echo "❌ DOWN"

echo -n "Grafana (3000): "
nc -z localhost 3000 && echo "✅ UP" || echo "❌ DOWN"

echo -n "Alertmanager (9093): "
nc -z localhost 9093 && echo "✅ UP" || echo "❌ DOWN"

echo ""
echo "3️⃣ Logs recentes (últimas 20 linhas)..."
echo "--- Prometheus ---"
docker logs newsdc_prometheus 2>&1 | tail -5 || echo "Container não encontrado"

echo ""
echo "--- Grafana ---"
docker logs newsdc_grafana 2>&1 | tail -5 || echo "Container não encontrado"

echo ""
echo "4️⃣ Comandos úteis:"
echo "  Iniciar: cd /home/matheus/Documentos/NewSDC/SDC/docker && docker compose -f docker-compose.monitoring.yml up -d"
echo "  Parar:   docker compose -f docker-compose.monitoring.yml down"
echo "  Logs:    docker compose -f docker-compose.monitoring.yml logs -f"
