#!/bin/bash
# Script de inicializacao do banco AI (db_ai).
# Executado automaticamente pelo entrypoint do PostgreSQL na primeira subida.
# Habilita as tres extensoes e distribui a tabela de embeddings via Citus.

set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    CREATE EXTENSION IF NOT EXISTS vector;
    CREATE EXTENSION IF NOT EXISTS citus;
    CREATE EXTENSION IF NOT EXISTS postgis;
    CREATE EXTENSION IF NOT EXISTS postgis_topology;

    CREATE TABLE IF NOT EXISTS dec_embeddings (
        id          bigserial    PRIMARY KEY,
        content     text         NOT NULL,
        embedding   vector(768),
        source_type varchar(100) NOT NULL DEFAULT 'rat',
        source_name varchar(255) NOT NULL DEFAULT '',
        chunk_number int         NOT NULL DEFAULT 0,
        metadata    jsonb,
        created_at  timestamp    DEFAULT now()
    );

    SELECT create_distributed_table('dec_embeddings', 'id');

    CREATE INDEX IF NOT EXISTS dec_embeddings_embedding_idx
        ON dec_embeddings
        USING ivfflat (embedding vector_cosine_ops)
        WITH (lists = 100);
EOSQL

echo "db_ai: extensoes vector + citus + postgis ativas, dec_embeddings distribuida."
