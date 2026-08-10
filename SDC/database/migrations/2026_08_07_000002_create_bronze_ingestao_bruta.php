<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Camada Bronze: guarda o payload exatamente como chegou. O conteudo e
        // texto, nao jsonb, porque as fontes devolvem texto delimitado e CSV —
        // serializar para json antes de gravar ja seria transformacao, e a regra
        // desta camada e nao transformar. O jsonb fica em meta, que descreve a
        // coleta (url, params, status), nao o dado coletado.
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS bronze.ingestao_bruta (
                id             bigserial PRIMARY KEY,
                fonte          varchar(64) NOT NULL,
                conteudo_bruto text        NOT NULL,
                formato        varchar(32) NOT NULL,
                hash_conteudo  char(64)    NOT NULL,
                meta           jsonb       NOT NULL DEFAULT '{}'::jsonb,
                coletado_em    timestamptz NOT NULL DEFAULT now(),
                processado_em  timestamptz NULL
            )
        SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS idx_bronze_fonte_hash ON bronze.ingestao_bruta (fonte, hash_conteudo)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_bronze_fonte_coletado ON bronze.ingestao_bruta (fonte, coletado_em DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_bronze_nao_processado ON bronze.ingestao_bruta (fonte) WHERE processado_em IS NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP TABLE IF EXISTS bronze.ingestao_bruta');
    }
};
