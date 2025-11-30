#!/bin/bash
# ============================================================================
# RESTORE - Restauração de Backup Jenkins
# ============================================================================

set -euo pipefail

# ===== CONFIGURAÇÕES =====
BACKUP_DIR="${BACKUP_DIR:-/backups}"
RESTORE_DIR="${RESTORE_DIR:-/var/jenkins_home}"
LOG_FILE="${LOG_FILE:-/var/log/backup/restore.log}"

# ===== FUNÇÕES =====
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"
}

error() {
    log "ERROR: $*"
    exit 1
}

list_backups() {
    log "Backups disponíveis:"
    find "$BACKUP_DIR" -name "jenkins-*.tar.gz" -type f | sort -r | while read -r backup; do
        SIZE=$(du -h "$backup" | cut -f1)
        DATE=$(stat -c %y "$backup" | cut -d' ' -f1,2)
        echo "  $(basename "$backup") - $SIZE - $DATE"
    done
}

verify_backup() {
    local backup_file=$1

    log "Verificando integridade do backup..."

    # Verificar checksum se existir
    if [ -f "${backup_file}.sha256" ]; then
        log "Verificando SHA256..."
        sha256sum -c "${backup_file}.sha256" || error "Checksum inválido!"
    else
        log "WARN: Arquivo .sha256 não encontrado, pulando verificação"
    fi

    # Testar integridade do arquivo
    pigz -t "$backup_file" || error "Arquivo corrompido (pigz -t failed)"
    tar -tzf "$backup_file" &>/dev/null || error "Arquivo corrompido (tar -t failed)"

    log "Backup verificado com sucesso!"
}

restore_backup() {
    local backup_file=$1

    log "Restaurando backup: $backup_file"

    # Verificar integridade primeiro
    verify_backup "$backup_file"

    # Criar backup do estado atual
    if [ -d "$RESTORE_DIR" ]; then
        BACKUP_CURRENT="${RESTORE_DIR}.backup-$(date +%Y%m%d_%H%M%S)"
        log "Fazendo backup do estado atual em: $BACKUP_CURRENT"
        mv "$RESTORE_DIR" "$BACKUP_CURRENT"
    fi

    # Criar diretório limpo
    mkdir -p "$RESTORE_DIR"

    # Extrair backup
    log "Extraindo backup..."
    tar -xzf "$backup_file" -C "$RESTORE_DIR" || {
        log "ERRO na extração! Restaurando backup anterior..."
        rm -rf "$RESTORE_DIR"
        [ -d "$BACKUP_CURRENT" ] && mv "$BACKUP_CURRENT" "$RESTORE_DIR"
        error "Falha ao restaurar backup"
    }

    # Ajustar permissões
    log "Ajustando permissões..."
    chown -R 1000:1000 "$RESTORE_DIR"

    log "Restore concluído com sucesso!"
    log "Para iniciar Jenkins: docker-compose up -d jenkins"
}

# ===== MAIN =====
mkdir -p "$(dirname "$LOG_FILE")"

if [ "$#" -eq 0 ]; then
    log "Uso: $0 <backup-file>"
    list_backups
    exit 1
fi

BACKUP_FILE="$1"

[ -f "$BACKUP_FILE" ] || error "Backup não encontrado: $BACKUP_FILE"

log "⚠️  ATENÇÃO: Esta operação irá SUBSTITUIR todos os dados do Jenkins!"
log "Backup: $BACKUP_FILE"
log "Destino: $RESTORE_DIR"
read -p "Deseja continuar? (yes/no): " -r
if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
    log "Operação cancelada pelo usuário"
    exit 0
fi

restore_backup "$BACKUP_FILE"
