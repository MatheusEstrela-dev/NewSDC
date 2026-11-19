<?php

declare(strict_types=1);

namespace Tests\Feature\Treinamento;

use App\Models\User;
use App\Modules\Treinamento\Enums\StatusTreinamento;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use App\Modules\Treinamento\Models\Treinamento;

/**
 * Issue #86, bloco 4.4: matriz de autorizacao da Area do Gestor.
 *
 * - Professor designado (so treinamento.professor.acessar, sem gestor.acessar,
 *   configurar, cancelar, delete nem edit): 403 em DELETE do curso, transicao
 *   para CANCELADO, edit/update, designar professor, desativar/reativar e em
 *   toda rota treinamentos.gestor.* de escrita (e de leitura, ja que ele nem
 *   tem gestor.acessar); 200/redirect no conteudo/alunos do curso que leciona
 *   e em pausar (ProfessorService::gerencia: designado OU cursos.edit).
 * - Gestor (cargo manager) e Administrador: 200/redirect nas mesmas acoes,
 *   exceto DELETE do curso, que so Administrador (e super-admin) tem.
 * - Usuario so com treinamento.gestor.acessar (sem configurar/cancelar): le a
 *   gestao (200), mas 403 na escrita.
 */
class GestaoAutorizacaoTest extends TreinamentoGestorTestCase
{
    // ── Professor designado, sem nenhum slug do gestor ──────────────────────

    public function test_professor_designado_sem_slugs_leva_403_em_acoes_de_gestor_e_encerramento(): void
    {
        $curso = $this->criarCursoEad();
        $professor = $this->usuarioComPermissoes(['treinamento.professor.acessar']);
        $this->designarProfessor($curso, $professor);
        $this->logarComo($professor);

        $this->delete(route('treinamentos.destroy', $curso->id))->assertForbidden();

        $this->post(route('treinamentos.status', $curso->id), ['status' => 'CANCELADO'])->assertForbidden();

        $this->get(route('treinamentos.edit', $curso->id))->assertForbidden();
        $this->put(route('treinamentos.update', $curso->id), ['titulo' => 'Outro titulo'])->assertForbidden();

        $outroUsuario = User::factory()->create();
        $this->post(route('treinamentos.professores.store', $curso->id), ['user_id' => $outroUsuario->id])->assertForbidden();

        $this->post(route('treinamentos.professor.desativar', $curso->id))->assertForbidden();
        $this->post(route('treinamentos.professor.reativar', $curso->id))->assertForbidden();
    }

    public function test_professor_designado_sem_slugs_leva_403_em_toda_rota_gestor(): void
    {
        $curso = $this->criarCurso();
        $professor = $this->usuarioComPermissoes(['treinamento.professor.acessar']);
        $this->logarComo($professor);

        $cursoEad = $this->criarCursoEad();
        $this->designarProfessor($cursoEad, $professor);
        $this->get(route('treinamentos.professor.gestao', $cursoEad->id))->assertForbidden();
        $this->get(route('treinamentos.gestor.show', $curso->id))->assertForbidden();
        $this->put(route('treinamentos.gestor.vagas.update', $curso->id), ['numero_vagas' => 5])->assertForbidden();
        $this->put(route('treinamentos.gestor.periodo.update', $curso->id), [])->assertForbidden();
        $this->post(route('treinamentos.gestor.cronograma.store', $curso->id), $this->dadosDeCronograma())->assertForbidden();
        $this->post(route('treinamentos.gestor.cancelar', $curso->id), [])->assertForbidden();
    }

    public function test_professor_designado_mantem_200_no_conteudo_alunos_e_pausar(): void
    {
        $curso = $this->criarCursoEad(['status' => StatusTreinamento::EM_ANDAMENTO->value]);
        $curso->publicarNoPortal();
        $professor = $this->usuarioComPermissoes(['treinamento.professor.acessar']);
        $this->designarProfessor($curso->fresh(), $professor);
        $this->logarComo($professor);

        $this->get(route('treinamentos.professor.conteudo', $curso->id))->assertOk();
        $this->get(route('treinamentos.professor.alunos', $curso->id))->assertOk();

        $this->post(route('treinamentos.professor.pausar', $curso->id))
            ->assertSessionHasNoErrors()
            ->assertRedirect();
    }

    // ── Gestor (cargo manager) ───────────────────────────────────────────────

    public function test_gestor_tem_200_ou_redirect_nas_acoes_de_configuracao_e_cancelamento(): void
    {
        $curso = $this->criarCurso();
        $gestor = $this->logarComo($this->usuarioComCargo('manager'));

        $this->get(route('treinamentos.professor.gestao', $this->criarCursoEad()->id))->assertOk();
        $this->get(route('treinamentos.gestor.show', $curso->id))->assertOk();

        $this->put(route('treinamentos.gestor.periodo.update', $curso->id), [])->assertSessionHasNoErrors();

        $this->post(route('treinamentos.gestor.cronograma.store', $curso->id), $this->dadosDeCronograma())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $cursoParaCancelar = $this->criarCurso();
        $this->post(route('treinamentos.gestor.cancelar', $cursoParaCancelar->id), [])
            ->assertSessionHasNoErrors()
            ->assertRedirect();
        $this->assertSame(StatusTreinamento::CANCELADO, $cursoParaCancelar->fresh()->status);

        $this->get(route('treinamentos.edit', $curso->id))->assertOk();
        $this->put(route('treinamentos.update', $curso->id), ['titulo' => 'Atualizado pelo gestor'])->assertSessionHasNoErrors();

        $this->post(route('treinamentos.status', $curso->id), ['status' => 'CANCELADO'])->assertSessionHasNoErrors();
    }

    public function test_gestor_nao_tem_permissao_de_excluir_curso(): void
    {
        $curso = $this->criarCurso();
        $this->logarComo($this->usuarioComCargo('manager'));

        $this->delete(route('treinamentos.destroy', $curso->id))->assertForbidden();
    }

    // ── Administrador ─────────────────────────────────────────────────────

    public function test_administrador_tem_200_ou_redirect_em_todas_as_acoes_incluindo_exclusao(): void
    {
        $curso = $this->criarCurso();
        $this->logarComo($this->usuarioComCargo('admin'));

        $this->get(route('treinamentos.gestor.show', $curso->id))->assertOk();

        $outroCurso = $this->criarCurso();
        $this->delete(route('treinamentos.destroy', $outroCurso->id))->assertRedirect();
        $this->assertSoftDeleted(Treinamento::class, ['id' => $outroCurso->id], $this->conexaoDoTreinamento());
    }

    // ── gestor.acessar isolado, sem configurar/cancelar ─────────────────────

    public function test_usuario_so_com_gestor_acessar_le_mas_nao_escreve(): void
    {
        $curso = $this->criarCurso();
        $usuario = $this->usuarioComPermissoes(['treinamento.gestor.acessar']);
        $this->logarComo($usuario);

        $this->get(route('treinamentos.gestor.show', $curso->id))->assertOk();

        $this->put(route('treinamentos.gestor.vagas.update', $curso->id), ['numero_vagas' => 5])->assertForbidden();
        $this->post(route('treinamentos.gestor.cancelar', $curso->id), [])->assertForbidden();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function dadosDeCronograma(): array
    {
        return [
            'titulo' => 'Aula de teste',
            'inicio' => now()->addDay()->toDateTimeString(),
        ];
    }

    private function conexaoDoTreinamento(): string
    {
        return \App\Modules\Treinamento\Support\ConexaoTreinamento::nome();
    }
}
