#!/bin/bash
set -e

echo "🚀 Iniciando container de desenvolvimento SDC..."

# Verifica se o diretório vendor existe (dependências PHP instaladas)
if [ ! -d "/var/www/vendor" ]; then
    echo "📦 Instalando dependências PHP (Composer)..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
    echo "✅ Dependências PHP instaladas com sucesso!"
else
    echo "✅ Dependências PHP já instaladas (vendor existe)"
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

# Configura o PHP-FPM para usar stderr corretamente
if [ "$1" = "php-fpm" ]; then
    echo "⚙️  Configurando PHP-FPM..."
    
    # Configura o PHP-FPM para usar stderr ao invés de arquivo de log
    # Isso resolve o problema de permissão no /proc/self/fd/2
    if [ -f /usr/local/etc/php-fpm.d/www.conf ]; then
        # Configura error_log para stderr (já é o padrão, mas garantimos)
        sed -i 's/^;*error_log = .*/error_log = \/proc\/self\/fd\/2/' /usr/local/etc/php-fpm.d/www.conf 2>/dev/null || true
        
        # Garante que catch_workers_output está habilitado para redirecionar stderr
        if ! grep -q "^catch_workers_output" /usr/local/etc/php-fpm.d/www.conf; then
            echo "catch_workers_output = yes" >> /usr/local/etc/php-fpm.d/www.conf
        fi
        
        # Garante que o usuário e grupo estão configurados corretamente
        sed -i 's/^user = .*/user = www-data/' /usr/local/etc/php-fpm.d/www.conf 2>/dev/null || true
        sed -i 's/^group = .*/group = www-data/' /usr/local/etc/php-fpm.d/www.conf 2>/dev/null || true
    fi
fi

echo "✅ Container de desenvolvimento pronto!"
echo ""

# Se o comando for php-fpm, executa diretamente como root
# O PHP-FPM fará a mudança para www-data automaticamente baseado na configuração
if [ "$1" = "php-fpm" ]; then
    # PHP-FPM precisa rodar como root inicialmente para poder fazer chown/chmod
    # Ele muda para www-data automaticamente baseado na configuração do www.conf
    exec "$@"
else
    # Para outros comandos, executa como root
    exec "$@"
fi

