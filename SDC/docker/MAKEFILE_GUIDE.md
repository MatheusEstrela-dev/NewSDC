# 🚀 Makefile - Guia de Uso Rápido

## 📋 Comandos Essenciais

### 🎯 ONE-COMMAND: Build e Deploy Completo

```bash
# Build completo de TODA a stack
make build-all

# Subir TODA a stack (dev + monitoring + tools)
make up-all

# Setup inicial completo (build + up + migrate + optimize)
make setup
```

---

## 🔧 Desenvolvimento

```bash
make dev              # Ambiente padrão
make dev-full         # Com todas ferramentas (phpmyadmin, redis-commander)
make dev-minimal      # Mínimo (app + db + redis)
make dev-logs         # Ver logs
make dev-ps           # Status
```

---

## 🏭 Produção

```bash
make prod             # Iniciar produção
make prod-build       # Build e deploy
make prod-logs        # Logs
make deploy-prod      # Deploy completo
```

---

## 📊 Monitoramento

```bash
make monitor          # Subir stack de monitoramento
make monitor-down     # Parar monitoramento
make monitor-logs     # Ver logs
```

**URLs após `make monitor`:**
- Grafana: http://localhost:3000 (admin/admin@123)
- Prometheus: http://localhost:9090
- Alertmanager: http://localhost:9093

---

## 💾 Banco de Dados

```bash
make db-migrate       # Executar migrations
make db-rollback      # Rollback
make db-fresh         # Fresh + seed (⚠️ APAGA DADOS!)
make db-seed          # Seeds
make db-backup        # Criar backup
make db-restore FILE=arquivo.sql
```

---

## 🎨 Frontend

```bash
make npm-install      # Install dependências
make npm-dev          # Dev server
make npm-build        # Build produção
```

---

## 🐚 Acesso & Debug

```bash
make shell            # Shell app
make shell-root       # Shell root
make shell-db         # MySQL CLI
make shell-redis      # Redis CLI
make cache-clear      # Limpar caches
```

---

## 📋 Status & Info

```bash
make status           # Status completo
make urls             # Mostrar URLs
make logs             # Logs (todos)
make logs-app         # Logs app
make health           # Healthcheck
make info             # Informações
```

---

## 🗑️ Limpeza

```bash
make clean            # Para containers
make clean-volumes    # Remove volumes (⚠️ APAGA DADOS!)
make clean-system     # Limpeza Docker
make nuke             # DESTRUIÇÃO TOTAL (⚠️)
```

---

## 🔄 Workflows Completos

```bash
make setup            # Setup inicial completo
make reset            # Reset completo (⚠️ APAGA DADOS!)
make restart-all      # Reiniciar tudo
```

---

## 🎯 Uso Diário - Exemplos Práticos

### Primeiro Setup
```bash
cd SDC
make setup
# Aguarde... e acesse http://localhost:8001
```

### Desenvolvimento
```bash
make dev              # Subir
make logs-app         # Ver logs
make shell            # Debug
make clean            # Parar
```

### Deploy
```bash
make db-backup        # Backup primeiro!
make deploy-prod      # Deploy
make health           # Verificar
```

---

## 🆘 Troubleshooting

```bash
# Container não inicia
make status
make logs-app
make dev-build

# Problemas de cache
make cache-clear
make build-clean

# Reset total
make nuke
make setup
```

---

## 📖 Ajuda Completa

```bash
make help             # Lista TODOS os comandos disponíveis
make                  # Mesmo que 'make help'
```

---

**SDC - Sistema de Defesa Civil**
