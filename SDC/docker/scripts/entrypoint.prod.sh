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

# Sempre regenerar .env a partir das variaveis de ambiente do Azure App Service
# (com WEBSITES_ENABLE_APP_SERVICE_STORAGE=false o FS e efemero a cada restart,
# mas mantemos a sobrescrita para garantir que mudancas em app settings tenham efeito imediato).
# Garantir diretorios de storage existem (Symfony/Laravel exige cache/sessions/views).
# Necessario porque WEBSITES_ENABLE_APP_SERVICE_STORAGE=false torna o FS efemero.
mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/framework/testing \
         storage/logs \
         storage/app/public \
         storage/app/compdec \
         storage/app/pae \
         storage/app/exports \
         bootstrap/cache
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

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
REDIS_HOST="${REDIS_HOST:-sdcdefesa.redis.cache.windows.net}"
REDIS_PORT="${REDIS_PORT:-6380}"
REDIS_PASSWORD="${REDIS_PASSWORD:-}"
REDIS_CLIENT="${REDIS_CLIENT:-predis}"
REDIS_SCHEME="${REDIS_SCHEME:-tls}"
REDIS_PREFIX="${REDIS_PREFIX:-sdc_prod_}"
CACHE_PREFIX="${CACHE_PREFIX:-sdc_prod_cache_}"
CACHE_DRIVER="${CACHE_DRIVER:-redis}"
SESSION_DRIVER="${SESSION_DRIVER:-redis}"
SESSION_DOMAIN="${SESSION_DOMAIN:-sdcdefesa.azurewebsites.net}"
SESSION_SECURE_COOKIE="${SESSION_SECURE_COOKIE:-true}"
SESSION_SAME_SITE="${SESSION_SAME_SITE:-lax}"
QUEUE_CONNECTION="${QUEUE_CONNECTION:-redis}"
OCTANE_SERVER="${OCTANE_SERVER:-roadrunner}"
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

# Executar migrations
echo "Executando migrations..."
php artisan migrate --force || echo "⚠️  Aviso: Erro ao executar migrations"

# Executar seeders completos (idempotentes — usam updateOrCreate/insertOrIgnore)
# Inclui: Roles, Órgãos, Admin, Usuários Mock (diversas hierarquias), RATs Mock
echo "Executando seeders (dados mock + hierarquias)..."
php artisan db:seed --force --class=DatabaseSeeder || echo "⚠️  Aviso: Erro ao executar seeders"

echo "✅ Banco inicializado com dados mock completos"
echo "   Admin: admin@defesa.mg.gov.br / password"
echo "   Hierarquias: super-admin, admin, manager, analyst, operator, viewer, user"
echo "   RATs: 15 registros (em_andamento, rascunho, finalizado)"

# Gerar documentacao Swagger (l5-swagger)
echo "Gerando documentacao Swagger..."
mkdir -p storage/api-docs
php artisan l5-swagger:generate 2>/dev/null || echo "Aviso: falha ao gerar swagger"

# Limpar caches
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

# Ajustar permissões
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Queue worker em background (processa emails, jobs assincronos).
# Container unico no App Service: roda o worker junto do Octane.
# Loop while reinicia o worker se ele cair ou atingir --max-time.
echo "Iniciando queue worker em background..."
(
    while true; do
        php artisan queue:work --queue=default --tries=3 --timeout=90 --sleep=3 --max-time=3600 2>&1
        echo "[queue:work] worker reiniciando em 2s..."
        sleep 2
    done
) &

# Iniciar servidor Octane (RoadRunner)
echo "Iniciando servidor Octane (RoadRunner)..."
exec php artisan octane:start --server=roadrunner --host=0.0.0.0 --port=8000 --workers=auto

