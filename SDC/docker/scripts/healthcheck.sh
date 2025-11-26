#!/bin/sh
# ============================================================================
# SDC - Healthcheck para Desenvolvimento
# ============================================================================

# Verificar PHP-FPM
SCRIPT_NAME=/fpm-ping \
SCRIPT_FILENAME=/fpm-ping \
REQUEST_METHOD=GET \
cgi-fcgi -bind -connect 127.0.0.1:9000 > /dev/null 2>&1

exit $?

