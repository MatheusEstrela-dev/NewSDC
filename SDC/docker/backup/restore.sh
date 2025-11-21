#!/bin/bash

# Script para restaurar backup do banco de dados
# Uso: docker exec sdc_backup /backup/restore.sh <arquivo_backup.sql.gz>

BACKUP_FILE="${1:-}"

if [ -z "${BACKUP_FILE}" ]; then
    echo "ERRO: Especifique o arquivo de backup"
    echo "Uso: docker exec sdc_backup /backup/restore.sh <arquivo_backup.sql.gz>"
    exit 1
fi

MYSQL_HOST="${MYSQL_HOST:-db}"
MYSQL_DATABASE="${MYSQL_DATABASE:-sdc_db}"
MYSQL_USER="${MYSQL_USER:-root}"
MYSQL_PASSWORD="${MYSQL_PASSWORD:-}"

BACKUP_DIR="/backup/data"
FULL_PATH="${BACKUP_DIR}/${BACKUP_FILE}"

if [ ! -f "${FULL_PATH}" ]; then
    echo "ERRO: Arquivo de backup não encontrado: ${FULL_PATH}"
    exit 1
fi

echo "[$(date +'%Y-%m-%d %H:%M:%S')] Restaurando backup: ${FULL_PATH}..."

# Restaurar backup
gunzip < "${FULL_PATH}" | mysql -h "${MYSQL_HOST}" \
                                  -u "${MYSQL_USER}" \
                                  -p"${MYSQL_PASSWORD}" \
                                  "${MYSQL_DATABASE}"

if [ $? -eq 0 ]; then
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] Backup restaurado com sucesso!"
    exit 0
else
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] ERRO: Falha ao restaurar backup!"
    exit 1
fi

