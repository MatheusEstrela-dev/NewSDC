<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $conn = DB::connection('pgsql');

        try {
            $conn->statement('CREATE EXTENSION IF NOT EXISTS vector');
        } catch (\Throwable $e) {
            // Em Azure Flexible Server a extensao precisa estar em azure.extensions.
            // Se nao estiver, abortamos esta migration sem falhar o batch para que o
            // schema principal seja criado. Habilite via Portal e rode novamente:
            //   php artisan migrate --path=database/migrations/2026_04_27_000001_create_dec_embeddings_table.php
            return;
        }

        $hasVector = $conn->selectOne("SELECT 1 AS ok FROM pg_extension WHERE extname='vector'");
        if (! $hasVector) {
            return;
        }

        $conn->statement('
            CREATE TABLE IF NOT EXISTS dec_embeddings (
                id          bigserial    PRIMARY KEY,
                content     text         NOT NULL,
                embedding   vector(768),
                source_type varchar(100) NOT NULL DEFAULT \'rat\',
                source_name varchar(255) NOT NULL DEFAULT \'\',
                chunk_number int         NOT NULL DEFAULT 0,
                metadata    jsonb,
                created_at  timestamp    DEFAULT now()
            )
        ');

        $conn->statement('
            CREATE INDEX IF NOT EXISTS dec_embeddings_embedding_idx
            ON dec_embeddings
            USING ivfflat (embedding vector_cosine_ops)
            WITH (lists = 100)
        ');
    }

    public function down(): void
    {
        DB::connection('pgsql')->statement('DROP TABLE IF EXISTS dec_embeddings');
        DB::connection('pgsql')->statement('DROP EXTENSION IF EXISTS vector');
    }
};
