#!/bin/sh
# ============================================================================
# SDC - Entrypoint de Producao Swoole para Azure App Service
# Octane sobre Swoole. Clonado de docker/scripts/entrypoint.prod.sh; as unicas
# diferencas sao OCTANE_SERVER=swoole, DB_PERSISTENT=false (obrigatorio sob
# SWOOLE_HOOK_ALL) e o exec final com --server=swoole.
# ============================================================================

set -e

cd /var/www

if [ ! -f composer.json ]; then
    echo "ERRO: composer.json NAO encontrado em /var/www"
    ls -la /var/www
    exit 1
fi

# Garantir diretorios de storage (FS efemero com WEBSITES_ENABLE_APP_SERVICE_STORAGE=false)
mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/framework/testing \
         storage/logs \
         storage/app/public \
         storage/app/compdec \
         storage/app/pae \
         storage/app/exports \
         bootstrap/cache
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Em dev/local, bootstrap/cache pode vir do bind mount do host. Caches antigos
# quebram o bootstrap antes mesmo de config:clear/package:discover rodarem.
rm -f bootstrap/cache/config.php \
      bootstrap/cache/events.php \
      bootstrap/cache/packages.php \
      bootstrap/cache/routes.php \
      bootstrap/cache/routes-v7.php \
      bootstrap/cache/services.php

echo "Regenerando .env a partir de variaveis de ambiente..."
# IMPORTANTE: aspar TODOS os valores (phpdotenv trata # como comentario em valores nao-aspeados)
cat > .env <<EOF
APP_NAME="${APP_NAME:-SDC - Sistema de Defesa Civil}"
APP_ENV="${APP_ENV:-production}"
APP_KEY="${APP_KEY:-}"
APP_DEBUG="${APP_DEBUG:-false}"
APP_URL="${APP_URL:-https://sdcdefesa.azurewebsites.net}"
DB_CONNECTION="${DB_CONNECTION:-pgsql}"
DB_HOST="${DB_HOST:-sdc-postgres.postgres.database.azure.com}"
DB_PORT="${DB_PORT:-5432}"
DB_DATABASE="${DB_DATABASE:-sdc}"
DB_USERNAME="${DB_USERNAME:-sdcdata}"
DB_PASSWORD="${DB_PASSWORD:-}"
DB_SSLMODE="${DB_SSLMODE:-require}"
DB_PERSISTENT="${DB_PERSISTENT:-false}"
REDIS_HOST="${REDIS_HOST:-sdcdefesa.redis.cache.windows.net}"
REDIS_PORT="${REDIS_PORT:-6380}"
REDIS_PASSWORD="${REDIS_PASSWORD:-}"
# phpredis (nao predis): App\Database\ConnectionSemaphore e tipado para ?Redis
# (phpredis). Com predis da TypeError e derruba o app. Trade-off: phpredis nao e
# auto-hookado pelo SWOOLE_HOOK_ALL ate existir um RedisPool nativo (Fase 3).
REDIS_CLIENT="${REDIS_CLIENT:-phpredis}"
REDIS_SCHEME="${REDIS_SCHEME:-tls}"
REDIS_PREFIX="${REDIS_PREFIX:-sdc_prod_}"
CACHE_PREFIX="${CACHE_PREFIX:-sdc_prod_cache_}"
CACHE_DRIVER="${CACHE_DRIVER:-redis}"
SESSION_DRIVER="${SESSION_DRIVER:-redis}"
SESSION_DOMAIN="${SESSION_DOMAIN:-}"
SESSION_SECURE_COOKIE="${SESSION_SECURE_COOKIE:-true}"
SESSION_SAME_SITE="${SESSION_SAME_SITE:-lax}"
QUEUE_CONNECTION="${QUEUE_CONNECTION:-redis}"
HASH_DRIVER="${HASH_DRIVER:-argon2id}"
OCTANE_SERVER="${OCTANE_SERVER:-swoole}"
OCTANE_HTTPS="${OCTANE_HTTPS:-true}"
FILESYSTEM_DISK="${FILESYSTEM_DISK:-public}"
AZURE_STORAGE_CONNECTION_STRING="${AZURE_STORAGE_CONNECTION_STRING:-}"
AZURE_STORAGE_URL="${AZURE_STORAGE_URL:-}"
AZURE_STORAGE_CONTAINER_PUBLIC="${AZURE_STORAGE_CONTAINER_PUBLIC:-sdc-public}"
AZURE_STORAGE_CONTAINER_COMPDEC="${AZURE_STORAGE_CONTAINER_COMPDEC:-sdc-compdec}"
AZURE_STORAGE_CONTAINER_PAE="${AZURE_STORAGE_CONTAINER_PAE:-sdc-pae}"
AZURE_STORAGE_CONTAINER_RAT="${AZURE_STORAGE_CONTAINER_RAT:-sdc-rat}"
AZURE_STORAGE_CONTAINER_EXPORTS="${AZURE_STORAGE_CONTAINER_EXPORTS:-sdc-exports}"
LOG_CHANNEL="${LOG_CHANNEL:-stack}"
LOG_LEVEL="${LOG_LEVEL:-info}"
EOF
echo "Arquivo .env regenerado"

# Gerar APP_KEY se nao existir ou estiver vazia
if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null || grep -q "^APP_KEY=$" .env 2>/dev/null || grep -q "^APP_KEY=\"\"" .env 2>/dev/null; then
    APP_KEY_VALUE=$(grep "^APP_KEY=" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' || echo "")
    if [ -z "$APP_KEY_VALUE" ] || [ "$APP_KEY_VALUE" == "base64:" ]; then
        echo "Gerando APP_KEY..."
        php artisan key:generate --force
        echo "APP_KEY gerada"
    fi
fi

# Migrations
echo "Executando migrations..."
php artisan migrate --force || echo "Aviso: Erro ao executar migrations"

# Seeders de dados mock apenas quando SEED_MOCK_DATA=true. Sao idempotentes
# (updateOrCreate): rodar em todo restart de producao RESETARIA as senhas dos
# usuarios mock para o default, reabrindo credenciais conhecidas. Em ambiente
# novo (banco vazio), setar SEED_MOCK_DATA=true no primeiro deploy e remover.
if [ "${SEED_MOCK_DATA:-false}" = "true" ]; then
    echo "Executando seeders (dados mock + hierarquias)..."
    php artisan db:seed --force --class=DatabaseSeeder || echo "Aviso: Erro ao executar seeders"
    echo "Banco inicializado"
else
    echo "SEED_MOCK_DATA != true; pulando seeders de dados mock."
fi

# Documentacao Swagger
echo "Gerando documentacao Swagger..."
mkdir -p storage/api-docs
php artisan l5-swagger:generate 2>/dev/null || echo "Aviso: falha ao gerar swagger"

# Limpar e RECONSTRUIR caches: sem o rebuild, todo worker boota com config e
# views frias (parse de config/*.php e Blade a cada boot de worker).
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php artisan config:cache || echo "Aviso: falha em config:cache"
php artisan view:cache 2>/dev/null || echo "Aviso: falha em view:cache"
# route:cache exige zero rotas closure; se falhar, segue sem cache de rotas.
php artisan route:cache 2>/dev/null || echo "Aviso: route:cache pulado (rotas closure presentes)"

chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Queue worker em background (mesmo padrao do entrypoint de producao).
# Container unico no App Service: worker roda junto do Octane. O subshell herda
# o "set -e"; o "set +e" abaixo garante que um crash do queue:work nao mate o
# loop de restart.
echo "Iniciando queue worker em background..."
(
    set +e
    while true; do
        php artisan queue:work --queue=high-throughput,default,low --tries=3 --timeout=90 --sleep=3 --max-time=3600 2>&1
        echo "[queue:work] worker saiu (codigo $?); reiniciando em 2s..."
        sleep 2
    done
) &

# Oversubscribe de workers: ~83% do tempo de um request e I/O (DB/Redis), entao
# rodar mais workers que vCores preenche a CPU enquanto uns esperam I/O. Default
# = vCores x OCTANE_WORKER_MULTIPLIER (3). OCTANE_WORKERS explicito tem prioridade.
# Em tier com pouca RAM (B1), reduza OCTANE_WORKER_MULTIPLIER para 2.
WORKERS="${OCTANE_WORKERS:-}"
if [ -z "$WORKERS" ]; then
    CORES=$(nproc 2>/dev/null || echo 1)
    MULT="${OCTANE_WORKER_MULTIPLIER:-3}"
    WORKERS=$((CORES * MULT))
fi
echo "vCores=$(nproc 2>/dev/null || echo '?'); workers=${WORKERS}; task-workers=${OCTANE_TASK_WORKERS:-4}"

# Iniciar servidor Octane (Swoole)
echo "Iniciando servidor Octane (Swoole)..."
exec php artisan octane:start \
    --server=swoole \
    --host=0.0.0.0 \
    --port="${PORT:-8000}" \
    --workers="${WORKERS}" \
    --task-workers="${OCTANE_TASK_WORKERS:-4}" \
    --max-requests="${OCTANE_MAX_REQUESTS:-500}"
