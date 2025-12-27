#!/bin/bash

echo "🔄 Limpando OPcache..."
php -r "opcache_reset(); echo 'OPcache cleared' . PHP_EOL;"

echo ""
echo "🧹 Limpando caches do Laravel..."
php artisan optimize:clear

echo ""
echo "📦 Otimizando autoloader..."
composer dump-autoload -o

echo ""
echo "✅ Tudo limpo!"
echo ""
echo "⚠️  IMPORTANTE: Para completar o processo, execute:"
echo "    sudo systemctl restart php8.3-fpm"
echo ""
echo "Ou se estiver usando Docker:"
echo "    cd docker && sudo docker-compose restart app"
