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
if docker exec newsdc_bun ps aux | grep -q "[v]ite"; then
    echo "✅ Vite esta rodando na porta 5175"
else
    echo "❌ Vite nao esta rodando!"
    echo "   Execute: docker compose restart bun"
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
echo "⚠️  IMPORTANTE: NAO execute 'bun run build' em dev!"
echo ""
