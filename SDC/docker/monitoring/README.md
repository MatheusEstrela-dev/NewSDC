# 📊 Sistema de Monitoramento SDC

Sistema de monitoramento completo baseado em **Prometheus + Grafana + Alertmanager** seguindo as metodologias:
- **Golden Signals** (Google SRE): Latência, Tráfego, Erros, Saturação
- **USE Method**: Utilization, Saturation, Errors

## 🚀 Quick Start

### 1. Iniciar o stack de monitoramento

```bash
cd /home/matheus/Documentos/NewSDC/SDC/docker
docker compose -f docker-compose.monitoring.yml up -d
```

### 2. Verificar status

```bash
docker compose -f docker-compose.monitoring.yml ps
```

### 3. Acessar as interfaces

- **Grafana:** http://localhost:3000
  - User: `admin`
  - Password: `admin@123` (mude no primeiro login)

- **Prometheus:** http://localhost:9090
  - Query console e métricas brutas

- **Alertmanager:** http://localhost:9093
  - Gerenciamento de alertas

- **cAdvisor:** http://localhost:8080
  - Métricas de containers em tempo real

## 📈 Exporters Disponíveis

| Exporter | Porta | Métrica |
|----------|-------|---------|
| Node Exporter | 9100 | CPU, Memória, Disco, Rede (HOST) |
| cAdvisor | 8080 | Containers Docker |
| MySQL Exporter | 9104 | Banco de dados |
| Redis Exporter | 9121 | Cache/Sessions |
| Nginx Exporter | 9113 | Web server |
| Blackbox Exporter | 9115 | Probes HTTP/TCP/ICMP |

## 🎯 Dashboards Hierárquicos

### Nível 1: Visão Executiva (Health Check)
- ✅ Status global do sistema (Verde/Vermelho)
- 📊 Uptime de serviços principais
- 👥 Usuários online / Requisições por minuto

### Nível 2: Golden Signals (Service Overview)
1. **Latência:** Tempo de resposta P50, P95, P99
2. **Tráfego:** Requisições por segundo (RPS)
3. **Erros:** Taxa de erro HTTP 4xx/5xx
4. **Saturação:** CPU, Memória, Conexões

### Nível 3: USE Method (Infrastructure Drill-down)
1. **Utilization:** % de uso de CPU, Memória, Disco, Rede
2. **Saturation:** Load average, filas, context switches
3. **Errors:** Erros de hardware, pacotes dropados, I/O errors

## 🔔 Alertas Configurados

### Críticos (ação imediata)
- ✅ Serviços DOWN (MySQL, Redis, Nginx, App)
- ✅ Alta taxa de erros HTTP 5xx (> 5%)
- ✅ Disco com menos de 15% livre
- ✅ Memória container > 90%
- ✅ Pool de conexões MySQL saturado

### Warnings (olhar em horário comercial)
- ⚠️ CPU > 80% por 10min
- ⚠️ Memória > 85%
- ⚠️ Latência P95 > 1s
- ⚠️ Cache hit rate < 80%
- ⚠️ Slow queries MySQL

### DeadMan Switch
- 💚 Alerta que **sempre dispara** (a cada 5min)
- Se parar = sistema de monitoramento caiu!

## 📝 Queries Úteis (Prometheus)

### Golden Signals

**Latência P95:**
```promql
histogram_quantile(0.95, 
  sum(rate(nginx_http_request_duration_seconds_bucket[5m])) by (le)
)
```

**Taxa de Erro:**
```promql
sum(rate(nginx_http_requests_total{status=~"5.."}[5m])) / 
sum(rate(nginx_http_requests_total[5m])) * 100
```

**Requisições por segundo:**
```promql
sum(rate(nginx_http_requests_total[5m]))
```

**Saturação de Memória:**
```promql
(1 - (node_memory_MemAvailable_bytes / node_memory_MemTotal_bytes)) * 100
```

### USE Method

**CPU Utilization:**
```promql
100 - (avg(rate(node_cpu_seconds_total{mode="idle"}[5m])) * 100)
```

**Load Average (Saturation):**
```promql
node_load15 / count(node_cpu_seconds_total{mode="system"})
```

**Network Errors:**
```promql
rate(node_network_receive_drop_total[5m])
```

## 🔧 Configuração Avançada

### Retenção de Dados
- **Padrão:** 30 dias
- **Limite:** 10GB
- Configurar em: `prometheus.yml` → `--storage.tsdb.retention`

### Alertas via Slack/Discord/Teams

1. Crie um Incoming Webhook no Slack
2. Edite `alertmanager/alertmanager.yml`:

```yaml
receivers:
  - name: 'critical-alerts'
    webhook_configs:
      - url: 'https://hooks.slack.com/services/SEU/WEBHOOK/AQUI'
```

3. Reload Alertmanager:
```bash
docker compose -f docker-compose.monitoring.yml restart alertmanager
```

### Adicionar Novos Targets

Edite `prometheus/prometheus.yml`:

```yaml
scrape_configs:
  - job_name: 'meu-servico'
    static_configs:
      - targets: ['meu-container:porta']
        labels:
          service: 'nome-servico'
```

Reload Prometheus:
```bash
curl -X POST http://localhost:9090/-/reload
```

## 🛠️ Troubleshooting

### Prometheus não scrape targets
```bash
# Ver logs
docker logs newsdc_prometheus

# Verificar config
docker exec newsdc_prometheus promtool check config /etc/prometheus/prometheus.yml
```

### Grafana não mostra dados
1. Verifique datasource: Settings → Data Sources → Prometheus → Test
2. Verifique se Prometheus está coletando: http://localhost:9090/targets

### Alertas não disparando
```bash
# Ver regras ativas
http://localhost:9090/rules

# Ver alertas pendentes
http://localhost:9090/alerts

# Logs do Alertmanager
docker logs newsdc_alertmanager
```

## 📊 Importar Dashboards Prontos

1. Acesse Grafana → Dashboards → Import
2. Use estes IDs do Grafana.com:

- **Node Exporter Full:** `1860`
- **Docker Container:** `193`
- **MySQL Overview:** `7362`
- **Redis Dashboard:** `11835`
- **Nginx:** `12708`

## 🔒 Segurança em Produção

1. **Mudar senhas padrão:**
```bash
# Grafana
GF_SECURITY_ADMIN_PASSWORD=SenhaForte123

# Prometheus (adicionar autenticação)
# Nginx reverse proxy com basic auth
```

2. **Restringir acesso por IP:**
Adicionar regras no `docker-compose.monitoring.yml`:
```yaml
ports:
  - "127.0.0.1:9090:9090"  # Apenas localhost
```

3. **HTTPS com Let's Encrypt:**
Usar Nginx reverse proxy com SSL

## 📦 Backup e Restore

### Backup de dashboards Grafana
```bash
docker exec newsdc_grafana grafana-cli admin export-dashboard
```

### Backup de dados Prometheus
```bash
docker run --rm -v newsdc_prometheus_data:/data -v $(pwd):/backup \
  alpine tar czf /backup/prometheus-backup.tar.gz /data
```

## 🎓 Recursos

- [Prometheus Docs](https://prometheus.io/docs/)
- [Grafana Docs](https://grafana.com/docs/)
- [Golden Signals (Google SRE)](https://sre.google/sre-book/monitoring-distributed-systems/)
- [USE Method](http://www.brendangregg.com/usemethod.html)

---

**Versão:** 1.0  
**Autor:** Matheus Estrela (KvN)  
**Data:** 2025-12-26
