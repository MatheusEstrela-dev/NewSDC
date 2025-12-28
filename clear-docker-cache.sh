#!/bin/bash

echo "🐳 Limpando OPcache do container Docker..."
docker exec newsdc_app php -r "opcache_reset(); echo 'OPcache cleared' . PHP_EOL;"

echo ""
echo "🧹 Limpando caches Laravel dentro do Docker..."
docker exec newsdc_app php artisan optimize:clear

echo ""
echo "📦 Otimizando autoloader..."
docker exec newsdc_app composer dump-autoload -o

echo ""
echo "🔄 Reiniciando container PHP..."
docker restart newsdc_app

echo ""
echo "⏳ Aguardando container iniciar (5 segundos)..."
sleep 5

echo ""
echo "✅ Processo concluído!"
echo ""
echo "📝 Teste agora:"
echo "    http://localhost:8001/tdap/recebimentos"
echo "    http://localhost:8001/tdap/movimentacoes"
echo ""
echo "Abra o Console (F12) e verifique que NÃO há mais erros de prop type!"
