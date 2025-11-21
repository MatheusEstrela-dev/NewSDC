#!/bin/bash
# ===== JENKINS BACKUP SCRIPT =====
# Script para backup manual e restauração do Jenkins

set -e

BACKUP_DIR="./jenkins_backups"
JENKINS_HOME="./jenkins_home"
DATE=$(date +%Y%m%d_%H%M%S)

# Cores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Função de backup
backup() {
    print_info "Iniciando backup do Jenkins..."

    # Verificar se diretório existe
    if [ ! -d "$JENKINS_HOME" ]; then
        print_error "Diretório jenkins_home não encontrado!"
        exit 1
    fi

    # Criar diretório de backup se não existir
    mkdir -p "$BACKUP_DIR"

    # Nome do arquivo de backup
    BACKUP_FILE="${BACKUP_DIR}/jenkins_home_${DATE}.tar.gz"

    print_info "Criando backup: $BACKUP_FILE"

    # Criar backup (excluindo cache e workspace para economizar espaço)
    tar -czf "$BACKUP_FILE" \
        --exclude='workspace/*' \
        --exclude='caches/*' \
        --exclude='.cache/*' \
        --exclude='war/*' \
        -C "$JENKINS_HOME" .

    BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
    print_info "Backup criado com sucesso! Tamanho: $BACKUP_SIZE"

    # Limpar backups antigos (manter apenas os últimos 7)
    print_info "Limpando backups antigos..."
    ls -t ${BACKUP_DIR}/jenkins_home_*.tar.gz | tail -n +8 | xargs -r rm -f

    print_info "Backups disponíveis:"
    ls -lh ${BACKUP_DIR}/jenkins_home_*.tar.gz 2>/dev/null || echo "  Nenhum backup encontrado"
}

# Função de restauração
restore() {
    if [ -z "$1" ]; then
        print_error "Especifique o arquivo de backup!"
        echo "Uso: $0 restore <arquivo_backup>"
        echo ""
        print_info "Backups disponíveis:"
        ls -lh ${BACKUP_DIR}/jenkins_home_*.tar.gz 2>/dev/null || echo "  Nenhum backup encontrado"
        exit 1
    fi

    RESTORE_FILE="$1"

    if [ ! -f "$RESTORE_FILE" ]; then
        print_error "Arquivo de backup não encontrado: $RESTORE_FILE"
        exit 1
    fi

    print_warning "ATENÇÃO: Esta operação irá substituir todos os dados do Jenkins!"
    print_warning "Jenkins home atual será movido para: ${JENKINS_HOME}.backup_${DATE}"
    read -p "Deseja continuar? (yes/no): " -r
    echo
    if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
        print_info "Operação cancelada"
        exit 0
    fi

    # Parar Jenkins se estiver rodando
    print_info "Parando Jenkins..."
    docker-compose -f docker-compose.jenkins.yml stop jenkins 2>/dev/null || true

    # Fazer backup do estado atual
    if [ -d "$JENKINS_HOME" ]; then
        print_info "Movendo jenkins_home atual para backup..."
        mv "$JENKINS_HOME" "${JENKINS_HOME}.backup_${DATE}"
    fi

    # Criar diretório limpo
    mkdir -p "$JENKINS_HOME"

    # Restaurar backup
    print_info "Restaurando backup: $RESTORE_FILE"
    tar -xzf "$RESTORE_FILE" -C "$JENKINS_HOME"

    # Ajustar permissões
    print_info "Ajustando permissões..."
    chown -R 1000:1000 "$JENKINS_HOME"

    print_info "Restauração concluída!"
    print_info "Inicie o Jenkins com: docker-compose -f docker-compose.jenkins.yml up -d"
}

# Função de listagem
list_backups() {
    print_info "Backups disponíveis:"
    if ls ${BACKUP_DIR}/jenkins_home_*.tar.gz 1> /dev/null 2>&1; then
        ls -lh ${BACKUP_DIR}/jenkins_home_*.tar.gz | awk '{print "  " $9 " (" $5 ")"}'
    else
        echo "  Nenhum backup encontrado"
    fi
}

# Menu principal
case "${1:-}" in
    backup)
        backup
        ;;
    restore)
        restore "$2"
        ;;
    list)
        list_backups
        ;;
    *)
        echo "Uso: $0 {backup|restore|list}"
        echo ""
        echo "Comandos:"
        echo "  backup          - Cria um novo backup"
        echo "  restore <file>  - Restaura um backup específico"
        echo "  list            - Lista backups disponíveis"
        exit 1
        ;;
esac
