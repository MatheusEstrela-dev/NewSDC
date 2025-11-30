# 📊 SDC - Guia Completo de Monitoring & Logging

## 🎯 Visão Geral

Sistema completo de **monitoramento, logging e health check** para produção com visualização em tempo real side-by-side.

### Stack de Monitoring

```
┌─────────────────────────────────────────────────────────┐
│                  CAMADA DE VISUALIZAÇÃO                  │
├─────────────────────────────────────────────────────────┤
│  Grafana (Port 3000)  │  Log Viewer (Web UI)            │
│  • Dashboards         │  • Logs em tempo real           │
│  • Alertas visuais    │  • Filtros avançados            │
└───────────┬────────────┴──────────────┬─────────────────┘
            │                           │
┌───────────▼───────────┐     ┌─────────▼────────────────┐
│   Prometheus          │     │   Laravel App            │
│   • Coleta métricas   │     │   • ActivityLogger       │
│   • Time-series DB    │     │   • Health Checks        │
│   • Alerting          │     │   • Métricas custom      │
└───────────┬───────────┘     └──────────┬───────────────┘
            │                            │
            │ ┌──────────────────────────┘
            │ │
┌───────────▼─▼────────────────────────────────────────┐
│              EXPORTERS & COLLECTORS                   │
├───────────────────────────────────────────────────────┤
│ Redis Exporter  │ MySQL Exporter  │ Node Exporter    │
└───────────────────────────────────────────────────────┘
```

---

## 🚀 INICIAR MONITORING STACK

### 1. Iniciar Aplicação + Monitoring

```bash
# Iniciar tudo de uma vez
docker compose -f docker/docker-compose.yml -f docker/docker-compose.monitoring.yml up -d

# Ou via Makefile (adicionar ao Makefile)
make dev-monitoring
```

### 2. Verificar Status

```bash
docker compose -f docker/docker-compose.monitoring.yml ps
```

**Serviços rodando:**
- ✅ Grafana: http://localhost:3000 (admin/admin123)
- ✅ Prometheus: http://localhost:9090
- ✅ AlertManager: http://localhost:9093
- ✅ Redis Exporter: http://localhost:9121
- ✅ MySQL Exporter: http://localhost:9104
- ✅ Node Exporter: http://localhost:9100

---

## 📡 HEALTH CHECK ENDPOINTS

### 1️⃣ **Basic Health Check**

```bash
GET http://localhost:8000/api/health
```

**Resposta:**
```json
{
  "status": "ok",
  "timestamp": "2025-11-27T22:00:00Z",
  "uptime": 3600
}
```

**Uso:** Load balancers, Kubernetes liveness probe

---

### 2️⃣ **Detailed Health Check**

```bash
GET http://localhost:8000/api/health/detailed
```

**Resposta:**
```json
{
  "status": "healthy",
  "timestamp": "2025-11-27T22:00:00Z",
  "checks": {
    "database": {
      "status": "ok",
      "latency_ms": 2.45,
      "connection": "mysql"
    },
    "redis": {
      "status": "ok",
      "latency_ms": 1.12,
      "memory_used_mb": 45.6,
      "connected_clients": 12
    },
    "cache": {
      "status": "ok",
      "driver": "redis"
    },
    "queue": {
      "status": "ok",
      "pending_jobs": 15,
      "queues_monitored": ["critical", "high", "default", "webhooks", "low"]
    },
    "storage": {
      "status": "ok",
      "total_gb": 100,
      "free_gb": 75,
      "used_percent": 25
    }
  },
  "system": {
    "memory_usage_mb": 128.5,
    "memory_peak_mb": 156.2,
    "cpu_load": [0.5, 0.6, 0.7],
    "php_version": "8.3.28",
    "laravel_version": "12.0"
  },
  "performance": {
    "uptime_seconds": 3600,
    "requests_per_minute": 1250
  }
}
```

**Uso:** Dashboards, alertas detalhados

---

### 3️⃣ **Prometheus Metrics**

```bash
GET http://localhost:8000/api/health/metrics
```

**Resposta (formato Prometheus):**
```
# HELP sdc_up Sistema está online (1) ou offline (0)
# TYPE sdc_up gauge
sdc_up 1

# HELP sdc_memory_usage_bytes Uso de memória em bytes
# TYPE sdc_memory_usage_bytes gauge
sdc_memory_usage_bytes 134742016

# HELP sdc_queue_jobs_pending Jobs pendentes na fila
# TYPE sdc_queue_jobs_pending gauge
sdc_queue_jobs_pending{queue="critical"} 2
sdc_queue_jobs_pending{queue="high"} 8
sdc_queue_jobs_pending{queue="webhooks"} 15

# HELP sdc_events_total Total de eventos por tipo
# TYPE sdc_events_total counter
sdc_events_total{type="api",event="request"} 12543
sdc_events_total{type="webhook",event="sent"} 234
sdc_events_total{type="error",event="critical"} 5
```

**Uso:** Prometheus scraping

---

## 📝 SISTEMA DE LOGGING

### Activity Logger - Uso no Código

```php
use App\Services\Logging\ActivityLogger;

// Log de evento da API
ActivityLogger::logApiRequest(
    endpoint: '/api/v1/webhooks/send',
    statusCode: 200,
    duration: 145.67,
    userId: auth()->id(),
    extra: ['integration_id' => 'int_123']
);

// Log de webhook
ActivityLogger::logWebhook(
    direction: 'outgoing',
    url: 'https://external-system.com/webhook',
    payload: $data,
    statusCode: 200,
    duration: 234.5,
    success: true
);

// Log de integração
ActivityLogger::logIntegration(
    integrationType: 'rest_api',
    action: 'create_lead',
    success: true,
    duration: 567.8,
    extra: ['salesforce_id' => 'SF123']
);

// Log de erro crítico
try {
    // código
} catch (\Exception $e) {
    ActivityLogger::logCriticalError(
        message: 'Failed to process payment',
        exception: $e,
        context: ['user_id' => $userId, 'amount' => 99.99]
    );
}

// Log de performance
$start = microtime(true);
// ... operação ...
ActivityLogger::logPerformance(
    operation: 'database_query',
    duration: (microtime(true) - $start) * 1000,
    metrics: ['rows' => 1000, 'query_type' => 'SELECT']
);

// Log de segurança
ActivityLogger::logSecurity(
    event: 'failed_login_attempt',
    data: ['ip' => request()->ip(), 'email' => $email],
    severity: 'warning'
);
```

---

## 🔍 LOG VIEWER API

### 1️⃣ **Logs Recentes**

```bash
GET http://localhost:8000/api/v1/logs/recent?type=all&limit=100
Authorization: Bearer {token}
```

**Parâmetros:**
- `type`: all, api, webhook, integration, error, performance, security
- `limit`: 1-1000 (default: 100)

**Resposta:**
```json
{
  "logs": [
    {
      "timestamp": "2025-11-27T22:30:15Z",
      "type": "api",
      "event": "request",
      "data": {
        "endpoint": "/api/v1/integration/execute",
        "status_code": 200,
        "duration_ms": 145.67
      },
      "user_id": "123",
      "ip": "192.168.1.100"
    }
  ],
  "total": 100,
  "type": "all"
}
```

---

### 2️⃣ **Métricas de Logs**

```bash
GET http://localhost:8000/api/v1/logs/metrics
Authorization: Bearer {token}
```

**Resposta:**
```json
{
  "metrics": [
    {"type": "api", "event": "request", "count": 12543},
    {"type": "webhook", "event": "sent", "count": 234}
  ],
  "summary": {
    "total_events": 15000,
    "events_by_type": {
      "api": 12543,
      "webhook": 1234,
      "error": 23
    }
  }
}
```

---

### 3️⃣ **Apenas Erros**

```bash
GET http://localhost:8000/api/v1/logs/errors
Authorization: Bearer {token}
```

---

### 4️⃣ **Stream em Tempo Real (SSE)**

```bash
GET http://localhost:8000/api/v1/logs/stream
Authorization: Bearer {token}
```

**Cliente JavaScript:**
```javascript
const evtSource = new EventSource('/api/v1/logs/stream', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN'
  }
});

evtSource.onmessage = (event) => {
  const log = JSON.parse(event.data);
  console.log('Novo log:', log);

  // Atualizar UI
  addLogToTable(log);
};
```

---

## 📊 GRAFANA DASHBOARDS

### Acessar Grafana

```
http://localhost:3000
Usuário: admin
Senha: admin123
```

### Dashboards Disponíveis

#### 1. **SDC - Overview**
- Requisições/minuto
- Taxa de erros
- Latência P50/P95/P99
- Uptime

#### 2. **SDC - Queue Monitoring**
- Jobs pendentes por fila
- Taxa de processamento
- Jobs falhados
- Duração média

#### 3. **SDC - System Resources**
- CPU usage
- Memory usage
- Disk I/O
- Network I/O

#### 4. **SDC - Database**
- Conexões ativas
- Queries/segundo
- Slow queries
- Table locks

#### 5. **SDC - Redis**
- Memory usage
- Hit/miss rate
- Connected clients
- Commands/segundo

---

## 🚨 ALERTAS

### Configuração no AlertManager

**Arquivo:** `docker/monitoring/alertmanager/alertmanager.yml`

**Alertas Configurados:**

| Alerta | Condição | Severidade | Ação |
|--------|----------|------------|------|
| ApplicationDown | sdc_up == 0 por 1min | critical | Webhook imediato |
| HighErrorRate | > 10 erros/s por 5min | warning | Webhook |
| HighQueueBacklog | > 1000 jobs por 10min | warning | Webhook |
| HighMemoryUsage | > 1GB por 5min | warning | Webhook |
| RedisDown | redis_up == 0 por 1min | critical | Webhook |
| MySQLDown | mysql_up == 0 por 1min | critical | Webhook |

**Webhook de Alertas:**
```bash
POST http://localhost:8000/api/alerts/webhook
```

---

## 🎨 VISUALIZAÇÃO SIDE-BY-SIDE

### Setup Recomendado

**Monitor 1 - Dashboards:**
```
┌──────────────────────────────────────────┐
│         Grafana - SDC Overview            │
│  • Req/min: 1250                         │
│  • Errors: 0.01%                         │
│  • Latency P95: 145ms                    │
│                                          │
│  [Gráfico de requisições]                │
│  [Gráfico de latência]                   │
└──────────────────────────────────────────┘
```

**Monitor 2 - Logs em Tempo Real:**
```
┌──────────────────────────────────────────┐
│      Log Viewer - Real-time Stream        │
│                                          │
│  22:30:15 [API] POST /webhooks/send 200  │
│  22:30:16 [WEBHOOK] Sent to external OK  │
│  22:30:17 [INTEGRATION] Salesforce OK    │
│  22:30:18 [API] GET /health/detailed 200 │
│                                          │
│  [Filtros: All | API | Errors]           │
└──────────────────────────────────────────┘
```

---

## 📝 CONFIGURAÇÃO DE LOGS

### Canais de Log (config/logging.php)

```php
'channels' => [
    // Log de eventos (ActivityLogger)
    'events' => [
        'driver' => 'daily',
        'path' => storage_path('logs/events.log'),
        'level' => 'debug',
        'days' => 30,
    ],
],
```

### Rotação de Logs

**Produção - Logrotate:**
```bash
/var/www/storage/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
}
```

---

## 🔧 COMANDOS ÚTEIS

### Verificar Saúde
```bash
# Basic
curl http://localhost:8000/api/health

# Detailed
curl http://localhost:8000/api/health/detailed | jq

# Metrics (Prometheus)
curl http://localhost:8000/api/health/metrics
```

### Ver Logs Recentes
```bash
# Via API
curl -H "Authorization: Bearer TOKEN" \
  "http://localhost:8000/api/v1/logs/recent?type=error&limit=50" | jq

# Arquivo
tail -f storage/logs/events.log
```

### Métricas do Sistema
```bash
# Via Prometheus
curl http://localhost:9090/api/v1/query?query=sdc_up

# Via API
curl -H "Authorization: Bearer TOKEN" \
  http://localhost:8000/api/v1/logs/metrics | jq
```

---

## 📊 QUERIES PROMETHEUS ÚTEIS

### Taxa de Requisições
```promql
rate(sdc_events_total{type="api"}[5m])
```

### Erros por Minuto
```promql
rate(sdc_events_total{type="error"}[1m]) * 60
```

### Jobs Pendentes Total
```promql
sum(sdc_queue_jobs_pending)
```

### Percentil 95 de Latência
```promql
histogram_quantile(0.95, rate(http_request_duration_seconds_bucket[5m]))
```

---

## 🚀 PRODUÇÃO - CHECKLIST

- [ ] Grafana configurado com autenticação forte
- [ ] AlertManager enviando notificações (Slack/Email)
- [ ] Logs sendo rotacionados diariamente
- [ ] Prometheus com 30 dias de retenção
- [ ] Dashboards personalizados criados
- [ ] Alertas testados
- [ ] Health checks configurados no load balancer
- [ ] Backup de métricas configurado

---

## 📈 MÉTRICAS DE SUCESSO

**Sistema está saudável quando:**
- ✅ Taxa de erros < 0.1%
- ✅ Latência P95 < 200ms
- ✅ Uptime > 99.9%
- ✅ Queue backlog < 500 jobs
- ✅ Memory usage < 80%
- ✅ Disk usage < 85%

---

## 🎯 EXEMPLO PRÁTICO

### Monitorar Deploy em Produção

**1. Abrir Grafana e Log Viewer lado a lado**

**2. Executar deploy:**
```bash
make deploy
```

**3. Monitorar em tempo real:**
- Grafana: Ver spike de requisições durante deploy
- Logs: Ver eventos de migração, cache clear, etc
- Health Check: Verificar se tudo voltou ao normal

**4. Alertas:**
- Se erro rate > 10%: AlertManager notifica
- Se queue backlog > 1000: AlertManager notifica

---

## 🆘 TROUBLESHOOTING

### Grafana não está mostrando dados
```bash
# Verificar Prometheus
curl http://localhost:9090/-/healthy

# Verificar scraping
curl http://localhost:9090/api/v1/targets

# Verificar métricas da app
curl http://localhost:8000/api/health/metrics
```

### Logs não aparecem no viewer
```bash
# Verificar Redis
docker compose -f docker/docker-compose.yml exec redis redis-cli ping

# Ver logs manualmente
redis-cli LRANGE logs:api 0 10
```

---

**🎉 Sistema completo de Monitoring & Logging configurado!**

**Acessos Rápidos:**
- Grafana: http://localhost:3000
- Prometheus: http://localhost:9090
- Log Viewer API: http://localhost:8000/api/v1/logs/recent
- Health Check: http://localhost:8000/api/health/detailed
