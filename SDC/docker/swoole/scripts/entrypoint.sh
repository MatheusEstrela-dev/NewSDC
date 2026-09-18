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
#
# --isolated: com mais de uma replica, TODAS executam este entrypoint, e sem o
# lock elas disputam o mesmo `migrate`. Duas rodando DDL concorrente sobre a
# mesma tabela dao desde erro de migration ja aplicada ate schema pela metade,
# e o modo de falha depende do momento -- nao e reproduzivel.
#
# Nao e hipotese: o on-premise sobe com `replicas: 2` e `order: start-first`,
# entao as duas podem chegar aqui ao mesmo tempo. Vale tambem para
# `docker compose up --scale app=N` em homologacao.
#
# O lock vive no cache (Redis). Quem nao pega o lock sai com codigo 0 sem rodar,
# que e o comportamento desejado: a primeira replica migra, as demais seguem
# para o boot.
echo "Executando migrations..."
php artisan migrate --force --isolated || echo "Aviso: Erro ao executar migrations"

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

# No container unico, cada classe de trabalho tem seu proprio consumidor.
# Topologias com servico queue dedicado desativam estes processos.
start_queue_worker() {
    (
        set +e
        while true; do
            php artisan queue:work "$1" --queue="$2" --timeout="$3" --tries=3 --sleep=1 --max-time=3600
            sleep 2
        done
    ) &
}

if [ "${START_EMBEDDED_QUEUE:-true}" = "true" ]; then
    start_queue_worker redis-critical critical,high 60
    start_queue_worker redis default,high-throughput 120
    start_queue_worker redis-webhooks webhooks 60
    start_queue_worker redis-low low 600
    start_queue_worker redis notificacoes_urgente,notificacoes 120
    start_queue_worker redis-medalhao medalhao 900
    # Auditoria tem consumidor proprio: e o job mais frequente do sistema e na
    # 'low' ficava atras dos certificados (timeout 600s), que seguravam a fila
    # inteira. Sem esta linha, na topologia de container unico os jobs de
    # auditoria simplesmente nao teriam quem os consumisse.
    start_queue_worker redis-auditoria auditoria 30
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
#   (WORKERS * CONN_POR_WORKER + TASK_WORKERS + QUEUE_WORKERS) * APP_INSTANCES
#       + EXTERNAL_DB_CONSUMERS + PG_ADMIN_RESERVE  <= PG_MAX_CONNECTIONS
# Descubra o real: psql -c 'SHOW max_connections;'  (dev = 100)
#   az postgres flexible-server parameter show -g <rg> -s <srv> -n max_connections
TASK_WORKERS="${OCTANE_TASK_WORKERS:-4}"
# Com o queue embutido desligado, este container nao mantem conexao de worker
# de fila; o container queue dedicado entra em EXTERNAL_DB_CONSUMERS.
if [ "${START_EMBEDDED_QUEUE:-true}" = "true" ]; then
    # 7 = um por chamada de start_queue_worker acima (critical, default,
    # webhooks, low, notificacoes, medalhao, auditoria). Este numero entra no
    # orcamento de conexoes: errar para menos faz o guardrail aprovar um boot
    # que o Postgres depois recusa no meio de um request.
    QUEUE_WORKERS="${QUEUE_WORKERS:-7}"
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
# Conexoes por worker HTTP: 1 sob hooks OFF (PDO quente por worker), mas ate
# SWOOLE_PG_POOL_SIZE sob hooks ON -- o CoroutineDatabaseManager da uma conexao
# POR COROUTINE, emprestada de um pool que existe POR WORKER. Sem este fator o
# guardrail projetava 63 quando o teto real era 640 (40 workers x pool 16), e
# ligar OCTANE_HOOK_FLAGS_ENABLED estourava o max_connections silenciosamente --
# a falha aparecia como "too many connections" no meio de um request, nao no boot.
#
# Referencia: questao aberta 7.1 de docs/superpowers/specs/2026-06-15-swoole-pdopool-consumo-design.md
#   pool_size x workers x instancias <= max_connections - reserva
if [ "${OCTANE_HOOK_FLAGS_ENABLED:-false}" = "true" ]; then
    PG_POOL_SIZE="${SWOOLE_PG_POOL_SIZE:-16}"
    require_uint SWOOLE_PG_POOL_SIZE "$PG_POOL_SIZE"
    CONN_POR_WORKER="$PG_POOL_SIZE"
    MODO_CONN="hooks ON: ${PG_POOL_SIZE} conexoes/worker (pool por-coroutine)"
else
    CONN_POR_WORKER=1
    MODO_CONN="hooks OFF: 1 conexao/worker"
fi
CONN_PER_INSTANCE=$(((WORKERS * CONN_POR_WORKER) + TASK_WORKERS + QUEUE_WORKERS))
CONN_PROJECTED=$((CONN_PER_INSTANCE * APP_INSTANCES + EXTERNAL_DB_CONSUMERS + PG_ADMIN_RESERVE))
echo "Modo de conexao: ${MODO_CONN}"

# Com POOLER, o destino das conexoes do app muda e a conta acima deixa de ser
# sobre o Postgres.
#
# Sem pooler a relacao e direta: cada worker abre uma conexao NO BANCO, entao o
# teto do app e max_connections. Com PgBouncer em transaction mode o app abre
# contra o POOLER (que aceita muitos clientes baratos, porque sao apenas sockets)
# e o pooler mantem um punhado de conexoes reais contra o Postgres.
#
# Sao dois orcamentos independentes, e checar so o antigo produz o pior erro
# possivel: o gate recusaria um boot perfeitamente valido por um limite que nao
# se aplica mais -- e a descoberta disso acontece com a janela de deploy aberta.
POOLER_MODE="${DB_POOLER_MODE:-}"

if [ -n "${POOLER_MODE}" ]; then
    PGB_MAX_CLIENTES="${PGBOUNCER_MAX_CLIENT_CONN:-1000}"
    PGB_POOL_SIZE="${PGBOUNCER_DEFAULT_POOL_SIZE:-25}"
    require_uint PGBOUNCER_MAX_CLIENT_CONN "$PGB_MAX_CLIENTES"
    require_uint PGBOUNCER_DEFAULT_POOL_SIZE "$PGB_POOL_SIZE"

    CONN_APP=$((CONN_PER_INSTANCE * APP_INSTANCES))
    echo "Pooler: DB_POOLER_MODE=${POOLER_MODE} -- o app conecta no PgBouncer, nao no Postgres."
    echo "Clientes no pooler: ${CONN_PER_INSTANCE}/inst x ${APP_INSTANCES} = ${CONN_APP} (teto ${PGB_MAX_CLIENTES})"

    if [ "${CONN_APP}" -gt "${PGB_MAX_CLIENTES}" ]; then
        echo "FATAL: clientes projetados (${CONN_APP}) excedem PGBOUNCER_MAX_CLIENT_CONN (${PGB_MAX_CLIENTES})."
        echo "       Suba max_client_conn no PgBouncer (cliente ocioso custa pouco: e socket, nao backend),"
        echo "       ou reduza OCTANE_WORKERS / APP_INSTANCES. Abortando boot."
        exit 1
    fi

    # O que chega ao Postgres agora e o pool do PgBouncer, nao os workers.
    CONN_PROJECTED=$((PGB_POOL_SIZE + EXTERNAL_DB_CONSUMERS + PG_ADMIN_RESERVE))
    echo "Conexoes PG projetadas: ${PGB_POOL_SIZE} do pool + ${EXTERNAL_DB_CONSUMERS} externas + ${PG_ADMIN_RESERVE} reserva = ${CONN_PROJECTED}"
else
    echo "Conexoes PG projetadas: ${CONN_PER_INSTANCE}/inst x ${APP_INSTANCES} + ${EXTERNAL_DB_CONSUMERS} externas + ${PG_ADMIN_RESERVE} reserva = ${CONN_PROJECTED}"
fi

if [ -n "${PG_MAX_CONNECTIONS:-}" ]; then
    require_uint PG_MAX_CONNECTIONS "$PG_MAX_CONNECTIONS"
    if [ "${CONN_PROJECTED}" -gt "${PG_MAX_CONNECTIONS}" ]; then
        echo "FATAL: conexoes projetadas (${CONN_PROJECTED}) excedem PG_MAX_CONNECTIONS (${PG_MAX_CONNECTIONS})."
        if [ -n "${POOLER_MODE}" ]; then
            echo "       Com pooler a alavanca e PGBOUNCER_DEFAULT_POOL_SIZE (hoje ${PGB_POOL_SIZE}):"
            echo "       o orcamento e pool_size <= ${PG_MAX_CONNECTIONS} - ${EXTERNAL_DB_CONSUMERS} - ${PG_ADMIN_RESERVE}."
            echo "       Numero de workers e de replicas NAO entra nesta conta -- so o pool."
        else
            echo "       Reduza OCTANE_WORKERS / OCTANE_WORKER_MULTIPLIER / OCTANE_TASK_WORKERS,"
            echo "       diminua APP_INSTANCES/EXTERNAL_DB_CONSUMERS, ou suba o tier do Postgres."
            if [ "${OCTANE_HOOK_FLAGS_ENABLED:-false}" = "true" ]; then
                echo "       Sob hooks ON a alavanca mais barata e SWOOLE_PG_POOL_SIZE (hoje ${CONN_POR_WORKER}):"
                echo "       o orcamento e pool_size x workers <= ${PG_MAX_CONNECTIONS} - ${EXTERNAL_DB_CONSUMERS} - ${PG_ADMIN_RESERVE} - ${TASK_WORKERS}."
                echo "       Alternativa: OCTANE_HOOK_FLAGS_ENABLED=false volta a 1 conexao/worker."
            fi
            echo "       Alternativa estrutural: DB_POOLER_MODE=transaction com PgBouncer, que"
            echo "       desacopla numero de workers de numero de conexoes no banco."
        fi
        echo "       Abortando boot."
        exit 1
    fi
    echo "OK: dentro do teto de ${PG_MAX_CONNECTIONS} conexoes do Postgres."
else
    echo "AVISO: PG_MAX_CONNECTIONS nao definido -- guardrail de conexoes inativo."
    echo "       Defina PG_MAX_CONNECTIONS (= SHOW max_connections) para ativar o gate."
fi

# Prepared statements + transaction mode: incompatibilidade classica.
#
# Prepared statement tem escopo de SESSAO. Em transaction mode o PREPARE pode
# cair numa conexao de servidor e o EXECUTE seguinte em outra, que nao o conhece
# -- o erro e "prepared statement ... does not exist", intermitente e so sob
# carga. Duas saidas: PgBouncer 1.21+ com max_prepared_statements > 0, ou
# DB_EMULATE_PREPARES=true (prepara no cliente). Avisar no boot e barato; o
# contrario e descobrir em producao.
if [ "${POOLER_MODE}" = "transaction" ] \
   && [ "$(printf '%s' "${DB_EMULATE_PREPARES:-false}" | tr '[:upper:]' '[:lower:]')" != "true" ] \
   && [ "${PGBOUNCER_MAX_PREPARED_STATEMENTS:-0}" = "0" ]; then
    echo "AVISO: transaction mode com prepared statements do servidor."
    echo "       Ligue max_prepared_statements no PgBouncer (1.21+) OU defina"
    echo "       DB_EMULATE_PREPARES=true. Sem um dos dois, esperar"
    echo "       'prepared statement does not exist' sob carga."
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
