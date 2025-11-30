#!/bin/bash
# ============================================================================
# BACKUP REMOTO - Sincronização para S3/Rsync/NFS
# ============================================================================

set -euo pipefail

# ===== CONFIGURAÇÕES =====
BACKUP_DIR="${BACKUP_DIR:-/backups}"
REMOTE_DIR="${REMOTE_DIR:-/remote}"
BACKUP_TYPE="${BACKUP_REMOTE_TYPE:-s3}"
LOG_FILE="${LOG_FILE:-/var/log/backup/remote.log}"

# S3 Config
S3_BUCKET="${S3_BUCKET:-}"
S3_REGION="${S3_REGION:-us-east-1}"
S3_STORAGE_CLASS="${S3_STORAGE_CLASS:-STANDARD_IA}"  # STANDARD, STANDARD_IA, GLACIER

# Rsync Config
RSYNC_HOST="${RSYNC_HOST:-}"
RSYNC_USER="${RSYNC_USER:-backup}"
RSYNC_PATH="${RSYNC_PATH:-/backups/jenkins}"

# ===== FUNÇÕES =====
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"
}

error() {
    log "ERROR: $*"
    exit 1
}

# ===== SYNC S3 =====
sync_s3() {
    log "Sincronizando backups para S3..."

    [ -n "$S3_BUCKET" ] || error "S3_BUCKET não configurado"

    # Upload de novos backups
    aws s3 sync "$BACKUP_DIR" "s3://$S3_BUCKET/jenkins/" \
        --region "$S3_REGION" \
        --storage-class "$S3_STORAGE_CLASS" \
        --exclude "*" \
        --include "*.tar.gz" \
        --include "*.sha256" \
        --no-progress \
        || error "Falha ao sincronizar com S3"

    # Lifecycle para arquivos antigos (mover para Glacier após 30 dias)
    aws s3api put-bucket-lifecycle-configuration \
        --bucket "$S3_BUCKET" \
        --lifecycle-configuration file:///scripts/s3-lifecycle.json \
        || log "WARN: Falha ao configurar lifecycle"

    log "Sincronização S3 concluída!"
    echo "$(date +%s)" > /remote/last_sync_success
}

# ===== SYNC RSYNC =====
sync_rsync() {
    log "Sincronizando backups via Rsync..."

    [ -n "$RSYNC_HOST" ] || error "RSYNC_HOST não configurado"

    rsync -avz --delete \
        --include="*.tar.gz" \
        --include="*.sha256" \
        --exclude="*" \
        "$BACKUP_DIR/" \
        "${RSYNC_USER}@${RSYNC_HOST}:${RSYNC_PATH}/" \
        || error "Falha ao sincronizar via Rsync"

    log "Sincronização Rsync concluída!"
    echo "$(date +%s)" > /remote/last_sync_success
}

# ===== SYNC NFS =====
sync_nfs() {
    log "Sincronizando backups para NFS..."

    # Copiar arquivos para NFS mount
    rsync -av --delete \
        "$BACKUP_DIR/" \
        "$REMOTE_DIR/" \
        || error "Falha ao sincronizar com NFS"

    log "Sincronização NFS concluída!"
    echo "$(date +%s)" > /remote/last_sync_success
}

# ===== MAIN =====
log "Iniciando backup remoto (tipo: $BACKUP_TYPE)..."

mkdir -p "$REMOTE_DIR"
mkdir -p "$(dirname "$LOG_FILE")"

case "$BACKUP_TYPE" in
    s3)
        sync_s3
        ;;
    rsync)
        sync_rsync
        ;;
    nfs)
        sync_nfs
        ;;
    *)
        error "Tipo de backup remoto inválido: $BACKUP_TYPE"
        ;;
esac

log "Backup remoto concluído!"
exit 0
