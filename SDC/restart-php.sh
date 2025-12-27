#!/bin/bash

echo "=========================================="
echo "Reiniciando PHP-FPM para limpar OPcache"
echo "=========================================="

sudo systemctl restart php8.3-fpm

if [ $? -eq 0 ]; then
    echo "✓ PHP-FPM reiniciado com sucesso!"
else
    echo "✗ Erro ao reiniciar PHP-FPM"
    exit 1
fi

echo ""
echo "Verificando status..."
sudo systemctl status php8.3-fpm --no-pager | head -10

echo ""
echo "=========================================="
echo "PRÓXIMOS PASSOS:"
echo "=========================================="
echo "1. Acesse: http://localhost:8001/login"
echo "2. Faça login com o admin user"
echo "3. Teste as páginas TDAP:"
echo "   - http://localhost:8001/tdap/produtos"
echo "   - http://localhost:8001/tdap/recebimentos"
echo "   - http://localhost:8001/tdap/movimentacoes"
echo ""
echo "Se ainda houver erro, abra o console do navegador (F12)"
echo "e verifique se ainda aparece o erro de prop type."
echo "=========================================="
