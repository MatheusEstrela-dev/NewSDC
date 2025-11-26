#!/bin/bash
# ============================================================================
# SDC - Backup Script para Produção
# Sistema Crítico 24/7 - Backup automatizado com retenção
# ============================================================================

set -e

# Configurações
BACKUP_DIR="/backup/data"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_RETENTION_DAYS=${BACKUP_RETENTION_DAYS:-30}
LOG_FILE="/backup/backup.log"

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a "$LOG_FILE"
}

log_success() {
    echo -e "${GREEN}[OK]${NC} $1" | tee -a "$LOG_FILE"
}

# Criar diretórios
mkdir -p "$BACKUP_DIR/mysql"
mkdir -p "$BACKUP_DIR/redis"
mkdir -p "$BACKUP_DIR/files"

log "=========================================="
log "Iniciando backup - $DATE"
log "=========================================="

# ==========================================
# Backup do MySQL
# ==========================================
log "Iniciando backup do MySQL..."

MYSQL_BACKUP_FILE="$BACKUP_DIR/mysql/sdc_mysql_${DATE}.sql.gz"

if mysqldump \
    -h "$MYSQL_HOST" \
    -u "$MYSQL_USER" \
    -p"$MYSQL_PASSWORD" \
    --single-transaction \
    --quick \
    --lock-tables=false \
    --routines \
    --triggers \
    --events \
    "$MYSQL_DATABASE" | gzip > "$MYSQL_BACKUP_FILE"; then
    
    MYSQL_SIZE=$(du -h "$MYSQL_BACKUP_FILE" | cut -f1)
    log_success "MySQL backup concluído: $MYSQL_SIZE"
else
    log_error "Falha no backup do MySQL"
fi

# ==========================================
# Backup do Redis
# ==========================================
log "Iniciando backup do Redis..."

REDIS_BACKUP_FILE="$BACKUP_DIR/redis/sdc_redis_${DATE}.rdb"

if redis-cli -h redis-master -a "$REDIS_PASSWORD" --no-auth-warning BGSAVE; then
    sleep 5  # Aguardar BGSAVE completar
    if cp /data/dump.rdb "$REDIS_BACKUP_FILE" 2>/dev/null; then
        gzip "$REDIS_BACKUP_FILE"
        log_success "Redis backup concluído"
    fi
else
    log_error "Falha no backup do Redis"
fi

# ==========================================
# Backup de arquivos (storage)
# ==========================================
log "Iniciando backup de arquivos..."

FILES_BACKUP_FILE="$BACKUP_DIR/files/sdc_files_${DATE}.tar.gz"

if tar -czf "$FILES_BACKUP_FILE" \
    -C /var/www/storage \
    --exclude='logs' \
    --exclude='framework/cache' \
    --exclude='framework/sessions' \
    --exclude='framework/views' \
    . 2>/dev/null; then
    
    FILES_SIZE=$(du -h "$FILES_BACKUP_FILE" | cut -f1)
    log_success "Files backup concluído: $FILES_SIZE"
else
    log_error "Falha no backup de arquivos"
fi

# ==========================================
# Upload para S3 (se configurado)
# ==========================================
if [ -n "$S3_BUCKET" ] && [ -n "$AWS_ACCESS_KEY_ID" ]; then
    log "Enviando para S3..."
    
    aws s3 cp "$MYSQL_BACKUP_FILE" "s3://$S3_BUCKET/mysql/" --storage-class STANDARD_IA
    aws s3 cp "${REDIS_BACKUP_FILE}.gz" "s3://$S3_BUCKET/redis/" --storage-class STANDARD_IA 2>/dev/null || true
    aws s3 cp "$FILES_BACKUP_FILE" "s3://$S3_BUCKET/files/" --storage-class STANDARD_IA
    
    log_success "Upload para S3 concluído"
fi

# ==========================================
# Limpeza de backups antigos (local)
# ==========================================
log "Limpando backups antigos (>${BACKUP_RETENTION_DAYS} dias)..."

find "$BACKUP_DIR" -type f -mtime +$BACKUP_RETENTION_DAYS -delete
DELETED=$(find "$BACKUP_DIR" -type f -mtime +$BACKUP_RETENTION_DAYS | wc -l)

log_success "Removidos $DELETED arquivos antigos"

# ==========================================
# Limpeza de backups antigos (S3)
# ==========================================
if [ -n "$S3_BUCKET" ] && [ -n "$AWS_ACCESS_KEY_ID" ]; then
    log "Limpando backups antigos no S3..."
    
    # Usa lifecycle policy no S3 (recomendado)
    # Ou: aws s3 ls "s3://$S3_BUCKET" --recursive | ...
fi

# ==========================================
# Verificação de integridade
# ==========================================
log "Verificando integridade dos backups..."

if gzip -t "$MYSQL_BACKUP_FILE" 2>/dev/null; then
    log_success "MySQL backup íntegro"
else
    log_error "MySQL backup corrompido!"
fi

if [ -f "${REDIS_BACKUP_FILE}.gz" ] && gzip -t "${REDIS_BACKUP_FILE}.gz" 2>/dev/null; then
    log_success "Redis backup íntegro"
fi

if gzip -t "$FILES_BACKUP_FILE" 2>/dev/null; then
    log_success "Files backup íntegro"
fi

# ==========================================
# Resumo
# ==========================================
TOTAL_SIZE=$(du -sh "$BACKUP_DIR" | cut -f1)
BACKUP_COUNT=$(find "$BACKUP_DIR" -type f | wc -l)

log "=========================================="
log "Backup concluído!"
log "Total de arquivos: $BACKUP_COUNT"
log "Tamanho total: $TOTAL_SIZE"
log "=========================================="

exit 0

