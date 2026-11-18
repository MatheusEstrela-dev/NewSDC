<?php

declare(strict_types=1);

namespace Tests\Feature\Treinamento;

/**
 * Issue #86, bloco 4.5: IDOR - item de cronograma de OUTRO curso na
 * URL tem de dar 404 (App\Modules\Treinamento\Controllers\Gestao\Concerns\EscopoDoCurso).
 * Testado com um usuario que tem todos os slugs de escrita (manager), para
 * isolar o 404 do escopo de um eventual 403 de permissao.
 */
class GestaoIdorTest extends TreinamentoGestorTestCase
{
    public function test_atualizar_item_de_cronograma_de_outro_curso_pela_url_da_404(): void
    {
        $this->logarComo($this->usuarioComCargo('manager'));

        $cursoA = $this->criarCurso();
        $comandos = app(\App\Modules\Treinamento\Services\Gestao\GestaoCursoComandos::class);
        $itemDeA = $comandos->adicionarItemDeCronograma($cursoA, [
            'titulo' => 'Aula do curso A',
            'inicio' => now()->addDay(),
        ], $this->usuarioComCargo('manager'));

        $cursoB = $this->criarCurso();

        $this->put(
            route('treinamentos.gestor.cronograma.update', [$cursoB->id, $itemDeA->id]),
            ['titulo' => 'Tentativa de IDOR'],
        )->assertNotFound();

        $this->assertSame('Aula do curso A', $itemDeA->fresh()->titulo);
    }

    public function test_remover_item_de_cronograma_de_outro_curso_pela_url_da_404(): void
    {
        $this->logarComo($this->usuarioComCargo('manager'));

        $cursoA = $this->criarCurso();
        $comandos = app(\App\Modules\Treinamento\Services\Gestao\GestaoCursoComandos::class);
        $itemDeA = $comandos->adicionarItemDeCronograma($cursoA, [
            'titulo' => 'Aula do curso A',
            'inicio' => now()->addDay(),
        ], $this->usuarioComCargo('manager'));

        $cursoB = $this->criarCurso();

        $this->delete(route('treinamentos.gestor.cronograma.destroy', [$cursoB->id, $itemDeA->id]))
            ->assertNotFound();

        $this->assertNotSoftDeleted($itemDeA->fresh());
    }
}
