<?php

declare(strict_types=1);

namespace Tests\Feature\Treinamento;

use App\Models\User;
use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Services\InscricaoService;
use DomainException;

/**
 * Issue #86, bloco 4.1: recusa de inscricao fora do periodo (turma e curso sem
 * turma, so o limite final recusa) e sem vaga (em inscrever() e em aprovar();
 * pendente nao ocupa vaga). Turma obrigatoria quando o curso tem turma; turma
 * de outro curso e recusada.
 *
 * Cobre App\Modules\Treinamento\Support\PeriodoDeInscricao,
 * App\Modules\Treinamento\Support\ControleDeVagas e
 * App\Modules\Treinamento\Services\InscricaoService::inscrever()/aprovar().
 */
class InscricaoTurmaVagasTest extends TreinamentoGestorTestCase
{
    private function service(): InscricaoService
    {
        return app(InscricaoService::class);
    }

    // ── Periodo: curso SEM turma ────────────────────────────────────────────

    public function test_curso_sem_turma_recusa_inscricao_apos_o_limite_final(): void
    {
        $curso = $this->criarCurso([
            'data_inicio' => now()->subMonth()->toDateString(),
            'data_fim' => now()->subDay()->toDateString(),
        ]);
        $aluno = User::factory()->create();

        try {
            $this->service()->inscrever($curso, $aluno);
            $this->fail('inscricao fora do periodo deveria ser recusada');
        } catch (DomainException $e) {
            $this->assertStringContainsString('encerraram em', $e->getMessage());
        }

        $this->assertDatabaseMissing('treinamento.inscricoes', [
            'treinamento_id' => $curso->id,
            'inscrito_id' => $aluno->id,
        ], $this->conexaoDoTreinamento());
    }

    public function test_curso_sem_turma_permite_inscricao_antecipada_antes_do_inicio(): void
    {
        $curso = $this->criarCurso([
            'data_inicio' => now()->addMonth()->toDateString(),
            'data_fim' => now()->addMonths(2)->toDateString(),
        ]);
        $aluno = User::factory()->create();

        $inscricao = $this->service()->inscrever($curso, $aluno);

        $this->assertSame(StatusInscricao::PENDENTE, $inscricao->status);
    }

    // ── Periodo: curso COM turma ────────────────────────────────────────────

    public function test_turma_com_periodo_encerrado_recusa_a_inscricao(): void
    {
        $curso = $this->criarCurso();
        $turma = $this->criarTurma($curso, [
            'data_inicio' => now()->subMonth()->toDateString(),
            'data_fim' => now()->subDay()->toDateString(),
        ]);
        $aluno = User::factory()->create();

        try {
            $this->service()->inscrever($curso, $aluno, null, $turma);
            $this->fail('inscricao em turma encerrada deveria ser recusada');
        } catch (DomainException $e) {
            $this->assertStringContainsString($turma->nome, $e->getMessage());
            $this->assertStringContainsString('encerraram em', $e->getMessage());
        }
    }

    public function test_turma_com_periodo_futuro_permite_inscricao_antecipada(): void
    {
        $curso = $this->criarCurso();
        $turma = $this->criarTurma($curso, [
            'data_inicio' => now()->addWeek()->toDateString(),
            'data_fim' => now()->addMonth()->toDateString(),
        ]);
        $aluno = User::factory()->create();

        $inscricao = $this->service()->inscrever($curso, $aluno, null, $turma);

        $this->assertSame($turma->id, $inscricao->turma_id);
    }

    // ── Turma obrigatoria quando o curso tem turma ──────────────────────────

    public function test_turma_e_obrigatoria_quando_o_curso_ja_tem_turma(): void
    {
        $curso = $this->criarCurso();
        $this->criarTurma($curso);
        $aluno = User::factory()->create();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Escolha uma turma');

        $this->service()->inscrever($curso, $aluno);
    }

    public function test_turma_de_outro_curso_e_recusada(): void
    {
        $curso = $this->criarCurso();
        $this->criarTurma($curso);
        $outroCurso = $this->criarCurso();
        $turmaAlheia = $this->criarTurma($outroCurso);
        $aluno = User::factory()->create();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('nao pertence a este treinamento');

        $this->service()->inscrever($curso, $aluno, null, $turmaAlheia);
    }

    public function test_curso_sem_nenhuma_turma_ignora_turma_mandada_pelo_chamador(): void
    {
        // Curso sem turma (EAD/legado): mesmo que o chamador mande uma turma de
        // OUTRO curso, a inscricao segue sem turma (contrato do bloco 1).
        $curso = $this->criarCurso();
        $outroCurso = $this->criarCurso();
        $turmaAlheia = $this->criarTurma($outroCurso);
        $aluno = User::factory()->create();

        $inscricao = $this->service()->inscrever($curso, $aluno, null, $turmaAlheia);

        $this->assertNull($inscricao->turma_id);
    }

    // ── Vagas: inscrever() ───────────────────────────────────────────────────

    public function test_inscrever_recusa_quando_turma_nao_tem_mais_vaga(): void
    {
        $curso = $this->criarCurso();
        $turma = $this->criarTurma($curso, ['numero_vagas' => 1]);
        $jaAprovado = User::factory()->create();
        $this->aprovarDiretamente($curso, $turma, $jaAprovado);

        $novoAluno = User::factory()->create();

        try {
            $this->service()->inscrever($curso, $novoAluno, null, $turma);
            $this->fail('inscricao em turma cheia deveria ser recusada');
        } catch (DomainException $e) {
            $this->assertStringContainsString($turma->nome, $e->getMessage());
            $this->assertStringContainsString('nao tem mais vagas', $e->getMessage());
        }
    }

    /**
     * Curso SEM turma lotado: a recusa vem de ControleDeVagas, sob trava, com a
     * mensagem de vaga (antes um gate sem trava respondia "nao esta recebendo
     * inscricoes no momento").
     */
    public function test_inscrever_recusa_quando_curso_sem_turma_nao_tem_mais_vaga(): void
    {
        $curso = $this->criarCurso(['numero_vagas' => 1]);
        $jaAprovado = User::factory()->create();
        $inscricaoAprovada = $this->service()->inscrever($curso, $jaAprovado);
        $inscricaoAprovada->update(['status' => StatusInscricao::APROVADA->value]);

        $novoAluno = User::factory()->create();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Este treinamento nao tem mais vagas.');

        $this->service()->inscrever($curso, $novoAluno);
    }

    public function test_inscricao_pendente_nao_ocupa_vaga(): void
    {
        $curso = $this->criarCurso();
        $turma = $this->criarTurma($curso, ['numero_vagas' => 1]);
        $pendente = User::factory()->create();

        $primeira = $this->service()->inscrever($curso, $pendente, null, $turma);
        $this->assertSame(StatusInscricao::PENDENTE, $primeira->status);

        // A unica vaga continua livre: a primeira inscricao ficou PENDENTE.
        $segundoAluno = User::factory()->create();
        $segunda = $this->service()->inscrever($curso, $segundoAluno, null, $turma);

        $this->assertSame(StatusInscricao::PENDENTE, $segunda->status);
    }

    // ── Vagas: aprovar() ─────────────────────────────────────────────────────

    public function test_aprovar_recusa_quando_a_vaga_foi_ocupada_entre_a_inscricao_e_a_aprovacao(): void
    {
        $curso = $this->criarCurso();
        $turma = $this->criarTurma($curso, ['numero_vagas' => 1]);

        $primeiroPendente = User::factory()->create();
        $inscricaoPendente = $this->service()->inscrever($curso, $primeiroPendente, null, $turma);

        $segundoAluno = User::factory()->create();
        $this->aprovarDiretamente($curso, $turma, $segundoAluno);

        $aprovador = User::factory()->create();

        try {
            $this->service()->aprovar($inscricaoPendente, $aprovador);
            $this->fail('aprovar a segunda vaga de uma turma de 1 vaga deveria ser recusado');
        } catch (DomainException $e) {
            $this->assertStringContainsString('nao tem mais vagas', $e->getMessage());
        }

        $this->assertSame(StatusInscricao::PENDENTE, $inscricaoPendente->fresh()->status);
    }

    /** Sem o gate antigo de inscrever() (ver teste acima), aprovar() usa a mensagem nova do bloco 1. */
    public function test_aprovar_recusa_quando_curso_sem_turma_nao_tem_mais_vaga(): void
    {
        $curso = $this->criarCurso(['numero_vagas' => 1]);
        $jaAprovado = User::factory()->create();
        $this->aprovarDiretamente($curso, null, $jaAprovado);

        $pendente = User::factory()->create();
        $inscricaoPendente = $this->inscricaoPendenteDiretamente($curso, null, $pendente);
        $aprovador = User::factory()->create();

        try {
            $this->service()->aprovar($inscricaoPendente, $aprovador);
            $this->fail('aprovar sem vaga no curso deveria ser recusado');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Este treinamento nao tem mais vagas.', $e->getMessage());
        }
    }

    public function test_aprovar_funciona_quando_ha_vaga_livre(): void
    {
        $curso = $this->criarCurso();
        $turma = $this->criarTurma($curso, ['numero_vagas' => 1]);
        $aluno = User::factory()->create();
        $inscricao = $this->service()->inscrever($curso, $aluno, null, $turma);
        $aprovador = User::factory()->create();

        $this->service()->aprovar($inscricao, $aprovador);

        $this->assertSame(StatusInscricao::APROVADA, $inscricao->fresh()->status);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Cria uma inscricao ja APROVADA, ocupando vaga, sem passar pelas regras de periodo/vaga do service. */
    private function aprovarDiretamente($curso, $turma, User $aluno): Inscricao
    {
        return Inscricao::create([
            'treinamento_id' => $curso->id,
            'turma_id' => $turma?->id,
            'inscrito_type' => User::class,
            'inscrito_id' => $aluno->id,
            'status' => StatusInscricao::APROVADA->value,
            'data_inscricao' => now(),
            'data_aprovacao' => now(),
            'qr_code_token' => (string) \Illuminate\Support\Str::uuid(),
        ]);
    }

    /** Cria uma inscricao PENDENTE direto no banco, sem passar pelo gate de Treinamento::podeReceberInscricao(). */
    private function inscricaoPendenteDiretamente($curso, $turma, User $aluno): Inscricao
    {
        return Inscricao::create([
            'treinamento_id' => $curso->id,
            'turma_id' => $turma?->id,
            'inscrito_type' => User::class,
            'inscrito_id' => $aluno->id,
            'status' => StatusInscricao::PENDENTE->value,
            'data_inscricao' => now(),
            'qr_code_token' => (string) \Illuminate\Support\Str::uuid(),
        ]);
    }

    private function conexaoDoTreinamento(): string
    {
        return \App\Modules\Treinamento\Support\ConexaoTreinamento::nome();
    }
}
