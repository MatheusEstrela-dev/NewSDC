#!/bin/bash
#
# =============================================================================
# SDC - Backup Automático de Banco de Dados MySQL
# Sistema Crítico 24/7 - Backup com Verificação e Retenção GFS
# =============================================================================
#
# Uso: ./backup-database.sh [manual|auto]
#

set -euo pipefail

# =============================================================================
# CONFIGURAÇÕES
# =============================================================================

BACKUP_DIR="${BACKUP_DIR:-/backups/database}"
BACKUP_PREFIX="sdc-db"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
DATE_ONLY=$(date +"%Y%m%d")

# MySQL
DB_HOST="${DB_HOST:-db-primary}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_DATABASE:-sdc}"
DB_USER="${DB_USERNAME:-sdc_user}"
DB_PASS="${DB_PASSWORD}"

# Retenção GFS (Grandfather-Father-Son)
DAILY_RETENTION=7      # 7 dias
WEEKLY_RETENTION=4     # 4 semanas
MONTHLY_RETENTION=12   # 12 meses

# Verificação
VERIFY_BACKUP=true
COMPRESS_BACKUP=true

# Notificações
NOTIFY_SLACK="${NOTIFY_SLACK:-false}"
SLACK_WEBHOOK="${SLACK_WEBHOOK_URL:-}"

# =============================================================================
# FUNÇÕES AUXILIARES
# =============================================================================

log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

error() {
    log "❌ ERROR: $1" >&2
}

success() {
    log "✅ $1"
}

notify_slack() {
    local message="$1"
    local status="${2:-info}"

    if [ "$NOTIFY_SLACK" = "true" ] && [ -n "$SLACK_WEBHOOK" ]; then
        local emoji
        case "$status" in
            success) emoji=":white_check_mark:" ;;
            error) emoji=":x:" ;;
            warning) emoji=":warning:" ;;
            *) emoji=":information_source:" ;;
        esac

        curl -X POST "$SLACK_WEBHOOK" \
            -H 'Content-Type: application/json' \
            -d "{\"text\":\"${emoji} SDC Database Backup: ${message}\"}" \
            --silent --show-error || true
    fi
}

# =============================================================================
# CRIAÇÃO DO BACKUP
# =============================================================================

create_backup() {
    local backup_type="${1:-auto}"
    local backup_file="${BACKUP_DIR}/${BACKUP_PREFIX}-${backup_type}-${TIMESTAMP}.sql"
    local compressed_file="${backup_file}.gz"

    log "🔄 Iniciando backup ${backup_type}..."
    log "📊 Database: ${DB_NAME}@${DB_HOST}:${DB_PORT}"

    # Criar diretório se não existe
    mkdir -p "$BACKUP_DIR"

    # Verificar conexão MySQL
    if ! mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" --silent; then
        error "Não foi possível conectar ao MySQL"
        notify_slack "Failed to connect to MySQL" "error"
        return 1
    fi

    # Criar backup com mysqldump
    log "💾 Executando mysqldump..."

    if mysqldump \
        --host="$DB_HOST" \
        --port="$DB_PORT" \
        --user="$DB_USER" \
        --password="$DB_PASS" \
        --databases "$DB_NAME" \
        --single-transaction \
        --quick \
        --lock-tables=false \
        --routines \
        --triggers \
        --events \
        --add-drop-database \
        --result-file="$backup_file" \
        2>/tmp/backup-error.log; then

        success "Backup SQL criado: $(basename $backup_file)"
    else
        error "Falha no mysqldump: $(cat /tmp/backup-error.log)"
        notify_slack "mysqldump failed" "error"
        return 1
    fi

    # Comprimir backup
    if [ "$COMPRESS_BACKUP" = "true" ]; then
        log "🗜️  Comprimindo backup..."

        if gzip -9 "$backup_file"; then
            success "Backup comprimido: $(basename $compressed_file)"
            backup_file="$compressed_file"
        else
            error "Falha na compressão"
            notify_slack "Compression failed" "warning"
        fi
    fi

    # Gerar checksum SHA256
    log "🔐 Gerando checksum..."
    sha256sum "$backup_file" > "${backup_file}.sha256"

    local checksum=$(awk '{print $1}' "${backup_file}.sha256")
    success "SHA256: ${checksum:0:16}..."

    # Verificar backup
    if [ "$VERIFY_BACKUP" = "true" ]; then
        verify_backup "$backup_file"
    fi

    # Informações do backup
    local size=$(du -h "$backup_file" | awk '{print $1}')
    success "Backup completo: $(basename $backup_file) ($size)"

    # Salvar metadados
    cat > "${backup_file}.meta" <<EOF
{
  "timestamp": "$(date -Iseconds)",
  "database": "$DB_NAME",
  "host": "$DB_HOST",
  "size": "$(stat -c%s "$backup_file")",
  "compressed": $COMPRESS_BACKUP,
  "checksum": "$checksum",
  "type": "$backup_type"
}
EOF

    # Criar symlink para latest
    ln -sf "$(basename $backup_file)" "${BACKUP_DIR}/${BACKUP_PREFIX}-latest.sql.gz"
    ln -sf "$(basename ${backup_file}.sha256)" "${BACKUP_DIR}/${BACKUP_PREFIX}-latest.sql.gz.sha256"

    notify_slack "Backup successful: $(basename $backup_file) ($size)" "success"

    echo "$backup_file"
}

# =============================================================================
# VERIFICAÇÃO DO BACKUP
# =============================================================================

verify_backup() {
    local backup_file="$1"

    log "✅ Verificando integridade do backup..."

    # Verificar checksum
    if ! sha256sum -c "${backup_file}.sha256" --status; then
        error "Checksum INVÁLIDO! Backup corrompido!"
        notify_slack "Backup verification FAILED - corrupt file!" "error"
        return 1
    fi

    # Verificar se é arquivo gzip válido
    if [[ "$backup_file" == *.gz ]]; then
        if ! gzip -t "$backup_file" 2>/dev/null; then
            error "Arquivo GZIP corrompido!"
            notify_slack "Backup verification FAILED - corrupt gzip!" "error"
            return 1
        fi
    fi

    # Verificar se contém dados SQL
    if [[ "$backup_file" == *.gz ]]; then
        local sql_check=$(zcat "$backup_file" | head -n 20 | grep -c "CREATE DATABASE\|CREATE TABLE" || true)
    else
        local sql_check=$(head -n 20 "$backup_file" | grep -c "CREATE DATABASE\|CREATE TABLE" || true)
    fi

    if [ "$sql_check" -lt 1 ]; then
        error "Backup não contém estrutura SQL válida!"
        notify_slack "Backup verification FAILED - invalid SQL!" "error"
        return 1
    fi

    success "Backup verificado com sucesso!"
    return 0
}

# =============================================================================
# RETENÇÃO GFS (Grandfather-Father-Son)
# =============================================================================

apply_retention_policy() {
    log "🗑️  Aplicando política de retenção GFS..."

    # Daily: Manter últimos 7 dias
    log "📅 Limpando backups diários (> ${DAILY_RETENTION} dias)..."
    find "$BACKUP_DIR" -name "${BACKUP_PREFIX}-auto-*.sql.gz" -type f -mtime +${DAILY_RETENTION} -delete
    find "$BACKUP_DIR" -name "${BACKUP_PREFIX}-auto-*.sha256" -type f -mtime +${DAILY_RETENTION} -delete
    find "$BACKUP_DIR" -name "${BACKUP_PREFIX}-auto-*.meta" -type f -mtime +${DAILY_RETENTION} -delete

    # Weekly: Converter backup de domingo em weekly
    if [ "$(date +%u)" -eq 7 ]; then
        log "📆 Criando backup semanal..."
        local latest_backup=$(ls -t "$BACKUP_DIR"/${BACKUP_PREFIX}-auto-*.sql.gz 2>/dev/null | head -n1)

        if [ -n "$latest_backup" ]; then
            local weekly_file="${BACKUP_DIR}/${BACKUP_PREFIX}-weekly-${TIMESTAMP}.sql.gz"
            cp "$latest_backup" "$weekly_file"
            cp "${latest_backup}.sha256" "${weekly_file}.sha256"
            cp "${latest_backup}.meta" "${weekly_file}.meta"
            success "Backup semanal criado: $(basename $weekly_file)"
        fi
    fi

    # Limpar backups semanais antigos (> 4 semanas)
    find "$BACKUP_DIR" -name "${BACKUP_PREFIX}-weekly-*.sql.gz" -type f -mtime +$((WEEKLY_RETENTION * 7)) -delete
    find "$BACKUP_DIR" -name "${BACKUP_PREFIX}-weekly-*.sha256" -type f -mtime +$((WEEKLY_RETENTION * 7)) -delete
    find "$BACKUP_DIR" -name "${BACKUP_PREFIX}-weekly-*.meta" -type f -mtime +$((WEEKLY_RETENTION * 7)) -delete

    # Monthly: Converter primeiro backup do mês em monthly
    if [ "$(date +%d)" -eq 01 ]; then
        log "📅 Criando backup mensal..."
        local latest_backup=$(ls -t "$BACKUP_DIR"/${BACKUP_PREFIX}-auto-*.sql.gz 2>/dev/null | head -n1)

        if [ -n "$latest_backup" ]; then
            local monthly_file="${BACKUP_DIR}/${BACKUP_PREFIX}-monthly-${TIMESTAMP}.sql.gz"
            cp "$latest_backup" "$monthly_file"
            cp "${latest_backup}.sha256" "${monthly_file}.sha256"
            cp "${latest_backup}.meta" "${monthly_file}.meta"
            success "Backup mensal criado: $(basename $monthly_file)"
        fi
    fi

    # Limpar backups mensais antigos (> 12 meses)
    find "$BACKUP_DIR" -name "${BACKUP_PREFIX}-monthly-*.sql.gz" -type f -mtime +$((MONTHLY_RETENTION * 30)) -delete
    find "$BACKUP_DIR" -name "${BACKUP_PREFIX}-monthly-*.sha256" -type f -mtime +$((MONTHLY_RETENTION * 30)) -delete
    find "$BACKUP_DIR" -name "${BACKUP_PREFIX}-monthly-*.meta" -type f -mtime +$((MONTHLY_RETENTION * 30)) -delete

    # Estatísticas
    local daily_count=$(ls -1 "$BACKUP_DIR"/${BACKUP_PREFIX}-auto-*.sql.gz 2>/dev/null | wc -l)
    local weekly_count=$(ls -1 "$BACKUP_DIR"/${BACKUP_PREFIX}-weekly-*.sql.gz 2>/dev/null | wc -l)
    local monthly_count=$(ls -1 "$BACKUP_DIR"/${BACKUP_PREFIX}-monthly-*.sql.gz 2>/dev/null | wc -l)
    local total_size=$(du -sh "$BACKUP_DIR" | awk '{print $1}')

    success "Retenção aplicada: ${daily_count} diários, ${weekly_count} semanais, ${monthly_count} mensais (${total_size})"
}

# =============================================================================
# MAIN
# =============================================================================

main() {
    local backup_type="${1:-auto}"

    log "🚀 Iniciando backup de banco de dados..."
    log "📂 Diretório: $BACKUP_DIR"
    log "🔧 Tipo: $backup_type"

    # Criar backup
    if backup_file=$(create_backup "$backup_type"); then
        success "Backup criado com sucesso!"

        # Aplicar política de retenção (apenas em backups automáticos)
        if [ "$backup_type" = "auto" ]; then
            apply_retention_policy
        fi

        log "✅ Processo de backup concluído com sucesso!"
        exit 0
    else
        error "Falha no processo de backup!"
        exit 1
    fi
}

# Executar
main "$@"
