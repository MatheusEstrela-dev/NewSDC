# 🔄 SDC - Fluxo de Inicialização Detalhado

Este documento detalha **passo a passo** o processo de inicialização completo do ambiente Docker.

---

## 📋 Sequência de Inicialização

### Comando de Inicialização

```bash
docker compose -f docker/docker-compose.yml up -d
```

---

## ⏱️ Timeline de Inicialização

```
T+0s    ┌─────────────────────────────────────────────────┐
        │ Docker Compose inicia                            │
        │ - Parse do docker-compose.yml                    │
        │ - Validação de configuração                     │
        │ - Resolução de variáveis de ambiente            │
        └─────────────────────────────────────────────────┘
        │
T+1s    ┌─────────────────────────────────────────────────┐
        │ Criação da Network                               │
        │ - Nome: sdc-dev_sdc_network                      │
        │ - Tipo: bridge                                   │
        │ - Subnet: 172.25.0.0/16                         │
        │ - Gateway: 172.25.0.1                           │
        └─────────────────────────────────────────────────┘
        │
T+2s    ┌─────────────────────────────────────────────────┐
        │ Criação dos Volumes                              │
        │ - db_data_dev                                    │
        │ - redis_data_dev                                 │
        │ - vendor_dev                                     │
        │ - node_modules_dev                               │
        └─────────────────────────────────────────────────┘
        │
        ┌─────────────────────────────────────────────────┐
        │ FASE 1: Containers Base (Sem Dependências)     │
        └─────────────────────────────────────────────────┘
        │
T+3s    ┌─────────────────────────────────────────────────┐
        │ [1] Container: db (MySQL)                       │
        │ Status: CREATING                                │
        │ Dependências: Nenhuma                           │
        └─────────────────────────────────────────────────┘
        │
        │ Pull imagem mysql:8.0 (se necessário)
        │ Criar container sdc_db_dev
        │ Montar volume db_data_dev
        │ Aplicar variáveis de ambiente
        │ Executar entrypoint do MySQL
        │
T+5s    │ MySQL inicializando...
        │ - Criando banco de dados 'sdc'
        │ - Criando usuário 'sdc'
        │ - Aplicando configurações
        │
T+10s   │ Health check iniciado (mysqladmin ping)
        │ Status: STARTING
        │
T+15s   │ Health check: OK
        │ Status: HEALTHY ✅
        │
        ┌─────────────────────────────────────────────────┐
        │ [2] Container: redis                            │
        │ Status: CREATING                                │
        │ Dependências: Nenhuma                           │
        └─────────────────────────────────────────────────┘
        │
        │ Pull imagem redis:7-alpine
        │ Criar container sdc_redis_dev
        │ Montar volume redis_data_dev
        │ Executar: redis-server --appendonly yes
        │
T+3s    │ Redis inicializando...
        │
T+5s    │ Health check iniciado (redis-cli ping)
        │ Status: STARTING
        │
T+7s    │ Health check: PONG
        │ Status: HEALTHY ✅
        │
        ┌─────────────────────────────────────────────────┐
        │ [3] Container: mailhog                          │
        │ Status: CREATING                                │
        │ Dependências: Nenhuma                           │
        └─────────────────────────────────────────────────┘
        │
        │ Pull imagem mailhog/mailhog:latest
        │ Criar container sdc_mailhog_dev
        │ Inicializar MailHog
        │ - SMTP server: porta 1025
        │ - Web UI: porta 8025
        │
T+4s    │ Status: RUNNING ✅
        │
        ┌─────────────────────────────────────────────────┐
        │ FASE 2: Aplicação (Depende de db e redis)      │
        └─────────────────────────────────────────────────┘
        │
T+20s   ┌─────────────────────────────────────────────────┐
        │ [4] Container: app (Laravel)                    │
        │ Status: CREATING                                │
        │ Dependências: db (HEALTHY), redis (HEALTHY)    │
        └─────────────────────────────────────────────────┘
        │
        │ Build imagem (se necessário):
        │ - Base: php:8.3-fpm-alpine
        │ - Instalar extensões PHP
        │ - Instalar Composer
        │ - Instalar Xdebug
        │ - Configurar PHP-FPM
        │
T+25s   │ Criar container sdc_app_dev
        │ Montar volumes:
        │   - Código fonte: ../:/var/www
        │   - vendor_dev (isolado)
        │   - node_modules_dev (isolado)
        │
T+30s   │ Executar entrypoint.dev.sh:
        │   - Criar diretórios
        │   - Ajustar permissões
        │   - Executar como www-data
        │
T+35s   │ Comando: php artisan serve --host=0.0.0.0 --port=8000
        │ Laravel iniciando...
        │ - Carregando configurações
        │ - Conectando ao MySQL (db:3306)
        │ - Conectando ao Redis (redis:6379)
        │
T+40s   │ Laravel servidor rodando na porta 8000
        │ Health check iniciado (curl http://localhost:8000)
        │ Status: STARTING
        │
T+50s   │ Health check: OK
        │ Status: HEALTHY ✅
        │
        ┌─────────────────────────────────────────────────┐
        │ [5] Container: nginx                            │
        │ Status: CREATING                                │
        │ Dependências: app (criado)                      │
        └─────────────────────────────────────────────────┘
        │
T+20s   │ Pull imagem nginx:1.25-alpine
        │ Criar container sdc_nginx_dev
        │ Montar volumes:
        │   - Código: ../:/var/www:ro
        │   - Config: ./nginx/dev.conf
        │   - Logs: ./logs/nginx
        │
T+22s   │ Aplicar configuração Nginx
        │ Inicializar Nginx
        │
T+25s   │ Health check iniciado (wget http://localhost/health)
        │ Status: STARTING
        │
T+27s   │ Health check: OK
        │ Status: HEALTHY ✅
        │
        ┌─────────────────────────────────────────────────┐
        │ FASE 3: Ferramentas (Profile: tools)            │
        └─────────────────────────────────────────────────┘
        │
        │ (Apenas se executado com --profile tools)
        │
T+30s   ┌─────────────────────────────────────────────────┐
        │ [6] Container: phpmyadmin                       │
        │ Status: CREATING                                │
        │ Dependências: db                                │
        └─────────────────────────────────────────────────┘
        │
        │ Pull imagem phpmyadmin:latest
        │ Criar container sdc_phpmyadmin_dev
        │ Configurar: PMA_HOST=db
        │
T+32s   │ Status: RUNNING ✅
        │
        ┌─────────────────────────────────────────────────┐
        │ [7] Container: redis-commander                 │
        │ Status: CREATING                                │
        │ Dependências: redis                             │
        └─────────────────────────────────────────────────┘
        │
        │ Pull imagem rediscommander/redis-commander:latest
        │ Criar container sdc_redis_commander_dev
        │ Configurar: REDIS_HOSTS=local:redis:6379
        │
T+33s   │ Status: RUNNING ✅
        │
T+60s   ┌─────────────────────────────────────────────────┐
        │ ✅ TODOS OS CONTAINERS INICIADOS                │
        │                                                 │
        │ Aplicação disponível em:                        │
        │ - http://localhost                              │
        │ - http://localhost:8000 (direto)                │
        │                                                 │
        │ Ferramentas:                                    │
        │ - Mailhog: http://localhost:8025                │
        │ - phpMyAdmin: http://localhost:8080             │
        │ - Redis Commander: http://localhost:8081        │
        └─────────────────────────────────────────────────┘
```

---

## 🔍 Detalhamento por Fase

### Fase 1: Containers Base

Estes containers **não dependem de outros** e podem iniciar em paralelo:

#### Container `db` (MySQL)

**Ordem:** 1º  
**Tempo:** ~15-30 segundos  
**Dependências:** Nenhuma

**Processo detalhado:**

1. **Pull da imagem** (se necessário)
   ```bash
   docker pull mysql:8.0
   ```

2. **Criação do container**
   ```bash
   docker create \
     --name sdc_db_dev \
     --hostname db \
     --network sdc-dev_sdc_network \
     --volume db_data_dev:/var/lib/mysql \
     mysql:8.0
   ```

3. **Aplicação de variáveis de ambiente**
   ```yaml
   MYSQL_ROOT_PASSWORD: root
   MYSQL_DATABASE: sdc
   MYSQL_USER: sdc
   MYSQL_PASSWORD: secret
   ```

4. **Inicialização do MySQL**
   - Criação do banco de dados
   - Criação do usuário
   - Aplicação de configurações (utf8mb4, etc.)

5. **Health check**
   ```bash
   mysqladmin ping -h localhost -u root -proot
   ```
   - Intervalo: 10s
   - Timeout: 5s
   - Retries: 5
   - Start period: 30s

6. **Status final:** HEALTHY ✅

---

#### Container `redis`

**Ordem:** 2º  
**Tempo:** ~2-5 segundos  
**Dependências:** Nenhuma

**Processo detalhado:**

1. **Pull da imagem**
   ```bash
   docker pull redis:7-alpine
   ```

2. **Criação do container**
   ```bash
   docker create \
     --name sdc_redis_dev \
     --hostname redis \
     --network sdc-dev_sdc_network \
     --volume redis_data_dev:/data \
     redis:7-alpine \
     redis-server --appendonly yes --maxmemory 256mb
   ```

3. **Inicialização do Redis**
   - Carrega dados persistentes (se existirem)
   - Inicia servidor Redis

4. **Health check**
   ```bash
   redis-cli ping
   ```
   - Intervalo: 10s
   - Timeout: 3s
   - Retries: 3
   - Start period: 5s

5. **Status final:** HEALTHY ✅

---

#### Container `mailhog`

**Ordem:** 3º  
**Tempo:** ~1-2 segundos  
**Dependências:** Nenhuma

**Processo detalhado:**

1. **Pull da imagem**
   ```bash
   docker pull mailhog/mailhog:latest
   ```

2. **Criação do container**
   ```bash
   docker create \
     --name sdc_mailhog_dev \
     --hostname mailhog \
     --network sdc-dev_sdc_network \
     mailhog/mailhog:latest
   ```

3. **Inicialização do MailHog**
   - SMTP server na porta 1025
   - Web UI na porta 8025

4. **Status final:** RUNNING ✅

---

### Fase 2: Aplicação

Estes containers **dependem** dos containers base estarem HEALTHY:

#### Container `app` (Laravel)

**Ordem:** 4º  
**Tempo:** ~30-60 segundos (primeira vez), ~10-20s (subsequentes)  
**Dependências:** `db` (HEALTHY), `redis` (HEALTHY)

**Processo detalhado:**

1. **Build da imagem** (se necessário)
   ```bash
   docker build \
     -f docker/Dockerfile.dev \
     --build-arg UID=1000 \
     --build-arg GID=1000 \
     -t sdc-dev-app \
     ..
   ```
   
   **Etapas do build:**
   - Base: php:8.3-fpm-alpine
   - Instalação de dependências do sistema
   - Instalação de extensões PHP
   - Instalação de Composer
   - Instalação de Xdebug
   - Configuração de PHP-FPM
   - Criação de usuário www-data

2. **Espera por dependências**
   ```yaml
   depends_on:
     db:
       condition: service_healthy  # Espera MySQL estar HEALTHY
     redis:
       condition: service_healthy  # Espera Redis estar HEALTHY
   ```

3. **Criação do container**
   ```bash
   docker create \
     --name sdc_app_dev \
     --hostname app \
     --network sdc-dev_sdc_network \
     --volume ../:/var/www:cached \
     --volume vendor_dev:/var/www/vendor \
     --volume node_modules_dev:/var/www/node_modules \
     sdc-dev-app
   ```

4. **Execução do entrypoint**
   ```bash
   /usr/local/bin/entrypoint.dev.sh
   ```
   
   **O que faz:**
   - Cria diretórios necessários
   - Ajusta permissões
   - Executa como usuário www-data

5. **Comando principal**
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```

6. **Laravel inicializa**
   - Carrega `.env`
   - Conecta ao MySQL (`db:3306`)
   - Conecta ao Redis (`redis:6379`)
   - Carrega rotas e configurações

7. **Health check**
   ```bash
   curl -f http://localhost:8000
   ```
   - Intervalo: 30s
   - Timeout: 10s
   - Retries: 3
   - Start period: 60s

8. **Status final:** HEALTHY ✅

---

#### Container `nginx`

**Ordem:** 5º  
**Tempo:** ~2-5 segundos  
**Dependências:** `app` (criado)

**Processo detalhado:**

1. **Pull da imagem**
   ```bash
   docker pull nginx:1.25-alpine
   ```

2. **Espera por dependências**
   ```yaml
   depends_on:
     - app  # Apenas precisa estar criado, não necessariamente HEALTHY
   ```

3. **Criação do container**
   ```bash
   docker create \
     --name sdc_nginx_dev \
     --hostname nginx \
     --network sdc-dev_sdc_network \
     --volume ../:/var/www:ro \
     --volume ./nginx/dev.conf:/etc/nginx/conf.d/default.conf:ro \
     nginx:1.25-alpine
   ```

4. **Aplicação da configuração Nginx**
   - Carrega `dev.conf`
   - Configura upstreams:
     - `app:8000` (Laravel)
     - `app:9000` (PHP-FPM)
     - `node:5173` (Vite - se disponível)

5. **Inicialização do Nginx**
   - Testa configuração: `nginx -t`
   - Inicia servidor: `nginx`

6. **Health check**
   ```bash
   wget -q --spider http://localhost/health
   ```
   - Intervalo: 30s
   - Timeout: 5s
   - Retries: 3
   - Start period: 10s

7. **Status final:** HEALTHY ✅

---

### Fase 3: Ferramentas (Opcional)

Estes containers só iniciam com `--profile tools`:

#### Container `phpmyadmin`

**Comando:**
```bash
docker compose -f docker/docker-compose.yml --profile tools up -d
```

**Processo:**
1. Pull imagem `phpmyadmin:latest`
2. Criar container
3. Configurar `PMA_HOST=db`
4. Inicializar phpMyAdmin
5. Status: RUNNING ✅

**Acesso:** http://localhost:8080

---

#### Container `redis-commander`

**Processo:**
1. Pull imagem `rediscommander/redis-commander:latest`
2. Criar container
3. Configurar `REDIS_HOSTS=local:redis:6379`
4. Inicializar Redis Commander
5. Status: RUNNING ✅

**Acesso:** http://localhost:8081

---

## 🔄 Ordem de Dependências

```
┌─────────┐
│   db    │ (Sem dependências)
└────┬────┘
     │
     │ HEALTHY
     │
┌────▼────┐     ┌─────────┐
│   app   │────►│  redis  │ (Sem dependências)
└────┬────┘     └─────────┘
     │
     │ CRIADO
     │
┌────▼────┐
│  nginx  │
└─────────┘

┌─────────┐
│ mailhog │ (Sem dependências)
└─────────┘
```

---

## 📊 Resumo de Tempos

| Container | Tempo Médio | Tempo Máximo | Dependências |
|-----------|-------------|--------------|--------------|
| `db` | 15-30s | 60s | Nenhuma |
| `redis` | 2-5s | 10s | Nenhuma |
| `mailhog` | 1-2s | 5s | Nenhuma |
| `app` | 30-60s | 120s | db, redis |
| `nginx` | 2-5s | 10s | app |
| **TOTAL** | **50-100s** | **200s** | - |

---

## ✅ Verificação de Inicialização

### Comando para verificar status:

```bash
docker compose -f docker/docker-compose.yml ps
```

### Saída esperada:

```
NAME                  STATUS
sdc_app_dev           Up 10s (healthy)
sdc_db_dev            Up 45s (healthy)
sdc_nginx_dev         Up 5s (healthy)
sdc_redis_dev         Up 40s (healthy)
sdc_mailhog_dev       Up 40s
```

### Verificar logs:

```bash
# Todos os containers
docker compose -f docker/docker-compose.yml logs -f

# Container específico
docker compose -f docker/docker-compose.yml logs -f app
```

### Verificar network:

```bash
docker network inspect sdc-dev_sdc_network
```

---

## 🐛 Troubleshooting de Inicialização

### Container não inicia

1. **Verificar logs:**
   ```bash
   docker compose logs <service>
   ```

2. **Verificar dependências:**
   ```bash
   docker compose ps
   ```

3. **Verificar health checks:**
   ```bash
   docker inspect <container> | grep -A 10 Health
   ```

### Container fica em "Starting"

1. **Verificar se dependências estão HEALTHY:**
   ```bash
   docker compose ps
   ```

2. **Verificar logs do container:**
   ```bash
   docker compose logs -f <service>
   ```

3. **Verificar recursos:**
   ```bash
   docker stats
   ```

### Erro de conexão entre containers

1. **Verificar network:**
   ```bash
   docker network inspect sdc-dev_sdc_network
   ```

2. **Testar DNS:**
   ```bash
   docker exec <container> ping <hostname>
   ```

3. **Verificar portas:**
   ```bash
   docker port <container>
   ```

---

**Última atualização:** 2024-11-26

