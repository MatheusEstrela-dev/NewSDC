<?php

declare(strict_types=1);

use App\Support\Database\GuardaDeBancoDedicado;
use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Feedback de conclusao do curso (issue "[CAPACITACOES] - Replicar a parte
 * Externa Treinamento"): o aluno avalia o curso de 1 a 5 ao concluir, e so
 * entao o certificado e liberado.
 *
 * ESTA MIGRATION NAO RODA COM `php artisan migrate`
 * Como as demais do modulo, vive em database/migrations/treinamento/ e so se
 * aplica contra a conexao `pgsql_treinamento`:
 *
 *   php artisan migrate --database=pgsql_treinamento --path=database/migrations/treinamento
 *
 * O que entra:
 * - treinamento.curso_avaliacoes: uma avaliacao por inscricao (UNIQUE), nota
 *   1..5 (CHECK; fonte da verdade e o enum NotaAvaliacaoCurso) e comentario
 *   opcional. Sem soft delete: a avaliacao e o que libera o certificado, e
 *   some-la reabriria um bloqueio sobre certificado ja entregue.
 * - certificados.exige_avaliacao: marca, na emissao, que aquele certificado so
 *   e liberado ao aluno depois da avaliacao. DEFAULT false de proposito: todo
 *   certificado que ja existe (e os copiados do sdc pelo
 *   treinamento:copiar-para-banco, que nao conhece a coluna) e legado e segue
 *   liberado como estava. Nenhum certificado e bloqueado retroativamente.
 *
 * NOTA (2026-11-19): a regra mudou antes de ir para producao. A avaliacao
 * passou a ser a etapa final obrigatoria da conclusao (ConclusaoService), e
 * sem conclusao nao ha certificado; a retencao do certificado deixou de
 * existir e o codigo nao le nem grava mais certificados.exige_avaliacao. A
 * coluna fica (migration ja aplicada nos bancos de dev e de teste), sempre
 * false, sem uso. Schema desta migration inalterado.
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

        $this->criarAvaliacoes();
        $this->marcarCertificadoQueExigeAvaliacao();
    }

    public function down(): void
    {
        if ($this->conexao()->getDriverName() !== 'pgsql') {
            return;
        }

        $this->validarDestino();

        $this->exec('ALTER TABLE treinamento.certificados DROP COLUMN IF EXISTS exige_avaliacao');
        $this->exec('DROP TABLE IF EXISTS treinamento.curso_avaliacoes');
    }

    private function criarAvaliacoes(): void
    {
        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.curso_avaliacoes (
                id bigserial PRIMARY KEY,
                inscricao_id bigint NOT NULL,
                treinamento_id bigint NOT NULL,
                nota smallint NOT NULL,
                comentario text,
                created_at timestamp(0),
                updated_at timestamp(0),
                CONSTRAINT curso_avaliacoes_inscricao_id_unique UNIQUE (inscricao_id),
                CONSTRAINT curso_avaliacoes_nota_check CHECK (nota BETWEEN 1 AND 5),
                CONSTRAINT curso_avaliacoes_inscricao_id_foreign
                    FOREIGN KEY (inscricao_id) REFERENCES treinamento.inscricoes (id) ON UPDATE CASCADE ON DELETE CASCADE,
                CONSTRAINT curso_avaliacoes_treinamento_id_foreign
                    FOREIGN KEY (treinamento_id) REFERENCES treinamento.treinamentos (id) ON UPDATE CASCADE ON DELETE CASCADE
            )
            SQL);
        $this->exec("COMMENT ON COLUMN treinamento.curso_avaliacoes.nota IS '1 Pessimo .. 5 Excelente (enum NotaAvaliacaoCurso)'");
        $this->indices('treinamento.curso_avaliacoes', [
            'curso_avaliacoes_treinamento_id_index' => 'treinamento_id',
            'curso_avaliacoes_treinamento_nota_index' => 'treinamento_id, nota',
        ]);
    }

    private function marcarCertificadoQueExigeAvaliacao(): void
    {
        $this->exec('ALTER TABLE treinamento.certificados ADD COLUMN IF NOT EXISTS exige_avaliacao boolean NOT NULL DEFAULT false');
        $this->exec("COMMENT ON COLUMN treinamento.certificados.exige_avaliacao IS 'true = so liberado ao aluno depois da avaliacao do curso; false = legado (emitido antes do feedback)'");
    }

    /**
     * @param  array<string, string>  $indices  nome => colunas
     */
    private function indices(string $tabela, array $indices): void
    {
        foreach ($indices as $nome => $colunas) {
            $this->exec("CREATE INDEX IF NOT EXISTS {$nome} ON {$tabela} ({$colunas})");
        }
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
