<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A fase do Acompanhamento nao pode contradizer o estado do cronograma.
 *
 * Os pares so batiam porque ativar() e encerrar() gravam os dois juntos. Uma
 * escrita direta (SQL, importacao, ETL, seeder) podia deixar um ativo na fase
 * `aprovado`, e o filtro por fase o escondia da listagem.
 *
 * Trigger, e nao CHECK: o CHECK RECUSARIA essas escritas (o ETL e varios
 * testes inserem `ativo = true` sem saber da fase), e o que se quer e que a
 * fase acompanhe o estado. A regra, a cada INSERT/UPDATE:
 *   - encerrado             => entregue
 *   - ativo e nao encerrado => em_execucao
 *   - rascunho              => mantem elaboracao/em_analise/aprovado; outra
 *                              coisa (ex.: reaberto a mao) volta a elaboracao
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION tdap_cronogramas_fase_coerente() RETURNS trigger AS $$
            BEGIN
                IF NEW.encerrado_em IS NOT NULL THEN
                    NEW.fase_tramite := 'entregue';
                ELSIF NEW.ativo THEN
                    NEW.fase_tramite := 'em_execucao';
                ELSIF NEW.fase_tramite IS NULL OR NEW.fase_tramite NOT IN ('elaboracao', 'em_analise', 'aprovado') THEN
                    NEW.fase_tramite := 'elaboracao';
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            DROP TRIGGER IF EXISTS tdap_cronogramas_fase_coerente ON tdap_cronogramas;
            CREATE TRIGGER tdap_cronogramas_fase_coerente
                BEFORE INSERT OR UPDATE OF ativo, encerrado_em, fase_tramite ON tdap_cronogramas
                FOR EACH ROW EXECUTE FUNCTION tdap_cronogramas_fase_coerente();
        SQL);

        // Alinha o acervo pelo proprio trigger (o UPDATE em fase_tramite o dispara).
        DB::statement('UPDATE tdap_cronogramas SET fase_tramite = fase_tramite');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS tdap_cronogramas_fase_coerente ON tdap_cronogramas;
            DROP FUNCTION IF EXISTS tdap_cronogramas_fase_coerente();
        SQL);
    }
};
