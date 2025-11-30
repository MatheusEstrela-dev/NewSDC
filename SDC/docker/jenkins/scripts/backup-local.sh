#!/bin/bash
# ============================================================================
# BACKUP LOCAL - Jenkins Home
# Backup inteligente com verificação de integridade
# ============================================================================

set -euo pipefail

# ===== CONFIGURAÇÕES =====
SOURCE_DIR="${SOURCE_DIR:-/source}"
BACKUP_DIR="${BACKUP_DIR:-/backups}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_PREFIX="jenkins"
BACKUP_FILE="${BACKUP_DIR}/${BACKUP_PREFIX}-${TIMESTAMP}.tar.gz"
LOG_FILE="${LOG_FILE:-/var/log/backup/backup.log}"

# Retenção (dias)
RETENTION_DAILY="${BACKUP_RETENTION_DAILY:-7}"
RETENTION_WEEKLY="${BACKUP_RETENTION_WEEKLY:-4}"
RETENTION_MONTHLY="${BACKUP_RETENTION_MONTHLY:-12}"

# Notificações
SLACK_WEBHOOK="${SLACK_WEBHOOK_URL:-}"
PROMETHEUS_GATEWAY="${PROMETHEUS_PUSHGATEWAY:-}"

# ===== FUNÇÕES =====
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"
}

error() {
    log "ERROR: $*"
    notify_failure "$*"
    exit 1
}

notify_success() {
    local backup_size=$1
    local duration=$2

    log "Backup concluído! Tamanho: $backup_size, Duração: ${duration}s"

    # Slack notification
    if [ -n "$SLACK_WEBHOOK" ]; then
        curl -X POST "$SLACK_WEBHOOK" \
            -H 'Content-Type: application/json' \
            -d "{
                \"text\": \"✅ Backup Jenkins concluído\",
                \"attachments\": [{
                    \"color\": \"good\",
                    \"fields\": [
                        {\"title\": \"Arquivo\", \"value\": \"$(basename "$BACKUP_FILE")\", \"short\": true},
                        {\"title\": \"Tamanho\", \"value\": \"$backup_size\", \"short\": true},
                        {\"title\": \"Duração\", \"value\": \"${duration}s\", \"short\": true}
                    ]
                }]
            }" &>/dev/null || true
    fi

    # Prometheus metrics
    if [ -n "$PROMETHEUS_GATEWAY" ]; then
        cat <<EOF | curl --data-binary @- "$PROMETHEUS_GATEWAY/metrics/job/jenkins_backup" || true
# HELP jenkins_backup_success Backup success status
# TYPE jenkins_backup_success gauge
jenkins_backup_success 1
# HELP jenkins_backup_duration_seconds Backup duration
# TYPE jenkins_backup_duration_seconds gauge
jenkins_backup_duration_seconds $duration
# HELP jenkins_backup_size_bytes Backup file size
# TYPE jenkins_backup_size_bytes gauge
jenkins_backup_size_bytes $(stat -c%s "$BACKUP_FILE")
# HELP jenkins_backup_timestamp Last backup timestamp
# TYPE jenkins_backup_timestamp gauge
jenkins_backup_timestamp $(date +%s)
EOF
    fi
}

notify_failure() {
    local error_msg=$1

    log "Backup FALHOU: $error_msg"

    if [ -n "$SLACK_WEBHOOK" ]; then
        curl -X POST "$SLACK_WEBHOOK" \
            -H 'Content-Type: application/json' \
            -d "{
                \"text\": \"❌ Backup Jenkins FALHOU\",
                \"attachments\": [{
                    \"color\": \"danger\",
                    \"fields\": [{\"title\": \"Erro\", \"value\": \"$error_msg\"}]
                }]
            }" &>/dev/null || true
    fi

    if [ -n "$PROMETHEUS_GATEWAY" ]; then
        cat <<EOF | curl --data-binary @- "$PROMETHEUS_GATEWAY/metrics/job/jenkins_backup" || true
jenkins_backup_success 0
jenkins_backup_timestamp $(date +%s)
EOF
    fi
}

# ===== PRÉ-VALIDAÇÕES =====
log "Iniciando backup do Jenkins..."

# Verificar se source existe
[ -d "$SOURCE_DIR" ] || error "Diretório source não encontrado: $SOURCE_DIR"

# Criar diretório de backup
mkdir -p "$BACKUP_DIR"
mkdir -p "$(dirname "$LOG_FILE")"

# Verificar espaço em disco (mínimo 10GB)
AVAILABLE_SPACE=$(df "$BACKUP_DIR" | tail -1 | awk '{print $4}')
MIN_SPACE=$((10 * 1024 * 1024))  # 10GB em KB

if [ "$AVAILABLE_SPACE" -lt "$MIN_SPACE" ]; then
    error "Espaço insuficiente! Disponível: ${AVAILABLE_SPACE}KB, Mínimo: ${MIN_SPACE}KB"
fi

# ===== BACKUP =====
START_TIME=$(date +%s)

log "Criando backup: $BACKUP_FILE"
log "Fonte: $SOURCE_DIR"

# Criar backup com compressão paralela (pigz)
# Excluir: workspace, caches, war, tmp
tar -C "$SOURCE_DIR" \
    --exclude='workspace/*' \
    --exclude='caches/*' \
    --exclude='.cache/*' \
    --exclude='war/*' \
    --exclude='tmp/*' \
    --exclude='*.log' \
    -cf - . | pigz -9 -p 2 > "$BACKUP_FILE" \
    || error "Falha ao criar backup"

END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

# ===== VERIFICAÇÃO DE INTEGRIDADE =====
log "Verificando integridade do backup..."

# Teste 1: Verificar se arquivo existe e não está vazio
[ -f "$BACKUP_FILE" ] || error "Arquivo de backup não foi criado"
[ -s "$BACKUP_FILE" ] || error "Arquivo de backup está vazio"

# Teste 2: Verificar se tar.gz é válido
if ! pigz -t "$BACKUP_FILE" &>/dev/null; then
    error "Arquivo de backup está corrompido (falha no pigz -t)"
fi

# Teste 3: Listar conteúdo sem extrair
if ! tar -tzf "$BACKUP_FILE" &>/dev/null; then
    error "Arquivo de backup está corrompido (falha no tar -t)"
fi

# Teste 4: Verificar arquivos críticos
CRITICAL_FILES=(
    "config.xml"
    "jobs"
    "users"
)

for file in "${CRITICAL_FILES[@]}"; do
    if ! tar -tzf "$BACKUP_FILE" | grep -q "^$file"; then
        error "Arquivo crítico ausente no backup: $file"
    fi
done

# Teste 5: Gerar checksum
CHECKSUM=$(sha256sum "$BACKUP_FILE" | awk '{print $1}')
echo "$CHECKSUM  $(basename "$BACKUP_FILE")" > "${BACKUP_FILE}.sha256"

log "Checksum SHA256: $CHECKSUM"

# ===== INFORMAÇÕES DO BACKUP =====
BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
UNCOMPRESSED_SIZE=$(tar -tzf "$BACKUP_FILE" | xargs -I{} stat -c%s "$SOURCE_DIR/{}" 2>/dev/null | awk '{s+=$1} END {print s}')
COMPRESSION_RATIO=$(echo "scale=2; $(stat -c%s "$BACKUP_FILE") * 100 / $UNCOMPRESSED_SIZE" | bc 2>/dev/null || echo "N/A")

log "Tamanho comprimido: $BACKUP_SIZE"
log "Taxa de compressão: ${COMPRESSION_RATIO}%"
log "Duração: ${DURATION}s"

# ===== ROTAÇÃO DE BACKUPS (GFS - Grandfather-Father-Son) =====
log "Aplicando rotação de backups (GFS)..."

# Daily backups (últimos 7 dias)
find "$BACKUP_DIR" -name "${BACKUP_PREFIX}-*.tar.gz" -type f -mtime +${RETENTION_DAILY} \
    -not -name "*-weekly-*" -not -name "*-monthly-*" \
    -exec rm -f {} \; \
    -exec rm -f {}.sha256 \;

# Weekly backups (primeiras 4 semanas)
# Se hoje é domingo, marcar como weekly
if [ "$(date +%u)" -eq 7 ]; then
    WEEKLY_FILE="${BACKUP_DIR}/${BACKUP_PREFIX}-weekly-${TIMESTAMP}.tar.gz"
    cp "$BACKUP_FILE" "$WEEKLY_FILE"
    cp "${BACKUP_FILE}.sha256" "${WEEKLY_FILE}.sha256"
    log "Backup semanal criado: $(basename "$WEEKLY_FILE")"

    # Remover weekly backups antigos (>4 semanas)
    find "$BACKUP_DIR" -name "${BACKUP_PREFIX}-weekly-*.tar.gz" -type f -mtime +$((RETENTION_WEEKLY * 7)) \
        -exec rm -f {} \; \
        -exec rm -f {}.sha256 \;
fi

# Monthly backups (primeiros 12 meses)
# Se hoje é dia 1, marcar como monthly
if [ "$(date +%d)" -eq 1 ]; then
    MONTHLY_FILE="${BACKUP_DIR}/${BACKUP_PREFIX}-monthly-${TIMESTAMP}.tar.gz"
    cp "$BACKUP_FILE" "$MONTHLY_FILE"
    cp "${BACKUP_FILE}.sha256" "${MONTHLY_FILE}.sha256"
    log "Backup mensal criado: $(basename "$MONTHLY_FILE")"

    # Remover monthly backups antigos (>12 meses)
    find "$BACKUP_DIR" -name "${BACKUP_PREFIX}-monthly-*.tar.gz" -type f -mtime +$((RETENTION_MONTHLY * 30)) \
        -exec rm -f {} \; \
        -exec rm -f {}.sha256 \;
fi

# ===== ESTATÍSTICAS =====
TOTAL_BACKUPS=$(find "$BACKUP_DIR" -name "${BACKUP_PREFIX}-*.tar.gz" | wc -l)
TOTAL_SIZE=$(du -sh "$BACKUP_DIR" | cut -f1)

log "Backups totais: $TOTAL_BACKUPS"
log "Espaço total usado: $TOTAL_SIZE"

# ===== NOTIFICAÇÃO DE SUCESSO =====
notify_success "$BACKUP_SIZE" "$DURATION"

log "Backup concluído com sucesso!"
exit 0
