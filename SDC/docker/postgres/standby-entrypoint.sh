#!/bin/bash
# ============================================================================
# Sobe um STANDBY de leitura por streaming replication.
#
# Na primeira vez o diretorio de dados esta vazio: copia o primario inteiro com
# pg_basebackup e sobe em modo standby. Nas vezes seguintes o diretorio ja tem
# dados e ele apenas sobe, reconectando ao primario de onde parou.
#
# POR QUE NAO E SO "APONTAR PARA O PRIMARIO": um standby nao e uma instancia
# vazia que sincroniza. Ele precisa ser uma COPIA FISICA do primario -- mesmo
# system identifier, mesmo timeline -- e e isso que o pg_basebackup faz. Um
# initdb proprio geraria um cluster diferente, que o primario recusaria.
# ============================================================================
set -euo pipefail

PRIMARY_HOST="${PRIMARY_HOST:-db}"
PRIMARY_PORT="${PRIMARY_PORT:-5432}"
REPLICATION_USER="${REPLICATION_USER:-replicator}"
REPLICATION_PASSWORD="${REPLICATION_PASSWORD:-replicator}"
# Slot proprio por standby: o primario retem o WAL de que CADA um ainda precisa.
# Sem slot, um standby que ficar para tras perde WAL ja reciclado e precisa de
# basebackup novo. O preco e simetrico: standby parado por muito tempo faz o
# WAL crescer no primario ate encher o disco -- por isso slot e de standby que
# existe de verdade, nunca esquecido para tras.
SLOT_NAME="${SLOT_NAME:-standby}"

export PGPASSWORD="$REPLICATION_PASSWORD"

if [ ! -s "${PGDATA}/PG_VERSION" ]; then
	echo "[standby ${SLOT_NAME}] diretorio vazio -- copiando do primario ${PRIMARY_HOST}"

	until pg_isready -h "$PRIMARY_HOST" -p "$PRIMARY_PORT" -U "$REPLICATION_USER" -q; do
		echo "[standby ${SLOT_NAME}] aguardando o primario aceitar conexao..."
		sleep 2
	done

	# O slot e criado ANTES da copia, de forma idempotente, e nao pelo
	# --create-slot do pg_basebackup.
	#
	# Motivo: --create-slot aborta com "replication slot already exists" se o
	# slot sobrou de uma tentativa anterior -- e como o pg_basebackup apaga o
	# diretorio ao falhar, o container reinicia, tenta de novo e falha igual.
	# Loop infinito que se parece com problema de rede e nao e.
	echo "[standby ${SLOT_NAME}] garantindo o slot de replicacao no primario"
	psql "host=${PRIMARY_HOST} port=${PRIMARY_PORT} user=${REPLICATION_USER} dbname=postgres" \
		-v ON_ERROR_STOP=1 -tAc \
		"SELECT pg_create_physical_replication_slot('${SLOT_NAME}')
		  WHERE NOT EXISTS (
		    SELECT 1 FROM pg_replication_slots WHERE slot_name = '${SLOT_NAME}'
		  );"

	# -R escreve standby.signal e primary_conninfo, que e o que faz o cluster
	#    subir em modo standby em vez de primario.
	# -S usa o slot garantido acima.
	# -Xs (stream) traz o WAL gerado DURANTE a copia pela mesma conexao; sem
	#    isso uma copia longa pode terminar precisando de WAL ja reciclado.
	pg_basebackup \
		--host="$PRIMARY_HOST" \
		--port="$PRIMARY_PORT" \
		--username="$REPLICATION_USER" \
		--pgdata="$PGDATA" \
		--format=plain \
		--wal-method=stream \
		--write-recovery-conf \
		--slot="$SLOT_NAME" \
		--checkpoint=fast \
		--progress --verbose

	# O Postgres recusa subir se o diretorio de dados for legivel por outros.
	chmod 0700 "$PGDATA"

	echo "[standby ${SLOT_NAME}] copia concluida"
else
	echo "[standby ${SLOT_NAME}] diretorio ja populado -- reconectando ao primario"
fi

unset PGPASSWORD

exec postgres
