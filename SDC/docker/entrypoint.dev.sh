#!/bin/bash
set -e

echo "🚀 Iniciando container de desenvolvimento SDC..."

# Verifica se o autoload.php existe (dependências PHP instaladas)
if [ ! -f "/var/www/vendor/autoload.php" ]; then
    echo "📦 Instalando dependências PHP (Composer)..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
    echo "✅ Dependências PHP instaladas com sucesso!"
else
    echo "✅ Dependências PHP já instaladas (autoload.php existe)"
fi

# Verifica se o arquivo .env existe
if [ ! -f "/var/www/.env" ]; then
    echo "⚙️  Arquivo .env não encontrado. Copiando .env.example..."
    if [ -f "/var/www/.env.example" ]; then
        cp /var/www/.env.example /var/www/.env
        echo "✅ Arquivo .env criado a partir de .env.example"
        echo "⚠️  IMPORTANTE: Configure as variáveis de ambiente no arquivo .env"
    else
        echo "⚠️  Arquivo .env.example não encontrado!"
    fi
fi

# Verifica se a chave da aplicação foi gerada (apenas se .env existe)
if [ -f "/var/www/.env" ] && ! grep -q "APP_KEY=base64:" /var/www/.env 2>/dev/null; then
    echo "🔑 Gerando chave da aplicação..."
    php artisan key:generate --force
    echo "✅ Chave da aplicação gerada!"
fi

# Verifica permissões dos diretórios de storage e cache
echo "🔐 Verificando permissões..."
chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

echo "✅ Container de desenvolvimento pronto!"
echo ""

# Executa o comando passado (do docker-compose ou CMD)
exec "$@"

