#!/bin/sh
# ============================================================================
# SDC - Entrypoint de Produção para Azure App Service
# ============================================================================

set -e

cd /var/www

# Verificar se composer.json existe
if [ ! -f composer.json ]; then
    echo "ERRO: composer.json NÃO encontrado em /var/www"
    echo "Conteúdo de /var/www:"
    ls -la /var/www
    echo "Procurando composer.json em outros lugares:"
    find / -name composer.json 2>/dev/null | head -5
    exit 1
fi

# Criar arquivo .env se não existir (usando variáveis de ambiente do Azure)
if [ ! -f .env ]; then
    echo "Criando arquivo .env a partir de variáveis de ambiente..."
    cat > .env <<EOF
APP_NAME="${APP_NAME:-SDC - Sistema de Defesa Civil}"
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY:-}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-http://localhost}
DB_CONNECTION=${DB_CONNECTION:-mysql}
DB_HOST=${DB_HOST:-localhost}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-sdc}
DB_USERNAME=${DB_USERNAME:-sdc}
DB_PASSWORD=${DB_PASSWORD:-}
REDIS_HOST=${REDIS_HOST:-}
REDIS_PORT=${REDIS_PORT:-6379}
REDIS_PASSWORD=${REDIS_PASSWORD:-}
CACHE_DRIVER=${CACHE_DRIVER:-file}
SESSION_DRIVER=${SESSION_DRIVER:-file}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}
LOG_CHANNEL=${LOG_CHANNEL:-stack}
LOG_LEVEL=${LOG_LEVEL:-error}
EOF
    echo "Arquivo .env criado"
fi

# Gerar APP_KEY se não existir ou estiver vazia
if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null || grep -q "^APP_KEY=$" .env 2>/dev/null || grep -q "^APP_KEY=\"\"" .env 2>/dev/null; then
    APP_KEY_VALUE=$(grep "^APP_KEY=" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' || echo "")
    if [ -z "$APP_KEY_VALUE" ] || [ "$APP_KEY_VALUE" == "base64:" ]; then
        echo "Gerando APP_KEY..."
        php artisan key:generate --force
        echo "APP_KEY gerada"
    fi
fi

# Executar migrations e seeders se necessário
# Verificar se a tabela users existe (indica se migrations foram executadas)
if [ "$DB_CONNECTION" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-database/database.sqlite}"
    if [ ! -f "$DB_FILE" ]; then
        echo "Criando arquivo SQLite: $DB_FILE"
        mkdir -p "$(dirname "$DB_FILE")"
        touch "$DB_FILE"
        chmod 664 "$DB_FILE"
        echo "Arquivo SQLite criado"
    fi
fi

# Verificar se migrations foram executadas (verifica se tabela users existe)
if ! php artisan migrate:status --quiet 2>/dev/null | grep -q "users"; then
    echo "Executando migrations..."
    php artisan migrate --force 2>/dev/null || echo "⚠️  Aviso: Erro ao executar migrations"
    
    # Verificar se usuário existe antes de executar seeder
    USER_EXISTS=$(php artisan tinker --execute="echo \App\Models\User::where('cpf', '12345678900')->exists() ? 'true' : 'false';" 2>/dev/null || echo "false")
    
    if [ "$USER_EXISTS" != "true" ]; then
        echo "Executando seeders (criando usuário de teste)..."
        php artisan db:seed --force --class=DatabaseSeeder 2>/dev/null || echo "⚠️  Aviso: Erro ao executar seeders"
        
        echo "✅ Banco de dados inicializado com usuário de teste"
        echo "   CPF: 12345678900 (sem formatação)"
        echo "   Senha: password"
    else
        echo "✅ Usuário de teste já existe no banco"
    fi
else
    echo "✅ Migrations já foram executadas"
fi

# Limpar caches
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

# Ajustar permissões
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Iniciar servidor
echo "Iniciando servidor Laravel..."
exec php artisan serve --host=0.0.0.0 --port=8000

