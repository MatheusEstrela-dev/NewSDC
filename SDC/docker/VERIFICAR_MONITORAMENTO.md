# 🔍 Como Verificar a Implementação do Monitoramento

## 1️⃣ Iniciar o Stack

```bash
cd /home/matheus/Documentos/NewSDC/SDC/docker
docker compose -f docker-compose.monitoring.yml up -d
```

## 2️⃣ Verificar Status dos Containers

```bash
docker compose -f docker-compose.monitoring.yml ps
```

Ou:

```bash
docker ps --filter "name=newsdc" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
```

**Containers esperados (10):**
- ✅ newsdc_prometheus
- ✅ newsdc_grafana
- ✅ newsdc_alertmanager
- ✅ newsdc_node_exporter
- ✅ newsdc_cadvisor
- ✅ newsdc_mysql_exporter
- ✅ newsdc_redis_exporter
- ✅ newsdc_nginx_exporter
- ✅ newsdc_blackbox_exporter

## 3️⃣ Acessar as Interfaces Web

### Grafana (Principal)
- **URL:** http://localhost:3000
- **User:** admin
- **Password:** admin@123

**O que fazer:**
1. Login
2. Ir em Dashboards → Import
3. Importar dashboard ID **1860** (Node Exporter Full)
4. Importar dashboard ID **193** (Docker Containers)
5. Ver métricas em tempo real

### Prometheus (Métricas Brutas)
- **URL:** http://localhost:9090
- **Queries para testar:**

```promql
# CPU Usage
100 - (avg(rate(node_cpu_seconds_total{mode="idle"}[5m])) * 100)

# Memória Usada %
(1 - (node_memory_MemAvailable_bytes / node_memory_MemTotal_bytes)) * 100

# Requisições por segundo (Nginx)
sum(rate(nginx_http_requests_total[5m]))

# Taxa de Erro HTTP
sum(rate(nginx_http_requests_total{status=~"5.."}[5m])) / sum(rate(nginx_http_requests_total[5m])) * 100
```

### Alertmanager (Alertas)
- **URL:** http://localhost:9093
- Ver alertas ativos e configurações de roteamento

### cAdvisor (Containers)
- **URL:** http://localhost:8080
- Métricas detalhadas de cada container

## 4️⃣ Verificar Exporters

Teste cada exporter individualmente:

```bash
# Node Exporter (métricas do host)
curl http://localhost:9100/metrics | head -20

# MySQL Exporter
curl http://localhost:9104/metrics | head -20

# Redis Exporter
curl http://localhost:9121/metrics | head -20

# Nginx Exporter
curl http://localhost:9113/metrics | head -20
```

## 5️⃣ Verificar Targets no Prometheus

1. Acesse: http://localhost:9090/targets
2. Todos devem estar **UP** (verde)
3. Se algum estiver DOWN (vermelho), verificar logs:

```bash
# Ver logs do Prometheus
docker logs newsdc_prometheus

# Ver logs de um exporter específico
docker logs newsdc_mysql_exporter
```

## 6️⃣ Testar Alertas

Acesse: http://localhost:9090/alerts

Você deve ver:
- ✅ **DeadMansSwitch** - FIRING (sempre ativo)
- Outros alertas em estado PENDING ou OK

## 7️⃣ Verificar Dashboards Criados

No Grafana, buscar por:
- "Golden Signals" (dashboard automático criado)

Ou importar prontos:

| ID | Dashboard | Descrição |
|----|-----------|-----------|
| 1860 | Node Exporter Full | CPU, Memória, Disco, Rede (ESSENCIAL) |
| 193 | Docker Containers | Métricas de containers |
| 7362 | MySQL Overview | Banco de dados |
| 11835 | Redis Dashboard | Cache |
| 12708 | Nginx | Web server |

## 8️⃣ Logs para Troubleshooting

```bash
# Ver logs de todos os serviços
docker compose -f docker-compose.monitoring.yml logs -f

# Logs específicos
docker logs -f newsdc_prometheus
docker logs -f newsdc_grafana
docker logs -f newsdc_alertmanager
```

## 9️⃣ Parar o Stack

```bash
docker compose -f docker-compose.monitoring.yml down
```

## 🔟 Rebuildar (se necessário)

```bash
docker compose -f docker-compose.monitoring.yml down -v
docker compose -f docker-compose.monitoring.yml up -d --force-recreate
```

---

## ✅ Checklist de Verificação

- [ ] Todos os 10 containers estão UP
- [ ] Prometheus acessível (port 9090)
- [ ] Grafana acessível (port 3000)
- [ ] Alertmanager acessível (port 9093)
- [ ] Todos os targets UP no Prometheus
- [ ] Dashboard Node Exporter importado
- [ ] DeadMansSwitch FIRING
- [ ] Métricas aparecendo no Grafana

---

**Se tudo estiver ✅ verde, o sistema está OPERACIONAL! 🎉**
