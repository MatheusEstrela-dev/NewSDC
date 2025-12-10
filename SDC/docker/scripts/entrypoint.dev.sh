#!/bin/bash
# ============================================================================
# SDC - Entrypoint de Desenvolvimento
# ============================================================================

set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[OK]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Banner
echo ""
echo -e "${BLUE}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║${NC}        ${GREEN}SDC - Sistema de Defesa Civil${NC}                        ${BLUE}║${NC}"
echo -e "${BLUE}║${NC}        ${YELLOW}Ambiente de Desenvolvimento${NC}                          ${BLUE}║${NC}"
echo -e "${BLUE}╚══════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Criar diretórios de log
mkdir -p /var/log/php
chown -R www-data:www-data /var/log/php

# Aguardar banco de dados estar pronto
if [ -n "$DB_HOST" ]; then
    log_info "Aguardando banco de dados..."
    max_tries=30
    counter=0
    while ! nc -z "$DB_HOST" "${DB_PORT:-3306}" 2>/dev/null; do
        counter=$((counter+1))
        if [ $counter -ge $max_tries ]; then
            log_error "Timeout aguardando banco de dados"
            exit 1
        fi
        sleep 1
    done
    log_success "Banco de dados disponível"
fi

# Aguardar Redis estar pronto
if [ -n "$REDIS_HOST" ]; then
    log_info "Aguardando Redis..."
    max_tries=30
    counter=0
    while ! nc -z "$REDIS_HOST" "${REDIS_PORT:-6379}" 2>/dev/null; do
        counter=$((counter+1))
        if [ $counter -ge $max_tries ]; then
            log_error "Timeout aguardando Redis"
            exit 1
        fi
        sleep 1
    done
    log_success "Redis disponível"
fi

# Mudar para o diretório de trabalho
cd /var/www || {
    log_error "Não foi possível acessar /var/www"
    exit 1
}

# Verificar se composer.json existe (volume montado)
log_info "Verificando montagem do volume..."
log_info "Diretório atual: $(pwd)"
log_info "Conteúdo do diretório: $(ls -la /var/www | head -20)"

if [ ! -f "/var/www/composer.json" ]; then
    log_warning "composer.json não encontrado. Aguardando montagem do volume..."
    # Aguardar até 30 segundos pelo composer.json
    max_wait=30
    wait_count=0
    while [ ! -f "/var/www/composer.json" ] && [ $wait_count -lt $max_wait ]; do
        sleep 1
        wait_count=$((wait_count+1))
        if [ $((wait_count % 5)) -eq 0 ]; then
            log_info "Aguardando... ($wait_count/$max_wait segundos)"
            log_info "Conteúdo: $(ls -la /var/www 2>/dev/null | head -10 || echo 'Diretório não acessível')"
        fi
    done
    
    if [ ! -f "/var/www/composer.json" ]; then
        log_error "composer.json não encontrado após aguardar $max_wait segundos."
        log_error "Conteúdo de /var/www:"
        ls -la /var/www 2>/dev/null || echo "Diretório não existe ou não é acessível"
        log_error "Verifique se o volume está montado corretamente no docker-compose.yml"
        exit 1
    fi
    log_success "composer.json encontrado"
fi

# Criar .env se não existir
if [ ! -f "/var/www/.env" ]; then
    log_info "Arquivo .env não encontrado. Criando a partir do .env.example..."
    if [ -f "/var/www/.env.example" ]; then
        cp /var/www/.env.example /var/www/.env
        log_success ".env criado a partir do .env.example"
    else
        log_warning ".env.example não encontrado. Criando .env básico..."
        cat > /var/www/.env <<EOF
APP_NAME=SDC
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=sdc
DB_USERNAME=sdc
DB_PASSWORD=secret

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@localhost"
MAIL_FROM_NAME="\${APP_NAME}"
EOF
        log_success ".env básico criado"
    fi
fi

# Instalar dependências se não existirem
if [ ! -d "/var/www/vendor" ] || [ ! -f "/var/www/vendor/autoload.php" ]; then
    log_info "Instalando dependências do Composer..."
    cd /var/www
    composer install --no-interaction --prefer-dist
    log_success "Dependências instaladas"
fi

# Gerar APP_KEY se não existir
if [ -z "$APP_KEY" ] || [ "$APP_KEY" == "base64:" ]; then
    if [ -f "/var/www/.env" ] && grep -q "APP_KEY=base64:" /var/www/.env; then
        log_success "APP_KEY já configurada"
    else
        log_info "Gerando APP_KEY..."
        cd /var/www
        php artisan key:generate --force
        log_success "APP_KEY gerada"
    fi
fi

# Executar migrations se necessário
if [ "$RUN_MIGRATIONS" == "true" ]; then
    log_info "Executando migrations..."
    php artisan migrate --force
    log_success "Migrations executadas"
fi

# Limpar caches de desenvolvimento
log_info "Limpando caches..."
cd /var/www
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
log_success "Caches limpos"

# Permissões
log_info "Ajustando permissões..."
cd /var/www
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
log_success "Permissões ajustadas"

# Mostrar informações
echo ""
log_info "PHP Version: $(php -v | head -n1)"
log_info "Composer Version: $(composer --version | head -n1)"
echo ""

# Executar comando passado
log_info "Iniciando: $@"
exec "$@"

