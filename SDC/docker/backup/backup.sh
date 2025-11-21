#!/bin/bash

# Configurações
BACKUP_DIR="/backup/data"
MYSQL_HOST="${MYSQL_HOST:-db}"
MYSQL_DATABASE="${MYSQL_DATABASE:-sdc_db}"
MYSQL_USER="${MYSQL_USER:-root}"
MYSQL_PASSWORD="${MYSQL_PASSWORD:-}"
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-7}"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="${BACKUP_DIR}/${MYSQL_DATABASE}_${TIMESTAMP}.sql.gz"

# Criar diretório de backup se não existir
mkdir -p "${BACKUP_DIR}"

# Log
echo "[$(date +'%Y-%m-%d %H:%M:%S')] Iniciando backup do banco de dados ${MYSQL_DATABASE}..."

# Executar backup
mysqldump -h "${MYSQL_HOST}" \
          -u "${MYSQL_USER}" \
          -p"${MYSQL_PASSWORD}" \
          --single-transaction \
          --routines \
          --triggers \
          --ssl-mode=DISABLED \
          "${MYSQL_DATABASE}" | gzip > "${BACKUP_FILE}"

# Verificar se o backup foi criado com sucesso
if [ $? -eq 0 ] && [ -f "${BACKUP_FILE}" ]; then
    BACKUP_SIZE=$(du -h "${BACKUP_FILE}" | cut -f1)
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] Backup criado com sucesso: ${BACKUP_FILE} (${BACKUP_SIZE})"
    
    # Remover backups antigos
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] Removendo backups mais antigos que ${RETENTION_DAYS} dias..."
    find "${BACKUP_DIR}" -name "${MYSQL_DATABASE}_*.sql.gz" -type f -mtime +${RETENTION_DAYS} -delete
    
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] Backup concluído com sucesso!"
    exit 0
else
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] ERRO: Falha ao criar backup!"
    exit 1
fi

