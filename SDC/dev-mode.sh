#!/bin/bash

echo "🔥 Ativando Modo Desenvolvimento (Hot Reload)"
echo "=============================================="
echo ""

# Remove build de produção
if [ -d "public/build" ]; then
    echo "🗑️  Removendo build de produção..."
    rm -rf public/build
    echo "✅ Build removido"
else
    echo "✅ Sem build de produção (já em dev mode)"
fi

# Limpa caches
echo ""
echo "🧹 Limpando caches..."
php artisan cache:clear > /dev/null 2>&1
php artisan view:clear > /dev/null 2>&1
docker exec newsdc_app php -r "opcache_reset();" > /dev/null 2>&1
echo "✅ Caches limpos"

# Verifica Vite
echo ""
echo "🔍 Verificando Vite dev server..."
if docker exec newsdc_node ps aux | grep -q "[v]ite"; then
    echo "✅ Vite está rodando na porta 5173"
else
    echo "❌ Vite não está rodando!"
    echo "   Execute: docker-compose restart newsdc_node"
fi

echo ""
echo "=============================================="
echo "✅ Modo Desenvolvimento Ativado!"
echo ""
echo "📝 Próximos passos:"
echo "   1. Edite seus arquivos .vue, .js, .css"
echo "   2. Salve (Ctrl+S)"
echo "   3. Hard refresh no navegador (Ctrl+Shift+R)"
echo "   4. As próximas mudanças serão automáticas!"
echo ""
echo "⚠️  IMPORTANTE: NÃO execute 'npm run build' em dev!"
echo ""
