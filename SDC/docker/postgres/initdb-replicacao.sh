#!/bin/bash
# ============================================================================
# Prepara o PRIMARIO para streaming replication.
#
# Roda uma unica vez, na inicializacao do cluster (/docker-entrypoint-initdb.d).
# Num volume que ja existe isto NAO executa -- ai os mesmos passos precisam ser
# aplicados a mao, e o script serve de referencia do que aplicar.
#
# Os parametros de WAL nao estao aqui porque o PostgreSQL 16 ja entrega o que a
# replicacao precisa por padrao (max_wal_senders=10, max_replication_slots=10,
# hot_standby=on) e o onprem.conf ja fixa wal_level=replica. O que falta e so
# identidade e permissao, que e o que este arquivo resolve.
# ============================================================================
set -euo pipefail

REPLICATION_USER="${REPLICATION_USER:-replicator}"
REPLICATION_PASSWORD="${REPLICATION_PASSWORD:-replicator}"

echo "[replicacao] criando role '${REPLICATION_USER}'"

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname postgres <<-SQL
	DO \$\$
	BEGIN
		IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = '${REPLICATION_USER}') THEN
			CREATE ROLE ${REPLICATION_USER}
				WITH REPLICATION LOGIN PASSWORD '${REPLICATION_PASSWORD}';
		END IF;
	END
	\$\$;
SQL

# O pg_hba gerado pela imagem libera replicacao apenas de localhost. Os standbys
# sao OUTROS containers, entao sem esta linha o pg_basebackup deles e recusado
# com "no pg_hba.conf entry for replication connection".
#
# A linha vai ANTES do "host all all all" generico: o pg_hba e avaliado em
# ordem e a primeira regra que casa decide.
echo "[replicacao] liberando conexao de replicacao para a rede do compose"

HBA="${PGDATA}/pg_hba.conf"
if ! grep -qE '^host\s+replication\s+all\s+all' "$HBA"; then
	printf '\n# streaming replication a partir dos standbys do compose\nhost replication all all scram-sha-256\n' >> "$HBA"
fi

echo "[replicacao] primario pronto para receber standbys"
