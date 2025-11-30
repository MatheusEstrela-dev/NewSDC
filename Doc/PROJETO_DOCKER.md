# 🐳 PROJETO - Documentação Docker SDC

## 📋 Informações do Projeto

- **Nome**: SDC - Sistema de Defesa Civil
- **Tipo**: Documentação Técnica - Docker
- **Versão**: 1.0.0
- **Data**: 2025-01-21
- **Status**: ✅ Ativo

---

## 🎯 Objetivo

Documentação completa da arquitetura Docker do projeto SDC, incluindo configurações de desenvolvimento e produção, containers, volumes, networks e processos de deploy.

---

## 📑 Índice

1. [Visão Geral](#-visão-geral)
2. [Arquitetura](#-arquitetura)
3. [Containers e Serviços](#-containers-e-serviços)
4. [Configuração de Desenvolvimento](#-configuração-de-desenvolvimento)
5. [Configuração de Produção](#-configuração-de-produção)
6. [Dockerfiles](#-dockerfiles)
7. [Networking](#-networking)
8. [Volumes e Persistência](#-volumes-e-persistência)
9. [Processo de Inicialização](#-processo-de-inicialização)
10. [Backup e Restore](#-backup-e-restore)
11. [Monitoramento](#-monitoramento)
12. [Troubleshooting](#-troubleshooting)
13. [Comandos Úteis](#-comandos-úteis)
14. [Segurança](#-segurança)

---

## 🏗️ Visão Geral

O projeto SDC utiliza uma arquitetura Docker multi-container para separar responsabilidades e facilitar o desenvolvimento e deploy. A aplicação é baseada em **Laravel 12** com **PHP 8.3** e utiliza **PHP-FPM + Nginx + MySQL + Redis**.

### Stack Tecnológica

- **Backend**: PHP 8.3-FPM, Laravel 12
- **Frontend**: Vue.js 3, Inertia.js, Tailwind CSS, Vite
- **Banco de Dados**: MySQL 8.0
- **Cache/Filas**: Redis 7
- **Web Server**: Nginx 1.25
- **Email Testing**: MailHog
- **CI/CD**: Jenkins (opcional)

---

## 🏛️ Arquitetura

### Diagrama de Arquitetura

```
┌─────────────────────────────────────────────────────────────────┐
│                         DOCKER HOST                             │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │                    sdc_network (Bridge)                   │ │
│  │                                                           │ │
│  │  ┌─────────────┐      ┌──────────────┐                  │ │
│  │  │   NGINX     │─────▶│  PHP-FPM App │                  │ │
│  │  │   (Port 80) │      │   (Laravel)  │                  │ │
│  │  │             │◀─────│  (Port 9000) │                  │ │
│  │  └─────────────┘      └──────────────┘                  │ │
│  │         │                     │                          │ │
│  │         │                     ├──────────────┐           │ │
│  │         │                     │              │           │ │
│  │         │              ┌──────▼─────┐  ┌────▼────┐      │ │
│  │         │              │   MySQL    │  │  Redis  │      │ │
│  │         │              │  (Port     │  │ (Cache) │      │ │
│  │         │              │   3306)    │  └─────────┘      │ │
│  │         │              └────────────┘                    │ │
│  │         │                     │                          │ │
│  │  ┌──────▼──────┐       ┌─────▼──────┐                  │ │
│  │  │    Node     │       │   Backup   │                  │ │
│  │  │  (Vite Dev) │       │ (Automated)│                  │ │
│  │  │ (Port 5173) │       └────────────┘                  │ │
│  │  └─────────────┘                                        │ │
│  │                                                         │ │
│  │  ┌─────────────┐       ┌──────────────┐               │ │
│  │  │  MailHog    │       │   Jenkins    │               │ │
│  │  │  (Dev only) │       │   (CI/CD)    │               │ │
│  │  │ (Port 8025) │       │ (Port 8080)  │               │ │
│  │  └─────────────┘       └──────────────┘               │ │
│  │                                                         │ │
│  └─────────────────────────────────────────────────────────┘ │
│                                                               │
│  ┌──────────────────── VOLUMES ────────────────────┐         │
│  │ • db_data_dev (MySQL data)                      │         │
│  │ • redis_data_dev (Redis data)                   │         │
│  │ • vendor_dev (Composer packages)                │         │
│  │ • node_modules_dev (NPM packages)              │         │
│  │ • jenkins_home (Jenkins configs)                │         │
│  │ • backup_data (Database backups)                │         │
│  │ • ./: (código fonte - bind mount)                │         │
│  └─────────────────────────────────────────────────┘         │
└─────────────────────────────────────────────────────────────────┘
```

### Estrutura de Arquivos

```
SDC/
├── docker/                           # Configurações Docker
│   ├── Dockerfile.dev                # Dockerfile para desenvolvimento
│   ├── Dockerfile.prod               # Dockerfile para produção
│   ├── docker-compose.yml            # Compose para desenvolvimento
│   ├── docker-compose.prod.yml       # Compose para produção
│   ├── docker-compose.monitoring.yml # Compose para monitoramento
│   │
│   ├── nginx/                        # Configurações Nginx
│   │   ├── dev.conf                  # Config desenvolvimento
│   │   ├── prod.conf                 # Config produção
│   │   └── default.conf              # Config padrão
│   │
│   ├── mysql/                        # Configurações MySQL
│   │   ├── dev.cnf                   # Config desenvolvimento
│   │   ├── prod-primary.cnf          # Config produção (master)
│   │   └── prod-replica.cnf          # Config produção (replica)
│   │
│   ├── config/                       # Configurações PHP
│   │   ├── php/
│   │   │   ├── dev.ini               # PHP dev
│   │   │   └── xdebug.ini            # Xdebug config
│   │   └── php-fpm/
│   │       └── dev.conf              # PHP-FPM dev
│   │
│   ├── scripts/                      # Scripts auxiliares
│   │   ├── entrypoint.dev.sh        # Entrypoint dev
│   │   ├── healthcheck.sh           # Healthcheck
│   │   └── healthcheck.prod.sh      # Healthcheck prod
│   │
│   ├── backup/                       # Scripts de backup
│   │   ├── backup.sh                # Backup automático
│   │   ├── backup-prod.sh           # Backup produção
│   │   └── restore.sh               # Restauração
│   │
│   ├── monitoring/                   # Configurações monitoramento
│   │   ├── prometheus.yml
│   │   ├── loki.yml
│   │   └── grafana/
│   │
│   └── jenkins/                      # Configurações Jenkins
│       ├── Dockerfile
│       └── init.groovy.d/
│
├── .dockerignore                     # Arquivos ignorados no build
└── .env.example                      # Variáveis de ambiente
```

---

## 🐋 Containers e Serviços

### 1. **App (PHP-FPM + Laravel)**

**Container**: `sdc_app_dev` (dev) | `sdc_app_prod` (prod)

**Responsabilidade**: Processar código PHP da aplicação Laravel

**Tecnologias**:
- PHP 8.3-FPM
- Composer 2.7
- Extensões PHP: pdo_mysql, mbstring, exif, pcntl, bcmath, gd, zip, redis, opcache, intl, sockets

**Portas Expostas**:
- `8000:8000` - Laravel Artisan Serve (dev)
- `9000:9000` - PHP-FPM

**Volumes**:
```yaml
# Desenvolvimento (bind mount)
- ./:/var/www:cached
- vendor_dev:/var/www/vendor
- node_modules_dev:/var/www/node_modules
- ./logs/php:/var/log/php

# Produção (imagem construída)
- app_public:/var/www/public:ro
```

**Variáveis de Ambiente**:
```bash
APP_ENV=local|production
APP_DEBUG=true|false
DB_HOST=db
REDIS_HOST=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
XDEBUG_MODE=debug,develop,coverage
```

**Health Check**:
```yaml
test: ["CMD", "curl", "-f", "http://localhost:8000"]
interval: 30s
timeout: 10s
retries: 3
start_period: 60s
```

---

### 2. **Nginx (Reverse Proxy)**

**Container**: `sdc_nginx_dev` (dev) | `sdc_nginx_prod` (prod)

**Responsabilidade**: Servir arquivos estáticos e fazer proxy para PHP-FPM

**Imagem**: `nginx:1.25-alpine`

**Portas Expostas**:
- `80:80` - HTTP
- `443:443` - HTTPS (prod)

**Volumes**:
```yaml
# Desenvolvimento
- ./:/var/www:ro
- ./docker/nginx/dev.conf:/etc/nginx/conf.d/default.conf:ro
- ./logs/nginx:/var/log/nginx

# Produção
- app_public:/var/www/public:ro
- ./docker/nginx/prod.conf:/etc/nginx/conf.d/default.conf:ro
- ./docker/nginx/ssl:/etc/nginx/ssl:ro
```

**Configurações Importantes**:
```nginx
# Health check endpoint
location /health {
    access_log off;
    return 200 "healthy\n";
    add_header Content-Type text/plain;
}

# Proxy para PHP-FPM
location ~ \.php$ {
    fastcgi_pass app:9000;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    include fastcgi_params;
    fastcgi_hide_header X-Powered-By;
    fastcgi_buffer_size 128k;
    fastcgi_buffers 256 16k;
    fastcgi_read_timeout 600;
}

# Cache de arquivos estáticos
location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
    access_log off;
}

# Segurança: Negar acesso a arquivos sensíveis
location ~ /\.(env|git|svn|htaccess) {
    deny all;
    return 404;
}
```

---

### 3. **MySQL (Banco de Dados)**

**Container**: `sdc_db_dev` (dev) | `sdc_db_prod` (prod)

**Responsabilidade**: Armazenar dados da aplicação

**Imagem**: `mysql:8.0`

**Portas Expostas**:
- `3306:3306` (dev apenas - para acesso externo)

**Volumes**:
```yaml
- db_data_dev:/var/lib/mysql
- ./docker/mysql/dev.cnf:/etc/mysql/conf.d/custom.cnf:ro
- ./docker/mysql/init:/docker-entrypoint-initdb.d:ro
```

**Variáveis de Ambiente**:
```bash
MYSQL_DATABASE=${DB_DATABASE}
MYSQL_ROOT_PASSWORD=${DB_ROOT_PASSWORD}
MYSQL_PASSWORD=${DB_PASSWORD}
MYSQL_USER=${DB_USERNAME}
TZ=America/Sao_Paulo
```

**Comando de Inicialização**:
```bash
--default-authentication-plugin=mysql_native_password
--character-set-server=utf8mb4
--collation-server=utf8mb4_unicode_ci
--sql_mode=STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION
```

**Health Check**:
```yaml
test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-u", "root", "-p${DB_ROOT_PASSWORD}"]
interval: 10s
timeout: 5s
retries: 5
start_period: 30s
```

**Otimizações** (dev.cnf):
```ini
[mysqld]
max_connections = 200
innodb_buffer_pool_size = 512M
innodb_log_file_size = 128M
query_cache_size = 64M
```

---

### 4. **Redis (Cache/Session/Queue)**

**Container**: `sdc_redis_dev` (dev) | `sdc_redis_prod` (prod)

**Responsabilidade**: Cache, sessões e filas

**Imagem**: `redis:7-alpine`

**Portas Expostas**:
- `6379:6379` (dev apenas)

**Volumes**:
```yaml
- redis_data_dev:/data
```

**Comando**:
```bash
redis-server --appendonly yes --maxmemory 256mb --maxmemory-policy allkeys-lru
```

**Health Check**:
```yaml
test: ["CMD", "redis-cli", "ping"]
interval: 10s
timeout: 3s
retries: 3
start_period: 5s
```

---

### 5. **Node (Vite Dev Server)**

**Container**: `sdc_node_dev`

**Responsabilidade**: Compilar assets frontend (JavaScript, CSS) em tempo real

**Imagem**: `node:20-alpine`

**Portas Expostas**:
- `5173:5173` - Vite HMR (Hot Module Replacement)

**Volumes**:
```yaml
- ./:/var/www:cached
- node_modules_dev:/var/www/node_modules
```

**Comando**:
```bash
sh -c "npm install && npm run dev"
```

**Variáveis de Ambiente**:
```bash
NODE_ENV=development
VITE_HOST=0.0.0.0
VITE_PORT=5173
```

**Uso**:
- Desenvolvimento: Assets são servidos via HMR (hot reload)
- Produção: Assets são compilados no build (`npm run build`)

---

### 6. **MailHog (Desenvolvimento apenas)**

**Container**: `sdc_mailhog_dev`

**Responsabilidade**: Capturar emails enviados pela aplicação (teste)

**Imagem**: `mailhog/mailhog:latest`

**Portas Expostas**:
- `1025:1025` - SMTP Server
- `8025:8025` - Web UI

**Acesso**: http://localhost:8025

**Configuração Laravel** (.env):
```bash
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_ENCRYPTION=null
```

---

### 7. **phpMyAdmin (Profile: tools)**

**Container**: `sdc_phpmyadmin_dev`

**Responsabilidade**: Interface web para gerenciamento do MySQL

**Imagem**: `phpmyadmin:latest`

**Portas Expostas**:
- `8080:80`

**Acesso**: http://localhost:8080

**Variáveis de Ambiente**:
```bash
PMA_HOST=db
PMA_USER=root
PMA_PASSWORD=${DB_ROOT_PASSWORD}
UPLOAD_LIMIT=100M
```

**Uso**: Incluir no profile `tools` para ativar:
```bash
docker compose --profile tools up -d
```

---

### 8. **Redis Commander (Profile: tools)**

**Container**: `sdc_redis_commander_dev`

**Responsabilidade**: Interface web para gerenciamento do Redis

**Imagem**: `rediscommander/redis-commander:latest`

**Portas Expostas**:
- `8081:8081`

**Acesso**: http://localhost:8081

**Uso**: Incluir no profile `tools` para ativar:
```bash
docker compose --profile tools up -d
```

---

### 9. **Backup (Automático)**

**Container**: `sdc_backup`

**Responsabilidade**: Backup automático do banco de dados MySQL

**Imagem**: `alpine:latest`

**Volumes**:
```yaml
- ./docker/backup:/backup
- backup_data:/backup/data
```

**Variáveis de Ambiente**:
```bash
MYSQL_HOST=db
MYSQL_DATABASE=${DB_DATABASE}
MYSQL_USER=${DB_USERNAME}
MYSQL_PASSWORD=${DB_PASSWORD}
BACKUP_INTERVAL=86400           # 24 horas
BACKUP_RETENTION_DAYS=7         # Manter últimos 7 dias
```

**Script de Backup**:
```bash
#!/bin/bash
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="${BACKUP_DIR}/${MYSQL_DATABASE}_${TIMESTAMP}.sql.gz"

# Dump do banco
mysqldump -h "${MYSQL_HOST}" \
          -u "${MYSQL_USER}" \
          -p"${MYSQL_PASSWORD}" \
          --single-transaction \
          --routines \
          --triggers \
          "${MYSQL_DATABASE}" | gzip > "${BACKUP_FILE}"

# Remover backups antigos (>7 dias)
find "${BACKUP_DIR}" -name "*.sql.gz" -type f -mtime +${RETENTION_DAYS} -delete
```

---

## 🚀 Configuração de Desenvolvimento

### Requisitos

- Docker Desktop 4.x+ ou Docker Engine 24.x+
- Docker Compose v2.x+
- 8GB RAM mínimo
- 20GB espaço em disco

### Quick Start

```bash
# 1. Clone o repositório
git clone https://github.com/seu-repo/New_SDC.git
cd New_SDC/SDC

# 2. Copie o arquivo de ambiente
cp .env.example .env

# 3. Configure as variáveis de ambiente
nano .env  # Ajuste DB_PASSWORD, etc

# 4. Inicie o ambiente
cd docker
docker compose up -d

# 5. Aguardar inicialização
docker compose logs -f app

# 6. Executar migrations
docker compose exec app php artisan migrate --seed

# 7. Acessar aplicação
# Laravel: http://localhost
# MailHog: http://localhost:8025
# Vite HMR: http://localhost:5173
```

### Serviços Disponíveis

| Serviço | URL | Descrição |
|---------|-----|-----------|
| App | http://localhost | Aplicação Laravel |
| Mailhog | http://localhost:8025 | Email testing |
| phpMyAdmin | http://localhost:8080 | DB Management (profile: tools) |
| Redis Commander | http://localhost:8081 | Redis UI (profile: tools) |

### Hot Reload

O ambiente de desenvolvimento possui hot reload automático:

- **PHP**: Alterações são refletidas imediatamente (OPcache desabilitado)
- **Frontend**: Vite HMR configurado (porta 5173)

Para rodar o Vite separadamente (melhor performance no Windows):

```bash
# No host (fora do Docker)
npm run dev

# Ou via Docker
docker compose exec node npm run dev
```

### Debugging com Xdebug

1. Configure seu IDE (VSCode/PHPStorm) para ouvir na porta 9003
2. Adicione breakpoints no código
3. Acesse a aplicação com `?XDEBUG_TRIGGER=1` ou configure a extensão do browser

---

## 🏭 Configuração de Produção

### Requisitos

- Docker Engine 24.x+
- Docker Compose v2.x+ ou Docker Swarm
- 16GB RAM mínimo (recomendado 32GB)
- 100GB SSD
- Linux (Ubuntu 22.04+ recomendado)

### Arquitetura de Alta Disponibilidade

```
                    ┌─────────────┐
                    │   Traefik   │
                    │ (Load Bal)  │
                    └─────┬───────┘
                          │
        ┌─────────────────┼─────────────────┐
        │                 │                 │
   ┌────▼────┐      ┌────▼────┐      ┌────▼────┐
   │  App 1  │      │  App 2  │      │  App 3  │
   │(Octane) │      │(Octane) │      │(Octane) │
   └────┬────┘      └────┬────┘      └────┬────┘
        │                 │                 │
        └────────┬────────┴────────┬───────┘
                 │                 │
          ┌──────▼──────┐   ┌─────▼──────┐
          │MySQL Primary│   │Redis Master│
          │  (Master)   │   │            │
          └──────┬──────┘   └─────┬──────┘
                 │                 │
          ┌──────▼──────┐   ┌─────▼──────┐
          │MySQL Replica│   │Redis Replic│
          │ (Read-only) │   │            │
          └─────────────┘   └────────────┘
```

### Deploy

```bash
# 1. Configure variáveis de produção
cp .env.example .env
vim .env  # Ajuste para produção

# 2. Build e deploy
docker compose -f docker/docker-compose.prod.yml build
docker compose -f docker/docker-compose.prod.yml up -d

# 3. Executar migrations (uma vez)
docker compose -f docker/docker-compose.prod.yml exec app php artisan migrate --force

# 4. Otimizar caches
docker compose -f docker/docker-compose.prod.yml exec app php artisan optimize

# 5. Health check
curl http://localhost/health
```

### Scaling

```bash
# Aumentar réplicas da aplicação
docker compose -f docker/docker-compose.prod.yml up -d --scale app=5 --scale queue=3
```

### SSL/TLS

O Traefik gerencia certificados SSL automaticamente via Let's Encrypt:

1. Configure `APP_DOMAIN` e `ACME_EMAIL` no `.env`
2. Aponte o DNS para o servidor
3. O certificado será obtido automaticamente

---

## 📝 Dockerfiles

### Dockerfile.dev (Desenvolvimento)

**Localização**: `SDC/docker/Dockerfile.dev`

**Características**:
- Single-stage build (mais rápido para rebuild)
- Bind mounts para código fonte (hot reload)
- Ferramentas de desenvolvimento incluídas (vim, strace)
- PHP configurado para desenvolvimento (erros visíveis)
- Xdebug habilitado
- Entrypoint script para auto-setup

**Estrutura**:
```dockerfile
FROM php:8.3-fpm-alpine AS base

# Instalar dependências do sistema + extensões PHP
RUN apk add --no-cache \
    git curl wget vim bash \
    libpng-dev oniguruma-dev libxml2-dev libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && pecl install redis xdebug && docker-php-ext-enable redis xdebug

# Copiar Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Configurar PHP para desenvolvimento
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Copiar e configurar entrypoint
COPY docker/scripts/entrypoint.dev.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
```

**Entrypoint Script** (`docker/scripts/entrypoint.dev.sh`):

Automatiza tarefas de inicialização:

```bash
#!/bin/bash
set -e

# 1. Instalar dependências Composer (se vendor/ não existe)
if [ ! -d "/var/www/vendor" ]; then
    composer install --no-interaction --prefer-dist
fi

# 2. Criar .env a partir de .env.example (se não existe)
if [ ! -f "/var/www/.env" ]; then
    cp /var/www/.env.example /var/www/.env
fi

# 3. Gerar chave da aplicação (se não configurada)
if ! grep -q "APP_KEY=base64:" /var/www/.env; then
    php artisan key:generate --force
fi

# 4. Ajustar permissões
chmod -R 775 /var/www/storage /var/www/bootstrap/cache
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# 5. Executar comando (php-fpm)
exec "$@"
```

---

### Dockerfile.prod (Produção)

**Localização**: `SDC/docker/Dockerfile.prod`

**Características**:
- Multi-stage build (imagem final menor)
- Assets frontend compilados no build
- Apenas dependências de runtime na imagem final
- OPcache configurado e otimizado
- PHP configurado para produção (erros ocultos)
- Health check integrado

**Estrutura**:

**Stage 1: Builder** (preparação)
```dockerfile
FROM php:8.3-fpm AS builder

WORKDIR /var/www

# Instalar dependências de BUILD (Composer, Node.js)
RUN apt-get update && apt-get install -y git curl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip opcache \
    && pecl install redis && docker-php-ext-enable redis

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Copiar código fonte
COPY . /var/www

# Instalar dependências PHP (sem dev)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Compilar assets frontend
RUN npm ci && npm run build && rm -rf node_modules
```

**Stage 2: Production** (imagem final)
```dockerfile
FROM php:8.3-fpm

# Instalar APENAS dependências de RUNTIME
RUN apt-get update && apt-get install -y \
    libpng16-16 libonig5 libxml2 libzip4 \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip opcache \
    && pecl install redis && docker-php-ext-enable redis

# Configurar PHP para produção
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# OPcache: Cache de bytecode para melhor performance
RUN { \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.validate_timestamps=0';  # Nunca revalidar em produção
    echo 'opcache.save_comments=1'; \
    echo 'opcache.fast_shutdown=1'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www

# Copiar código compilado do builder
COPY --from=builder --chown=www-data:www-data /var/www /var/www

# Ajustar permissões
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
```

**Benefícios do Multi-stage**:
- Imagem final: ~200MB (vs ~800MB single-stage)
- Não inclui Composer, Node.js, git na produção
- Melhor segurança (menos ferramentas = menos superfície de ataque)

---

## 🌐 Networking

### Network: `sdc_network`

**Tipo**: Bridge (padrão)

**Subnet**: `172.25.0.0/16` (customizado)

**Containers Conectados**:
- app
- nginx
- db
- redis
- node
- mailhog (dev)
- phpmyadmin (dev)
- redis-commander (dev)
- backup

**Como Funciona**:

Todos os containers na mesma network podem se comunicar usando o **nome do serviço** como hostname.

**Exemplo**:

```php
// No código Laravel
DB_HOST=db              // Resolve para IP do container sdc_db
REDIS_HOST=redis        // Resolve para IP do container sdc_redis
```

```nginx
# No Nginx
fastcgi_pass app:9000;  // Resolve para IP do container sdc_app_*
```

**Resolução DNS Interna**:

Docker possui um DNS server embutido que resolve nomes de containers:

```bash
# Dentro do container app
ping db
# PING db (172.25.0.3) 56(84) bytes of data.

nslookup redis
# Server:    127.0.0.11
# Address:   127.0.0.11#53
# Name:      redis
# Address:   172.25.0.4
```

---

## 💾 Volumes e Persistência

### Volumes Named (Persistentes)

#### 1. `db_data_dev`

**Conteúdo**: Dados do MySQL
**Localização**: `/var/lib/docker/volumes/sdc-dev_db_data_dev/_data`
**Tamanho Médio**: 100MB - 10GB (depende da aplicação)

**Backup**:
```bash
# Criar backup
docker run --rm -v sdc-dev_db_data_dev:/data -v $(pwd):/backup alpine tar czf /backup/db_data_backup.tar.gz -C /data .

# Restaurar backup
docker run --rm -v sdc-dev_db_data_dev:/data -v $(pwd):/backup alpine sh -c "rm -rf /data/* && tar xzf /backup/db_data_backup.tar.gz -C /data"
```

#### 2. `redis_data_dev`

**Conteúdo**: Dados persistentes do Redis (AOF habilitado)
**Localização**: `/var/lib/docker/volumes/sdc-dev_redis_data_dev/_data`

#### 3. `vendor_dev`

**Conteúdo**: Pacotes Composer instalados
**Localização**: `/var/lib/docker/volumes/sdc-dev_vendor_dev/_data`
**Benefício**: Cache de dependências PHP (não reinstala a cada rebuild)

#### 4. `node_modules_dev`

**Conteúdo**: Pacotes NPM instalados
**Localização**: `/var/lib/docker/volumes/sdc-dev_node_modules_dev/_data`
**Benefício**: Cache de dependências Node.js (não reinstala a cada rebuild)

#### 5. `backup_data`

**Conteúdo**: Backups SQL do banco de dados
**Localização**: `/var/lib/docker/volumes/backup_data/_data`

### Bind Mounts (Desenvolvimento)

#### Código Fonte: `./:/var/www`

**Tipo**: Bind mount bidirecional
**Conteúdo**: Todo o código fonte da aplicação
**Benefício**: Hot reload - mudanças no código refletem instantaneamente

#### Volumes Anônimos (Cache)

```yaml
- /var/www/vendor        # Não sobrescrever vendor/ do host
- /var/www/node_modules  # Não sobrescrever node_modules/ do host
```

**Por quê?**

Dependências compiladas no container (Linux) podem ser incompatíveis com o host (Windows/Mac). Volumes anônimos isolam essas dependências.

---

## 🚀 Processo de Inicialização

### Desenvolvimento

```bash
# 1. Clonar repositório
git clone https://github.com/user/New_SDC.git
cd New_SDC/SDC

# 2. Copiar variáveis de ambiente
cp .env.example .env
nano .env  # Configurar DB_PASSWORD, etc

# 3. Subir containers
cd docker
docker compose up -d

# 4. Aguardar inicialização (automatizada via entrypoint)
docker compose logs -f app

# Output esperado:
# 🚀 Iniciando container de desenvolvimento SDC...
# 📦 Instalando dependências PHP (Composer)...
# ✅ Dependências PHP instaladas com sucesso!
# ⚙️  Arquivo .env não encontrado. Copiando .env.example...
# ✅ Arquivo .env criado
# 🔑 Gerando chave da aplicação...
# ✅ Chave da aplicação gerada!
# 🔐 Verificando permissões...
# ✅ Container de desenvolvimento pronto!

# 5. Executar migrations
docker compose exec app php artisan migrate --seed

# 6. Acessar aplicação
# Laravel: http://localhost
# MailHog: http://localhost:8025
# Vite HMR: http://localhost:5173
```

### Produção

```bash
# 1. Build das imagens
docker compose -f docker/docker-compose.prod.yml build

# 2. Subir serviços
docker compose -f docker/docker-compose.prod.yml up -d

# 3. Executar migrations (uma vez)
docker compose -f docker/docker-compose.prod.yml exec app php artisan migrate --force

# 4. Otimizar caches
docker compose -f docker/docker-compose.prod.yml exec app php artisan optimize

# 5. Health check
curl http://localhost/health
# Output: healthy
```

---

## 💾 Backup e Restore

### Backup Automático

O container `sdc_backup` executa backups automaticamente a cada 24 horas.

**Script**: `docker/backup/backup.sh`

**Variáveis**:
```bash
BACKUP_INTERVAL=86400        # 24 horas
BACKUP_RETENTION_DAYS=7      # Manter últimos 7 backups
```

### Processo de Backup

```
┌──────────────────────────────────────────────────────────┐
│  Container: sdc_backup                                   │
│                                                          │
│  ┌────────────────────────────────────────────────────┐ │
│  │ 1. Aguardar intervalo (24h)                        │ │
│  │ 2. Executar mysqldump                              │ │
│  │ 3. Comprimir com gzip                              │ │
│  │ 4. Salvar: db_YYYYMMDD_HHMMSS.sql.gz              │ │
│  │ 5. Remover backups > 7 dias                        │ │
│  │ 6. Log em backup.log                               │ │
│  │ 7. Voltar ao passo 1                               │ │
│  └────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────┘
```

### Comandos de Backup

**Listar backups**:
```bash
docker exec sdc_backup ls -lh /backup/data

# Output:
# -rw-r--r-- 1 root root 1.2M Jan 21 12:00 sdc_db_20250121_120000.sql.gz
# -rw-r--r-- 1 root root 1.1M Jan 20 12:00 sdc_db_20250120_120000.sql.gz
```

**Ver logs de backup**:
```bash
docker exec sdc_backup cat /backup/backup.log

# Output:
# [2025-01-21 12:00:01] Iniciando backup do banco de dados sdc_db...
# [2025-01-21 12:00:15] Backup criado com sucesso: sdc_db_20250121_120000.sql.gz (1.2M)
# [2025-01-21 12:00:15] Removendo backups mais antigos que 7 dias...
# [2025-01-21 12:00:15] Backup concluído com sucesso!
```

**Forçar backup manual**:
```bash
docker exec sdc_backup /backup/backup.sh
```

**Restaurar backup**:
```bash
# 1. Copiar backup para host
docker cp sdc_backup:/backup/data/sdc_db_20250121_120000.sql.gz ./

# 2. Descomprimir e restaurar
gunzip < sdc_db_20250121_120000.sql.gz | \
  docker exec -i sdc_db_dev mysql -u root -p${DB_PASSWORD} ${DB_DATABASE}

# 3. Limpar cache da aplicação
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
```

---

## 📊 Monitoramento

### Stack de Observabilidade

- **Prometheus**: Coleta de métricas
- **Grafana**: Dashboards e visualização
- **Loki**: Agregação de logs
- **Promtail**: Coleta de logs

### Acessos

| Serviço | URL | Credenciais |
|---------|-----|-------------|
| Grafana | https://grafana.seu-dominio.com | admin / (definido no .env) |
| Prometheus | https://prometheus.seu-dominio.com | (basic auth) |
| Traefik Dashboard | https://traefik.seu-dominio.com | (basic auth) |

### Alertas Configurados

- Application Down
- High Response Time (>2s)
- High Error Rate (>5%)
- MySQL Down / High Connections / Slow Queries
- Redis Down / High Memory
- High CPU/Memory/Disk Usage
- Container Issues

### Health Checks

Todos os serviços possuem health checks configurados:

```bash
# Verificar saúde de todos os containers
docker compose ps

# Inspecionar health check específico
docker inspect --format='{{json .State.Health}}' sdc_app_dev | jq

# Output:
# {
#   "Status": "healthy",
#   "FailingStreak": 0,
#   "Log": [
#     {
#       "Start": "2025-01-21T12:00:00Z",
#       "End": "2025-01-21T12:01:00Z",
#       "ExitCode": 0,
#       "Output": "healthy"
#     }
#   ]
# }
```

---

## 🔧 Troubleshooting

### Container não inicia

```bash
# Ver logs detalhados
docker compose logs -f app

# Verificar status
docker compose ps

# Reiniciar serviço específico
docker compose restart app
```

### Problemas de permissão

```bash
# Ajustar UID/GID no .env
HOST_UID=$(id -u)
HOST_GID=$(id -g)

# Rebuild
docker compose build --no-cache app
docker compose up -d
```

### MySQL não conecta

```bash
# Verificar se está pronto
docker compose exec db mysqladmin ping -h localhost

# Ver logs
docker compose logs db
```

### Performance lenta (Windows/Mac)

1. Use volumes nomeados para `vendor` e `node_modules`
2. Execute o Vite no host ao invés do container
3. Aumente recursos do Docker Desktop

### Limpar tudo

```bash
# Limpar containers e volumes
docker compose down -v

# Limpar TUDO (imagens incluídas)
docker system prune -a --volumes
```

---

## 🛠️ Comandos Úteis

### Desenvolvimento

```bash
# Subir todos os serviços
docker compose up -d

# Ver logs em tempo real
docker compose logs -f app

# Executar comandos Artisan
docker compose exec app php artisan migrate
docker compose exec app php artisan tinker
docker compose exec app php artisan test

# Instalar dependências
docker compose exec app composer install
docker compose exec node npm install

# Acessar shell do container
docker compose exec app bash
docker compose exec db mysql -u root -p${DB_PASSWORD}

# Limpar caches
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan view:clear

# Rebuild de imagens (após mudanças no Dockerfile)
docker compose build --no-cache app

# Parar e remover tudo
docker compose down -v
```

### Produção

```bash
# Build das imagens
docker compose -f docker/docker-compose.prod.yml build

# Subir serviços
docker compose -f docker/docker-compose.prod.yml up -d

# Ver status dos containers
docker compose ps

# Executar migrations
docker compose -f docker/docker-compose.prod.yml exec app php artisan migrate --force

# Otimizar para produção
docker compose exec app php artisan optimize
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache

# Ver uso de recursos
docker stats

# Ver health check de todos os serviços
docker compose ps
docker inspect --format='{{json .State.Health}}' sdc_app_prod | jq

# Logs de todos os serviços
docker compose logs --tail=100 -f

# Backup manual
docker exec sdc_backup /backup/backup.sh

# Zero-downtime deployment
docker compose -f docker/docker-compose.prod.yml up -d --no-deps --build app nginx
```

### Troubleshooting

```bash
# Verificar conectividade entre containers
docker compose exec app ping db
docker compose exec app ping redis

# Verificar DNS interno
docker compose exec app nslookup db

# Verificar portas abertas
docker compose exec app netstat -tuln

# Inspecionar volume
docker volume inspect sdc-dev_db_data_dev

# Ver logs de um serviço específico
docker compose logs --tail=50 app

# Entrar no container como root (troubleshooting)
docker compose exec -u root app bash

# Verificar versões
docker compose exec app php -v
docker compose exec app composer --version
docker compose exec node node --version

# Limpar tudo e recomeçar
docker compose down -v
docker system prune -a --volumes
docker compose up -d
```

---

## 🔐 Segurança

### Práticas Implementadas

1. **Usuário não-root**: Container `app` roda como `www-data`
2. **Secrets via Environment**: Senhas não commitadas no código
3. **Read-only volumes**: Assets estáticos em produção
4. **Health checks**: Detecta containers não saudáveis
5. **Nginx headers**: X-Frame-Options, X-Content-Type-Options, etc
6. **PHP expose_php=Off**: Oculta versão do PHP
7. **Arquivos sensíveis bloqueados**: `.env`, `.git` retornam 404
8. **SSL/TLS**: HTTPS configurado em produção

### Checklist de Segurança

- [ ] Alterar todas as senhas padrão no `.env`
- [ ] Configurar `APP_KEY` único
- [ ] Não expor portas desnecessárias (3306, 6379) em produção
- [ ] Usar certificados SSL válidos (Let's Encrypt)
- [ ] Habilitar firewall (UFW/iptables)
- [ ] Manter imagens Docker atualizadas
- [ ] Configurar rate limiting no Nginx
- [ ] Backups criptografados
- [ ] Monitoramento de logs (falhas de login)

---

## ⚖️ Diferenças Dev vs Prod

| Aspecto | Desenvolvimento | Produção |
|---------|----------------|-----------|
| **Dockerfile** | Single-stage (rápido rebuild) | Multi-stage (imagem menor) |
| **Código Fonte** | Bind mount (hot reload) | Copiado na imagem |
| **PHP Config** | `php.ini-development` (erros visíveis) | `php.ini-production` (erros ocultos) |
| **Memory Limit** | 512M | 256M |
| **OPcache** | Desabilitado | Habilitado e otimizado |
| **Xdebug** | Habilitado | Desabilitado |
| **Composer** | `--dev` | `--no-dev --optimize-autoloader` |
| **Assets** | HMR via Vite (port 5173) | Compilados no build |
| **Portas Expostas** | DB (3306), Redis (6379), MailHog | Apenas HTTP/HTTPS |
| **MailHog** | Incluído | Não incluído |
| **Jenkins** | Incluído (opcional) | Separado |
| **Backup** | Manual | Automático (24h) |
| **Health Checks** | Básico | Completo |
| **Restart Policy** | `unless-stopped` | `always` |
| **User** | `www-data` (UID/GID do host) | `www-data` |
| **Volumes** | Múltiplos bind mounts | Volumes nomeados apenas |

---

## 📚 Recursos Adicionais

### Documentação Relacionada

- [DOCKER_ARCHITECTURE.md](./DOCKER_ARCHITECTURE.md) - Arquitetura completa, comunicação entre containers e topologia de rede
- [docker_FLUXO_INICIALIZACAO.md](./docker_FLUXO_INICIALIZACAO.md) - Processo de inicialização passo a passo com timeline detalhada
- [docker_README.md](./docker_README.md) - Guia rápido de uso
- [JENKINS_SETUP.md](./JENKINS_SETUP.md) - Setup completo do Jenkins
- [CI_CD_JENKINS_COMMIT.md](./CI_CD_JENKINS_COMMIT.md) - Processo CI/CD detalhado

### Links Úteis

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Reference](https://docs.docker.com/compose/compose-file/)
- [Laravel Docker Best Practices](https://laravel.com/docs/deployment)
- [PHP-FPM Configuration](https://www.php.net/manual/en/install.fpm.configuration.php)
- [Nginx Performance Tuning](https://www.nginx.com/blog/tuning-nginx/)

---

## 🤝 Contribuindo

Para modificar a arquitetura Docker:

1. Faça mudanças nos arquivos `docker/` ou `docker-compose.*.yml`
2. Teste localmente: `docker compose build && docker compose up -d`
3. Documente as mudanças neste arquivo
4. Abra um PR com as alterações

---

## 📞 Suporte

Em caso de problemas:

1. Verifique os logs: `docker compose logs -f`
2. Consulte este README
3. Consulte a documentação relacionada
4. Abra uma issue no repositório

---

**Criado pela equipe SDC DevOps**  
**Última atualização**: 2025-01-21  
**Versão do documento**: 1.0.0

