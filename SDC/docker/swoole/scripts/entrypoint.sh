#!/bin/sh
# ============================================================================
# SDC - Entrypoint de Producao (Octane sobre Swoole)
# Unico entrypoint de producao do projeto (o legado FrankenPHP/App Service
# foi removido). OCTANE_SERVER=swoole e DB_PERSISTENT=false sao obrigatorios.
# ============================================================================

set -e

# A configuracao do Octane desliga hooks ate haver isolamento completo das
# requisicoes HTTP. Recusar a flag antiga evita um deploy que aparente liga-los.
if [ "${OCTANE_HOOK_FLAGS_ENABLED:-false}" = "true" ]; then
    echo "FATAL: OCTANE_HOOK_FLAGS_ENABLED=true indisponivel nesta versao do Octane."
    exit 1
fi

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
AZURE_STORAGE_CONTAINER_NOTAS_TECNICAS="${AZURE_STORAGE_CONTAINER_NOTAS_TECNICAS:-sdc-notas-tecnicas}"
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

# Bancos dedicados ATIVOS: criar o banco/extensoes e migrar no proprio caminho.
# Nao fatal: se um banco dedicado falhar, os modulos criticos sobem do mesmo
# jeito e so o modulo dedicado fica sem schema. Roda depois do migrate do sdc
# porque nenhum banco dedicado e pre-requisito dele.
php artisan db:bancos-dedicados --ativos --formato=linhas 2>/dev/null | while IFS='|' read -r BANCO_CONEXAO BANCO_MIGRATIONS; do
    [ -n "${BANCO_CONEXAO}" ] || continue
    echo "Banco dedicado ${BANCO_CONEXAO}: provisionando e migrando..."
    php artisan db:provisionar "${BANCO_CONEXAO}" --isolated < /dev/null || echo "Aviso: Erro ao provisionar ${BANCO_CONEXAO}"
    php artisan migrate --database="${BANCO_CONEXAO}" --path="${BANCO_MIGRATIONS}" --force --isolated < /dev/null || echo "Aviso: Erro ao migrar ${BANCO_CONEXAO}"
done

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

# Quantidade de hosts numa lista separada por virgula (ignora vazios e espacos).
contar_hosts() {
    printf '%s' "${1:-}" | awk -F',' '{ n=0; for(i=1;i<=NF;i++) { gsub(/[[:space:]]/, "", $i); if(length($i)) n++ } print n }'
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
#       + <BANCO>_CONN_PROJECTED + EXTERNAL_DB_CONSUMERS + PG_ADMIN_RESERVE
#       <= PG_MAX_CONNECTIONS
# <BANCO>_CONN_PROJECTED soma as conexoes dedicadas (log, geo, treinamento,
# ranking), com sessao e pool proprios no MESMO servidor; zero enquanto o
# modulo segue na pgsql.
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

# Cada membro pode virar primario. Reservar tambem as sessoes leitoras que
# podem continuar abertas nele apos uma promocao, inclusive as de tasks/filas.
READ_CONN_PROJECTED=0
if [ "${DB_READ_ENABLED:-false}" = "true" ]; then
    READ_HOST_COUNT="$(contar_hosts "${DB_READ_HOSTS:-}")"
    READ_CONN_POR_WORKER=1
    if [ "${OCTANE_HOOK_FLAGS_ENABLED:-false}" = "true" ]; then
        READ_CONN_POR_WORKER="${SWOOLE_PG_READ_POOL_SIZE:-2}"
        require_uint SWOOLE_PG_READ_POOL_SIZE "$READ_CONN_POR_WORKER"
    fi
    READ_CONN_PER_INSTANCE=$(((WORKERS * READ_CONN_POR_WORKER + TASK_WORKERS + QUEUE_WORKERS) * READ_HOST_COUNT))
    READ_CONN_PROJECTED=$((READ_CONN_PER_INSTANCE * APP_INSTANCES + EXTERNAL_DB_CONSUMERS * READ_HOST_COUNT))
fi
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

# Conexoes dedicadas (log, geo, treinamento, ranking): so abrem sessao propria
# quando a chave de ativacao aponta para outra conexao que nao a pgsql. Do
# contrario o modulo usa a sessao da pgsql e ja esta na conta acima. Mesma forma
# da conta principal: 1 PDO por worker com hooks OFF, o pool inteiro por worker
# com hooks ON, + tasks e filas. Os consumidores externos (filas dedicadas,
# scheduler) tambem usam cada banco, entao EXTERNAL_DB_CONSUMERS entra de novo,
# uma vez por banco, como nos leitores.
#
# Cada banco gera ${ROTULO}_CONN_PROJECTED, _CONN_NAME, _CONN_POR_WORKER e
# _VIA_POOLER. Pelo pooler so passa quando o host esta vazio ou igual a DB_HOST;
# com host proprio (homolog: direto no db) as sessoes caem direto no Postgres.
# Com ${ROTULO}_PG_MAX_CONNECTIONS definido o banco e tratado como SERVIDOR
# SEPARADO: tem teto proprio e nao soma no do sdc. Sem ele a soma e
# conservadora (assume o mesmo servidor), que e o que o log sempre fez.
valor_de() {
    eval "printf '%s' \"\${$1:-}\""
}

projetar_conexao_dedicada() {
    _rotulo="$1"
    _conexao="$2"
    _var_pool="$3"
    _var_host="$4"
    _pool_padrao="$5"
    # Opcionais: servidor onde as sessoes caem (rotulo cujo ${X}_PG_MAX_CONNECTIONS
    # vale; SDC = servidor principal, sem teto proprio) e sessoes de replica
    # (var do pool por host, padrao, quantidade de hosts de replica).
    _servidor="${6:-${_rotulo}}"
    _var_replica="${7:-}"
    _replica_padrao="${8:-0}"
    _hosts_replica="${9:-0}"

    eval "${_rotulo}_CONN_NAME=''; ${_rotulo}_CONN_PROJECTED=0; ${_rotulo}_CONN_POR_WORKER=1; ${_rotulo}_VIA_POOLER=false; ${_rotulo}_CONN_SEPARADO=false; ${_rotulo}_SERVIDOR='${_servidor}'; ${_rotulo}_POOL_VAR='${_var_pool}'"

    if [ -z "${_conexao}" ] || [ "${_conexao}" = "${DB_CONNECTION:-pgsql}" ] || [ "${_conexao}" = "pgsql" ]; then
        return 0
    fi

    _por_worker=1
    _modo="hooks OFF: 1 conexao/worker"
    if [ "${OCTANE_HOOK_FLAGS_ENABLED:-false}" = "true" ]; then
        _por_worker="$(valor_de "${_var_pool}")"
        _por_worker="${_por_worker:-${_pool_padrao}}"
        require_uint "${_var_pool}" "${_por_worker}"
        _modo="hooks ON: ${_por_worker} conexoes/worker"
    fi
    _por_instancia=$(((WORKERS * _por_worker) + TASK_WORKERS + QUEUE_WORKERS))
    _externas=$((EXTERNAL_DB_CONSUMERS))
    if [ "${_hosts_replica}" -gt 0 ]; then
        _por_worker_replica=1
        if [ "${OCTANE_HOOK_FLAGS_ENABLED:-false}" = "true" ]; then
            _por_worker_replica="$(valor_de "${_var_replica}")"
            _por_worker_replica="${_por_worker_replica:-${_replica_padrao}}"
            require_uint "${_var_replica}" "${_por_worker_replica}"
        fi
        _por_instancia=$((_por_instancia + (WORKERS * _por_worker_replica + TASK_WORKERS + QUEUE_WORKERS) * _hosts_replica))
        _externas=$((_externas * (1 + _hosts_replica)))
        _modo="${_modo} + ${_hosts_replica} replica(s)"
    fi
    _projetado=$((_por_instancia * APP_INSTANCES + _externas))

    _host="$(valor_de "${_var_host}")"
    _via_pooler=false
    if [ -n "${POOLER_MODE}" ] && { [ -z "${_host}" ] || [ "${_host}" = "${DB_HOST:-}" ]; }; then
        _via_pooler=true
    fi

    eval "${_rotulo}_CONN_NAME='${_conexao}'; ${_rotulo}_CONN_PROJECTED=${_projetado}; ${_rotulo}_CONN_POR_WORKER=${_por_worker}; ${_rotulo}_VIA_POOLER=${_via_pooler}"
    _nome="$(printf '%s' "${_rotulo}" | tr '[:upper:]' '[:lower:]')"
    echo "Conexao de ${_nome} ${_conexao} (${_modo}): ${_por_instancia}/inst x ${APP_INSTANCES} + ${_externas} externas = ${_projetado}"
}

# Ranking e sempre um banco separado: so entra na conta quando o modulo esta
# habilitado (config/ranking.php, RANKING_HABILITADO).
RANKING_CONN_ALVO=""
if [ "$(printf '%s' "${RANKING_HABILITADO:-false}" | tr '[:upper:]' '[:lower:]')" = "true" ]; then
    RANKING_CONN_ALVO="ranking"
fi

# Pools de leitura/origem (database.coroutine_pools) tem sessoes proprias e so
# existem com o dominio ativo: pgsql_geo_read cai no servidor do geo (mais as
# replicas, com GEO_DB_READ_ENABLED/DB_READ_ENABLED + hosts), ranking_read no do
# ranking e ranking_source_ro no do sdc (RANKING_SOURCE_HOST: origem das projecoes).
GEO_READ_CONN_ALVO=""
GEO_REPLICA_HOSTS=0
if [ -n "${DB_GEO_CONNECTION:-}" ]; then
    GEO_READ_CONN_ALVO="pgsql_geo_read"
    if [ "$(printf '%s' "${GEO_DB_READ_ENABLED:-${DB_READ_ENABLED:-false}}" | tr '[:upper:]' '[:lower:]')" = "true" ]; then
        if [ -n "${GEO_DB_READ_HOSTS:-}" ]; then
            GEO_REPLICA_HOSTS="$(contar_hosts "${GEO_DB_READ_HOSTS}")"
        elif [ -z "${GEO_DB_HOST:-}" ] || [ "${GEO_DB_HOST}" = "${DB_HOST:-}" ]; then
            GEO_REPLICA_HOSTS="$(contar_hosts "${DB_READ_HOSTS:-}")"
        fi
    fi
fi
RANKING_READ_CONN_ALVO=""
RANKING_SOURCE_CONN_ALVO=""
if [ -n "${RANKING_CONN_ALVO}" ]; then
    RANKING_READ_CONN_ALVO="ranking_read"
    RANKING_SOURCE_CONN_ALVO="ranking_source_ro"
fi

# sdc_powerbi so conta com DB_POWERBI_CONNECTION. A escrita cai no lider (soma
# no teto do sdc, salvo POWERBI_PG_MAX_CONNECTIONS). Leitura e fonte vivem no
# membro de BI, servidor proprio com teto BI_PG_MAX_CONNECTIONS (rotulo BI):
# nao pesam no max_connections do lider.
POWERBI_READ_CONN_ALVO=""
FONTE_BI_CONN_ALVO=""
if [ -n "${DB_POWERBI_CONNECTION:-}" ]; then
    POWERBI_READ_CONN_ALVO="pgsql_powerbi_read"
    FONTE_BI_CONN_ALVO="pgsql_fonte_bi"
fi

BANCOS_DEDICADOS="LOG GEO GEO_READ TREINAMENTO RANKING RANKING_READ RANKING_SOURCE POWERBI POWERBI_READ FONTE_BI"
projetar_conexao_dedicada LOG "${DB_LOG_CONNECTION:-}" SWOOLE_PG_LOG_POOL_SIZE LOG_DB_HOST 4
projetar_conexao_dedicada GEO "${DB_GEO_CONNECTION:-}" SWOOLE_PG_GEO_POOL_SIZE GEO_DB_HOST 4
projetar_conexao_dedicada GEO_READ "${GEO_READ_CONN_ALVO}" SWOOLE_PG_GEO_READ_POOL_SIZE GEO_DB_HOST 4 GEO SWOOLE_PG_GEO_REPLICA_POOL_SIZE 1 "${GEO_REPLICA_HOSTS}"
projetar_conexao_dedicada TREINAMENTO "${DB_TREINAMENTO_CONNECTION:-}" SWOOLE_PG_TREINAMENTO_POOL_SIZE TREINAMENTO_DB_HOST 4
projetar_conexao_dedicada RANKING "${RANKING_CONN_ALVO}" SWOOLE_PG_RANKING_POOL_SIZE RANKING_DB_HOST 4
projetar_conexao_dedicada RANKING_READ "${RANKING_READ_CONN_ALVO}" SWOOLE_PG_RANKING_READ_POOL_SIZE RANKING_DB_HOST 3 RANKING
projetar_conexao_dedicada RANKING_SOURCE "${RANKING_SOURCE_CONN_ALVO}" SWOOLE_PG_RANKING_SOURCE_POOL_SIZE RANKING_SOURCE_HOST 2 SDC
projetar_conexao_dedicada POWERBI "${DB_POWERBI_CONNECTION:-}" SWOOLE_PG_POWERBI_POOL_SIZE POWERBI_DB_HOST 4
projetar_conexao_dedicada POWERBI_READ "${POWERBI_READ_CONN_ALVO}" SWOOLE_PG_POWERBI_READ_POOL_SIZE POWERBI_DB_READ_HOST 4 BI
projetar_conexao_dedicada FONTE_BI "${FONTE_BI_CONN_ALVO}" SWOOLE_PG_FONTE_BI_POOL_SIZE FONTE_BI_DB_HOST 2 BI

if [ -n "${POOLER_MODE}" ]; then
    PGB_MAX_CLIENTES="${PGBOUNCER_MAX_CLIENT_CONN:-1000}"
    PGB_POOL_SIZE="${PGBOUNCER_DEFAULT_POOL_SIZE:-25}"
    require_uint PGBOUNCER_MAX_CLIENT_CONN "$PGB_MAX_CLIENTES"
    require_uint PGBOUNCER_DEFAULT_POOL_SIZE "$PGB_POOL_SIZE"

    CONN_APP=$((CONN_PER_INSTANCE * APP_INSTANCES))
    CLIENTES_LOG=""
    for ROTULO in ${BANCOS_DEDICADOS}; do
        if [ "$(valor_de "${ROTULO}_VIA_POOLER")" = "true" ]; then
            # Banco pelo pooler: sao clientes dele, e o pool_size abaixo ja os cobre.
            PROJETADO_BANCO="$(valor_de "${ROTULO}_CONN_PROJECTED")"
            CONN_APP=$((CONN_APP + PROJETADO_BANCO))
            CLIENTES_LOG="${CLIENTES_LOG} + ${PROJETADO_BANCO} $(printf '%s' "${ROTULO}" | tr '[:upper:]' '[:lower:]')"
            eval "${ROTULO}_CONN_PROJECTED=0"
        fi
    done
    echo "Pooler: DB_POOLER_MODE=${POOLER_MODE} -- o app conecta no PgBouncer, nao no Postgres."
    echo "Clientes no pooler: ${CONN_PER_INSTANCE}/inst x ${APP_INSTANCES}${CLIENTES_LOG} = ${CONN_APP} (teto ${PGB_MAX_CLIENTES})"

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

# Banco com teto proprio (servidor separado) nao soma no do sdc: e checado
# contra o ${ROTULO}_PG_MAX_CONNECTIONS dele. Sem teto, soma (mesmo servidor).
for ROTULO in ${BANCOS_DEDICADOS}; do
    PROJETADO_BANCO="$(valor_de "${ROTULO}_CONN_PROJECTED")"
    [ "${PROJETADO_BANCO}" -gt 0 ] || continue
    NOME_BANCO="$(valor_de "${ROTULO}_CONN_NAME")"
    ROTULO_MIN="$(printf '%s' "${ROTULO}" | tr '[:upper:]' '[:lower:]')"
    # Pools filhos (geo_read, ranking_read) dividem o servidor do dominio pai: o
    # teto e o do pai e as sessoes acumulam. SDC nunca tem teto proprio.
    SERVIDOR="$(valor_de "${ROTULO}_SERVIDOR")"
    TETO_BANCO=""
    [ "${SERVIDOR}" = "SDC" ] || TETO_BANCO="$(valor_de "${SERVIDOR}_PG_MAX_CONNECTIONS")"
    if [ -n "${TETO_BANCO}" ]; then
        require_uint "${SERVIDOR}_PG_MAX_CONNECTIONS" "${TETO_BANCO}"
        ACUMULADO_SERVIDOR=$(($(valor_de "SOMA_SERVIDOR_${SERVIDOR}") + PROJETADO_BANCO))
        eval "SOMA_SERVIDOR_${SERVIDOR}=${ACUMULADO_SERVIDOR}"
        if [ "${ACUMULADO_SERVIDOR}" -gt "${TETO_BANCO}" ]; then
            echo "FATAL: conexoes projetadas de ${ROTULO_MIN} (${PROJETADO_BANCO}; ${ACUMULADO_SERVIDOR} no servidor) excedem ${SERVIDOR}_PG_MAX_CONNECTIONS (${TETO_BANCO})."
            echo "       Reduza $(valor_de "${ROTULO}_POOL_VAR") ou o numero de workers/replicas. Abortando boot."
            exit 1
        fi
        echo "Servidor separado de ${ROTULO_MIN} (${NOME_BANCO}): ${PROJETADO_BANCO} de ${TETO_BANCO} (${ACUMULADO_SERVIDOR} no servidor)."
        eval "${ROTULO}_CONN_SEPARADO=true"
        continue
    fi
    CONN_SEM_BANCO="${CONN_PROJECTED}"
    CONN_PROJECTED=$((CONN_PROJECTED + PROJETADO_BANCO))
    echo "Split no Postgres: ${CONN_SEM_BANCO} sdc + ${PROJETADO_BANCO} ${ROTULO_MIN} (${NOME_BANCO}) = ${CONN_PROJECTED}"
done

if [ "$READ_CONN_PROJECTED" -gt 0 ]; then
    CONN_PROJECTED=$((CONN_PROJECTED + READ_CONN_PROJECTED))
    echo "Reserva de leitores por membro: ${READ_CONN_PROJECTED}; teto combinado: ${CONN_PROJECTED}"
fi

# Sob hooks ON, conexao pgsql fora de database.coroutine_pools cai no manager
# padrao: UM PDO por worker compartilhado entre coroutines (o protocolo do
# Postgres corrompe com duas queries simultaneas) e fora da conta acima. Le o
# config ja cacheado (config:cache acima): array puro, sem subir a aplicacao.
if [ "${OCTANE_HOOK_FLAGS_ENABLED:-false}" = "true" ] && [ -f bootstrap/cache/config.php ]; then
    PG_SEM_POOL=$(php -r '$c = require "bootstrap/cache/config.php"; $d = $c["database"] ?? []; $p = $d["coroutine_pools"] ?? []; $f = []; foreach (($d["connections"] ?? []) as $n => $x) { if (($x["driver"] ?? "") === "pgsql" && ! isset($p[$n])) { $f[] = $n; } } echo implode(",", $f);' 2>/dev/null || true)
    if [ -n "${PG_SEM_POOL}" ]; then
        echo "AVISO: hooks ON com conexoes pgsql fora de database.coroutine_pools: ${PG_SEM_POOL}."
        echo "       Usadas dentro de coroutine, dividem 1 PDO por worker entre coroutines e nao"
        echo "       entram na conta acima. Liste-as em coroutine_pools ou use so fora de coroutine."
    fi
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
        fi
        for ROTULO in ${BANCOS_DEDICADOS}; do
            PROJETADO_BANCO="$(valor_de "${ROTULO}_CONN_PROJECTED")"
            if [ "${PROJETADO_BANCO}" -gt 0 ] && [ "$(valor_de "${ROTULO}_CONN_SEPARADO")" != "true" ]; then
                ROTULO_MIN="$(printf '%s' "${ROTULO}" | tr '[:upper:]' '[:lower:]')"
                echo "       A conexao de ${ROTULO_MIN} soma ${PROJETADO_BANCO} sessoes diretas no Postgres."
                if [ "${OCTANE_HOOK_FLAGS_ENABLED:-false}" = "true" ]; then
                    echo "       Sob hooks ON reduza $(valor_de "${ROTULO}_POOL_VAR") (hoje $(valor_de "${ROTULO}_CONN_POR_WORKER")) -- cada"
                    echo "       unidade a menos libera ${WORKERS} x ${APP_INSTANCES} conexoes."
                fi
                echo "       Ou volte o ${ROTULO_MIN} para a sessao da pgsql desligando a conexao dedicada."
            fi
        done
        if [ -z "${POOLER_MODE}" ]; then
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
