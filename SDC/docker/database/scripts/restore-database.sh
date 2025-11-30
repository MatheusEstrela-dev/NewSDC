#!/bin/bash
#
# =============================================================================
# SDC - Restore de Banco de Dados MySQL
# Sistema Crítico 24/7 - Restore com Verificação
# =============================================================================
#
# Uso: ./restore-database.sh <arquivo-backup.sql.gz>
#

set -euo pipefail

# =============================================================================
# CONFIGURAÇÕES
# =============================================================================

# MySQL
DB_HOST="${DB_HOST:-db-primary}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_DATABASE:-sdc}"
DB_USER="${DB_USERNAME:-sdc_user}"
DB_PASS="${DB_PASSWORD}"

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

# =============================================================================
# VALIDAÇÃO
# =============================================================================

validate_backup() {
    local backup_file="$1"

    log "🔍 Validando backup..."

    # Verificar se arquivo existe
    if [ ! -f "$backup_file" ]; then
        error "Arquivo de backup não encontrado: $backup_file"
        return 1
    fi

    # Verificar checksum se existir
    if [ -f "${backup_file}.sha256" ]; then
        log "🔐 Verificando checksum..."

        if sha256sum -c "${backup_file}.sha256" --status; then
            success "Checksum válido"
        else
            error "Checksum INVÁLIDO! Backup pode estar corrompido!"
            read -p "Deseja continuar mesmo assim? (yes/no): " confirm

            if [ "$confirm" != "yes" ]; then
                return 1
            fi
        fi
    else
        log "⚠️  Arquivo de checksum não encontrado, pulando verificação"
    fi

    # Verificar se é gzip válido
    if [[ "$backup_file" == *.gz ]]; then
        if ! gzip -t "$backup_file" 2>/dev/null; then
            error "Arquivo GZIP corrompido!"
            return 1
        fi
        success "Arquivo GZIP válido"
    fi

    return 0
}

# =============================================================================
# RESTORE
# =============================================================================

restore_database() {
    local backup_file="$1"
    local backup_name=$(basename "$backup_file")

    log "🔄 Iniciando restore de banco de dados..."
    log "📂 Arquivo: $backup_name"
    log "📊 Database: ${DB_NAME}@${DB_HOST}:${DB_PORT}"

    # Verificar conexão MySQL
    if ! mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" --silent; then
        error "Não foi possível conectar ao MySQL"
        return 1
    fi

    success "Conexão MySQL estabelecida"

    # ⚠️ AVISO CRÍTICO
    echo ""
    echo "╔════════════════════════════════════════════════════════════╗"
    echo "║                    ⚠️  ATENÇÃO CRÍTICA  ⚠️                  ║"
    echo "╠════════════════════════════════════════════════════════════╣"
    echo "║                                                            ║"
    echo "║  Este processo irá SUBSTITUIR COMPLETAMENTE o banco de    ║"
    echo "║  dados atual com o backup:                                ║"
    echo "║                                                            ║"
    echo "║  📂 $backup_name"
    echo "║  🗄️  Database: $DB_NAME"
    echo "║  🖥️  Host: $DB_HOST"
    echo "║                                                            ║"
    echo "║  ⚠️  TODOS OS DADOS ATUAIS SERÃO PERDIDOS!                ║"
    echo "║                                                            ║"
    echo "╚════════════════════════════════════════════════════════════╝"
    echo ""

    read -p "Digite 'CONFIRMO RESTORE' para continuar: " confirmation

    if [ "$confirmation" != "CONFIRMO RESTORE" ]; then
        log "Restore cancelado pelo usuário"
        return 1
    fi

    # Criar backup de segurança antes do restore
    log "💾 Criando backup de segurança antes do restore..."
    local safety_backup="/tmp/pre-restore-${DB_NAME}-$(date +%Y%m%d_%H%M%S).sql.gz"

    if mysqldump \
        --host="$DB_HOST" \
        --port="$DB_PORT" \
        --user="$DB_USER" \
        --password="$DB_PASS" \
        --databases "$DB_NAME" \
        --single-transaction \
        --quick \
        | gzip > "$safety_backup"; then

        success "Backup de segurança criado: $safety_backup"
    else
        error "Falha ao criar backup de segurança"
        read -p "Deseja continuar sem backup de segurança? (yes/no): " confirm

        if [ "$confirm" != "yes" ]; then
            return 1
        fi
    fi

    # Executar restore
    log "🔄 Executando restore..."

    if [[ "$backup_file" == *.gz ]]; then
        # Arquivo comprimido
        if zcat "$backup_file" | mysql \
            --host="$DB_HOST" \
            --port="$DB_PORT" \
            --user="$DB_USER" \
            --password="$DB_PASS" \
            2>/tmp/restore-error.log; then

            success "Restore concluído com sucesso!"
        else
            error "Falha no restore: $(cat /tmp/restore-error.log)"
            log "💡 Backup de segurança disponível em: $safety_backup"
            return 1
        fi
    else
        # Arquivo não comprimido
        if mysql \
            --host="$DB_HOST" \
            --port="$DB_PORT" \
            --user="$DB_USER" \
            --password="$DB_PASS" \
            < "$backup_file" \
            2>/tmp/restore-error.log; then

            success "Restore concluído com sucesso!"
        else
            error "Falha no restore: $(cat /tmp/restore-error.log)"
            log "💡 Backup de segurança disponível em: $safety_backup"
            return 1
        fi
    fi

    # Verificar restore
    log "✅ Verificando restore..."

    local table_count=$(mysql \
        --host="$DB_HOST" \
        --port="$DB_PORT" \
        --user="$DB_USER" \
        --password="$DB_PASS" \
        --database="$DB_NAME" \
        --silent \
        --execute="SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}'" \
        2>/dev/null)

    if [ "$table_count" -gt 0 ]; then
        success "Database restaurado com ${table_count} tabelas"
    else
        error "Restore pode ter falhado - nenhuma tabela encontrada!"
        return 1
    fi

    # Limpar backup de segurança (opcional)
    log "🗑️  Backup de segurança mantido em: $safety_backup"
    log "   Execute 'rm $safety_backup' se não precisar mais"

    return 0
}

# =============================================================================
# MAIN
# =============================================================================

main() {
    if [ $# -lt 1 ]; then
        error "Uso: $0 <arquivo-backup.sql.gz>"
        echo ""
        echo "Exemplos:"
        echo "  $0 /backups/database/sdc-db-auto-20250130_120000.sql.gz"
        echo "  $0 /backups/database/sdc-db-latest.sql.gz"
        exit 1
    fi

    local backup_file="$1"

    # Validar backup
    if ! validate_backup "$backup_file"; then
        error "Validação do backup falhou!"
        exit 1
    fi

    # Executar restore
    if restore_database "$backup_file"; then
        success "✅ Restore concluído com sucesso!"
        exit 0
    else
        error "❌ Falha no restore!"
        exit 1
    fi
}

# Executar
main "$@"
