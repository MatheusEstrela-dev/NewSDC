#!/bin/bash

echo "🐳 Limpando OPcache do container Docker..."
sudo docker exec newsdc_app php -r "opcache_reset(); echo 'OPcache cleared' . PHP_EOL;"

echo ""
echo "🧹 Limpando caches Laravel dentro do Docker..."
sudo docker exec newsdc_app php artisan optimize:clear

echo ""
echo "🔄 Reiniciando container PHP..."
sudo docker restart newsdc_app

echo ""
echo "⏳ Aguardando container iniciar (5 segundos)..."
sleep 5

echo ""
echo "✅ Processo concluído!"
echo ""
echo "📝 Teste agora:"
echo "    http://localhost:8001/tdap/recebimentos"
echo "    http://localhost:8001/tdap/movimentacoes"
