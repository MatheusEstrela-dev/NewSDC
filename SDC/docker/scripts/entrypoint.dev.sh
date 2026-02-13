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

# ----------------------------------------------------------------------------
# Composer dependencies
# ----------------------------------------------------------------------------
# Em dev usamos volume nomeado para /var/www/vendor. Quando o composer.lock muda
# (nova dependência), o vendor pode ficar "velho" e causar erros como
# "Trait/Class not found". Para evitar isso, guardamos um fingerprint e
# reinstalamos automaticamente quando houver mudança.
composer_fingerprint() {
    # Preferir composer.lock (estado real instalado). Fallback para composer.json.
    if [ -f "/var/www/composer.lock" ]; then
        sha256sum /var/www/composer.lock | awk '{print $1}'
    else
        sha256sum /var/www/composer.json | awk '{print $1}'
    fi
}

ensure_composer_dependencies() {
    local vendor_autoload="/var/www/vendor/autoload.php"
    local fingerprint_file="/var/www/vendor/.sdc-composer-fingerprint.sha256"
    local current_fp
    current_fp="$(composer_fingerprint)"

    # Se vendor/autoload.php existe e fingerprint bate, nada a fazer
    if [ -f "$vendor_autoload" ] && [ -f "$fingerprint_file" ]; then
        local previous_fp
        previous_fp="$(cat "$fingerprint_file" 2>/dev/null || echo '')"
        if [ "$previous_fp" = "$current_fp" ]; then
            log_success "Dependências Composer já estão atualizadas (fingerprint OK)"
            return 0
        fi
        log_warning "composer.lock/composer.json mudou. Tentando atualizar dependências..."
    elif [ -f "$vendor_autoload" ]; then
        log_info "Vendor encontrado mas sem fingerprint. Tentando atualizar..."
    else
        log_info "vendor/autoload.php não encontrado. Tentando instalar dependências..."
    fi

    # Tentar composer install com timeout (rede pode não estar disponível)
    cd /var/www
    if timeout 120 composer install --no-interaction --prefer-dist 2>&1; then
        echo "$current_fp" > "$fingerprint_file" 2>/dev/null || true
        log_success "Dependências Composer instaladas/atualizadas"
    else
        if [ -f "$vendor_autoload" ]; then
            log_warning "Composer install falhou (sem rede?), mas vendor/autoload.php existe. Continuando com dependências do build..."
            echo "$current_fp" > "$fingerprint_file" 2>/dev/null || true
        else
            log_error "Composer install falhou e vendor/autoload.php não existe."
            log_error "Verifique a conexão de rede dos containers ou rebuild a imagem: docker compose build app"
            exit 1
        fi
    fi
}

ensure_composer_dependencies

# Gerar manifesto de pacotes (necessário pois --no-scripts foi usado no build)
log_info "Gerando manifesto de pacotes..."
php artisan package:discover --ansi || log_warning "Falha ao descobrir pacotes via artisan"

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
    if php artisan migrate --force 2>&1; then
        log_success "Migrations executadas"
    else
        log_warning "Algumas migrations falharam (tabelas/colunas já existem?). Continuando..."
    fi
fi

# Executar seeders se habilitado (dados mock para desenvolvimento)
if [ "$RUN_SEEDERS" == "true" ]; then
    log_info "Executando seeders (mock data: usuários, hierarquias, RATs)..."
    cd /var/www
    php artisan db:seed --force --class=DatabaseSeeder 2>/dev/null && \
        log_success "Seeders executados (30+ usuários, 15 RATs, 7 hierarquias)" || \
        log_warning "Erro ao executar seeders"
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
# ----------------------------------------------------------------------------
# Octane (RoadRunner) Setup
# ----------------------------------------------------------------------------
log_info "Configurando Octane (RoadRunner)..."

# Instalar binário do RoadRunner se não existir
if [ ! -f "/var/www/rr" ]; then
    log_info "Binário RoadRunner não encontrado. Instalando..."
    cd /var/www
    # Tenta usar o comando do octane primeiro
    php artisan octane:install --server=roadrunner --no-interaction || {
        log_warning "Falha ao instalar via artisan. Tentando baixar diretamente..."
        vendor/bin/rr get-binary --no-interaction || log_error "Falha ao baixar binário RoadRunner"
    }
    chmod +x rr 2>/dev/null || true
    log_success "RoadRunner instalado"
fi

# Iniciar Octane com Watch (hot reload)
log_info "Iniciando Octane com Watch..."
echo ""
echo -e "${GREEN}🚀 Servidor Octane (RoadRunner) iniciando em http://0.0.0.0:8000${NC}"
echo -e "${BLUE}ℹ️  Modo Watch ativado (File changes trigger reload)${NC}"
echo ""

# Executar comando
exec php artisan octane:start --server=roadrunner --host=0.0.0.0 --port=8000 --watch

