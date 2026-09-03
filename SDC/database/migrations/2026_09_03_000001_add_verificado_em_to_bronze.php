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

        // coletado_em responde "quando este conteudo chegou". Nao responde
        // "quando a fonte foi checada pela ultima vez", e as duas divergem
        // justamente quando nada muda: o dedup por hash recusa o payload
        // identico sem gravar nada, entao coletado_em congela.
        //
        // O efeito era uma tela dizendo "atualizado em 02/09 08:30" enquanto o
        // pipeline havia consultado a fonte 15 minutos antes. Para sismo isso e
        // grave: "sem evento novo" e a resposta certa na maioria dos ciclos, e
        // era indistinguivel de "o coletor morreu".
        //
        // Nova coluna em vez de reaproveitar coletado_em porque as duas
        // informacoes sao necessarias ao mesmo tempo: a idade do dado e a
        // idade da verificacao.
        DB::statement('ALTER TABLE bronze.ingestao_bruta ADD COLUMN IF NOT EXISTS verificado_em timestamptz NULL');

        // Retroativo: sem isto as linhas existentes ficariam nulas e a tela
        // cairia no fallback como se nunca tivesse havido verificacao.
        DB::statement('UPDATE bronze.ingestao_bruta SET verificado_em = coletado_em WHERE verificado_em IS NULL');

        // A leitura e sempre "a verificacao mais recente desta fonte".
        DB::statement('CREATE INDEX IF NOT EXISTS idx_bronze_fonte_verificado ON bronze.ingestao_bruta (fonte, verificado_em DESC)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS bronze.idx_bronze_fonte_verificado');
        DB::statement('ALTER TABLE bronze.ingestao_bruta DROP COLUMN IF EXISTS verificado_em');
    }
};
