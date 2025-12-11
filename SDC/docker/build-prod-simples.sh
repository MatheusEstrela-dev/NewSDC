#!/bin/bash
# ============================================================================
# SDC - Build Simples para Produção
# ============================================================================
# Uso: ./build-prod-simples.sh
# ============================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

echo "========================================"
echo "SDC - Build Produção"
echo "========================================"
echo ""
echo "Context: $PROJECT_ROOT"
echo "Dockerfile: docker/Dockerfile.prod"
echo ""

cd "$PROJECT_ROOT"

# Verificar se composer.json existe
if [ ! -f "composer.json" ]; then
    echo "❌ ERRO: composer.json não encontrado em $PROJECT_ROOT"
    exit 1
fi

echo "✅ composer.json encontrado"
echo ""

# Build
echo "🏗️  Building imagem..."
docker build \
    -f docker/Dockerfile.prod \
    -t sdc-dev-app:latest \
    -t apidover.azurecr.io/sdc-dev-app:latest \
    .

echo ""
echo "✅ Build concluído!"
echo ""
echo "Imagens criadas:"
echo "  - sdc-dev-app:latest"
echo "  - apidover.azurecr.io/sdc-dev-app:latest"
echo ""
echo "Para fazer push:"
echo "  az acr login --name apidover"
echo "  docker push apidover.azurecr.io/sdc-dev-app:latest"

