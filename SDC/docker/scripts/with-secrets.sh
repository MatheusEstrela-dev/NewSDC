#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Decripta secrets do docker/.env (gerenciado via dotenvx) e executa o
# comando recebido com as variaveis ja injetadas no ambiente.
#
# Uso:
#   ./docker/scripts/with-secrets.sh docker compose -f docker/compose.dev.yml up -d
#   ./docker/scripts/with-secrets.sh docker compose -f docker/compose.dev.yml up -d --force-recreate
#
# Requer:
#   - npm i (para ter o @dotenvx/dotenvx em node_modules);
#   - docker/.env.keys local (chave privada que decripta os 'encrypted:...').
# ---------------------------------------------------------------------------
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

if [ ! -f "$PROJECT_ROOT/docker/.env.keys" ]; then
    echo "[with-secrets] AVISO: docker/.env.keys nao encontrado." >&2
    echo "                Solicite a chave a um dev autorizado e coloque-a em docker/.env.keys" >&2
    echo "                (esse arquivo NAO vai pro git)." >&2
fi

cd "$PROJECT_ROOT"
exec npx dotenvx run -f docker/.env -- "$@"
