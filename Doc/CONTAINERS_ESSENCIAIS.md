# 🐳 Guia de Containers - SDC

## ❌ NUNCA DESLIGAR (Aplicação não funciona sem)

```bash
# 3 containers críticos - 443MB RAM
sdc_app_dev      # Laravel + Octane - APLICAÇÃO PRINCIPAL
sdc_db_dev       # MySQL - BANCO DE DADOS
sdc_redis_dev    # Redis - CACHE + FILAS
```

**Se desligar qualquer um destes, a aplicação PARA de funcionar.**

---

## ⚠️ ESSENCIAL PARA PRODUÇÃO (Funciona sem, mas não recomendado)

```bash
# 1 container - 10MB RAM
sdc_nginx_dev    # Web Server - Proxy Reverso
```

**Sem ele:**
- ❌ Não acessa via porta 80/443
- ❌ Sem SSL/TLS
- ✅ Mas app funciona via `http://localhost:8000`

---

## 📧 DESENVOLVIMENTO (Pode desligar tranquilo)

```bash
# 1 container - 2MB RAM
sdc_mailhog_dev  # Servidor de email para testes
```

**Sem ele:**
- ✅ App funciona normalmente
- ❌ Emails de teste não aparecem no Mailhog
- ℹ️ Emails ainda são "enviados" (vão para log)

**Comando para desligar:**
```bash
docker compose -f docker/docker-compose.yml stop mailhog
```

---

## 📊 MONITORING STACK (Pode desligar tudo)

```bash
# 5 containers - 171MB RAM
sdc_prometheus       # Coleta de métricas
sdc_grafana          # Dashboards visuais
sdc_alertmanager     # Sistema de alertas
sdc_redis_exporter   # Métricas do Redis
sdc_node_exporter    # Métricas do sistema
```

**Sem eles:**
- ✅ Aplicação funciona 100%
- ❌ Perde Grafana (http://localhost:3000)
- ❌ Perde Prometheus (http://localhost:9090)
- ❌ Perde alertas automáticos
- ❌ Perde Health Dashboard visual

**Comando para desligar tudo:**
```bash
docker compose -f docker/docker-compose.yml -f docker/docker-compose.monitoring.yml stop prometheus grafana alertmanager redis-exporter node-exporter
```

**Economia: ~171MB RAM**

---

## 🎯 CENÁRIOS DE USO

### Cenário 1: DESENVOLVIMENTO COMPLETO (Atual - Recomendado)
```bash
# Todos containers rodando
Total: ~626MB RAM

✅ Aplicação funcionando
✅ Monitoring completo
✅ Health Dashboard
✅ Grafana + Prometheus
✅ Teste de emails
```

### Cenário 2: DESENVOLVIMENTO SIMPLES
```bash
# Sem monitoring
docker compose -f docker/docker-compose.yml -f docker/docker-compose.monitoring.yml stop prometheus grafana alertmanager redis-exporter node-exporter

Total: ~455MB RAM

✅ Aplicação funcionando
✅ Teste de emails
❌ Sem monitoring
```

### Cenário 3: MÍNIMO ESSENCIAL
```bash
# Apenas app, db, redis
docker compose -f docker/docker-compose.yml stop nginx mailhog
docker compose -f docker/docker-compose.yml -f docker/docker-compose.monitoring.yml stop prometheus grafana alertmanager redis-exporter node-exporter

Total: ~443MB RAM

✅ Aplicação funcionando (via :8000)
❌ Sem porta 80
❌ Sem monitoring
❌ Sem teste de emails
```

---

## 📊 TABELA RESUMO

| Container | Tipo | RAM | Pode Desligar? | Consequência |
|-----------|------|-----|----------------|--------------|
| sdc_app_dev | ❌ CRÍTICO | 65MB | **NÃO** | App para |
| sdc_db_dev | ❌ CRÍTICO | 374MB | **NÃO** | App para |
| sdc_redis_dev | ❌ CRÍTICO | 4MB | **NÃO** | App para |
| sdc_nginx_dev | ⚠️ Importante | 10MB | Sim | Perde porta 80 |
| sdc_mailhog_dev | 📧 Dev | 2MB | ✅ Sim | Perde teste email |
| sdc_prometheus | 📊 Monitoring | 40MB | ✅ Sim | Perde métricas |
| sdc_grafana | 📊 Monitoring | 99MB | ✅ Sim | Perde dashboards |
| sdc_alertmanager | 📊 Monitoring | 12MB | ✅ Sim | Perde alertas |
| sdc_redis_exporter | 📊 Monitoring | 10MB | ✅ Sim | Perde métricas Redis |
| sdc_node_exporter | 📊 Monitoring | 10MB | ✅ Sim | Perde métricas host |

---

## 🚀 COMANDOS RÁPIDOS

### Ver status atual:
```bash
docker compose -f docker/docker-compose.yml -f docker/docker-compose.monitoring.yml ps
```

### Reiniciar tudo:
```bash
docker compose -f docker/docker-compose.yml -f docker/docker-compose.monitoring.yml restart
```

### Parar tudo:
```bash
docker compose -f docker/docker-compose.yml -f docker/docker-compose.monitoring.yml down
```

### Iniciar tudo:
```bash
docker compose -f docker/docker-compose.yml -f docker/docker-compose.monitoring.yml up -d
```

### Parar apenas monitoring:
```bash
docker compose -f docker/docker-compose.yml -f docker/docker-compose.monitoring.yml stop prometheus grafana alertmanager redis-exporter node-exporter
```

### Iniciar apenas monitoring:
```bash
docker compose -f docker/docker-compose.yml -f docker/docker-compose.monitoring.yml up -d prometheus grafana alertmanager redis-exporter node-exporter
```

---

## ✅ RECOMENDAÇÃO FINAL

**Para desenvolvimento: MANTER TUDO RODANDO**

Motivos:
- 626MB é muito pouco (4% da RAM disponível)
- Monitoring é essencial para ver performance
- Health Dashboard é muito útil
- Grafana ajuda a identificar problemas

**Economizar 171MB não vale a pena perder todo o monitoring!**

---

**Atualizado:** 2025-11-27
