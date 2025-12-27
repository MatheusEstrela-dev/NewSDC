# 🎯 Sistema de Monitoramento SDC - Implantação Completa

## ✅ O que foi implementado

### 📦 Stack Completa
- ✅ **Prometheus** v2.48.1 - Coleta e armazenamento TSDB
- ✅ **Grafana** v10.2.3 - Dashboards e visualização
- ✅ **Alertmanager** v0.26.0 - Gerenciamento de alertas
- ✅ **Node Exporter** v1.7.0 - Métricas de infraestrutura
- ✅ **cAdvisor** v0.47.2 - Métricas de containers
- ✅ **MySQL Exporter** v0.15.1 - Métricas do banco
- ✅ **Redis Exporter** v1.56.0 - Métricas do cache
- ✅ **Nginx Exporter** v1.0.0 - Métricas do web server
- ✅ **Blackbox Exporter** v0.24.0 - Probes externos

### 📊 Metodologias Implementadas

#### Golden Signals (Google SRE)
1. **Latência** - P50, P95, P99 de tempo de resposta
2. **Tráfego** - Requisições por segundo (RPS)
3. **Erros** - Taxa de erro HTTP 4xx/5xx
4. **Saturação** - CPU, Memória, Disco, Conexões

#### USE Method (Brendan Gregg)
1. **Utilization** - % uso de CPU, Memória, Disco, Rede
2. **Saturation** - Load average, filas, context switches
3. **Errors** - Erros de hardware, pacotes dropados

### 🔔 Sistema de Alertas

#### Alertas Críticos (15 regras)
- Serviços DOWN (MySQL, Redis, Nginx, App)
- Alta taxa de erros HTTP 5xx (> 5%)
- Disco com menos de 15% livre
- Memória container > 90%
- Pool de conexões saturado
- Container OOM killed
- Disk I/O errors

#### Alertas de Warning (12 regras)
- CPU > 80% por 10min
- Memória > 85%
- Latência P95 > 1s
- Cache hit rate < 80%
- Slow queries MySQL
- Load average alto
- Context switches elevados
- Fragmentação Redis

#### DeadMan Switch
- Alerta que sempre dispara a cada 5min
- Se parar = sistema de monitoramento caiu

### 📁 Estrutura de Arquivos

```
SDC/docker/
├── docker-compose.monitoring.yml  # Stack completa (10 serviços)
├── monitoring/
│   ├── README.md                  # Documentação completa
│   ├── prometheus/
│   │   ├── prometheus.yml         # Config principal
│   │   └── alerts/
│   │       ├── golden_signals.yml # 10 alertas Golden Signals
│   │       ├── use_method.yml     # 8 alertas USE Method
│   │       └── services.yml       # 15 alertas de serviços
│   ├── alertmanager/
│   │   └── alertmanager.yml       # Roteamento inteligente
│   ├── grafana/
│   │   ├── provisioning/
│   │   │   ├── datasources/       # Prometheus auto-config
│   │   │   └── dashboards/        # Auto-import
│   │   └── dashboards/
│   │       └── golden-signals.json # Dashboard pronto
│   └── blackbox/
│       └── blackbox.yml           # Probes HTTP/TCP/ICMP
├── nginx/
│   └── status.conf                # Endpoint /nginx_status
└── start-monitoring.sh            # Script de inicialização
```

## 🚀 Como Usar

### 1. Iniciar Stack de Monitoramento

```bash
cd /home/matheus/Documentos/NewSDC/SDC/docker
bash start-monitoring.sh
```

Ou manualmente:
```bash
docker compose -f docker-compose.monitoring.yml up -d
```

### 2. Verificar Status

```bash
docker compose -f docker-compose.monitoring.yml ps
```

### 3. Acessar Interfaces

| Serviço | URL | Credenciais |
|---------|-----|-------------|
| **Grafana** | http://localhost:3000 | admin / admin@123 |
| **Prometheus** | http://localhost:9090 | - |
| **Alertmanager** | http://localhost:9093 | - |
| **cAdvisor** | http://localhost:8080 | - |

### 4. Importar Dashboards Prontos

No Grafana, vá em **Dashboards → Import** e use estes IDs:

- **Node Exporter Full:** `1860` ⭐ Essencial
- **Docker Containers:** `193`
- **MySQL Overview:** `7362`
- **Redis Dashboard:** `11835`
- **Nginx:** `12708`
- **Prometheus Stats:** `3662`

## 📊 Dashboards Hierárquicos

### Nível 1: Executive Dashboard (Health Check)
- Status global: Verde/Vermelho
- Uptime de serviços
- Métricas de negócio

### Nível 2: Golden Signals (Service View)
- **Latência:** Gráfico com P50, P95, P99
- **Tráfego:** RPS total e por endpoint
- **Erros:** Taxa 4xx/5xx com threshold
- **Saturação:** CPU, Memória, Disco, Rede

### Nível 3: USE Method (Infrastructure Drill-down)
- **CPU:** Uso, Load, Context Switches
- **Memória:** Uso, Swap, Page Faults
- **Disco:** I/O, Latência, Espaço
- **Rede:** Throughput, Errors, Drops

## 🔧 Configuração em Produção

### 1. Configurar Alertas (Slack/Discord/Teams)

Edite `monitoring/alertmanager/alertmanager.yml`:

```yaml
receivers:
  - name: 'critical-alerts'
    webhook_configs:
      - url: 'https://hooks.slack.com/services/SEU/WEBHOOK'
        send_resolved: true
```

Reload:
```bash
docker compose -f docker-compose.monitoring.yml restart alertmanager
```

### 2. Ajustar Retenção de Dados

Edite `docker-compose.monitoring.yml`:

```yaml
prometheus:
  command:
    - '--storage.tsdb.retention.time=60d'  # 60 dias
    - '--storage.tsdb.retention.size=20GB'  # 20GB
```

### 3. Segurança

**Mudar senha Grafana:**
```bash
# Editar docker-compose.monitoring.yml
GF_SECURITY_ADMIN_PASSWORD: SuaSenhaForte123!
```

**Restringir acesso:**
```yaml
ports:
  - "127.0.0.1:9090:9090"  # Apenas localhost
```

### 4. Backup

**Dashboards Grafana:**
```bash
docker exec newsdc_grafana grafana-cli admin export-dashboard
```

**Dados Prometheus:**
```bash
docker run --rm \
  -v newsdc_prometheus_data:/data \
  -v $(pwd):/backup \
  alpine tar czf /backup/prometheus-backup.tar.gz /data
```

## 📈 Queries Úteis

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

**RPS:**
```promql
sum(rate(nginx_http_requests_total[5m]))
```

**Saturação Memória:**
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

## 🎯 Portas Utilizadas

| Porta | Serviço | Descrição |
|-------|---------|-----------|
| 3000 | Grafana | Interface web |
| 9090 | Prometheus | Query UI |
| 9093 | Alertmanager | Alertas UI |
| 9100 | Node Exporter | Métricas host |
| 8080 | cAdvisor | Containers UI |
| 9104 | MySQL Exporter | Métricas MySQL |
| 9121 | Redis Exporter | Métricas Redis |
| 9113 | Nginx Exporter | Métricas Nginx |
| 9115 | Blackbox Exporter | Probes |

## 🛠️ Troubleshooting

### Prometheus não scrape targets

```bash
# Ver logs
docker logs newsdc_prometheus

# Validar config
docker exec newsdc_prometheus \
  promtool check config /etc/prometheus/prometheus.yml

# Reload config
curl -X POST http://localhost:9090/-/reload
```

### Grafana sem dados

1. Settings → Data Sources → Prometheus → Test
2. http://localhost:9090/targets (verificar se está UP)
3. Verificar logs: `docker logs newsdc_grafana`

### Alertas não disparando

```bash
# Ver regras ativas
http://localhost:9090/rules

# Ver alertas pendentes
http://localhost:9090/alerts

# Logs Alertmanager
docker logs newsdc_alertmanager
```

## 📊 Próximos Passos (Opcional)

- [ ] Long-term storage (Thanos/VictoriaMetrics)
- [ ] Service discovery dinâmico
- [ ] Métricas de aplicação Laravel (custom exporter)
- [ ] Logs agregados (Loki + Promtail)
- [ ] Tracing distribuído (Tempo/Jaeger)
- [ ] Testes de carga automáticos (K6)

## 📚 Recursos

- [Prometheus Docs](https://prometheus.io/docs/)
- [Grafana Docs](https://grafana.com/docs/)
- [Golden Signals](https://sre.google/sre-book/monitoring-distributed-systems/)
- [USE Method](http://www.brendangregg.com/usemethod.html)
- [RED Method](https://grafana.com/blog/2018/08/02/the-red-method-how-to-instrument-your-services/)

---

## 🎉 Conclusão

Sistema de monitoramento **production-ready** implementado com:

✅ **10 serviços** integrados  
✅ **33 alertas** configurados (15 críticos + 12 warnings + 1 deadman + 5 containers)  
✅ **Golden Signals + USE Method** implementados  
✅ **Dashboards hierárquicos** (3 níveis)  
✅ **Roteamento inteligente** de alertas  
✅ **Deduplicação** e inibição de alertas  
✅ **Documentação completa**  
✅ **Scripts de automação**  

**Status:** ✅ Pronto para produção  
**Versão:** 1.0  
**Autor:** Matheus Estrela (KvN)  
**Data:** 2025-12-26
