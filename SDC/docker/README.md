# 🐳 Docker - SDC (Sistema de Defesa Civil)

## 📋 Visão Geral

Esta pasta contém toda a infraestrutura Docker do projeto SDC, incluindo:

- **Dockerfiles** para desenvolvimento e produção
- **Docker Compose** para orquestração de serviços
- **Configurações** de Nginx, PHP, MySQL, Redis
- **Stack de Monitoramento** (Prometheus, Grafana, Alertmanager)
- **Scripts** de automação e utilidades

---

## 🚀 Quick Start

### Opção 1: Makefile (Recomendado)

```bash
# Setup completo com um único comando
make setup

# Ou apenas desenvolvimento
make dev

# Ver todas opções
make help
```

### Opção 2: Docker Compose Direto

```bash
# Desenvolvimento
docker compose -f docker/docker-compose.yml up -d

# Produção
docker compose -f docker/docker-compose.prod.yml up -d
```

---

## 📁 Estrutura de Arquivos

```
docker/
├── docker-compose.yml              # Compose desenvolvimento
├── docker-compose.prod.yml         # Compose produção
├── docker-compose.jenkins.yml      # Compose Jenkins CI/CD
├── docker-compose.ssr.yml          # Compose SSR (Server-Side Rendering)
│
├── Dockerfile.dev                  # Dockerfile desenvolvimento
├── Dockerfile.prod                 # Dockerfile produção
├── Dockerfile.queue                # Dockerfile queue worker
│
├── config/                         # Configurações
│   ├── nginx/
│   │   ├── dev.conf               # Nginx desenvolvimento
│   │   └── prod.conf              # Nginx produção
│   ├── php/
│   │   ├── dev.ini                # PHP desenvolvimento
│   │   ├── prod.ini               # PHP produção
│   │   └── xdebug.ini             # Xdebug
│   ├── php-fpm/
│   │   ├── dev.conf               # PHP-FPM desenvolvimento
│   │   └── prod.conf              # PHP-FPM produção
│   └── mysql/
│       ├── dev.cnf                # MySQL desenvolvimento
│       └── prod.cnf               # MySQL produção
│
├── monitoring/                     # Stack de monitoramento
│   ├── prometheus/
│   │   ├── prometheus.yml         # Config Prometheus
│   │   └── alerts/                # Regras de alerta
│   ├── grafana/
│   │   ├── provisioning/          # Provisioning
│   │   └── dashboards/            # Dashboards
│   ├── alertmanager/
│   │   └── alertmanager.yml       # Config Alertmanager
│   └── blackbox/
│       └── blackbox.yml           # Config Blackbox Exporter
│
├── scripts/                        # Scripts
│   ├── entrypoint.dev.sh          # Entrypoint desenvolvimento
│   ├── entrypoint.prod.sh         # Entrypoint produção
│   ├── healthcheck.sh             # Healthcheck
│   └── backup.sh                  # Backup automatizado
│
├── logs/                          # Logs (gitignored)
│   ├── nginx/
│   ├── php/
│   ├── queue/
│   └── monitoring/
│
├── MAKEFILE_GUIDE.md              # Guia do Makefile
└── README.md                      # Este arquivo
```

---

## 🎯 Serviços Disponíveis

### Aplicação (Desenvolvimento)

| Serviço | Porta | URL | Descrição |
|---------|-------|-----|-----------|
| **App (Laravel)** | 8001 | http://localhost:8001 | Aplicação Laravel |
| **Nginx** | 8082 | http://localhost:8082 | Reverse Proxy |
| **Vite (HMR)** | 5173 | http://localhost:5173 | Hot Module Replacement |

### Banco de Dados

| Serviço | Porta | URL | Credenciais |
|---------|-------|-----|-------------|
| **MySQL** | 3307 | localhost:3307 | user: `sdc`, pass: `secret` |
| **Redis** | 6380 | localhost:6380 | - |
| **phpMyAdmin** | 8083 | http://localhost:8083 | root/root |
| **Redis Commander** | 8084 | http://localhost:8084 | - |

### Ferramentas

| Serviço | Porta | URL | Descrição |
|---------|-------|-----|-----------|
| **Mailhog** | 8026 | http://localhost:8026 | Email testing |

### Monitoramento

| Serviço | Porta | URL | Credenciais |
|---------|-------|-----|-------------|
| **Grafana** | 3000 | http://localhost:3000 | admin/admin@123 |
| **Prometheus** | 9090 | http://localhost:9090 | - |
| **Alertmanager** | 9093 | http://localhost:9093 | - |
| **cAdvisor** | 8080 | http://localhost:8080 | - |

### Exporters (Métricas)

| Exporter | Porta | Endpoint |
|----------|-------|----------|
| Node Exporter | 9100 | http://localhost:9100/metrics |
| MySQL Exporter | 9104 | http://localhost:9104/metrics |
| Redis Exporter | 9121 | http://localhost:9121/metrics |
| Nginx Exporter | 9113 | http://localhost:9113/metrics |
| Blackbox Exporter | 9115 | http://localhost:9115/metrics |

---

## 🔧 Uso com Makefile

### Comandos Principais

```bash
# Build e setup inicial
make build-all        # Build completo
make setup            # Setup inicial completo

# Desenvolvimento
make dev              # Ambiente padrão
make dev-full         # Com todas ferramentas
make dev-logs         # Ver logs

# Monitoramento
make monitor          # Subir monitoramento
make monitor-down     # Parar monitoramento

# Banco de dados
make db-migrate       # Migrations
make db-seed          # Seeds
make db-backup        # Backup

# Limpeza
make clean            # Parar containers
make clean-volumes    # Remover volumes

# Info
make status           # Status
make urls             # URLs disponíveis
make help             # Ajuda completa
```

**📖 Para guia completo:** veja [MAKEFILE_GUIDE.md](./MAKEFILE_GUIDE.md)

---

## 🐳 Uso com Docker Compose

### Desenvolvimento

```bash
# Subir ambiente completo
docker compose -f docker/docker-compose.yml up -d

# Subir com ferramentas (phpmyadmin, redis-commander)
docker compose -f docker/docker-compose.yml --profile tools up -d

# Ver logs
docker compose -f docker/docker-compose.yml logs -f

# Parar
docker compose -f docker/docker-compose.yml down

# Parar e remover volumes
docker compose -f docker/docker-compose.yml down -v
```

### Produção

```bash
# Subir produção
docker compose -f docker/docker-compose.prod.yml up -d

# Build e subir
docker compose -f docker/docker-compose.prod.yml up -d --build

# Ver logs
docker compose -f docker/docker-compose.prod.yml logs -f

# Parar
docker compose -f docker/docker-compose.prod.yml down
```

### Monitoramento

```bash
# Subir apenas monitoramento
docker compose -f docker/docker-compose.yml up -d \
  prometheus grafana alertmanager \
  node-exporter cadvisor \
  mysql-exporter redis-exporter nginx-exporter blackbox-exporter

# Parar monitoramento
docker compose -f docker/docker-compose.yml stop \
  prometheus grafana alertmanager \
  node-exporter cadvisor \
  mysql-exporter redis-exporter nginx-exporter blackbox-exporter
```

---

## 🏗️ Arquitetura

### Desenvolvimento

```
┌─────────────────────────────────────────────────────────┐
│                     Docker Network                      │
│                     (sdc_network)                       │
│                                                         │
│  ┌──────────┐  ┌─────────┐  ┌────────┐  ┌──────────┐ │
│  │  Nginx   │─▶│   App   │─▶│  MySQL │  │  Redis   │ │
│  │  :8082   │  │  :8001  │  │  :3307 │  │  :6380   │ │
│  └──────────┘  └─────────┘  └────────┘  └──────────┘ │
│                     │                                   │
│                     ▼                                   │
│  ┌──────────┐  ┌─────────┐  ┌────────┐                │
│  │   Node   │  │  Queue  │  │Mailhog │                │
│  │  :5173   │  │ Worker  │  │ :8026  │                │
│  └──────────┘  └─────────┘  └────────┘                │
└─────────────────────────────────────────────────────────┘
```

### Monitoramento

```
┌─────────────────────────────────────────────────────────┐
│                   Monitoring Stack                      │
│                                                         │
│  ┌──────────┐  ┌────────────┐  ┌──────────────┐      │
│  │ Grafana  │◀─│ Prometheus │◀─│ Alertmanager │      │
│  │  :3000   │  │   :9090    │  │    :9093     │      │
│  └──────────┘  └────────────┘  └──────────────┘      │
│                     ▲                                   │
│                     │                                   │
│  ┌──────────┐  ┌────────┐  ┌────────┐  ┌──────────┐ │
│  │ cAdvisor │  │ Node   │  │ MySQL  │  │  Redis   │ │
│  │  :8080   │  │  Exp   │  │  Exp   │  │   Exp    │ │
│  └──────────┘  └────────┘  └────────┘  └──────────┘ │
│                                                         │
│  ┌──────────┐  ┌──────────┐                           │
│  │  Nginx   │  │Blackbox  │                           │
│  │   Exp    │  │   Exp    │                           │
│  └──────────┘  └──────────┘                           │
└─────────────────────────────────────────────────────────┘
```

---

## 📊 Volumes

### Volumes Nomeados

```yaml
newsdc_db_data:           # Dados MySQL
newsdc_redis_data:        # Dados Redis
newsdc_vendor:            # Vendor PHP (performance)
newsdc_node_modules:      # Node modules (performance)
newsdc_prometheus_data:   # Dados Prometheus
newsdc_grafana_data:      # Dados Grafana
newsdc_alertmanager_data: # Dados Alertmanager
```

### Bind Mounts

```yaml
../:/var/www                        # Código fonte
./nginx/dev.conf:/etc/nginx/...     # Config Nginx
./config/php/dev.ini:/usr/local/... # Config PHP
./logs/:/var/log/...                # Logs
```

---

## 🔒 Segurança

### Desenvolvimento

- Xdebug habilitado
- Logs verbose
- Permissões relaxadas
- Ports expostos

### Produção

- Xdebug desabilitado
- OPcache habilitado
- Logs mínimos
- Usuário não-root
- Secrets via environment
- Network isolada

---

## 🧪 Testes

```bash
# Via Makefile
make test
make test-coverage

# Via Docker Compose
docker compose -f docker/docker-compose.yml exec app php artisan test
```

---

## 📝 Logs

### Localização

```
docker/logs/
├── nginx/        # Logs Nginx
├── php/          # Logs PHP-FPM
├── queue/        # Logs Queue Worker
└── monitoring/   # Logs Monitoramento
```

### Visualizar Logs

```bash
# Via Makefile
make logs         # Todos
make logs-app     # App
make logs-nginx   # Nginx
make logs-db      # Database

# Via Docker
docker compose -f docker/docker-compose.yml logs -f app
docker compose -f docker/docker-compose.yml logs -f --tail=100 nginx
```

---

## 🔄 CI/CD

### Jenkins

```bash
# Subir Jenkins
docker compose -f docker/docker-compose.jenkins.yml up -d

# Acessar
# http://localhost:8080
```

---

## 🆘 Troubleshooting

### Container não inicia

```bash
# Ver status
make status

# Ver logs
make logs-app

# Rebuild
make dev-build
```

### Erro de porta em uso

```bash
# Verificar portas
sudo lsof -i :8001
sudo lsof -i :3307

# Parar container conflitante
docker ps
docker stop <container-id>
```

### Problemas de permissão

```bash
# Verificar UID/GID
id -u
id -g

# Rebuild com UID/GID corretos
HOST_UID=$(id -u) HOST_GID=$(id -g) make build-all
```

### Network issues

```bash
# Remover e recriar network
make network-clean
make network-init
make dev
```

### Limpar tudo e recomeçar

```bash
# ⚠️ CUIDADO: Apaga TUDO!
make nuke
make setup
```

---

## 🔧 Customização

### Variáveis de Ambiente

Crie um arquivo `.env` na raiz do projeto:

```env
# Database
DB_DATABASE=sdc
DB_USERNAME=sdc
DB_PASSWORD=secret
DB_ROOT_PASSWORD=root

# Redis
REDIS_PASSWORD=

# Grafana
GRAFANA_ADMIN_PASSWORD=admin@123

# Host
HOST_UID=1000
HOST_GID=1000
```

### Override de Configurações

Crie `docker-compose.override.yml`:

```yaml
version: '3.8'

services:
  app:
    environment:
      - CUSTOM_VAR=value
    ports:
      - "9000:8000"
```

---

## 📚 Recursos Adicionais

### Documentação

- [MAKEFILE_GUIDE.md](./MAKEFILE_GUIDE.md) - Guia completo do Makefile
- [../Justfile](../Justfile) - Comandos de banco de dados

### Links Úteis

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Laravel Docker Best Practices](https://laravel.com/docs/deployment)
- [Prometheus Documentation](https://prometheus.io/docs/)
- [Grafana Documentation](https://grafana.com/docs/)

---

## ✅ Checklist

### Primeiro Setup

- [ ] Clonar repositório
- [ ] Criar arquivo `.env`
- [ ] Executar `make setup`
- [ ] Verificar `make status`
- [ ] Acessar http://localhost:8001
- [ ] Testar `make health`

### Uso Diário

- [ ] `make dev` - Subir ambiente
- [ ] `make logs-app` - Monitorar
- [ ] Desenvolver...
- [ ] `make test` - Testar
- [ ] `make clean` - Parar

### Deploy Produção

- [ ] `make db-backup` - Backup
- [ ] `make test` - Testes
- [ ] `make deploy-prod` - Deploy
- [ ] `make health` - Verificar

---

**Desenvolvido para SDC - Sistema de Defesa Civil**

Para dúvidas ou problemas, execute: `make help`
