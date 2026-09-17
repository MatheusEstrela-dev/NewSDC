#!/bin/sh
set -e

if [ -n "${ANEXOS_ROOT:-}" ] && [ ! -e "${ANEXOS_ROOT}/.sdc_storage_mounted" ]; then
    echo "ERRO: disco de anexos sem marcador .sdc_storage_mounted."
    exit 1
fi

umask 002
if [ "${AUTOLOAD_REFRESH_ON_BOOT:-false}" = "true" ]; then
    composer dump-autoload --optimize --no-interaction
fi

exec php artisan queue:work "$@"
