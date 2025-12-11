# 🔧 Corrigir Erro: Composer.json não encontrado no Container

## 🔴 Problema Identificado

**Erro nos logs:**
```
Composer could not find a composer.json file in /var/www
To initialize a project, please create a composer.json file.
```

**Causa:**
O Dockerfile não está copiando os arquivos da aplicação Laravel para dentro do container. O container inicia, mas não tem o código-fonte.

---

## ✅ Solução: Criar Dockerfile de Produção Correto

### Opção 1: Dockerfile Multi-Stage (Recomendado)

Criar arquivo: `SDC/docker/Dockerfile.prod`

```dockerfile
# ============================================================================
# SDC - PRODUCTION DOCKERFILE
# Build otimizado para Azure App Service
# ============================================================================

# ============================================================================
# STAGE 1: Build Dependencies
# ============================================================================
FROM php:8.3-fpm-alpine AS builder

# Instalar dependências de compilação
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev \
    oniguruma-dev \
    libxml2-dev \
    libzip-dev \
    postgresql-dev \
    icu-dev \
    linux-headers \
    $PHPIZE_DEPS

# Instalar extensões PHP necessárias
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    pdo_pgsql \
    mysqli \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache

# Instalar Redis
RUN pecl install redis-6.0.2 \
    && docker-php-ext-enable redis

# Instalar Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Diretório de trabalho
WORKDIR /var/www

# Copiar apenas composer.json e composer.lock primeiro (cache layer)
COPY composer.json composer.lock ./

# Instalar dependências PHP (sem dev)
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

# ============================================================================
# STAGE 2: Build Frontend Assets
# ============================================================================
FROM node:20-alpine AS frontend

WORKDIR /var/www

# Copiar package files
COPY package*.json ./

# Instalar dependências Node
RUN npm ci --only=production

# Copiar código fonte
COPY . .

# Build assets
RUN npm run build

# ============================================================================
# STAGE 3: Production Image
# ============================================================================
FROM php:8.3-fpm-alpine

# Labels
LABEL maintainer="SDC Team"
LABEL environment="production"

# Instalar apenas dependências runtime
RUN apk add --no-cache \
    bash \
    curl \
    nginx \
    supervisor \
    libpng \
    libjpeg-turbo \
    freetype \
    libwebp \
    oniguruma \
    libxml2 \
    libzip \
    postgresql-libs \
    icu-libs \
    fcgi

# Copiar extensões do builder
COPY --from=builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

# Configuração PHP para produção
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Copiar configurações customizadas
COPY docker/config/php/prod.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/config/php-fpm/prod.conf /usr/local/etc/php-fpm.d/zz-docker.conf
COPY docker/config/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/config/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Criar usuário www-data
RUN addgroup -g 1000 www-data \
    && adduser -u 1000 -G www-data -h /var/www -s /bin/sh -D www-data

# Diretório de trabalho
WORKDIR /var/www

# Copiar vendor do builder
COPY --from=builder --chown=www-data:www-data /var/www/vendor ./vendor

# Copiar assets do frontend
COPY --from=frontend --chown=www-data:www-data /var/www/public/build ./public/build

# Copiar código da aplicação
COPY --chown=www-data:www-data . .

# Criar diretórios necessários e ajustar permissões
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Otimizar autoloader
RUN composer dump-autoload --optimize --classmap-authoritative

# Scripts
COPY docker/scripts/entrypoint.prod.sh /usr/local/bin/entrypoint.sh
COPY docker/scripts/healthcheck.sh /usr/local/bin/healthcheck.sh

RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/healthcheck.sh

# Healthcheck
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD /usr/local/bin/healthcheck.sh

# Expor porta
EXPOSE 8000

# Entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
```

### Criar Entrypoint de Produção

Arquivo: `SDC/docker/scripts/entrypoint.prod.sh`

```bash
#!/bin/bash
set -e

echo "🚀 Starting SDC Application (Production)"

# Aguardar banco de dados (se necessário)
if [ -n "$DB_HOST" ]; then
    echo "⏳ Waiting for database..."
    timeout 60 sh -c 'until nc -z $DB_HOST ${DB_PORT:-3306}; do sleep 1; done' || echo "⚠️ Database timeout"
fi

# Gerar APP_KEY se não existir
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "🔑 Generating APP_KEY..."
    php artisan key:generate --force
fi

# Executar migrations (produção)
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "📊 Running migrations..."
    php artisan migrate --force
fi

# Otimizar cache
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Limpar caches antigos
php artisan optimize:clear

echo "✅ Application ready!"

# Executar comando
exec "$@"
```

### Criar Configuração do Nginx

Arquivo: `SDC/docker/config/nginx/default.conf`

```nginx
server {
    listen 8000;
    listen [::]:8000;
    server_name _;
    root /var/www/public;

    index index.php;

    charset utf-8;

    # Logs
    access_log /var/log/nginx/access.log;
    error_log /var/log/nginx/error.log;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Laravel
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Criar Configuração do Supervisor

Arquivo: `SDC/docker/config/supervisor/supervisord.conf`

```ini
[supervisord]
nodaemon=true
user=root
logfile=/var/log/supervisor/supervisord.log
pidfile=/var/run/supervisord.pid

[program:php-fpm]
command=php-fpm -F
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
autorestart=true
startretries=0

[program:nginx]
command=nginx -g 'daemon off;'
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
autorestart=true
startretries=0
```

---

## 🔨 Build e Deploy

### 1. Build da Imagem

```bash
cd SDC

# Build com tag
docker build -f docker/Dockerfile.prod -t sdc-dev-app:latest .

# Tag para ACR
docker tag sdc-dev-app:latest apidover.azurecr.io/sdc-dev-app:latest

# Push para ACR
az acr login --name apidover
docker push apidover.azurecr.io/sdc-dev-app:latest
```

### 2. Atualizar App Service

```bash
# Atualizar container
az webapp config container set \
  --name newsdc2027 \
  --resource-group DEFESA_CIVIL \
  --docker-custom-image-name apidover.azurecr.io/sdc-dev-app:latest

# Configurar variáveis de ambiente
az webapp config appsettings set \
  --name newsdc2027 \
  --resource-group DEFESA_CIVIL \
  --settings \
    WEBSITES_PORT="8000" \
    APP_NAME="SDC" \
    APP_ENV="production" \
    APP_DEBUG="false" \
    APP_URL="https://newsdc2027.azurewebsites.net" \
    RUN_MIGRATIONS="false" \
    DB_CONNECTION="sqlite" \
    WEBSITES_ENABLE_APP_SERVICE_STORAGE="true"

# Gerar APP_KEY
APP_KEY=$(docker run --rm php:8.3-cli php -r "echo 'base64:' . base64_encode(random_bytes(32)) . PHP_EOL;")
az webapp config appsettings set \
  --name newsdc2027 \
  --resource-group DEFESA_CIVIL \
  --settings APP_KEY="$APP_KEY"

# Reiniciar
az webapp restart --name newsdc2027 --resource-group DEFESA_CIVIL
```

### 3. Verificar

```bash
# Ver logs
az webapp log tail --name newsdc2027 --resource-group DEFESA_CIVIL

# Testar site
curl https://newsdc2027.azurewebsites.net/

# Health check
curl https://newsdc2027.azurewebsites.net/health
```

---

## 📝 Atualizar Jenkinsfile

Atualizar linha do build no Jenkinsfile:

```groovy
stage('Build Docker Images') {
    steps {
        echo '🏗️ Building Docker images...'

        script {
            // Build com Dockerfile de produção
            sh '''
                cd SDC
                docker build -f docker/Dockerfile.prod -t sdc-dev-app:latest .
            '''
        }
    }
}
```

---

## ✅ Checklist

- [ ] Criar `SDC/docker/Dockerfile.prod`
- [ ] Criar `SDC/docker/scripts/entrypoint.prod.sh`
- [ ] Criar `SDC/docker/config/nginx/default.conf`
- [ ] Criar `SDC/docker/config/supervisor/supervisord.conf`
- [ ] Criar `SDC/docker/config/php/prod.ini`
- [ ] Build e push para ACR
- [ ] Configurar variáveis no App Service
- [ ] Gerar e configurar APP_KEY
- [ ] Testar aplicação

---

<div align="center">

**🔧 Correção: Composer.json não encontrado**

*Criar Dockerfile de produção correto*

</div>
