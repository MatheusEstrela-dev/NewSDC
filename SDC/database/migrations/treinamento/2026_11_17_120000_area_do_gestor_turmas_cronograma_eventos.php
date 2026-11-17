<?php

declare(strict_types=1);

use App\Support\Database\GuardaDeBancoDedicado;
use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Area do Gestor (issue #86) - bloco 1: banco e dominio.
 *
 * ESTA MIGRATION NAO RODA COM `php artisan migrate`
 * Como a principal do modulo, vive em database/migrations/treinamento/ e so se
 * aplica contra a conexao `pgsql_treinamento`:
 *
 *   php artisan migrate --database=pgsql_treinamento --path=database/migrations/treinamento
 *
 * O que entra:
 * - tipo SEMI_PRESENCIAL no CHECK de treinamentos.tipo (o CHECK e recriado
 *   inteiro: DROP IF EXISTS + ADD);
 * - treinamento.turmas: recorte datado e com vagas obrigatorias de um curso;
 * - inscricoes.turma_id: NULL para o legado e para curso sem turma (EAD), que
 *   seguem valendo pelas datas e vagas do proprio curso;
 * - treinamento.curso_cronogramas: eventos datados (aulas, provas, encontros)
 *   do curso ou de uma turma, para o calendario do gestor;
 * - treinamento.curso_eventos: historico append-only do fluxo do curso.
 *
 * POR QUE curso_eventos.tipo NAO TEM CHECK
 * O historico e append-only e a lista de tipos cresce a cada tela nova do
 * gestor. A fonte da verdade e o enum TipoEventoCurso; um CHECK aqui obrigaria
 * uma migration a cada tipo novo e derrubaria a gravacao do historico (erro em
 * transacao aborta o request inteiro no Postgres).
 *
 * POR QUE NAO HA FK PARA users
 * users vive no banco sdc. curso_eventos.autor_id fica como bigint indexado,
 * igual a treinamentos.created_by e inscricoes.aprovado_por_id.
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

        $this->ampliarTipoDeTreinamento();
        $this->criarTurmas();
        $this->ligarInscricaoNaTurma();
        $this->criarCronograma();
        $this->criarHistorico();
    }

    public function down(): void
    {
        if ($this->conexao()->getDriverName() !== 'pgsql') {
            return;
        }

        $this->validarDestino();

        $this->exec('DROP TABLE IF EXISTS treinamento.curso_eventos');
        $this->exec('DROP TABLE IF EXISTS treinamento.curso_cronogramas');
        $this->exec('ALTER TABLE treinamento.inscricoes DROP CONSTRAINT IF EXISTS inscricoes_turma_id_foreign');
        $this->exec('ALTER TABLE treinamento.inscricoes DROP COLUMN IF EXISTS turma_id');
        $this->exec('DROP TABLE IF EXISTS treinamento.turmas');

        // Volta o CHECK de tipo ao conjunto anterior. Se houver curso
        // SEMI_PRESENCIAL gravado, o ADD falha de proposito: o dado teria de
        // ser reclassificado antes.
        $this->exec('ALTER TABLE treinamento.treinamentos DROP CONSTRAINT IF EXISTS treinamentos_tipo_check');
        $this->exec("ALTER TABLE treinamento.treinamentos ADD CONSTRAINT treinamentos_tipo_check CHECK (tipo IN ('PRESENCIAL', 'ONLINE', 'EAD'))");
    }

    /** SEMI_PRESENCIAL passa a valer; ONLINE continua (decisao do gestor). */
    private function ampliarTipoDeTreinamento(): void
    {
        $this->exec('ALTER TABLE treinamento.treinamentos DROP CONSTRAINT IF EXISTS treinamentos_tipo_check');
        $this->exec("ALTER TABLE treinamento.treinamentos ADD CONSTRAINT treinamentos_tipo_check CHECK (tipo IN ('PRESENCIAL', 'SEMI_PRESENCIAL', 'ONLINE', 'EAD'))");
    }

    /**
     * Turma: o recorte que de fato tem data e vaga. Ao contrario do curso
     * (data_inicio/data_fim opcionais e numero_vagas NULL = ilimitado), aqui
     * periodo e vagas sao obrigatorios - e o que torna recusavel a inscricao
     * fora do periodo e a inscricao em turma cheia.
     */
    private function criarTurmas(): void
    {
        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.turmas (
                id bigserial PRIMARY KEY,
                treinamento_id bigint NOT NULL,
                nome varchar(120) NOT NULL,
                data_inicio date NOT NULL,
                data_fim date NOT NULL,
                numero_vagas integer NOT NULL,
                local varchar(255),
                municipio_id bigint,
                created_at timestamp(0),
                updated_at timestamp(0),
                deleted_at timestamp(0),
                CONSTRAINT turmas_periodo_check CHECK (data_fim >= data_inicio),
                CONSTRAINT turmas_numero_vagas_check CHECK (numero_vagas > 0),
                CONSTRAINT turmas_treinamento_id_foreign
                    FOREIGN KEY (treinamento_id) REFERENCES treinamento.treinamentos (id) ON UPDATE CASCADE ON DELETE CASCADE
            )
            SQL);
        $this->exec("COMMENT ON COLUMN treinamento.turmas.numero_vagas IS 'Obrigatorio e > 0: turma nao tem vaga ilimitada'");
        $this->exec("COMMENT ON COLUMN treinamento.turmas.municipio_id IS 'municipios.id no banco sdc; sem FK (outro banco)'");
        $this->exec('CREATE UNIQUE INDEX IF NOT EXISTS turmas_treinamento_nome_unique ON treinamento.turmas (treinamento_id, nome) WHERE deleted_at IS NULL');
        $this->indices('treinamento.turmas', [
            'turmas_treinamento_id_index' => 'treinamento_id',
            'turmas_data_inicio_index' => 'data_inicio',
            'turmas_municipio_id_fkidx' => 'municipio_id',
        ]);
    }

    /**
     * turma_id e NULL de proposito: inscricao antiga e curso sem turma (EAD,
     * legado) continuam validos e passam a valer pelas datas e vagas do curso.
     * ON DELETE RESTRICT: apagar turma com inscricao e erro de dominio, nao
     * perda silenciosa de inscricao.
     */
    private function ligarInscricaoNaTurma(): void
    {
        $this->exec('ALTER TABLE treinamento.inscricoes ADD COLUMN IF NOT EXISTS turma_id bigint');
        $this->exec('ALTER TABLE treinamento.inscricoes DROP CONSTRAINT IF EXISTS inscricoes_turma_id_foreign');
        $this->exec(<<<'SQL'
            ALTER TABLE treinamento.inscricoes
                ADD CONSTRAINT inscricoes_turma_id_foreign
                FOREIGN KEY (turma_id) REFERENCES treinamento.turmas (id) ON UPDATE CASCADE ON DELETE RESTRICT
            SQL);
        $this->exec("COMMENT ON COLUMN treinamento.inscricoes.turma_id IS 'NULL = curso sem turma (EAD/legado): periodo e vagas valem pelo curso'");
        $this->indices('treinamento.inscricoes', [
            'inscricoes_turma_id_index' => 'turma_id',
            'inscricoes_turma_status_index' => 'turma_id, status',
        ]);
    }

    /**
     * Cronograma: o que aparece no calendario. turma_id NULL = evento do curso
     * inteiro (vale para todas as turmas).
     */
    private function criarCronograma(): void
    {
        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.curso_cronogramas (
                id bigserial PRIMARY KEY,
                treinamento_id bigint NOT NULL,
                turma_id bigint,
                titulo varchar(160) NOT NULL,
                descricao text,
                tipo varchar(20) NOT NULL DEFAULT 'AULA',
                inicio timestamp(0) NOT NULL,
                fim timestamp(0),
                local varchar(255),
                created_at timestamp(0),
                updated_at timestamp(0),
                deleted_at timestamp(0),
                CONSTRAINT curso_cronogramas_tipo_check CHECK (tipo IN ('AULA', 'PROVA', 'ENTREGA', 'ENCONTRO', 'OUTRO')),
                CONSTRAINT curso_cronogramas_periodo_check CHECK (fim IS NULL OR fim >= inicio),
                CONSTRAINT curso_cronogramas_treinamento_id_foreign
                    FOREIGN KEY (treinamento_id) REFERENCES treinamento.treinamentos (id) ON UPDATE CASCADE ON DELETE CASCADE,
                CONSTRAINT curso_cronogramas_turma_id_foreign
                    FOREIGN KEY (turma_id) REFERENCES treinamento.turmas (id) ON UPDATE CASCADE ON DELETE CASCADE
            )
            SQL);
        $this->exec("COMMENT ON COLUMN treinamento.curso_cronogramas.turma_id IS 'NULL = evento do curso inteiro'");
        $this->indices('treinamento.curso_cronogramas', [
            'curso_cronogramas_treinamento_inicio_index' => 'treinamento_id, inicio',
            'curso_cronogramas_turma_inicio_index' => 'turma_id, inicio',
            'curso_cronogramas_inicio_index' => 'inicio',
        ]);
    }

    /**
     * Historico do fluxo do curso: append-only, uma linha por acontecimento.
     * Nao substitui a trilha de acoes do modulo Notificacoes (que so manda card
     * ao sino); esta tabela e a que o gestor le na tela.
     */
    private function criarHistorico(): void
    {
        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.curso_eventos (
                id bigserial PRIMARY KEY,
                treinamento_id bigint NOT NULL,
                turma_id bigint,
                tipo varchar(40) NOT NULL,
                descricao varchar(255) NOT NULL,
                dados jsonb NOT NULL DEFAULT '{}'::jsonb,
                autor_id bigint,
                created_at timestamp(0) NOT NULL,
                CONSTRAINT curso_eventos_treinamento_id_foreign
                    FOREIGN KEY (treinamento_id) REFERENCES treinamento.treinamentos (id) ON UPDATE CASCADE ON DELETE CASCADE,
                CONSTRAINT curso_eventos_turma_id_foreign
                    FOREIGN KEY (turma_id) REFERENCES treinamento.turmas (id) ON UPDATE CASCADE ON DELETE SET NULL
            )
            SQL);
        $this->exec("COMMENT ON COLUMN treinamento.curso_eventos.autor_id IS 'users.id no banco sdc; sem FK (outro banco). NULL = acao de sistema (job, comando)'");
        $this->exec("COMMENT ON COLUMN treinamento.curso_eventos.tipo IS 'Sem CHECK: a fonte da verdade e o enum TipoEventoCurso'");
        $this->indices('treinamento.curso_eventos', [
            'curso_eventos_treinamento_id_index' => 'treinamento_id, id',
            'curso_eventos_turma_id_index' => 'turma_id',
            'curso_eventos_created_at_index' => 'created_at',
            'curso_eventos_autor_id_fkidx' => 'autor_id',
        ]);
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
