<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::connection('pgsql')->statement('CREATE EXTENSION IF NOT EXISTS vector');

        DB::connection('pgsql')->statement('
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

        DB::connection('pgsql')->statement('
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
