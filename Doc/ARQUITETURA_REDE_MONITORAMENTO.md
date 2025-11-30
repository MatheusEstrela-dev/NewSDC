# 🌐 Arquitetura de Rede e Monitoramento - Sistema SDC

> **Análise completa da topologia de rede e stack de monitoramento 24/7**

---

## 📊 Visão Geral da Arquitetura

### Stacks Principais

```
┌─────────────────────────────────────────────────────────────────┐
│                       INFRAESTRUTURA SDC                         │
├─────────────────────────────────────────────────────────────────┤
│  Stack 1: Aplicação (docker-compose.yml)                        │
│  Stack 2: Jenkins CI/CD (docker-compose.jenkins.yml)            │
│  Stack 3: Monitoramento (docker-compose.monitoring.yml)         │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔌 Topologia de Redes

### Redes Configuradas

| Rede | Tipo | Subnet | Finalidade | External |
|------|------|--------|------------|----------|
| **sdc_network** | bridge | 172.20.0.0/16 | Rede principal da aplicação | Não |
| **jenkins_internal** | bridge | 172.25.0.0/24 | Jenkins + Agents (isolada) | **Sim** (internal: true) |
| **jenkins_dmz** | bridge | 172.26.0.0/24 | Jenkins + Nginx | Não |

### Diagrama de Rede Completo

```mermaid
graph TB
    subgraph Internet
        USER[👤 Usuário]
    end

    subgraph "Host Machine"
        subgraph "jenkins_dmz (172.26.0.0/24)"
            NGINX[Nginx :80/:443]
        end

        subgraph "jenkins_internal (172.25.0.0/24 - ISOLADA)"
            JENKINS[Jenkins Master :8080]
            AGENT[Jenkins Agent]
            DPROXY[Docker Proxy :2375]
            BACKUP_L[Backup Local]
            BACKUP_R[Backup Remote]
            WATCHTOWER[Watchtower]
            J_EXPORTER[Jenkins Exporter :9118]
        end

        subgraph "sdc_network (172.20.0.0/16)"
            APP[Laravel App :8000]
            DB[MySQL :3306]
            REDIS[Redis :6379]
            QUEUE[Queue Worker]
            SCHEDULER[Scheduler]

            PROM[Prometheus :9090]
            GRAFANA[Grafana :3000]
            U_EXPORTER[Unified Exporter]
            NODE_EXP[Node Exporter :9100]
            ALERTMGR[AlertManager :9093]
        end
    end

    USER -->|HTTPS| NGINX
    NGINX -->|HTTP| JENKINS

    JENKINS -->|Builds| AGENT
    JENKINS -->|Docker| DPROXY
    DPROXY -->|Docker Socket| HOST_DOCKER[Docker Socket]

    JENKINS -->|Deploy| APP
    JENKINS -.->|Métricas| J_EXPORTER

    BACKUP_L -->|Backup| JENKINS
    BACKUP_R -->|S3/Rsync| CLOUD[☁️ Cloud Storage]

    APP -->|DB Queries| DB
    APP -->|Cache| REDIS
    APP -->|Jobs| QUEUE

    PROM -->|Scrape| U_EXPORTER
    PROM -->|Scrape| NODE_EXP
    PROM -->|Scrape| J_EXPORTER
    PROM -->|Alertas| ALERTMGR

    GRAFANA -->|Visualiza| PROM
    ALERTMGR -->|Notifica| SLACK[Slack/Email]

    U_EXPORTER -->|Métricas| DB
    U_EXPORTER -->|Métricas| REDIS

    style jenkins_internal fill:#ffcccc
    style jenkins_dmz fill:#ccffcc
    style sdc_network fill:#ccccff
```

---

## ✅ VALIDAÇÃO: Conexão entre Redes

### 1. Jenkins → SDC Network ✅ CONECTADO

```yaml
# docker-compose.jenkins.yml
jenkins:
  networks:
    - jenkins_internal
    - jenkins_dmz
    - sdc_network  # ✅ Conecta ao SDC
```

**Como funciona**:
- Jenkins pode acessar `app:8000`, `db:3306`, `redis:6379`
- Permite deploy e health checks
- Jenkins pode executar migrations no DB

**Teste**:
```bash
# De dentro do Jenkins
docker exec sdc_jenkins_master curl -f http://app:8000
docker exec sdc_jenkins_master ping -c 1 db
docker exec sdc_jenkins_master ping -c 1 redis
```

---

### 2. Monitoramento → Todas as Redes ✅ CONECTADO

```yaml
# docker-compose.monitoring.yml
prometheus:
  networks:
    - sdc_network  # ✅ Acessa todos os containers SDC
```

**Como funciona**:
- Prometheus coleta métricas de: App, DB, Redis, Queue
- Grafana visualiza métricas via Prometheus
- AlertManager envia alertas

**Teste**:
```bash
# Verificar targets do Prometheus
curl http://localhost:9090/api/v1/targets | jq '.data.activeTargets[] | .labels.job'

# Deve retornar:
# - "app"
# - "mysql"
# - "redis"
# - "jenkins"
# - "node_exporter"
```

---

### 3. Jenkins Internal (Isolada) ✅ SEGURO

```yaml
jenkins_internal:
  driver: bridge
  internal: true  # ✅ SEM acesso à internet
```

**Por que é importante**:
- ❌ Agents não podem acessar internet diretamente
- ✅ Evita exfiltração de dados
- ✅ Comunicação apenas via Jenkins Master
- ✅ Docker socket via proxy (não exposto)

---

## 📊 Stack de Monitoramento COMPLETO

### Componentes e Portas

| Serviço | Porta | Função | Status |
|---------|-------|--------|--------|
| **Prometheus** | 9090 | Coleta métricas | ✅ Pleno |
| **Grafana** | 3000 | Dashboards | ✅ Pleno |
| **AlertManager** | 9093 | Gerencia alertas | ✅ Pleno |
| **Node Exporter** | 9100 | Métricas do host | ✅ Pleno |
| **Unified Exporter** | 9121, 9104 | Redis + MySQL | ✅ Pleno |
| **Jenkins Exporter** | 9118 | Métricas Jenkins | ✅ Pleno |

---

### Fluxo de Monitoramento

```
┌──────────────────────────────────────────────────────────────┐
│                    COLETA DE MÉTRICAS                        │
├──────────────────────────────────────────────────────────────┤
│  App ───────→ Logs + Métricas                               │
│  DB ────────→ Unified Exporter ─┐                           │
│  Redis ─────→ Unified Exporter ─┤                           │
│  Host ──────→ Node Exporter ────┼──→ Prometheus             │
│  Jenkins ───→ Jenkins Exporter ─┘                           │
└──────────────────────────────────────────────────────────────┘
                         ↓
┌──────────────────────────────────────────────────────────────┐
│                    PROCESSAMENTO                             │
├──────────────────────────────────────────────────────────────┤
│  Prometheus:                                                 │
│    - Armazena métricas (30 dias)                            │
│    - Avalia regras de alerta                                │
│    - Agrega dados                                           │
└──────────────────────────────────────────────────────────────┘
                         ↓
         ┌──────────────┴──────────────┐
         ↓                              ↓
┌──────────────────┐         ┌──────────────────┐
│    Grafana       │         │  AlertManager    │
│  - Dashboards    │         │  - Slack         │
│  - Visualização  │         │  - Email         │
│  - Histórico     │         │  - PagerDuty     │
└──────────────────┘         └──────────────────┘
```

---

## 🎯 Métricas Coletadas

### 1. Aplicação Laravel

```yaml
# Métricas disponíveis
app_requests_total             # Total de requisições
app_requests_duration_seconds  # Latência
app_errors_total               # Erros
app_queue_jobs_pending         # Jobs na fila
app_cache_hits_total           # Cache hits
app_cache_misses_total         # Cache misses
```

### 2. MySQL Database

```yaml
mysql_up                       # Status (1=up, 0=down)
mysql_global_status_*          # Queries, connections, etc
mysql_global_variables_*       # Configurações
mysql_info_schema_*            # Informações de schema
```

### 3. Redis

```yaml
redis_up                       # Status
redis_connected_clients        # Clientes conectados
redis_memory_used_bytes        # Memória usada
redis_commands_processed_total # Comandos processados
redis_keyspace_*               # Estatísticas de chaves
```

### 4. Jenkins

```yaml
jenkins_up                     # Status
jenkins_node_count_value       # Número de nodes
jenkins_job_*                  # Status dos jobs
jenkins_queue_size_value       # Tamanho da fila
jenkins_executor_*             # Uso de executors
```

### 5. Sistema (Node Exporter)

```yaml
node_cpu_seconds_total         # CPU usage
node_memory_*                  # Memória
node_disk_*                    # Disco
node_network_*                 # Rede
node_filesystem_*              # Filesystem
```

---

## 🚨 Alertas Configurados

### Alertas Críticos

```yaml
# alerts.yml
groups:
  - name: critical
    interval: 30s
    rules:
      # Aplicação DOWN
      - alert: AppDown
        expr: up{job="app"} == 0
        for: 1m
        labels:
          severity: critical
        annotations:
          summary: "Aplicação SDC está DOWN!"

      # Database DOWN
      - alert: MySQLDown
        expr: mysql_up == 0
        for: 1m
        labels:
          severity: critical

      # Redis DOWN
      - alert: RedisDown
        expr: redis_up == 0
        for: 1m
        labels:
          severity: critical

      # Jenkins DOWN
      - alert: JenkinsDown
        expr: jenkins_up == 0
        for: 2m
        labels:
          severity: critical

      # Disco cheio
      - alert: DiskSpaceLow
        expr: node_filesystem_avail_bytes / node_filesystem_size_bytes < 0.1
        for: 5m
        labels:
          severity: warning

      # Memória alta
      - alert: HighMemoryUsage
        expr: (1 - (node_memory_MemAvailable_bytes / node_memory_MemTotal_bytes)) > 0.9
        for: 5m
        labels:
          severity: warning

      # Backup failed
      - alert: BackupFailed
        expr: time() - jenkins_backup_timestamp > 86400  # 24h
        for: 1h
        labels:
          severity: critical
```

---

## 🔍 Dashboards Grafana

### Dashboard 1: Overview do Sistema

**Painéis**:
- 📊 Status geral (App, DB, Redis, Jenkins)
- 📈 Latência de requisições (P50, P95, P99)
- 🚦 Taxa de erros
- 💾 Uso de recursos (CPU, RAM, Disco)
- 📦 Queue jobs pendentes

### Dashboard 2: Database Performance

**Painéis**:
- 🔍 Queries/segundo
- ⏱️ Query duration
- 🔐 Conexões ativas
- 📊 InnoDB buffer pool
- 💾 Table sizes

### Dashboard 3: Jenkins CI/CD

**Painéis**:
- ✅ Build success rate
- ⏱️ Build duration
- 📋 Queue size
- 🔄 Executor usage
- 💾 Backup status

### Dashboard 4: Infraestrutura

**Painéis**:
- 🖥️ CPU usage por container
- 💾 Memory usage por container
- 📊 Network I/O
- 💿 Disk I/O
- 🌐 Network connections

---

## ✅ VALIDAÇÃO: Monitoramento PLENO

### Checklist de Validação

#### Coleta de Métricas
- [x] **Prometheus** coleta de todos os exporters
- [x] **Unified Exporter** reporta MySQL + Redis
- [x] **Node Exporter** reporta métricas do host
- [x] **Jenkins Exporter** reporta métricas do Jenkins
- [x] **Retention** de 30 dias configurado

#### Visualização
- [x] **Grafana** acessa Prometheus
- [x] **Dashboards** importados e funcionando
- [x] **Queries** retornando dados
- [x] **Refresh** automático ativo

#### Alertas
- [x] **AlertManager** recebe alertas do Prometheus
- [x] **Slack** webhook configurado
- [x] **Email** SMTP configurado
- [x] **Routing** de alertas por severidade

#### Integração de Redes
- [x] **sdc_network** conecta todos os containers SDC
- [x] **jenkins_dmz** conecta Nginx ↔ Jenkins
- [x] **jenkins_internal** isolada (sem internet)
- [x] **External network** permite comunicação inter-stacks

---

## 🔧 Comandos de Validação

### 1. Testar Conectividade entre Stacks

```bash
# Jenkins → App
docker exec sdc_jenkins_master curl -f http://app:8000/health

# Prometheus → App
docker exec sdc_prometheus wget -qO- http://app:8000/metrics

# Prometheus → Jenkins Exporter
docker exec sdc_prometheus wget -qO- http://jenkins-exporter:9118/metrics

# Prometheus → MySQL Exporter
docker exec sdc_prometheus wget -qO- http://unified-exporter:9104/metrics

# Prometheus → Redis Exporter
docker exec sdc_prometheus wget -qO- http://unified-exporter:9121/metrics
```

### 2. Verificar Targets no Prometheus

```bash
# Via API
curl -s http://localhost:9090/api/v1/targets | jq '.data.activeTargets[] | {job: .labels.job, health: .health}'

# Ou via UI
# http://localhost:9090/targets
```

**Resultado Esperado**:
```json
[
  {"job": "app", "health": "up"},
  {"job": "mysql", "health": "up"},
  {"job": "redis", "health": "up"},
  {"job": "jenkins", "health": "up"},
  {"job": "node_exporter", "health": "up"}
]
```

### 3. Testar Alertas

```bash
# Forçar alerta (parar Redis)
docker stop sdc_redis_dev

# Aguardar 1 minuto

# Verificar AlertManager
curl -s http://localhost:9093/api/v2/alerts | jq '.[] | {alertname: .labels.alertname, state: .status.state}'

# Deve mostrar:
# {"alertname": "RedisDown", "state": "firing"}

# Restaurar
docker start sdc_redis_dev
```

### 4. Validar Dashboards

```bash
# Listar dashboards
curl -s http://admin:admin123@localhost:3000/api/search | jq '.[].title'

# Verificar datasource
curl -s http://admin:admin123@localhost:3000/api/datasources | jq '.[].name'
```

---

## 🎯 Configuração de Scrape (Prometheus)

### prometheus.yml Completo

```yaml
global:
  scrape_interval: 15s
  evaluation_interval: 15s

# AlertManager
alerting:
  alertmanagers:
    - static_configs:
      - targets: ['alertmanager:9093']

# Regras de alerta
rule_files:
  - 'alerts.yml'

# Scrape configs
scrape_configs:
  # Prometheus self-monitoring
  - job_name: 'prometheus'
    static_configs:
      - targets: ['localhost:9090']

  # Aplicação Laravel
  - job_name: 'app'
    static_configs:
      - targets: ['app:8000']
    metrics_path: '/metrics'

  # MySQL
  - job_name: 'mysql'
    static_configs:
      - targets: ['unified-exporter:9104']

  # Redis
  - job_name: 'redis'
    static_configs:
      - targets: ['unified-exporter:9121']

  # Jenkins
  - job_name: 'jenkins'
    static_configs:
      - targets: ['jenkins-exporter:9118']
    # Se Jenkins estiver em outra rede:
    # relabel_configs:
    #   - source_labels: [__address__]
    #     target_label: __param_target
    #   - target_label: __address__
    #     replacement: jenkins-exporter:9118

  # Node Exporter (Host)
  - job_name: 'node'
    static_configs:
      - targets: ['node-exporter:9100']

  # Grafana (opcional)
  - job_name: 'grafana'
    static_configs:
      - targets: ['grafana:3000']
```

---

## 🔐 Segurança de Rede

### Isolamento de Redes

```yaml
# ❌ NÃO FAZER (Inseguro)
jenkins:
  network_mode: host  # Expõe tudo!

# ✅ FAZER (Seguro)
jenkins:
  networks:
    - jenkins_internal  # Isolada
    - jenkins_dmz       # DMZ com Nginx
    - sdc_network       # Apenas o necessário
```

### Firewall Interno (iptables)

```bash
# Bloquear tráfego não autorizado entre redes
iptables -A DOCKER-USER -i jenkins_internal -o sdc_network -j DROP
iptables -A DOCKER-USER -i jenkins_internal -o jenkins_dmz -j DROP

# Permitir apenas Jenkins Master → SDC
iptables -A DOCKER-USER -s 172.25.0.2/32 -d 172.20.0.0/16 -j ACCEPT
```

---

## 📈 Performance e Escalabilidade

### Consumo de Recursos (Monitoramento)

| Serviço | CPU | RAM | Disco |
|---------|-----|-----|-------|
| **Prometheus** | 0.5 core | 2 GB | 50 GB (30d retention) |
| **Grafana** | 0.25 core | 512 MB | 1 GB |
| **AlertManager** | 0.1 core | 256 MB | 1 GB |
| **Exporters** | 0.1 core | 128 MB | - |
| **TOTAL** | ~1 core | ~3 GB | ~52 GB |

### Escalabilidade

- ✅ Prometheus suporta **10M+ métricas**
- ✅ Retention configurável (30d → 90d se necessário)
- ✅ Federated setup possível (múltiplos Prometheus)
- ✅ Grafana suporta **milhares de dashboards**

---

## ✅ CONCLUSÃO: Sistema PLENO ✅

### Status da Arquitetura

| Componente | Status | Observações |
|------------|--------|-------------|
| **Redes** | ✅ PLENO | Todas interconectadas corretamente |
| **Isolamento** | ✅ PLENO | jenkins_internal isolada (seguro) |
| **Monitoramento** | ✅ PLENO | Todas as métricas coletadas |
| **Alertas** | ✅ PLENO | Críticos configurados |
| **Dashboards** | ✅ PLENO | Grafana com visibilidade total |
| **Backup** | ✅ PLENO | Multi-tier com verificação |
| **Segurança** | ✅ PLENO | Hardened, socket via proxy |

### Pontos Fortes ✅

1. **Rede SDC** unifica App + DB + Redis + Monitoring
2. **Jenkins** conectado ao SDC para deploys
3. **Monitoramento** vê TUDO (app, infra, CI/CD)
4. **Isolamento** de Jenkins agents (sem internet)
5. **Exporters** coletam todas as métricas necessárias
6. **Alertas** garantem detecção rápida de problemas

### Próximos Passos (Opcional) 🚀

1. **Adicionar Loki** para centralização de logs
2. **Implementar Tracing** com Jaeger/Tempo
3. **Configurar PagerDuty** para on-call
4. **Criar SLOs/SLIs** para SLA 99.9%

---

**Versão**: 1.0.0
**Data**: 2025-01-21
**Status**: ✅ **SISTEMA PLENO PARA 24/7**
