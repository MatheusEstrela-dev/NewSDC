# 📁 Localização de Arquivos - SDC

> **Guia rápido para encontrar arquivos importantes do projeto**

---

## 🎯 Arquitetura de Pastas

```
New_SDC/
├── SDC/                          # 🚀 APLICAÇÃO PRINCIPAL
│   ├── docker/                   # 🐳 TODOS os arquivos Docker aqui!
│   │   ├── docker-compose.yml              # App Dev
│   │   ├── docker-compose.prod.yml         # App Produção
│   │   ├── docker-compose.monitoring.yml   # Monitoring Stack
│   │   ├── docker-compose.jenkins.yml      # ✅ Jenkins CI/CD (AQUI!)
│   │   ├── jenkins/                        # Configs Jenkins
│   │   │   ├── Dockerfile
│   │   │   ├── Dockerfile.backup
│   │   │   ├── nginx.conf
│   │   │   ├── casc.yaml
│   │   │   └── scripts/
│   │   │       ├── backup-local.sh
│   │   │       ├── backup-remote.sh
│   │   │       └── restore.sh
│   │   └── monitoring/                     # Configs Prometheus/Grafana
│   ├── Jenkinsfile                # Pipeline CI/CD
│   ├── Makefile                   # Comandos Docker
│   └── Justfile                   # Comandos Database
│
├── Doc/                          # 📚 DOCUMENTAÇÃO
│   ├── JENKINS_PIPELINE.md       # Doc do pipeline
│   ├── JENKINS_SETUP_24-7.md     # Setup para produção
│   ├── AUDITORIA_PROBLEMAS_CRITICOS.md
│   ├── ARQUITETURA_REDE_MONITORAMENTO.md
│   ├── SUMARIO_ARQUITETURA_CRITICA.md
│   └── JUSTFILE_DATABASE.md      # Doc do Justfile
│
└── task002.md                    # Problemas conhecidos
```

---

## 🐳 Docker Compose Files

### ✅ CORRETO: Todos na pasta `SDC/docker/`

| Arquivo | Finalidade | Uso |
|---------|-----------|-----|
| **docker-compose.yml** | App Dev | `cd SDC/docker && docker compose up -d` |
| **docker-compose.prod.yml** | App Produção | `cd SDC/docker && docker compose -f docker-compose.prod.yml up -d` |
| **docker-compose.monitoring.yml** | Monitoring | `cd SDC/docker && docker compose -f docker-compose.yml -f docker-compose.monitoring.yml up -d` |
| **docker-compose.jenkins.yml** | Jenkins CI/CD | `cd SDC/docker && docker compose -f docker-compose.jenkins.yml up -d` |

### ❌ INCORRETO: ~~Na raiz do projeto~~

```bash
# ❌ DELETADO!
/docker-compose.jenkins.yml  # Estava duplicado na raiz
```

**Motivo**: Centralização e organização

---

## 🚀 Como Usar os Compose Files

### 1. Aplicação (Dev)

```bash
cd SDC/docker
docker compose up -d
```

### 2. Aplicação (Produção)

```bash
cd SDC/docker
docker compose -f docker-compose.prod.yml up -d
```

### 3. Monitoring Stack

```bash
cd SDC/docker
docker compose -f docker-compose.yml -f docker-compose.monitoring.yml up -d
```

### 4. Jenkins CI/CD ⭐

```bash
cd SDC/docker
docker compose -f docker-compose.jenkins.yml up -d
```

---

## 📚 Documentação

### Guias de Setup

| Documento | Finalidade |
|-----------|-----------|
| [JENKINS_SETUP_24-7.md](./JENKINS_SETUP_24-7.md) | Setup completo Jenkins para produção |
| [JENKINS_PIPELINE.md](./JENKINS_PIPELINE.md) | Documentação do pipeline CI/CD |
| [JUSTFILE_DATABASE.md](./JUSTFILE_DATABASE.md) | Comandos de banco de dados |
| [ARQUITETURA_REDE_MONITORAMENTO.md](./ARQUITETURA_REDE_MONITORAMENTO.md) | Topologia de redes e monitoring |

### Análises e Auditorias

| Documento | Finalidade |
|-----------|-----------|
| [AUDITORIA_PROBLEMAS_CRITICOS.md](./AUDITORIA_PROBLEMAS_CRITICOS.md) | Validação contra problemas conhecidos |
| [SUMARIO_ARQUITETURA_CRITICA.md](./SUMARIO_ARQUITETURA_CRITICA.md) | Sumário executivo da arquitetura |
| [ARQUITETURA_BACKUP_JENKINS_REVIEW.md](./ARQUITETURA_BACKUP_JENKINS_REVIEW.md) | Análise de backup e recovery |

---

## 🔧 Comandos Úteis

### Makefile (Comandos Gerais)

```bash
cd SDC
make dev          # Iniciar dev
make dev-monitoring  # Dev + Monitoring
make prod         # Iniciar produção
make migrate      # Executar migrations
make test         # Executar testes
```

### Justfile (Comandos de Database)

```bash
cd SDC
just migrate      # Executar migrations
just rollback     # Reverter migrations
just fresh        # Reset banco com seeds
just backup manual  # Criar backup manual
just status       # Ver status do banco
```

---

## 🎯 Comandos Jenkins

### Iniciar Jenkins

```bash
cd SDC/docker
docker compose -f docker-compose.jenkins.yml up -d
```

### Ver Logs

```bash
docker compose -f docker-compose.jenkins.yml logs -f jenkins
```

### Backup Manual

```bash
docker compose -f docker-compose.jenkins.yml exec backup-local \
  /scripts/backup-local.sh
```

### Restore

```bash
docker compose -f docker-compose.jenkins.yml exec backup-local \
  /scripts/restore.sh /backups/jenkins-YYYYMMDD_HHMMSS.tar.gz
```

---

## 🗂️ Estrutura de Volumes

```
SDC/docker/
├── jenkins_home/         # Dados do Jenkins (persistente)
├── jenkins_backups/      # Backups locais
├── monitoring/           # Configs Prometheus/Grafana
│   ├── prometheus/
│   │   ├── prometheus.yml
│   │   └── alerts.yml
│   ├── grafana/
│   │   ├── provisioning/
│   │   └── dashboards/
│   └── alertmanager/
│       └── alertmanager.yml
└── logs/                 # Logs dos containers
    ├── nginx/
    └── php/
```

---

## ⚠️ IMPORTANTE: Não Comitar

Adicione ao `.gitignore`:

```gitignore
# Jenkins
jenkins_home/
jenkins_backups/
jenkins_agent_workdir/

# Monitoramento
prometheus_data/
grafana_data/
alertmanager_data/

# Secrets
.env
*.pem
*.key

# Logs
logs/
*.log
```

---

## 🔄 Migração de Arquivos

### Se você tinha arquivos na raiz:

```bash
# ❌ Antes (Errado)
/docker-compose.jenkins.yml
/docker-compose.monitoring.yml

# ✅ Agora (Correto)
/SDC/docker/docker-compose.jenkins.yml
/SDC/docker/docker-compose.monitoring.yml
```

### Comandos Atualizados

```bash
# ❌ Antes
docker compose -f docker-compose.jenkins.yml up -d

# ✅ Agora
cd SDC/docker
docker compose -f docker-compose.jenkins.yml up -d
```

---

## 📋 Checklist de Validação

Após clonar o repositório, verifique:

- [ ] Existe `SDC/docker/docker-compose.yml`
- [ ] Existe `SDC/docker/docker-compose.prod.yml`
- [ ] Existe `SDC/docker/docker-compose.monitoring.yml`
- [ ] Existe `SDC/docker/docker-compose.jenkins.yml`
- [ ] Existe `SDC/Jenkinsfile`
- [ ] Existe `SDC/Makefile`
- [ ] Existe `SDC/Justfile`
- [ ] NÃO existe `docker-compose.jenkins.yml` na raiz
- [ ] Pasta `Doc/` contém todas as documentações

---

**Versão**: 1.0.0
**Data**: 2025-01-30
**Última atualização**: Centralização de docker-compose files
