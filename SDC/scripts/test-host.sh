#!/usr/bin/env bash
#
# Roda a suite PHPUnit no HOST (fora do container), contra o Postgres de dev.
#
# Existe porque rodar teste neste projeto pelo caminho obvio nao funciona, e
# cada armadilha falha de um jeito diferente e nao obvio:
#
#   1. `php artisan test` nao existe. Responde `Command "test" is not defined`
#      mesmo com nunomaduro/collision instalado e o TestCommand.php presente no
#      vendor -- o comando nao chega a ser registrado.
#
#   2. O `php` do PATH e 8.1.25 e NAO carrega o vendor/. Da parse error em
#      sebastian/version, por causa de `readonly class`. Precisa do 8.3.
#
#   3. O php.ini do PHP 8.3 do Laragon nao habilita pdo_pgsql. Sem isso o
#      PDO responde "could not find driver".
#
#   4. `.env.testing` forca DB_CONNECTION=sqlite e DB_DATABASE=:memory:, e o
#      phpunit.xml forca APP_ENV=testing. Como as migrations deste projeto nao
#      rodam em sqlite, o banco vem VAZIO e todo teste morre com
#      "no such table". Por isso as env vars de banco vao explicitas aqui:
#      o dotenv do Laravel e imutavel, entao variavel de ambiente real vence o
#      arquivo .env.
#
#   5. Se bootstrap/cache/config.php existir, o item 4 nao resolve -- config
#      cacheado ignora env var. O script derruba o cache antes.
#
# Uso:
#   scripts/test-host.sh                      # suite inteira
#   scripts/test-host.sh --filter=EnumsTest   # um teste
#   scripts/test-host.sh --filter=Cisterna    # um modulo
#
# Qualquer argumento e repassado ao phpunit.

set -euo pipefail

PHPDIR="${SDC_PHP_DIR:-/c/laragon/bin/php/php-8.3.16-Win32-vs16-x64}"
PHP="${PHPDIR}/php.exe"

if [[ ! -x "${PHP}" ]]; then
    echo "ERRO: PHP 8.3 nao encontrado em ${PHP}" >&2
    echo "Ajuste SDC_PHP_DIR para o diretorio do PHP 8.3 do Laragon." >&2
    exit 1
fi

cd "$(dirname "${BASH_SOURCE[0]}")/.."

# Config cacheado ignora env var (item 5).
if [[ -f bootstrap/cache/config.php ]]; then
    echo "Derrubando bootstrap/cache/config.php (config cacheado ignora env var)..."
    rm -f bootstrap/cache/config.php
fi

# Postgres de dev, exposto pelo container newsdc_dev_db.
export DB_CONNECTION="${DB_CONNECTION:-pgsql}"
export DB_HOST="${DB_HOST:-127.0.0.1}"
export DB_PORT="${DB_PORT:-5434}"
export DB_DATABASE="${DB_DATABASE:-sdc}"
export DB_USERNAME="${DB_USERNAME:-sdc}"
export DB_PASSWORD="${DB_PASSWORD:-secret}"

exec "${PHP}" \
    -d extension_dir="${PHPDIR}/ext" \
    -d extension=pdo_pgsql \
    vendor/bin/phpunit "$@"
