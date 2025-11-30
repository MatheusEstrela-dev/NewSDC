# 🚀 Jenkins CI/CD Setup - Pronto para Produção

Esta configuração resolve **TODOS** os problemas mencionados no [jenkins02.md](jenkins02.md) e garante um ambiente Jenkins perfeito para CI/CD em produção dentro do Docker.

## 📋 Índice

- [Problemas Resolvidos](#-problemas-resolvidos)
- [Arquitetura](#-arquitetura)
- [Pré-requisitos](#-pré-requisitos)
- [Instalação Rápida](#-instalação-rápida)
- [Configuração Detalhada](#-configuração-detalhada)
- [Troubleshooting](#-troubleshooting)
- [Backup e Restauração](#-backup-e-restauração)
- [Segurança](#-segurança)

---

## ✅ Problemas Resolvidos

### 1. **Pesadelo do UID/GID (Permission Denied)** ✓
- **Problema**: Jenkins não consegue escrever em volumes mapeados
- **Solução**: Script `setup.sh` configura automaticamente `chown -R 1000:1000` em todos os diretórios
- **Localização**: [jenkins/Dockerfile:25-30](jenkins/Dockerfile#L25-L30)

### 2. **Java Heap vs. Limite do Docker (OOM Killer)** ✓
- **Problema**: JVM tenta alocar mais memória que o container permite
- **Solução**:
  - `JAVA_OPTS=-Xmx3g` configurado no docker-compose
  - `mem_limit: 4G` definido (sempre maior que Xmx)
  - Deploy limits: 4 CPUs, 4GB RAM
- **Localização**: [docker-compose.jenkins.yml:28-29](docker-compose.jenkins.yml#L28-L29)

### 3. **SSH e Git (Chaves e Hosts)** ✓
- **Problema**: Chaves SSH não existem dentro do container
- **Solução**:
  - `known_hosts` pre-populado com GitHub, GitLab, Bitbucket
  - Suporte a SSH keys via credenciais Jenkins
- **Localização**: [jenkins/Dockerfile:33-40](jenkins/Dockerfile#L33-L40)

### 4. **Conflito de Portas e Networking** ✓
- **Problema**: Containers não se enxergam (localhost falha)
- **Solução**:
  - Bridge network `jenkins_network` + `sdc_network`
  - NGINX reverse proxy
  - Jenkinsfile usa `--network sdc_network`
- **Localização**: [docker-compose.jenkins.yml:129-135](docker-compose.jenkins.yml#L129-L135)

### 5. **Workspace Crescer Infinitamente** ✓
- **Problema**: Disco lota com builds antigos
- **Solução**:
  - Agentes Docker efêmeros
  - `buildDiscarder` mantém apenas 10 builds
  - Limpeza automática de cache no post-build
- **Localização**: [SDC/Jenkinsfile:28-29](SDC/Jenkinsfile#L28-L29)

### 6. **Acesso ao Docker Socket (DooD)** ✓
- **Problema**: Jenkins precisa construir imagens Docker
- **Solução**:
  - Docker socket mapeado com grupo correto
  - Usuário jenkins adicionado ao grupo docker
  - Docker CLI instalado no container
- **Localização**: [jenkins/Dockerfile:45-54](jenkins/Dockerfile#L45-L54)

---

## 🏗️ Arquitetura

```
┌─────────────────────────────────────────────────────────┐
│                      NGINX (SSL)                        │
│              (Port 80/443 → Jenkins:8080)               │
└───────────────────────┬─────────────────────────────────┘
                        │
┌───────────────────────▼─────────────────────────────────┐
│              JENKINS MASTER (Controller)                │
│  - Java 17 + Jenkins LTS                                │
│  - Docker CLI (DooD)                                    │
│  - PHP, Composer, Node.js, NPM                          │
│  - Volumes: jenkins_home (persistente)                  │
│  - Memory: 4GB limit, Xmx=3g                            │
└───────────────────────┬─────────────────────────────────┘
                        │
        ┌───────────────┼───────────────┐
        │               │               │
┌───────▼──────┐ ┌─────▼──────┐ ┌─────▼──────┐
│ Agent Docker │ │ SDC Network│ │   Backup   │
│  (JNLP)      │ │   (DB,App) │ │ (Automático│
└──────────────┘ └────────────┘ └────────────┘
```

---

## 🔧 Pré-requisitos

### Sistema Operacional
- **Linux**: Ubuntu 20.04+, Debian 11+, CentOS 8+, Rocky Linux 9+
- **Windows**: WSL2 com Docker Desktop
- **macOS**: Docker Desktop

### Software Necessário
```bash
# Docker
docker --version  # Mínimo: 20.10+

# Docker Compose
docker-compose --version  # Mínimo: 2.0+

# Git
git --version  # Qualquer versão recente
```

### Hardware Recomendado
- **CPU**: 4 cores (mínimo 2)
- **RAM**: 8GB (mínimo 4GB)
- **Disco**: 50GB livres (mínimo 20GB)
- **Rede**: Conexão estável para pull de imagens

---

## 🚀 Instalação Rápida

### Passo 1: Clone o Repositório
```bash
cd /opt  # ou diretório de sua preferência
git clone https://github.com/user/New_SDC.git
cd New_SDC
```

### Passo 2: Execute o Setup (Linux)
```bash
# Dar permissão de execução
chmod +x jenkins/setup.sh jenkins/backup.sh

# Executar setup (PRECISA SER ROOT para chown)
sudo ./jenkins/setup.sh
```

### Passo 3: Configurar Variáveis de Ambiente
```bash
# Editar arquivo .env
nano jenkins/.env

# OBRIGATÓRIO ALTERAR:
JENKINS_ADMIN_PASSWORD=SuaSenhaSegura123!
JENKINS_ADMIN_EMAIL=seu-email@empresa.com
GIT_REPO_URL=https://github.com/user/New_SDC.git

# Salvar e fechar (Ctrl+X, Y, Enter)
```

### Passo 4: Iniciar Jenkins
```bash
# Construir e iniciar
docker-compose -f docker-compose.jenkins.yml up -d

# Monitorar logs
docker-compose -f docker-compose.jenkins.yml logs -f jenkins

# Aguardar mensagem: "Jenkins is fully up and running"
```

### Passo 5: Acessar Jenkins
```bash
# Navegador
http://seu-ip:8080
# ou
https://seu-ip:443

# Login
Usuário: admin
Senha: [conforme configurado no .env]
```

---

## ⚙️ Configuração Detalhada

### 1. Configurar Chaves SSH para Git

#### Opção A: Gerar Nova Chave
```bash
# Gerar chave dentro do container Jenkins
docker-compose -f docker-compose.jenkins.yml exec jenkins ssh-keygen -t ed25519 -C "jenkins@sdc"

# Obter chave pública
docker-compose -f docker-compose.jenkins.yml exec jenkins cat /var/jenkins_home/.ssh/id_ed25519.pub

# Adicionar no GitHub/GitLab:
# GitHub: Settings → SSH Keys → Add SSH Key
# GitLab: Preferences → SSH Keys → Add Key
```

#### Opção B: Usar Chave Existente
```bash
# Copiar sua chave para jenkins_home
cp ~/.ssh/id_rsa jenkins_home/.ssh/
cp ~/.ssh/id_rsa.pub jenkins_home/.ssh/

# Ajustar permissões
sudo chown -R 1000:1000 jenkins_home/.ssh
sudo chmod 600 jenkins_home/.ssh/id_rsa
sudo chmod 644 jenkins_home/.ssh/id_rsa.pub
```

### 2. Configurar Credenciais no Jenkins

1. Acessar: **Manage Jenkins** → **Manage Credentials**
2. Adicionar credenciais SSH:
   - **Kind**: SSH Username with private key
   - **ID**: `git-ssh-key`
   - **Username**: `git`
   - **Private Key**: Cole o conteúdo de `id_rsa`

3. Adicionar credenciais Docker Registry (se necessário):
   - **Kind**: Username with password
   - **ID**: `docker-registry-credentials`
   - **Username**: Seu usuário Docker Hub
   - **Password**: Sua senha ou token

### 3. Configurar Pipeline

1. **Dashboard** → **New Item**
2. Nome: `SDC-Pipeline`
3. Tipo: **Pipeline**
4. Em **Pipeline**:
   - **Definition**: Pipeline script from SCM
   - **SCM**: Git
   - **Repository URL**: `https://github.com/user/New_SDC.git`
   - **Credentials**: Selecionar `git-ssh-key`
   - **Branch**: `*/main` e `*/develop`
   - **Script Path**: `SDC/Jenkinsfile`

5. Em **Build Triggers**:
   - ✅ **GitHub hook trigger for GITScm polling**
   - ✅ **Poll SCM**: `H/5 * * * *` (verifica a cada 5 minutos)

6. **Save**

### 4. Configurar Webhook no GitHub

```
# URL do Webhook
http://seu-jenkins:8080/github-webhook/

# Payload URL: Cole a URL acima
# Content type: application/json
# Events: Just the push event
# Active: ✓
```

---

## 🔍 Troubleshooting

### Problema 1: Jenkins não inicia (CrashLoopBackOff)

**Sintomas**: Container reinicia constantemente

**Diagnóstico**:
```bash
# Ver logs
docker-compose -f docker-compose.jenkins.yml logs jenkins | tail -50

# Verificar permissões
ls -la jenkins_home/
```

**Soluções**:
```bash
# Solução 1: Permissões incorretas
sudo chown -R 1000:1000 jenkins_home

# Solução 2: Memória insuficiente
# Editar docker-compose.jenkins.yml:
# Reduzir JAVA_OPTS: -Xmx2g
# Reduzir mem_limit: 3G

# Solução 3: Verificar logs detalhados
docker-compose -f docker-compose.jenkins.yml logs jenkins --tail=200
```

### Problema 2: Permission Denied ao acessar Docker socket

**Sintomas**: `Got permission denied while trying to connect to the Docker daemon socket`

**Diagnóstico**:
```bash
# Verificar GID do Docker no host
getent group docker

# Ver GID configurado no container
docker-compose -f docker-compose.jenkins.yml exec jenkins id
```

**Solução**:
```bash
# Reconfigurar com GID correto
DOCKER_GID=$(getent group docker | cut -d: -f3)
echo "DOCKER_GID=$DOCKER_GID" >> jenkins/.env

# Reconstruir imagem
docker-compose -f docker-compose.jenkins.yml build --build-arg DOCKER_GID=$DOCKER_GID

# Restart
docker-compose -f docker-compose.jenkins.yml up -d --force-recreate
```

### Problema 3: OOM Killed (Java out of memory)

**Sintomas**: Container para sem aviso, logs do sistema mostram "Out of Memory"

**Diagnóstico**:
```bash
# Ver logs do kernel (Linux)
dmesg | grep -i "out of memory"

# Ver uso de memória
docker stats jenkins_master
```

**Solução**:
```bash
# Editar docker-compose.jenkins.yml
# IMPORTANTE: mem_limit > JAVA_OPTS Xmx

# Exemplo para servidor com 8GB RAM:
JAVA_OPTS=-Xmx2g  # 2GB para Java
mem_limit: 3G     # 3GB para container (1GB overhead)

# Restart
docker-compose -f docker-compose.jenkins.yml up -d --force-recreate
```

### Problema 4: Builds falhando com "Cannot connect to Docker daemon"

**Sintomas**: Pipeline falha no stage de build Docker

**Diagnóstico**:
```bash
# Testar dentro do container
docker-compose -f docker-compose.jenkins.yml exec jenkins docker ps
```

**Solução**:
```bash
# 1. Verificar se socket está mapeado
docker inspect jenkins_master | grep docker.sock

# 2. Verificar permissões do socket no HOST
ls -la /var/run/docker.sock
# Deve ser: srw-rw---- 1 root docker

# 3. Se não funcionar, adicionar jenkins ao grupo docker (método temporário)
docker-compose -f docker-compose.jenkins.yml exec -u root jenkins usermod -aG docker jenkins
docker-compose -f docker-compose.jenkins.yml restart jenkins
```

### Problema 5: Git clone falha com SSH

**Sintomas**: `Host key verification failed`

**Solução**:
```bash
# Opção 1: Aceitar host manualmente
docker-compose -f docker-compose.jenkins.yml exec jenkins ssh -T git@github.com

# Opção 2: Rebuild (known_hosts já está configurado)
docker-compose -f docker-compose.jenkins.yml build --no-cache
docker-compose -f docker-compose.jenkins.yml up -d --force-recreate
```

### Problema 6: Disco cheio

**Sintomas**: `No space left on device`

**Diagnóstico**:
```bash
# Ver uso de disco
df -h

# Ver tamanho dos volumes Docker
docker system df
```

**Solução**:
```bash
# Limpar containers parados
docker container prune -f

# Limpar imagens não usadas
docker image prune -a -f

# Limpar volumes órfãos (CUIDADO!)
docker volume prune -f

# Limpar tudo (CUIDADO!)
docker system prune -a --volumes -f

# Limpar builds antigos do Jenkins (manter últimos 5)
cd jenkins_home/jobs/*/builds/
ls -t | tail -n +6 | xargs rm -rf
```

---

## 💾 Backup e Restauração

### Backup Manual

```bash
# Criar backup
./jenkins/backup.sh backup

# Listar backups disponíveis
./jenkins/backup.sh list

# Output:
#   jenkins_backups/jenkins_home_20250121_143022.tar.gz (1.2G)
#   jenkins_backups/jenkins_home_20250120_020015.tar.gz (1.1G)
```

### Backup Automático

O backup automático já está configurado no `docker-compose.jenkins.yml`:
- **Frequência**: A cada 24 horas
- **Retenção**: Últimos 7 backups
- **Localização**: `./jenkins_backups/`

### Restaurar Backup

```bash
# Parar Jenkins
docker-compose -f docker-compose.jenkins.yml stop

# Restaurar
./jenkins/backup.sh restore jenkins_backups/jenkins_home_20250121_143022.tar.gz

# Iniciar Jenkins
docker-compose -f docker-compose.jenkins.yml up -d
```

### Backup de Disaster Recovery

```bash
# Backup completo incluindo configurações
tar -czf jenkins_complete_backup.tar.gz \
    jenkins_home/ \
    jenkins/ \
    docker-compose.jenkins.yml \
    SDC/Jenkinsfile

# Em outra máquina:
# 1. Instalar Docker
# 2. Extrair backup
tar -xzf jenkins_complete_backup.tar.gz

# 3. Executar setup
sudo ./jenkins/setup.sh

# 4. Iniciar
docker-compose -f docker-compose.jenkins.yml up -d
```

---

## 🔒 Segurança

### Checklist de Segurança

- [ ] **Alterar senha padrão do admin**
  - Manage Jenkins → Manage Users → admin → Configure → Password

- [ ] **Habilitar HTTPS com certificado válido** (Let's Encrypt)
  ```bash
  # Instalar certbot
  sudo apt install certbot

  # Gerar certificado
  sudo certbot certonly --standalone -d jenkins.seudominio.com

  # Copiar certificados
  sudo cp /etc/letsencrypt/live/jenkins.seudominio.com/fullchain.pem jenkins/ssl/jenkins.crt
  sudo cp /etc/letsencrypt/live/jenkins.seudominio.com/privkey.pem jenkins/ssl/jenkins.key

  # Restart
  docker-compose -f docker-compose.jenkins.yml restart jenkins-nginx
  ```

- [ ] **Configurar firewall**
  ```bash
  # UFW (Ubuntu)
  sudo ufw allow 22/tcp    # SSH
  sudo ufw allow 80/tcp    # HTTP
  sudo ufw allow 443/tcp   # HTTPS
  sudo ufw enable

  # Fechar porta 8080 (só NGINX deve expor)
  # Editar docker-compose.jenkins.yml e remover:
  # - "8080:8080"
  ```

- [ ] **Habilitar autenticação de dois fatores (2FA)**
  - Instalar plugin: `otp-credentials`
  - Manage Jenkins → Configure Global Security → Enable 2FA

- [ ] **Limitar tentativas de login**
  - Instalar plugin: `login-theme`
  - Configurar lockout após 5 tentativas

- [ ] **Configurar Role-Based Access Control (RBAC)**
  - Instalar plugin: `role-strategy`
  - Manage Jenkins → Manage and Assign Roles

- [ ] **Auditar permissões de scripts**
  - Manage Jenkins → In-process Script Approval
  - Revisar e aprovar scripts apenas de fontes confiáveis

- [ ] **Backup criptografado**
  ```bash
  # Criptografar backup
  gpg --symmetric --cipher-algo AES256 jenkins_backups/jenkins_home_*.tar.gz

  # Descriptografar
  gpg --decrypt jenkins_home_*.tar.gz.gpg > jenkins_home_backup.tar.gz
  ```

---

## 📊 Monitoramento

### Métricas Disponíveis

1. **Health Checks**
   ```bash
   # Jenkins
   curl http://localhost:8080/login

   # NGINX
   curl http://localhost:80/health
   ```

2. **Logs**
   ```bash
   # Jenkins logs
   docker-compose -f docker-compose.jenkins.yml logs -f jenkins

   # NGINX access logs
   tail -f jenkins/logs/jenkins-access.log

   # NGINX error logs
   tail -f jenkins/logs/jenkins-error.log
   ```

3. **Prometheus Metrics** (opcional)
   - Plugin instalado: `prometheus`
   - Endpoint: `http://jenkins:8080/prometheus`

---

## 🎯 Próximos Passos

1. ✅ Configure chaves SSH
2. ✅ Crie seu primeiro pipeline
3. ✅ Configure webhook no GitHub
4. ✅ Teste um build completo
5. ✅ Configure notificações (Slack/Email)
6. ✅ Implemente Disaster Recovery Plan
7. ✅ Documente seu processo de deploy

---

## 📚 Referências

- [Jenkins Official Documentation](https://www.jenkins.io/doc/)
- [Docker Best Practices](https://docs.docker.com/develop/dev-best-practices/)
- [Pipeline Syntax](https://www.jenkins.io/doc/book/pipeline/syntax/)
- [jenkins02.md](jenkins02.md) - Documento original com problemas identificados

---

## 📞 Suporte

Em caso de problemas:

1. Verifique a seção [Troubleshooting](#-troubleshooting)
2. Consulte os logs: `docker-compose logs jenkins`
3. Abra uma issue no repositório

---

**Criado com ❤️ pela equipe SDC DevOps**
