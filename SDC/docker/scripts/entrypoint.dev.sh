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

cd /var/www

# Instalar dependências se não existirem
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    log_info "Instalando dependências do Composer..."
    composer install --no-interaction --prefer-dist
    log_success "Dependências instaladas"
fi

# Gerar APP_KEY se não existir
if [ -z "$APP_KEY" ] || [ "$APP_KEY" == "base64:" ]; then
    if [ -f ".env" ] && grep -q "APP_KEY=base64:" .env; then
        log_success "APP_KEY já configurada"
    else
        log_info "Gerando APP_KEY..."
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
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
log_success "Caches limpos"

# Permissões
log_info "Ajustando permissões..."
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

