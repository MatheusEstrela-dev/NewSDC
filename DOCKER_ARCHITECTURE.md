# 🐳 Arquitetura Docker do Projeto SDC

Documentação completa da arquitetura Docker atual do projeto SDC, detalhando todos os containers, volumes, networks e processos implementados.

---

## 📋 Índice

- [Visão Geral](#-visão-geral)
- [Estrutura de Arquivos](#-estrutura-de-arquivos)
- [Containers e Serviços](#-containers-e-serviços)
- [Dockerfiles](#-dockerfiles)
- [Networking](#-networking)
- [Volumes e Persistência](#-volumes-e-persistência)
- [Processo de Inicialização](#-processo-de-inicialização)
- [Backup Automático](#-backup-automático)
- [Diferenças Dev vs Prod](#-diferenças-dev-vs-prod)
- [Comandos Úteis](#-comandos-úteis)

---

## 🏗️ Visão Geral

O projeto SDC utiliza uma arquitetura Docker multi-container para separar responsabilidades e facilitar o desenvolvimento e deploy. A aplicação é baseada em Laravel e utiliza PHP-FPM + Nginx + MySQL + Redis.

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
│  │ • sdc_db_data (MySQL data)                      │         │
│  │ • jenkins_home (Jenkins configs)                │         │
│  │ • backup_data (Database backups)                │         │
│  │ • ./: (código fonte - bind mount)               │         │
│  └─────────────────────────────────────────────────┘         │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📁 Estrutura de Arquivos

```
SDC/
├── docker/                           # Configurações Docker
│   ├── Dockerfile.dev                # Dockerfile para desenvolvimento
│   ├── Dockerfile.prod               # Dockerfile para produção
│   ├── entrypoint.dev.sh             # Script de inicialização (dev)
│   ├── install-dependencies.sh       # Instalador de dependências
│   │
│   ├── nginx/                        # Configurações Nginx
│   │   ├── default.conf              # Config desenvolvimento
│   │   └── prod.conf                 # Config produção (otimizada)
│   │
│   ├── mysql/                        # Configurações MySQL
│   │   └── my.cnf                    # Otimizações MySQL
│   │
│   └── backup/                       # Scripts de backup
│       ├── backup.sh                 # Backup automático do banco
│       ├── restore.sh                # Restauração de backup
│       └── backup.log                # Log de backups
│
├── docker-compose.dev.yml            # Compose para desenvolvimento
├── docker-compose.prod.yml           # Compose para produção
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
- Composer
- Extensões PHP: pdo_mysql, mbstring, exif, pcntl, bcmath, gd, zip, redis, opcache

**Portas Expostas**: Nenhuma (comunica via network interna)

**Volumes**:
```yaml
# Desenvolvimento (bind mount)
- ./:/var/www
- /var/www/vendor         # Volume anônimo (cache)
- /var/www/node_modules   # Volume anônimo (cache)

# Produção (imagem construída)
- app_public:/var/www/public:ro  # Read-only
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
```

**Health Check** (Produção):
```yaml
test: ["CMD", "php-fpm-healthcheck"]
interval: 30s
timeout: 10s
retries: 3
start_period: 40s
```

---

### 2. **Nginx (Reverse Proxy)**

**Container**: `sdc_nginx` (dev) | `sdc_nginx_prod` (prod)

**Responsabilidade**: Servir arquivos estáticos e fazer proxy para PHP-FPM

**Imagem**: `nginx:alpine` (leve e segura)

**Portas Expostas**:
- `80:80` - HTTP
- `443:443` - HTTPS (prod)

**Volumes**:
```yaml
# Desenvolvimento
- ./:/var/www
- ./docker/nginx:/etc/nginx/conf.d

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

**Container**: `sdc_db` (dev) | `sdc_db_prod` (prod)

**Responsabilidade**: Armazenar dados da aplicação

**Imagem**: `mysql:8.0`

**Portas Expostas**:
- `3306:3306` (dev apenas - para acesso externo)

**Volumes**:
```yaml
- sdc_db_data:/var/lib/mysql         # Dados persistentes
- ./docker/mysql/my.cnf:/etc/mysql/conf.d/custom.cnf:ro
```

**Variáveis de Ambiente**:
```bash
MYSQL_DATABASE=${DB_DATABASE}
MYSQL_ROOT_PASSWORD=${DB_ROOT_PASSWORD}
MYSQL_PASSWORD=${DB_PASSWORD}
MYSQL_USER=${DB_USERNAME}
```

**Comando de Inicialização**:
```bash
--default-authentication-plugin=mysql_native_password
--character-set-server=utf8mb4
--collation-server=utf8mb4_unicode_ci
```

**Health Check** (Produção):
```yaml
test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
interval: 30s
timeout: 10s
retries: 3
start_period: 30s
```

**Otimizações** (my.cnf):
```ini
[mysqld]
max_connections = 200
innodb_buffer_pool_size = 512M
innodb_log_file_size = 128M
query_cache_size = 64M
```

---

### 4. **Redis (Cache/Session/Queue)**

**Container**: `sdc_redis` (dev) | `sdc_redis_prod` (prod)

**Responsabilidade**: Cache, sessões e filas

**Imagem**: `redis:alpine`

**Portas Expostas**:
- `6379:6379` (dev apenas)

**Volumes**:
```yaml
# Produção apenas (persistência)
- sdc_redis_data:/data
```

**Comando** (Produção):
```bash
redis-server --appendonly yes --requirepass ${REDIS_PASSWORD}
```

**Health Check**:
```yaml
test: ["CMD", "redis-cli", "ping"]
interval: 30s
timeout: 10s
retries: 3
```

---

### 5. **Node (Vite Dev Server)**

**Container**: `sdc_node`

**Responsabilidade**: Compilar assets frontend (JavaScript, CSS) em tempo real

**Imagem**: `node:20-alpine`

**Portas Expostas**:
- `5173:5173` - Vite HMR (Hot Module Replacement)

**Volumes**:
```yaml
- ./:/var/www
- /var/www/node_modules  # Cache
```

**Comando**:
```bash
sh -c "if [ ! -d 'node_modules' ] || [ ! -f 'node_modules/.package-lock.json' ]; then npm install; fi && npm run dev -- --host"
```

**Uso**:
- Desenvolvimento: Assets são servidos via HMR (hot reload)
- Produção: Assets são compilados no build (`npm run build`)

---

### 6. **Queue Worker (Produção)**

**Container**: `sdc_queue_prod`

**Responsabilidade**: Processar jobs assíncronos (emails, notificações, etc)

**Imagem**: Mesma do `app` (PHP-FPM)

**Comando**:
```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

**Variáveis**:
```bash
APP_ENV=production
REDIS_HOST=redis
```

---

### 7. **Scheduler (Produção)**

**Container**: `sdc_scheduler_prod`

**Responsabilidade**: Executar tarefas agendadas (Laravel Scheduler)

**Imagem**: Mesma do `app` (PHP-FPM)

**Comando**:
```bash
sh -c "while true; do php artisan schedule:run --verbose --no-interaction & sleep 60; done"
```

---

### 8. **MailHog (Desenvolvimento apenas)**

**Container**: `sdc_mailhog`

**Responsabilidade**: Capturar emails enviados pela aplicação (teste)

**Imagem**: `mailhog/mailhog`

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

### 9. **Jenkins (CI/CD)**

**Container**: `sdc_jenkins`

**Responsabilidade**: Automação de CI/CD

**Imagem**: `jenkins/jenkins:lts`

**Portas Expostas**:
- `8080:8080` - Web UI
- `50000:50000` - Agentes JNLP

**Volumes**:
```yaml
- jenkins_home:/var/jenkins_home              # Persistência
- ./:/var/jenkins_workspace                   # Código fonte
- /var/run/docker.sock:/var/run/docker.sock  # Docker-in-Docker
```

**User**: `root` (necessário para acessar Docker socket)

> ⚠️ **Nota**: Jenkins está incluído no dev para testes locais. Em produção, recomenda-se Jenkins separado. Ver [docker-compose.jenkins.yml](docker-compose.jenkins.yml)

---

### 10. **Backup (Automático)**

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

**Comando**:
```bash
sh -c "
  apk add --no-cache mysql-client bash &&
  chmod +x /backup/*.sh &&
  while true; do
    /backup/backup.sh >> /backup/backup.log 2>&1;
    sleep ${BACKUP_INTERVAL:-86400};
  done
"
```

**Script de Backup** ([docker/backup/backup.sh](SDC/docker/backup/backup.sh)):
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

**Restaurar Backup**:
```bash
# Listar backups
docker exec sdc_backup ls -lh /backup/data

# Restaurar
gunzip < backup_20250121_120000.sql.gz | docker exec -i sdc_db mysql -u root -p${DB_PASSWORD} ${DB_DATABASE}
```

---

## 📝 Dockerfiles

### Dockerfile.dev (Desenvolvimento)

**Localização**: [SDC/docker/Dockerfile.dev](SDC/docker/Dockerfile.dev)

**Características**:
- Single-stage build (mais rápido para rebuild)
- Bind mounts para código fonte (hot reload)
- Ferramentas de desenvolvimento incluídas (vim, gosu)
- PHP configurado para desenvolvimento (erros visíveis)
- Entrypoint script para auto-setup

**Estrutura**:
```dockerfile
FROM php:8.3-fpm

# Instalar dependências do sistema + extensões PHP
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip \
    libzip-dev libpq-dev vim gosu \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && pecl install redis && docker-php-ext-enable redis

# Copiar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar PHP para desenvolvimento
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" \
    && echo "memory_limit = 512M" >> "$PHP_INI_DIR/conf.d/custom.ini"

# Copiar e configurar entrypoint
COPY docker/entrypoint.dev.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
```

**Entrypoint Script** ([docker/entrypoint.dev.sh](SDC/docker/entrypoint.dev.sh)):

Automatiza tarefas de inicialização:

```bash
#!/bin/bash
set -e

# 1. Instalar dependências Composer (se vendor/ não existe)
if [ ! -d "/var/www/vendor" ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
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
exec gosu www-data "$@"
```

---

### Dockerfile.prod (Produção)

**Localização**: [SDC/docker/Dockerfile.prod](SDC/docker/Dockerfile.prod)

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
RUN apt-get update && apt-get install -y git curl libpng-dev ... \
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
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

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

# Configurações customizadas
RUN { \
    echo 'memory_limit = 256M'; \
    echo 'upload_max_filesize = 32M'; \
    echo 'post_max_size = 32M'; \
    echo 'max_execution_time = 60'; \
    echo 'expose_php = Off';  # Segurança: ocultar versão PHP
    } > /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www

# Copiar código compilado do builder
COPY --from=builder --chown=www-data:www-data /var/www /var/www

# Ajustar permissões
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Health check script
RUN echo '#!/bin/sh' > /usr/local/bin/php-fpm-healthcheck \
    && echo 'SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000' >> /usr/local/bin/php-fpm-healthcheck \
    && chmod +x /usr/local/bin/php-fpm-healthcheck

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

**Subnet**: `172.25.0.0/16` (customizado em prod)

**Containers Conectados**:
- app
- nginx
- db
- redis
- node
- mailhog (dev)
- jenkins (dev)
- backup
- queue (prod)
- scheduler (prod)

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

**Conexão com Redes Externas**:

Para conectar com outros stacks Docker (ex: Jenkins externo):

```yaml
networks:
  sdc_network:
    driver: bridge
  jenkins_network:
    external: true  # Rede criada fora deste compose
```

---

## 💾 Volumes e Persistência

### Volumes Named (Persistentes)

#### 1. `sdc_db_data`

**Conteúdo**: Dados do MySQL
**Localização**: `/var/lib/docker/volumes/sdc_db_data/_data`
**Tamanho Médio**: 100MB - 10GB (depende da aplicação)

**Backup**:
```bash
# Criar backup
docker run --rm -v sdc_db_data:/data -v $(pwd):/backup alpine tar czf /backup/db_data_backup.tar.gz -C /data .

# Restaurar backup
docker run --rm -v sdc_db_data:/data -v $(pwd):/backup alpine sh -c "rm -rf /data/* && tar xzf /backup/db_data_backup.tar.gz -C /data"
```

#### 2. `sdc_redis_data` (Produção)

**Conteúdo**: Dados persistentes do Redis (se AOF habilitado)
**Localização**: `/var/lib/docker/volumes/sdc_redis_data/_data`

#### 3. `jenkins_home`

**Conteúdo**: Configurações, plugins e jobs do Jenkins
**Localização**: `/var/lib/docker/volumes/jenkins_home/_data`
**Tamanho Médio**: 1GB - 5GB

#### 4. `backup_data`

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
docker-compose -f docker-compose.dev.yml up -d

# 4. Aguardar inicialização (automatizada via entrypoint)
docker-compose logs -f app

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
docker-compose exec app php artisan migrate --seed

# 6. Acessar aplicação
# Laravel: http://localhost
# MailHog: http://localhost:8025
# Vite HMR: http://localhost:5173
```

### Produção

```bash
# 1. Build das imagens
docker-compose -f docker-compose.prod.yml build

# 2. Subir serviços
docker-compose -f docker-compose.prod.yml up -d

# 3. Executar migrations (uma vez)
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

# 4. Otimizar caches
docker-compose -f docker-compose.prod.yml exec app php artisan optimize

# 5. Health check
curl http://localhost/health
# Output: healthy
```

---

## 📦 Backup Automático

### Configuração

O container `sdc_backup` executa backups automaticamente a cada 24 horas.

**Script**: [docker/backup/backup.sh](SDC/docker/backup/backup.sh)

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
  docker exec -i sdc_db mysql -u root -p${DB_PASSWORD} ${DB_DATABASE}

# 3. Limpar cache da aplicação
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
```

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
| **Health Checks** | Desabilitado | Habilitado |
| **Restart Policy** | `unless-stopped` | `always` |
| **User** | `root` → `www-data` (entrypoint) | `www-data` |
| **Volumes** | Múltiplos bind mounts | Volumes nomeados apenas |

---

## 🛠️ Comandos Úteis

### Desenvolvimento

```bash
# Subir todos os serviços
docker-compose -f docker-compose.dev.yml up -d

# Ver logs em tempo real
docker-compose logs -f app

# Executar comandos Artisan
docker-compose exec app php artisan migrate
docker-compose exec app php artisan tinker
docker-compose exec app php artisan test

# Instalar dependências
docker-compose exec app composer install
docker-compose exec node npm install

# Acessar shell do container
docker-compose exec app bash
docker-compose exec db mysql -u root -p${DB_PASSWORD}

# Limpar caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Rebuild de imagens (após mudanças no Dockerfile)
docker-compose build --no-cache app

# Parar e remover tudo
docker-compose down -v
```

### Produção

```bash
# Build das imagens
docker-compose -f docker-compose.prod.yml build

# Subir serviços
docker-compose -f docker-compose.prod.yml up -d

# Ver status dos containers
docker-compose ps

# Executar migrations
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

# Otimizar para produção
docker-compose exec app php artisan optimize
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# Ver uso de recursos
docker stats

# Ver health check de todos os serviços
docker-compose ps
docker inspect --format='{{json .State.Health}}' sdc_app_prod | jq

# Logs de todos os serviços
docker-compose logs --tail=100 -f

# Backup manual
docker exec sdc_backup /backup/backup.sh

# Zero-downtime deployment
docker-compose -f docker-compose.prod.yml up -d --no-deps --build app nginx
```

### Troubleshooting

```bash
# Verificar conectividade entre containers
docker-compose exec app ping db
docker-compose exec app ping redis

# Verificar DNS interno
docker-compose exec app nslookup db

# Verificar portas abertas
docker-compose exec app netstat -tuln

# Inspecionar volume
docker volume inspect sdc_db_data

# Ver logs de um serviço específico
docker-compose logs --tail=50 app

# Entrar no container como root (troubleshooting)
docker-compose exec -u root app bash

# Verificar versões
docker-compose exec app php -v
docker-compose exec app composer --version
docker-compose exec node node --version

# Limpar tudo e recomeçar
docker-compose down -v
docker system prune -a --volumes
docker-compose up -d
```

---

## 📊 Monitoramento e Métricas

### Health Checks

Todos os serviços de produção possuem health checks configurados:

```bash
# Verificar saúde de todos os containers
docker-compose ps

# Inspecionar health check específico
docker inspect --format='{{json .State.Health}}' sdc_app_prod | jq

# Output:
# {
#   "Status": "healthy",
#   "FailingStreak": 0,
#   "Log": [
#     {
#       "Start": "2025-01-21T12:00:00Z",
#       "End": "2025-01-21T12:00:01Z",
#       "ExitCode": 0,
#       "Output": "healthy"
#     }
#   ]
# }
```

### Logs Centralizados

```bash
# Ver logs de todos os serviços
docker-compose logs -f

# Ver logs apenas de erros
docker-compose logs -f | grep -i error

# Exportar logs para arquivo
docker-compose logs --no-color > logs_$(date +%Y%m%d).txt
```

### Métricas de Recursos

```bash
# Ver uso em tempo real
docker stats

# Output:
# CONTAINER ID   NAME             CPU %     MEM USAGE / LIMIT     MEM %
# abc123         sdc_app_prod     2.50%     180MiB / 512MiB       35.16%
# def456         sdc_nginx_prod   0.10%     12MiB / 128MiB        9.38%
# ghi789         sdc_db_prod      1.20%     350MiB / 1GiB         34.18%

# Ver métricas de um container específico
docker stats sdc_app_prod --no-stream
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
- [ ] Não expor portas desnecessárias (3306, 6379)
- [ ] Usar certificados SSL válidos (Let's Encrypt)
- [ ] Habilitar firewall (UFW/iptables)
- [ ] Manter imagens Docker atualizadas
- [ ] Configurar rate limiting no Nginx
- [ ] Backups criptografados
- [ ] Monitoramento de logs (falhas de login)

---

## 📚 Recursos Adicionais

### Documentação Relacionada

- [JENKINS_SETUP.md](JENKINS_SETUP.md) - Setup completo do Jenkins
- [CI_CD_JENKINS_COMMIT.md](CI_CD_JENKINS_COMMIT.md) - Processo CI/CD detalhado
- [jenkins02.md](jenkins02.md) - Problemas comuns do Jenkins em Docker

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
2. Teste localmente: `docker-compose build && docker-compose up -d`
3. Documente as mudanças neste arquivo
4. Abra um PR com as alterações

---

**Criado pela equipe SDC DevOps**
**Última atualização**: 2025-01-21
**Versão do documento**: 1.0.0
