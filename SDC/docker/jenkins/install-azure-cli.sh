#!/bin/bash
# ============================================================================
# Script para instalar Azure CLI no Jenkins
# ============================================================================

set -e

echo "=========================================="
echo "Instalando Azure CLI no Jenkins..."
echo "=========================================="

# Detectar sistema operacional
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$ID
else
    echo "❌ Não foi possível detectar o sistema operacional"
    exit 1
fi

# Instalar Azure CLI baseado no OS
case $OS in
    ubuntu|debian)
        echo "📦 Instalando Azure CLI para Ubuntu/Debian..."
        curl -sL https://aka.ms/InstallAzureCLIDeb | bash
        ;;
    rhel|centos|fedora)
        echo "📦 Instalando Azure CLI para RHEL/CentOS/Fedora..."
        rpm --import https://packages.microsoft.com/keys/microsoft.asc
        echo -e "[azure-cli]\nname=Azure CLI\nbaseurl=https://packages.microsoft.com/yumrepos/azure-cli\nenabled=1\ngpgcheck=1\ngpgkey=https://packages.microsoft.com/keys/microsoft.asc" | tee /etc/yum.repos.d/azure-cli.repo
        yum install -y azure-cli
        ;;
    alpine)
        echo "📦 Instalando Azure CLI para Alpine..."
        apk add --no-cache bash curl
        curl -sL https://aka.ms/InstallAzureCLI | bash
        ;;
    *)
        echo "⚠️  Sistema operacional não suportado: $OS"
        echo "Tentando instalação genérica..."
        curl -sL https://aka.ms/InstallAzureCLI | bash
        ;;
esac

# Verificar instalação
if command -v az &> /dev/null; then
    AZ_VERSION=$(az --version | head -n 1)
    echo "✅ Azure CLI instalado com sucesso: $AZ_VERSION"

    # Verificar extensões necessárias
    echo "📦 Verificando extensões do Azure CLI..."
    az extension add --name containerapp --upgrade 2>/dev/null || true

    echo "✅ Instalação concluída!"
else
    echo "❌ Erro: Azure CLI não foi instalado corretamente"
    exit 1
fi

