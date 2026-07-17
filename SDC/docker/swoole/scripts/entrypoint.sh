#!/bin/sh
# ============================================================================
# SDC - Entrypoint de Producao (Octane sobre Swoole)
# Unico entrypoint de producao do projeto (o legado FrankenPHP/App Service
# foi removido). OCTANE_SERVER=swoole e DB_PERSISTENT=false sao obrigatorios
# sob SWOOLE_HOOK_ALL.
# ============================================================================

set -e

# Anexos no bind mount (ANEXOS_ROOT): app e queue podem rodar com usuarios
# diferentes (root/www-data). umask 002 mantem diretorios/arquivos novos
# graveis pelo grupo (a raiz de cada modulo tem setgid www-data no host).
umask 002

# Guarda contra mount ausente: o fstab usa nofail, entao se o disco de anexos
# nao montar no boot o host sobe com /mnt/newsdc_storage VAZIO e o Docker
# bind-monta o diretorio vazio — o app gravaria no disco errado sem erro.
# O marcador .sdc_storage_mounted existe so dentro do filesystem do disco.
if [ -n "${ANEXOS_ROOT:-}" ] && [ ! -e "${ANEXOS_ROOT}/.sdc_storage_mounted" ]; then
    echo "ERRO: ANEXOS_ROOT=${ANEXOS_ROOT} sem o marcador .sdc_storage_mounted."
    echo "      O disco de anexos provavelmente nao esta montado no host (fstab nofail)."
    echo "      Verifique: mount | grep newsdc_storage"
    exit 1
fi

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
         storage/app/rat \
         storage/app/tdap \
         storage/app/exports \
         bootstrap/cache
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Remover symlink legado public/anexos-rat -> ANEXOS_ROOT/RAT: o RAT deixou de
# ser servido por URL publica (anexos agora sao privados, servidos por rota
# autenticada). Tirar do config/filesystems.php impede storage:link de RECRIAR,
# mas NAO apaga um symlink ja criado em deploy anterior — sem este rm, todo
# anexo RAT continuaria acessivel SEM autenticacao em /anexos-rat na VM.
rm -f public/anexos-rat 2>/dev/null || true

# Em dev/local, bootstrap/cache pode vir do bind mount do host. Caches antigos
# quebram o bootstrap antes mesmo de config:clear/package:discover rodarem.
rm -f bootstrap/cache/config.php \
      bootstrap/cache/events.php \
      bootstrap/cache/packages.php \
      bootstrap/cache/routes.php \
      bootstrap/cache/routes-v7.php \
      bootstrap/cache/services.php

# Autoloader: onde o codigo entra por bind mount + git pull (stack dev na VM),
# o classmap authoritative congelado na imagem nao conhece classe nova e o
# request morre com BindingResolutionException (incidente PasswordVerifier,
# 13/07). Com a flag ligada, todo boot regenera o classmap contra o codigo
# montado. Producao (imagem imutavel) mantem false: o dump do build basta.
if [ "${AUTOLOAD_REFRESH_ON_BOOT:-false}" = "true" ]; then
    echo "AUTOLOAD_REFRESH_ON_BOOT=true; regenerando autoloader (classmap authoritative)..."
    composer dump-autoload --optimize --classmap-authoritative --no-interaction \
        || echo "Aviso: falha ao regenerar autoloader; seguindo com o classmap da imagem"
fi

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
# Com hooks Swoole OFF (modelo seguro: 1 request por worker, sem overlap de
# coroutine), o phpredis NAO e compartilhado entre coroutines -> Redis volta a
# ser seguro. file cache perde coerencia no scale-out (FS efemero por-container),
# entao cache volta pro Redis. Sessao segue cookie (stateless, escala horizontal).
CACHE_DRIVER="${CACHE_DRIVER:-redis}"
SESSION_DRIVER="${SESSION_DRIVER:-cookie}"
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

# Gate fail-closed: aborta ANTES de servir se alguma classe type-hintada em rota
# (ex.: FormRequest) for irresolvivel. Repete a reflexao que o Ziggy faz em todo
# render; sem isto, um classmap inconsistente com o codigo derruba TODA pagina em
# runtime (incidente de 10/06). Falhar aqui mata o deploy, nao o usuario.
php artisan route:verify-signatures || { echo "FATAL: assinaturas de rota irresoluveis -- abortando boot"; exit 1; }

chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Queue worker em background (mesmo padrao do entrypoint de producao).
# Container unico no App Service: worker roda junto do Octane. O subshell herda
# o "set -e"; o "set +e" abaixo garante que um crash do queue:work nao mate o
# loop de restart. A lista de filas cobre TODAS as filas da aplicacao
# (RequestPriority: critical/high/default/low + webhooks inbound +
# high-throughput legada) em ordem de prioridade — fila fora da lista vira
# job orfao que nunca e consumido.
#
# START_EMBEDDED_QUEUE (default true): no Azure (container unico) o worker
# PRECISA rodar aqui. Em topologias com container queue dedicado (compose
# on-premise/dev), setar false para nao duplicar consumidores nem gastar
# CPU/RAM/conexao PG com um segundo worker no container do app.
if [ "${START_EMBEDDED_QUEUE:-true}" = "true" ]; then
    echo "Iniciando queue worker em background..."
    (
        set +e
        while true; do
            php artisan queue:work --queue=critical,high,high-throughput,webhooks,default,low --tries=3 --timeout=90 --sleep=3 --max-time=3600 2>&1
            echo "[queue:work] worker saiu (codigo $?); reiniciando em 2s..."
            sleep 2
        done
    ) &
else
    echo "Queue worker embutido desativado (START_EMBEDDED_QUEUE=false); usando container queue dedicado."
fi

# Oversubscribe de workers: ~83% do tempo de um request e I/O (DB/Redis), entao
# rodar mais workers que vCores preenche a CPU enquanto uns esperam I/O. Default
# = vCores x OCTANE_WORKER_MULTIPLIER (3). OCTANE_WORKERS explicito tem prioridade.
# Em tier com pouca RAM (B1), reduza OCTANE_WORKER_MULTIPLIER para 2.
# Valida que um valor e inteiro nao-negativo; aborta com mensagem clara senao.
# Sem isto: (a) $(( )) com valor nao-numerico aborta o boot sob set -e com erro
# cripto; (b) o gate abaixo, com PG_MAX_CONNECTIONS malformado, falharia ABERTO
# (a comparacao erra, o if vira falso e segue como "ok"). Validar na entrada
# torna o erro de config explicito (fail-closed em config).
require_uint() {
    case "$2" in
        ''|*[!0-9]*)
            echo "FATAL: ${1}='${2}' nao e um inteiro valido. Corrija a App Setting/env. Abortando boot."
            exit 1
            ;;
    esac
}

WORKERS="${OCTANE_WORKERS:-}"
if [ -n "$WORKERS" ]; then
    require_uint OCTANE_WORKERS "$WORKERS"
else
    CORES=$(nproc 2>/dev/null || echo 1)
    MULT="${OCTANE_WORKER_MULTIPLIER:-3}"
    require_uint OCTANE_WORKER_MULTIPLIER "$MULT"
    WORKERS=$((CORES * MULT))
fi
echo "vCores=$(nproc 2>/dev/null || echo '?'); workers=${WORKERS}; task-workers=${OCTANE_TASK_WORKERS:-4}"

# ----------------------------------------------------------------------------
# Guardrail de conexoes Postgres (Swoole hooks OFF = ~1 conexao PDO por worker)
# ----------------------------------------------------------------------------
# Sob hooks off cada worker HTTP mantem 1 conexao pgsql quente; cada task worker
# abre 1 quando roda closure de DB; o queue:work em background NESTE container
# mantem +QUEUE_WORKERS. Containers SEPARADOS (ex.: um servico 'queue' dedicado
# com supervisord) entram em EXTERNAL_DB_CONSUMERS -- somado UMA vez, nao por
# instancia. O teto e o max_connections do Postgres, COMPARTILHADO por todos.
# Conta:
#   (WORKERS + TASK_WORKERS + QUEUE_WORKERS) * APP_INSTANCES
#       + EXTERNAL_DB_CONSUMERS + PG_ADMIN_RESERVE  <= PG_MAX_CONNECTIONS
# Descubra o real: psql -c 'SHOW max_connections;'  (dev = 100)
#   az postgres flexible-server parameter show -g <rg> -s <srv> -n max_connections
TASK_WORKERS="${OCTANE_TASK_WORKERS:-4}"
# Com o queue embutido desligado, este container nao mantem conexao de worker
# de fila; o container queue dedicado entra em EXTERNAL_DB_CONSUMERS.
if [ "${START_EMBEDDED_QUEUE:-true}" = "true" ]; then
    QUEUE_WORKERS="${QUEUE_WORKERS:-1}"
else
    QUEUE_WORKERS="${QUEUE_WORKERS:-0}"
fi
APP_INSTANCES="${APP_INSTANCES:-1}"
EXTERNAL_DB_CONSUMERS="${EXTERNAL_DB_CONSUMERS:-0}"
PG_ADMIN_RESERVE="${PG_ADMIN_RESERVE:-5}"
require_uint OCTANE_TASK_WORKERS "$TASK_WORKERS"
require_uint QUEUE_WORKERS "$QUEUE_WORKERS"
require_uint APP_INSTANCES "$APP_INSTANCES"
require_uint EXTERNAL_DB_CONSUMERS "$EXTERNAL_DB_CONSUMERS"
require_uint PG_ADMIN_RESERVE "$PG_ADMIN_RESERVE"
CONN_PER_INSTANCE=$((WORKERS + TASK_WORKERS + QUEUE_WORKERS))
CONN_PROJECTED=$((CONN_PER_INSTANCE * APP_INSTANCES + EXTERNAL_DB_CONSUMERS + PG_ADMIN_RESERVE))
echo "Conexoes PG projetadas: ${CONN_PER_INSTANCE}/inst x ${APP_INSTANCES} + ${EXTERNAL_DB_CONSUMERS} externas + ${PG_ADMIN_RESERVE} reserva = ${CONN_PROJECTED}"
if [ -n "${PG_MAX_CONNECTIONS:-}" ]; then
    require_uint PG_MAX_CONNECTIONS "$PG_MAX_CONNECTIONS"
    if [ "${CONN_PROJECTED}" -gt "${PG_MAX_CONNECTIONS}" ]; then
        echo "FATAL: conexoes projetadas (${CONN_PROJECTED}) excedem PG_MAX_CONNECTIONS (${PG_MAX_CONNECTIONS})."
        echo "       Reduza OCTANE_WORKERS / OCTANE_WORKER_MULTIPLIER / OCTANE_TASK_WORKERS,"
        echo "       diminua APP_INSTANCES/EXTERNAL_DB_CONSUMERS, ou suba o tier do Postgres. Abortando boot."
        exit 1
    fi
    echo "OK: dentro do teto de ${PG_MAX_CONNECTIONS} conexoes do Postgres."
else
    echo "AVISO: PG_MAX_CONNECTIONS nao definido -- guardrail de conexoes inativo."
    echo "       Defina PG_MAX_CONNECTIONS (= SHOW max_connections) para ativar o gate."
fi

# Iniciar servidor Octane (Swoole)
echo "Iniciando servidor Octane (Swoole)..."
exec php artisan octane:start \
    --server=swoole \
    --host=0.0.0.0 \
    --port="${PORT:-8000}" \
    --workers="${WORKERS}" \
    --task-workers="${TASK_WORKERS}" \
    --max-requests="${OCTANE_MAX_REQUESTS:-500}"
