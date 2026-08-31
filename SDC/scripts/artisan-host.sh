#!/usr/bin/env bash
#
# Roda artisan no HOST contra o Postgres do container, sem passar pelo Octane.
#
# Por que existe: os comandos de ETL do Cisterna (extrair/refinar) processam
# milhares de linhas e sao muito mais rapidos e observaveis fora do container.
# Mas rodar `php artisan` cru no host cai em quatro armadilhas:
#
#   1. O PHP do PATH pode nao ser o 8.3 do Laragon.
#   2. A extensao pdo_pgsql nao vem habilitada no php.ini do Laragon.
#   3. O Postgres do container publica em 127.0.0.1:5434, nao no 5432 do .env
#      (que aponta para o host `db`, nome que so existe na rede do Docker).
#   4. `bootstrap/cache/config.php` IGNORA env var. Todo restart do container
#      recria esse arquivo com host=db, e a partir dai qualquer execucao no
#      host tenta conectar em `db:5432` e morre em timeout -- por isso o
#      cache e derrubado aqui.
#
# Uso:
#   scripts/artisan-host.sh cisterna:refinar-legado --only=vistorias
#   scripts/artisan-host.sh migrate --force
#
# O container continua funcionando: ele recria o config no proximo boot.

set -euo pipefail

PHPDIR="${LARAGON_PHP:-/c/laragon/bin/php/php-8.3.16-Win32-vs16-x64}"
PHP="${PHPDIR}/php.exe"

if [[ ! -x "${PHP}" ]]; then
    echo "ERRO: PHP nao encontrado em ${PHP}" >&2
    echo "Ajuste com: LARAGON_PHP=/caminho/do/php scripts/artisan-host.sh ..." >&2
    exit 1
fi

if [[ -f bootstrap/cache/config.php ]]; then
    echo "Derrubando bootstrap/cache/config.php (config cacheado ignora env var)..."
    rm -f bootstrap/cache/config.php
fi

DB_CONNECTION=pgsql \
DB_HOST=127.0.0.1 \
DB_PORT="${SDC_DB_PORT:-5434}" \
DB_DATABASE="${SDC_DB_DATABASE:-sdc}" \
DB_USERNAME="${SDC_DB_USERNAME:-sdc}" \
DB_PASSWORD="${SDC_DB_PASSWORD:-secret}" \
CACHE_STORE=array \
CACHE_DRIVER=array \
SESSION_DRIVER=array \
QUEUE_CONNECTION=sync \
    "${PHP}" -d extension_dir="${PHPDIR}/ext" -d extension=pdo_pgsql \
    artisan "$@"
