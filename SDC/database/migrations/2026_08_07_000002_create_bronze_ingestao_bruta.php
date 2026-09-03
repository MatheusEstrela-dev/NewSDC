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
                processado_em  timestamptz NULL,
                -- coletado_em responde "quando este conteudo chegou". Nao
                -- responde "quando a fonte foi checada pela ultima vez", e as
                -- duas divergem justamente quando nada muda: o dedup por hash
                -- recusa o payload identico sem gravar nada, entao coletado_em
                -- congela enquanto o coletor segue rodando.
                --
                -- O efeito era uma tela dizendo "atualizado em 02/09 08:30"
                -- com o pipeline tendo consultado a fonte 15 minutos antes.
                -- Para sismo isso e grave: "sem evento novo" e a resposta certa
                -- na maioria dos ciclos (6 eventos em MG em 90 dias), e era
                -- indistinguivel de "o coletor morreu".
                --
                -- Coluna separada, e nao reuso de coletado_em, porque as duas
                -- informacoes sao necessarias ao mesmo tempo: a idade do dado e
                -- a idade da verificacao.
                verificado_em  timestamptz NULL
            )
        SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS idx_bronze_fonte_hash ON bronze.ingestao_bruta (fonte, hash_conteudo)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_bronze_fonte_coletado ON bronze.ingestao_bruta (fonte, coletado_em DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_bronze_nao_processado ON bronze.ingestao_bruta (fonte) WHERE processado_em IS NULL');

        // A leitura e sempre "a verificacao mais recente desta fonte".
        DB::statement('CREATE INDEX IF NOT EXISTS idx_bronze_fonte_verificado ON bronze.ingestao_bruta (fonte, verificado_em DESC)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP TABLE IF EXISTS bronze.ingestao_bruta');
    }
};
