<?php

declare(strict_types=1);

use App\Support\Database\GuardaDeBancoDedicado;
use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Formulario de avaliacao da CEDEC MG na etapa final do curso: alem da nota
 * geral ("Como voce avalia a capacitacao?", coluna nota), o aluno responde
 * "Em que medida o conteudo apresentado foi relevante...?" (1..5) e deixa
 * sugestao/comentario (agora obrigatorio, validado no AvaliarCursoRequest).
 *
 * ESTA MIGRATION NAO RODA COM `php artisan migrate`
 *
 *   php artisan migrate --database=pgsql_treinamento --path=database/migrations/treinamento
 *
 * nota_relevancia e NULLABLE de proposito: as avaliacoes ja gravadas (antes
 * desta pergunta) nao tem a resposta e seguem validas. Toda avaliacao nova
 * grava a relevancia (obrigatoria no request). CHECK espelha o enum
 * NotaRelevanciaCurso (1..5).
 */
return new class extends Migration
{
    protected $connection = 'pgsql_treinamento';

    public function up(): void
    {
        if ($this->conexao()->getDriverName() !== 'pgsql') {
            return;
        }

        $this->validarDestino();

        $this->exec('ALTER TABLE treinamento.curso_avaliacoes ADD COLUMN IF NOT EXISTS nota_relevancia smallint');
        $this->exec(<<<'SQL'
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_constraint
                    WHERE conname = 'curso_avaliacoes_nota_relevancia_check'
                      AND conrelid = 'treinamento.curso_avaliacoes'::regclass
                ) THEN
                    ALTER TABLE treinamento.curso_avaliacoes
                        ADD CONSTRAINT curso_avaliacoes_nota_relevancia_check CHECK (nota_relevancia BETWEEN 1 AND 5);
                END IF;
            END
            $$
            SQL);
        $this->exec("COMMENT ON COLUMN treinamento.curso_avaliacoes.nota_relevancia IS '1 Nao foi relevante .. 5 Muito relevante (enum NotaRelevanciaCurso); NULL = avaliacao anterior a pergunta'");
    }

    public function down(): void
    {
        if ($this->conexao()->getDriverName() !== 'pgsql') {
            return;
        }

        $this->validarDestino();

        $this->exec('ALTER TABLE treinamento.curso_avaliacoes DROP CONSTRAINT IF EXISTS curso_avaliacoes_nota_relevancia_check');
        $this->exec('ALTER TABLE treinamento.curso_avaliacoes DROP COLUMN IF EXISTS nota_relevancia');
    }

    private function conexao(): Connection
    {
        return DB::connection($this->connection);
    }

    private function exec(string $sql): void
    {
        $this->conexao()->statement($sql);
    }

    private function validarDestino(): void
    {
        GuardaDeBancoDedicado::validar($this->conexao(), 'treinamento');
    }
};
