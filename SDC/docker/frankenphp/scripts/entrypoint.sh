#!/bin/sh
# ============================================================================
# SDC - Entrypoint FrankenPHP (Producao)
# Inicializa aplicacao Laravel com Octane FrankenPHP
# ============================================================================

set -e

cd /app

echo "=== SDC FrankenPHP Entrypoint ==="
echo "Ambiente: ${APP_ENV:-production}"

# ── Docker Secrets ──
# Le arquivos de /run/secrets/ e exporta como variaveis de ambiente
for secret_file in /run/secrets/*; do
    if [ -f "$secret_file" ]; then
        var_name=$(basename "$secret_file" | tr '[:lower:]' '[:upper:]')
        export "$var_name"="$(cat "$secret_file")"
        echo "Secret carregada: $var_name"
    fi
done

# Criar .env minimo se nao existir (usa variaveis de ambiente do container)
if [ ! -f .env ]; then
    echo "Criando .env minimo..."
    cat > .env <<EOF
APP_NAME=${APP_NAME:-SDC}
APP_ENV=${APP_ENV:-local}
APP_KEY=${APP_KEY:-}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-http://localhost}
OCTANE_SERVER=frankenphp
DB_CONNECTION=${DB_CONNECTION:-mysql}
DB_HOST=${DB_HOST:-localhost}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-sdc}
DB_USERNAME=${DB_USERNAME:-sdc}
DB_PASSWORD=${DB_PASSWORD:-}
REDIS_HOST=${REDIS_HOST:-redis}
REDIS_PORT=${REDIS_PORT:-6379}
CACHE_DRIVER=${CACHE_DRIVER:-redis}
SESSION_DRIVER=${SESSION_DRIVER:-redis}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-redis}
EOF
fi

# Exibir config de banco para debug
echo "DB_HOST: ${DB_HOST}"
echo "DB_DATABASE: ${DB_DATABASE}"
echo "DB_USERNAME: ${DB_USERNAME}"

# Gerar APP_KEY se necessario
if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
    echo "Gerando APP_KEY..."
    # Gerar key manualmente para evitar boot do Laravel
    NEW_KEY=$(php -r "echo 'base64:'.base64_encode(random_bytes(32));")
    sed -i "s|^APP_KEY=.*|APP_KEY=${NEW_KEY}|" .env
    echo "APP_KEY gerada: ${NEW_KEY:0:20}..."
fi

# Aguardar banco de dados (se configurado) - timeout curto para nao travar
if [ -n "$DB_HOST" ] && [ "$DB_HOST" != "localhost" ]; then
    echo "Verificando banco de dados em $DB_HOST:${DB_PORT:-3306}..."
    max_attempts=5
    attempt=0
    DB_READY=false
    while [ $attempt -lt $max_attempts ]; do
        if nc -z -w 2 "$DB_HOST" "${DB_PORT:-3306}" 2>/dev/null; then
            echo "Banco de dados disponivel!"
            DB_READY=true
            break
        fi
        attempt=$((attempt + 1))
        echo "Tentativa $attempt/$max_attempts..."
        sleep 1
    done
    if [ "$DB_READY" = "false" ]; then
        echo "Aviso: Banco nao acessivel, continuando sem migrations..."
    fi
fi

# Migrations (ignorar erros) - apenas se DB disponivel
if [ "$DB_READY" = "true" ] || [ -z "$DB_HOST" ] || [ "$DB_HOST" = "localhost" ]; then
    echo "Executando migrations..."
    set +e
    php artisan migrate --force 2>&1
    MIGRATE_RESULT=$?
    set -e
    if [ $MIGRATE_RESULT -ne 0 ]; then
        echo "Aviso: Migrations nao executadas (exit code: $MIGRATE_RESULT)"
    fi
else
    echo "Pulando migrations (banco nao disponivel)"
fi

# Limpar caches antigos (ignorar erros)
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

# Cache para producao (opcional em dev)
if [ "${APP_ENV:-production}" = "production" ]; then
    echo "Gerando caches de producao..."
    php artisan config:cache 2>/dev/null || true
    php artisan route:cache 2>/dev/null || true
    php artisan view:cache 2>/dev/null || true
    php artisan event:cache 2>/dev/null || true
fi

# Permissoes (apenas diretorios principais, sem recursao em volumes montados)
chmod 775 storage storage/framework storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache 2>/dev/null || true

# Criar rota de health check SEM middleware (evita dependencia de DB/session no health check)
if ! grep -q "health.*withoutMiddleware" routes/web.php 2>/dev/null; then
    # Remove health check antigo se existir (com middleware web que causa timeout)
    sed -i '/\/\/ Health check/d' routes/web.php 2>/dev/null || true
    sed -i "/Route::get('\/health', fn() => response('OK', 200));/d" routes/web.php 2>/dev/null || true
    echo "" >> routes/web.php
    echo "// Health check - sem middleware para evitar dependencia de DB/session" >> routes/web.php
    echo "Route::get('/health', fn() => response()->json(['status' => 'ok', 'timestamp' => now()->toIso8601String()]))->withoutMiddleware('web');" >> routes/web.php
fi

echo "=== Iniciando FrankenPHP ==="
echo "Workers: ${FRANKENPHP_WORKERS:-auto}"
echo "Max Requests: ${FRANKENPHP_MAX_REQUESTS:-500}"
echo "HTTPS: ${OCTANE_HTTPS:-true}"

# Verificar se binario frankenphp existe
FRANKENPHP_BIN=$(which frankenphp 2>/dev/null || echo "")
if [ -z "$FRANKENPHP_BIN" ]; then
    echo "ERRO: Binario frankenphp nao encontrado no PATH!"
    echo "PATH atual: $PATH"
    ls -la /usr/local/bin/franken* 2>/dev/null || echo "Nenhum binario frankenphp em /usr/local/bin/"
    exit 1
fi
echo "Binario FrankenPHP: $FRANKENPHP_BIN"
echo "Versao: $(frankenphp version 2>&1)"

# Criar symlink em base_path para que o Octane encontre sem tentar download
# O Octane procura em base_path() primeiro via ExecutableFinder
if [ ! -f /app/frankenphp ] && [ -f "$FRANKENPHP_BIN" ]; then
    ln -sf "$FRANKENPHP_BIN" /app/frankenphp
    echo "Symlink criado: /app/frankenphp -> $FRANKENPHP_BIN"
fi

# Garantir que o binario esta no PATH
export PATH="/usr/local/bin:$PATH"

# Iniciar FrankenPHP via Octane
# --no-interaction impede download automatico de binario incompativel (GNU em Alpine/musl)
if [ "${OCTANE_HTTPS:-true}" = "true" ]; then
    echo "Porta: ${PORT:-443} (HTTPS + HTTP/2 + HTTP/3)"
    echo "Certificado auto-gerado pelo Caddy (CA interna em /data/caddy/pki/)"

    # Usar Caddyfile customizado se existir (controle total de TLS e redirect)
    if [ -f /app/docker/Caddyfile.dev ]; then
        echo "Usando Caddyfile customizado: /app/docker/Caddyfile.dev"
        # Com Caddyfile customizado, o TLS e redirect sao controlados pelo Caddyfile
        # Nao passar --https/--http-redirect para evitar conflito com auto_https do Caddy
        exec php artisan octane:frankenphp \
            --host=localhost \
            --port=${PORT:-443} \
            --caddyfile=/app/docker/Caddyfile.dev \
            --workers=${FRANKENPHP_WORKERS:-auto} \
            --max-requests=${FRANKENPHP_MAX_REQUESTS:-500} \
            --no-interaction
    else
        exec php artisan octane:frankenphp \
            --host=localhost \
            --port=${PORT:-443} \
            --https \
            --http-redirect \
            --workers=${FRANKENPHP_WORKERS:-auto} \
            --max-requests=${FRANKENPHP_MAX_REQUESTS:-500} \
            --no-interaction
    fi
else
    echo "Porta: ${PORT:-80} (HTTP)"
    exec php artisan octane:frankenphp \
        --host=0.0.0.0 \
        --port=${PORT:-80} \
        --workers=${FRANKENPHP_WORKERS:-auto} \
        --max-requests=${FRANKENPHP_MAX_REQUESTS:-500} \
        --no-interaction
fi
