#!/bin/sh
set -eu

: "${PATRONI_NAME:?PATRONI_NAME ausente}"
: "${PATRONI_POSTGRESQL_DATA_DIR:?PATRONI_POSTGRESQL_DATA_DIR ausente}"
: "${PATRONI_SUPERUSER_PASSWORD:?PATRONI_SUPERUSER_PASSWORD ausente}"
: "${PATRONI_REPLICATION_PASSWORD:?PATRONI_REPLICATION_PASSWORD ausente}"

managed_marker="/var/lib/postgresql/.patroni-managed-${PATRONI_NAME}"
if [ -f "$PATRONI_POSTGRESQL_DATA_DIR/PG_VERSION" ] \
    && [ ! -f "$managed_marker" ] \
    && [ "${PATRONI_ALLOW_EXISTING_DATA:-false}" != "true" ]; then
    echo "PGDATA existente: preparar backup e adocao antes de definir PATRONI_ALLOW_EXISTING_DATA=true." >&2
    exit 1
fi

if [ "$(id -u)" = "0" ]; then
    if [ "${ETCD_EMBEDDED:-false}" = "true" ]; then
        mkdir -p /etcd-data
        chown -R postgres:postgres /etcd-data
        chmod 700 /etcd-data
    fi
    mkdir -p "$PATRONI_POSTGRESQL_DATA_DIR" /run/patroni /var/run/postgresql
    chown postgres:postgres "$PATRONI_POSTGRESQL_DATA_DIR" /run/patroni /var/run/postgresql
    chmod 700 "$PATRONI_POSTGRESQL_DATA_DIR" /run/patroni
    touch "$managed_marker"
    chown postgres:postgres "$managed_marker"
    exec gosu postgres "$0" "$@"
fi

umask 077
python3 - <<'PY'
import os
import yaml

with open('/etc/patroni/patroni.yml', encoding='utf-8') as source:
    configuration = yaml.safe_load(source)
for parameter in ['shared_buffers', 'effective_cache_size', 'maintenance_work_mem']:
    override = os.environ.get('PATRONI_MEMORY_' + parameter.upper())
    if override:
        configuration['postgresql']['parameters'][parameter] = override
with open('/run/patroni/patroni.yml', 'w', encoding='utf-8') as target:
    yaml.safe_dump(configuration, target)
PY
if [ "${ETCD_EMBEDDED:-false}" = "true" ]; then
    exec python3 /usr/local/bin/supervise.py "$@"
fi
exec "$@"
