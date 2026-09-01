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

        // Fato puro: nao repete nome nem coordenada da estacao, que vivem na
        // dimensao. Diferente de silver.sismos, onde repetir se justifica porque
        // o evento sismico nao tem entidade estavel por tras. Estacao tem, e
        // isso faz corrigir uma coordenada corrigir o historico num update.
        //
        // Sem FK para estacoes_meteorologicas de proposito: uma estacao pode
        // aparecer nas leituras antes de entrar no inventario, e perder a
        // leitura por isso seria pior que ter fato sem dimensao por um ciclo.
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS silver.leituras_inmet (
                id               bigserial PRIMARY KEY,
                codigo_estacao   varchar(16)  NOT NULL,
                medido_em        timestamptz  NOT NULL,
                temperatura      numeric(6,2) NULL,
                umidade          numeric(6,2) NULL,
                precipitacao     numeric(8,2) NULL,
                velocidade_vento numeric(6,2) NULL,
                pressao          numeric(8,2) NULL,
                ingestao_id      bigint       NULL REFERENCES bronze.ingestao_bruta (id) ON DELETE SET NULL,
                created_at       timestamptz  NOT NULL DEFAULT now(),
                updated_at       timestamptz  NOT NULL DEFAULT now(),
                CONSTRAINT uq_silver_leituras_inmet UNIQUE (codigo_estacao, medido_em)
            )
        SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS idx_silver_leituras_inmet_medido ON silver.leituras_inmet (medido_em DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_silver_leituras_inmet_estacao ON silver.leituras_inmet (codigo_estacao, medido_em DESC)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP TABLE IF EXISTS silver.leituras_inmet');
    }
};
