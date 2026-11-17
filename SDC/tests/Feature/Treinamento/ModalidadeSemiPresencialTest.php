<?php

declare(strict_types=1);

namespace Tests\Feature\Treinamento;

use App\Modules\Treinamento\Enums\TipoTreinamento;

/**
 * Issue #86, bloco 4.2: modalidade SEMI_PRESENCIAL aceita no Store/Update do
 * curso (bloco 1 pedia a troca de `in:PRESENCIAL,ONLINE,EAD` por
 * `new Enum(TipoTreinamento::class)` em StoreTreinamentoRequest e
 * UpdateTreinamentoRequest), `requerLocal()` true e nao e EAD.
 */
class ModalidadeSemiPresencialTest extends TreinamentoGestorTestCase
{
    public function test_enum_semi_presencial_requer_local_nao_tem_sala_de_aula_e_usa_turma(): void
    {
        $tipo = TipoTreinamento::SEMI_PRESENCIAL;

        $this->assertTrue($tipo->requerLocal());
        $this->assertFalse($tipo->temSalaDeAula());
        $this->assertTrue($tipo->usaTurma());
    }

    public function test_curso_semi_presencial_nao_e_tratado_como_ead(): void
    {
        $curso = $this->criarCurso(['tipo' => TipoTreinamento::SEMI_PRESENCIAL->value]);

        $this->assertFalse($curso->isEad());
        $this->assertTrue($curso->usaTurma());
    }

    public function test_store_do_curso_aceita_semi_presencial(): void
    {
        $admin = $this->logarComo($this->usuarioComCargo('admin'));

        $resposta = $this->post(route('treinamentos.admin.store'), [
            'titulo' => 'Curso semi-presencial de teste',
            'carga_horaria' => 20,
            'categoria' => 'CURSO',
            'tipo' => 'SEMI_PRESENCIAL',
            'publico_alvo' => 'SERVIDORES',
        ]);

        $resposta->assertSessionHasNoErrors();
        $resposta->assertRedirect();

        $this->assertDatabaseHas('treinamento.treinamentos', [
            'titulo' => 'Curso semi-presencial de teste',
            'tipo' => 'SEMI_PRESENCIAL',
        ], $this->conexaoDoTreinamento());
    }

    public function test_update_do_curso_aceita_semi_presencial(): void
    {
        $admin = $this->logarComo($this->usuarioComCargo('admin'));
        $curso = $this->criarCurso(['tipo' => TipoTreinamento::PRESENCIAL->value]);

        $resposta = $this->put(route('treinamentos.update', $curso->id), [
            'tipo' => 'SEMI_PRESENCIAL',
        ]);

        $resposta->assertSessionHasNoErrors();
        $this->assertSame(TipoTreinamento::SEMI_PRESENCIAL, $curso->fresh()->tipo);
    }

    private function conexaoDoTreinamento(): string
    {
        return \App\Modules\Treinamento\Support\ConexaoTreinamento::nome();
    }
}
