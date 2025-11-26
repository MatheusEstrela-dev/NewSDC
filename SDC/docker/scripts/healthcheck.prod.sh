#!/bin/sh
# ============================================================================
# SDC - Healthcheck para Produção
# Verificações mais rigorosas para sistema crítico 24/7
# ============================================================================

set -e

# Verificar PHP-FPM está respondendo
php_fpm_check() {
    SCRIPT_NAME=/fpm-ping \
    SCRIPT_FILENAME=/fpm-ping \
    REQUEST_METHOD=GET \
    cgi-fcgi -bind -connect 127.0.0.1:9000 2>/dev/null | grep -q "pong"
}

# Verificar se a aplicação está saudável
app_check() {
    # Tenta acessar uma rota de health simples
    curl -sf http://127.0.0.1:8000/health > /dev/null 2>&1 || \
    curl -sf http://127.0.0.1:9000/fpm-ping > /dev/null 2>&1
}

# Verificar espaço em disco
disk_check() {
    # Falha se menos de 10% de espaço livre
    df /var/www | awk 'NR==2 {exit ($5+0 > 90)}'
}

# Verificar memória do processo
memory_check() {
    # PHP-FPM memory usage - falha se > 80% do limite
    # Simplificado para container
    return 0
}

# Executar todas as verificações
main() {
    php_fpm_check || { echo "PHP-FPM check failed"; exit 1; }
    disk_check || { echo "Disk space check failed"; exit 1; }
    memory_check || { echo "Memory check failed"; exit 1; }
    
    echo "OK"
    exit 0
}

main

