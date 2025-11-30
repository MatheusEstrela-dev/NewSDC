# 🚀 Setup Jenkins para Sistema Crítico 24/7

> **Guia completo de implantação para ambiente de produção**

---

## ⚠️ IMPORTANTE - Leia Antes de Começar!

Este setup foi projetado para sistemas **CRÍTICOS 24/7** com:
- ✅ **Backup multi-tier** com verificação automática
- ✅ **Segurança hardened** (docker-socket-proxy, read-only FS)
- ✅ **Monitoramento** via Prometheus + Grafana
- ✅ **Alta disponibilidade** (preparado para failover)
- ✅ **Zero downtime** em manutenções

---

## 📋 Pré-requisitos

### Sistema Operacional
- **Ubuntu 20.04+** ou **Debian 11+**
- **CentOS 8+** ou **RHEL 8+**

### Recursos Mínimos (Produção)
| Recurso | Mínimo | Recomendado |
|---------|--------|-------------|
| **CPU** | 4 cores | 8 cores |
| **RAM** | 8 GB | 16 GB |
| **Disco (SSD)** | 100 GB | 500 GB |
| **Disco (Backup)** | 500 GB | 2 TB |
| **Network** | 100 Mbps | 1 Gbps |

### Software
- **Docker** 24.0+ ([instalar](https://docs.docker.com/engine/install/))
- **Docker Compose** 2.20+ ([instalar](https://docs.docker.com/compose/install/))
- **Git** 2.30+
- **Bash** 4.0+

---

## 🔧 Instalação Passo a Passo

### 1. Preparar Diretórios

```bash
# Navegar para pasta docker
cd SDC/docker

# Criar estrutura de diretórios
mkdir -p jenkins_home
mkdir -p jenkins_backups
mkdir -p jenkins/ssl
mkdir -p jenkins/logs
mkdir -p jenkins/scripts

# Ajustar permissões (Jenkins usa UID 1000)
chown -R 1000:1000 jenkins_home
chmod 755 jenkins_home

# Permissões de backup
chmod 755 jenkins_backups
```

---

### 2. Configurar Variáveis de Ambiente

Criar arquivo `.env`:

```bash
cat > .env <<'EOF'
# ===== JENKINS =====
JENKINS_ADMIN_USER=admin
JENKINS_ADMIN_PASSWORD=changeme_strong_password_here
JENKINS_AGENT_SECRET=generate_with_uuidgen

# ===== BACKUP =====
# Local
BACKUP_RETENTION_DAILY=30
BACKUP_RETENTION_WEEKLY=12
BACKUP_RETENTION_MONTHLY=12

# Remote (S3)
BACKUP_REMOTE_TYPE=s3
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
S3_BUCKET=your-jenkins-backups
S3_REGION=us-east-1

# Ou Remote (Rsync)
# BACKUP_REMOTE_TYPE=rsync
# RSYNC_HOST=backup.example.com
# RSYNC_USER=jenkins
# RSYNC_PATH=/backups/jenkins

# ===== NOTIFICAÇÕES =====
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL

# ===== MONITORAMENTO =====
PROMETHEUS_PUSHGATEWAY=http://prometheus-pushgateway:9091
JENKINS_PROMETHEUS_USER=prometheus
JENKINS_PROMETHEUS_PASSWORD=prometheus_password

# ===== SSL (Opcional) =====
# SSL_CERT_PATH=./jenkins/ssl/cert.pem
# SSL_KEY_PATH=./jenkins/ssl/key.pem
EOF

# Proteger .env
chmod 600 .env
```

---

### 3. Gerar Certificados SSL (Produção)

```bash
# Opção 1: Let's Encrypt (Recomendado)
certbot certonly --standalone -d jenkins.your-domain.com
cp /etc/letsencrypt/live/jenkins.your-domain.com/fullchain.pem jenkins/ssl/cert.pem
cp /etc/letsencrypt/live/jenkins.your-domain.com/privkey.pem jenkins/ssl/key.pem

# Opção 2: Self-Signed (Desenvolvimento)
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout jenkins/ssl/key.pem \
    -out jenkins/ssl/cert.pem \
    -subj "/CN=jenkins.local"

# Ajustar permissões
chmod 600 jenkins/ssl/*.pem
```

---

### 4. Configurar Nginx

Criar `jenkins/nginx.conf`:

```nginx
upstream jenkins {
    server jenkins:8080 fail_timeout=0;
}

server {
    listen 80;
    server_name jenkins.your-domain.com;

    # Redirect HTTP to HTTPS
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name jenkins.your-domain.com;

    # SSL Configuration
    ssl_certificate /etc/nginx/ssl/cert.pem;
    ssl_certificate_key /etc/nginx/ssl/key.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security Headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Logging
    access_log /var/log/nginx/jenkins-access.log;
    error_log /var/log/nginx/jenkins-error.log;

    # Max upload size
    client_max_body_size 100M;

    # Health check endpoint
    location /health {
        access_log off;
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }

    # Jenkins proxy
    location / {
        proxy_pass http://jenkins;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # WebSocket support
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";

        # Timeouts
        proxy_connect_timeout 90;
        proxy_send_timeout 90;
        proxy_read_timeout 90;

        # Buffering
        proxy_buffering off;
        proxy_request_buffering off;
    }
}
```

---

### 5. Iniciar Jenkins

```bash
# Iniciar todos os serviços
docker-compose -f docker-compose.jenkins.yml up -d

# Verificar logs
docker-compose -f docker-compose.jenkins.yml logs -f jenkins

# Aguardar Jenkins ficar pronto (pode levar 2-3 minutos)
docker-compose -f docker-compose.jenkins.yml logs -f | grep -m 1 "Jenkins is fully up and running"
```

---

### 6. Configuração Inicial do Jenkins

```bash
# 1. Obter senha inicial
docker-compose -f docker-compose.jenkins.yml exec jenkins \
    cat /var/jenkins_home/secrets/initialAdminPassword

# 2. Acessar Jenkins
# https://jenkins.your-domain.com
# Ou: http://localhost:8080

# 3. Configurar:
# - Instalar plugins sugeridos
# - Criar usuário admin
# - Configurar URL do Jenkins
```

---

## 📦 Plugins Essenciais

### Instalar via Jenkins UI

1. **Manage Jenkins** → **Manage Plugins** → **Available**

2. Selecionar:
   - [x] Git
   - [x] Docker
   - [x] Docker Pipeline
   - [x] Blue Ocean
   - [x] Pipeline
   - [x] Prometheus metrics
   - [x] Slack Notification
   - [x] Configuration as Code (JCasC)

3. **Install without restart**

---

## 🔐 Hardening de Segurança

### 1. Habilitar Security Realm

```groovy
// Manage Jenkins → Configure Global Security

import jenkins.model.*
import hudson.security.*

def instance = Jenkins.getInstance()

// Matrix-based security
def strategy = new FullControlOnceLoggedInAuthorizationStrategy()
strategy.setAllowAnonymousRead(false)
instance.setAuthorizationStrategy(strategy)

instance.save()
```

### 2. Configurar CSRF Protection

```groovy
import hudson.security.csrf.DefaultCrumbIssuer
import jenkins.model.Jenkins

def instance = Jenkins.getInstance()
instance.setCrumbIssuer(new DefaultCrumbIssuer(true))
instance.save()
```

### 3. Limitar Executors no Master

```groovy
import jenkins.model.Jenkins

Jenkins.instance.setNumExecutors(0)
Jenkins.instance.save()
```

---

## 📊 Configurar Monitoramento

### 1. Prometheus Exporter

Já incluído no docker-compose! Métricas em:
```
http://localhost:9118/metrics
```

### 2. Configurar Prometheus (scrape config)

```yaml
# prometheus.yml
scrape_configs:
  - job_name: 'jenkins'
    static_configs:
      - targets: ['jenkins-exporter:9118']
    metric_relabel_configs:
      - source_labels: [__name__]
        regex: 'jenkins_(.*)'
        target_label: __name__
        replacement: '${1}'
```

### 3. Dashboards Grafana

Importar dashboards:
- **Jenkins Performance**: ID `9964`
- **Jenkins Build Monitor**: ID `12754`

---

## 💾 Configurar Backup

### 1. Backup Local (Automático)

Já configurado via cron! Backups a cada 6 horas em:
```
./jenkins_backups/
```

### 2. Backup Remoto S3

Configurar variáveis no `.env`:
```bash
BACKUP_REMOTE_TYPE=s3
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
S3_BUCKET=your-bucket
S3_REGION=us-east-1
```

### 3. Testar Backup Manual

```bash
# Executar backup manual
docker-compose -f docker-compose.jenkins.yml exec backup-local \
    /scripts/backup-local.sh

# Listar backups
ls -lh jenkins_backups/

# Verificar integridade
tar -tzf jenkins_backups/jenkins-*.tar.gz | head
```

### 4. Testar Restore

```bash
# Em ambiente de TESTE!
docker-compose -f docker-compose.jenkins.yml exec backup-local \
    /scripts/restore.sh /backups/jenkins-20250121_120000.tar.gz
```

---

## 🚨 Plano de Disaster Recovery

### Cenário 1: Corrupção de Dados

```bash
# 1. Parar Jenkins
docker-compose -f docker-compose.jenkins.yml stop jenkins

# 2. Restaurar último backup
docker-compose -f docker-compose.jenkins.yml exec backup-local \
    /scripts/restore.sh /backups/jenkins-YYYYMMDD_HHMMSS.tar.gz

# 3. Reiniciar Jenkins
docker-compose -f docker-compose.jenkins.yml up -d jenkins

# 4. Verificar
curl -f https://jenkins.your-domain.com/login
```

### Cenário 2: Perda Total do Servidor

```bash
# 1. Provisionar novo servidor

# 2. Instalar Docker + Docker Compose

# 3. Clonar repositório
git clone https://github.com/your-org/SDC.git
cd SDC/docker

# 4. Baixar backup do S3
aws s3 sync s3://your-bucket/jenkins/ ./jenkins_backups/

# 5. Restaurar backup
# (Seguir passos do Cenário 1)

# 6. Verificar SSL, DNS, etc.
```

### Cenário 3: Falha de Backup

```bash
# 1. Verificar logs
docker-compose -f docker-compose.jenkins.yml logs backup-local

# 2. Verificar espaço em disco
df -h

# 3. Executar backup manual
docker-compose -f docker-compose.jenkins.yml exec backup-local \
    /scripts/backup-local.sh

# 4. Verificar notificações Slack
```

---

## 📈 Monitoramento de Saúde

### Healthchecks

```bash
# Jenkins
curl -f http://localhost:8080/login || echo "Jenkins DOWN"

# Nginx
curl -f http://localhost/health || echo "Nginx DOWN"

# Docker Proxy
curl -f http://localhost:2375/_ping || echo "Docker Proxy DOWN"

# Backup (último backup < 24h)
find jenkins_backups/ -name "jenkins-*.tar.gz" -mtime -1 | grep -q . || echo "Backup FAILED"
```

### Logs

```bash
# Jenkins
docker-compose -f docker-compose.jenkins.yml logs -f jenkins

# Backup
docker-compose -f docker-compose.jenkins.yml logs -f backup-local

# Nginx
docker-compose -f docker-compose.jenkins.yml logs -f nginx

# Todos
docker-compose -f docker-compose.jenkins.yml logs -f
```

---

## 🔄 Manutenção

### Atualização do Jenkins

```bash
# 1. Backup manual antes
docker-compose -f docker-compose.jenkins.yml exec backup-local \
    /scripts/backup-local.sh

# 2. Pull nova imagem
docker-compose -f docker-compose.jenkins.yml pull jenkins

# 3. Recriar container
docker-compose -f docker-compose.jenkins.yml up -d --force-recreate jenkins

# 4. Verificar logs
docker-compose -f docker-compose.jenkins.yml logs -f jenkins
```

### Limpeza de Espaço

```bash
# Limpar builds antigos (via Jenkins UI)
# Manage Jenkins → Manage Old Data

# Limpar Docker
docker system prune -f

# Limpar logs
find jenkins/logs/ -name "*.log" -mtime +30 -delete
```

---

## 📋 Checklist Pós-Instalação

### Segurança
- [ ] SSL/TLS configurado e funcionando
- [ ] CSRF protection habilitado
- [ ] Security realm configurado
- [ ] Usuários e permissões definidos
- [ ] Executors no master = 0
- [ ] Docker socket via proxy (não exposto diretamente)

### Backup
- [ ] Backup local funcionando (verificar logs)
- [ ] Backup remoto configurado e testado
- [ ] Restore testado com sucesso
- [ ] Notificações Slack funcionando
- [ ] Retenção configurada (GFS)

### Monitoramento
- [ ] Prometheus exporter funcionando
- [ ] Grafana dashboards importados
- [ ] Alertas configurados
- [ ] Healthchecks validados

### Alta Disponibilidade
- [ ] Documentação de DR criada
- [ ] Runbooks de emergência prontos
- [ ] Time treinado
- [ ] Backup offsite confirmado

---

## 🆘 Troubleshooting

### Jenkins não inicia

```bash
# Verificar logs
docker-compose -f docker-compose.jenkins.yml logs jenkins

# Verificar permissões
ls -la jenkins_home/
# Deve ser 1000:1000

# Corrigir permissões
sudo chown -R 1000:1000 jenkins_home/

# Reiniciar
docker-compose -f docker-compose.jenkins.yml restart jenkins
```

### Backup falhando

```bash
# Verificar espaço em disco
df -h

# Verificar logs de backup
docker-compose -f docker-compose.jenkins.yml logs backup-local

# Executar backup manual com debug
docker-compose -f docker-compose.jenkins.yml exec backup-local \
    bash -x /scripts/backup-local.sh
```

### Performance ruim

```bash
# Verificar recursos
docker stats

# Aumentar heap do Jenkins (editar docker-compose.jenkins.yml)
# JAVA_OPTS: -Xmx6g (ao invés de 3g)

# Recriar container
docker-compose -f docker-compose.jenkins.yml up -d --force-recreate jenkins
```

---

## 📚 Referências

- [Documentação Oficial Jenkins](https://www.jenkins.io/doc/)
- [Análise de Riscos](./ARQUITETURA_BACKUP_JENKINS_REVIEW.md)
- [Pipeline Documentation](./JENKINS_PIPELINE.md)
- [Docker Security](https://docs.docker.com/engine/security/)

---

**Versão**: 1.0.0
**Data**: 2025-01-21
**Classificação**: PRODUÇÃO - Sistema Crítico 24/7
**Autor**: SDC DevOps Team
