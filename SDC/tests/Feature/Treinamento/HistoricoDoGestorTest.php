<?php

declare(strict_types=1);

namespace Tests\Feature\Treinamento;

use App\Modules\Treinamento\Enums\TipoEventoCurso;
use App\Modules\Treinamento\Models\CursoEvento;
use App\Modules\Treinamento\Services\Gestao\GestaoCursoComandos;
use App\Modules\Treinamento\Services\Gestao\GestaoCursoConsulta;

/**
 * Issue #86, bloco 4.6: acoes do gestor geram linhas em treinamento.curso_eventos
 * (App\Modules\Treinamento\Services\HistoricoDoCursoService, chamado por
 * GestaoCursoComandos) e aparecem paginadas por GestaoCursoConsulta::historico().
 */
class HistoricoDoGestorTest extends TreinamentoGestorTestCase
{
    public function test_criar_turma_grava_evento_turma_criada(): void
    {
        $curso = $this->criarCurso();
        $gestor = $this->usuarioComCargo('manager');

        app(GestaoCursoComandos::class)->criarTurma($curso, [
            'nome' => 'Turma A',
            'data_inicio' => now()->toDateString(),
            'data_fim' => now()->addMonth()->toDateString(),
            'numero_vagas' => 10,
        ], $gestor);

        $this->assertDatabaseHas('treinamento.curso_eventos', [
            'treinamento_id' => $curso->id,
            'tipo' => TipoEventoCurso::TURMA_CRIADA->value,
            'autor_id' => $gestor->id,
        ], $this->conexaoDoTreinamento());
    }

    public function test_cancelar_curso_grava_evento_curso_cancelado_e_o_motivo(): void
    {
        $curso = $this->criarCurso();
        $gestor = $this->usuarioComCargo('manager');

        app(GestaoCursoComandos::class)->cancelarCurso($curso, $gestor, 'Falta de inscritos');

        $this->assertDatabaseHas('treinamento.curso_eventos', [
            'treinamento_id' => $curso->id,
            'tipo' => TipoEventoCurso::CURSO_CANCELADO->value,
            'autor_id' => $gestor->id,
        ], $this->conexaoDoTreinamento());

        // O motivo entra como uma segunda linha (cancelarCurso nao duplica o
        // evento principal, que ja vem de transicionarStatus - ver bloco 1).
        $linhaDoMotivo = CursoEvento::query()
            ->porTreinamento($curso->id)
            ->where('descricao', 'like', '%Falta de inscritos%')
            ->first();

        $this->assertNotNull($linhaDoMotivo);
    }

    public function test_definir_vagas_grava_evento_vagas_alteradas_com_antes_e_depois(): void
    {
        $curso = $this->criarCurso(['numero_vagas' => 5]);
        $gestor = $this->usuarioComCargo('manager');

        app(GestaoCursoComandos::class)->definirVagasDoCurso($curso, 20, $gestor);

        $evento = CursoEvento::query()
            ->porTreinamento($curso->id)
            ->where('tipo', TipoEventoCurso::VAGAS_ALTERADAS->value)
            ->first();

        $this->assertNotNull($evento);
        $this->assertSame(5, $evento->dados['antes']);
        $this->assertSame(20, $evento->dados['depois']);
    }

    public function test_adicionar_item_de_cronograma_grava_evento(): void
    {
        $curso = $this->criarCurso();
        $gestor = $this->usuarioComCargo('manager');

        app(GestaoCursoComandos::class)->adicionarItemDeCronograma($curso, [
            'titulo' => 'Aula inaugural',
            'inicio' => now()->addDay(),
        ], $gestor);

        $this->assertDatabaseHas('treinamento.curso_eventos', [
            'treinamento_id' => $curso->id,
            'tipo' => TipoEventoCurso::CRONOGRAMA_ADICIONADO->value,
        ], $this->conexaoDoTreinamento());
    }

    public function test_historico_paginado_traz_o_nome_do_autor_e_a_ordem_mais_recente_primeiro(): void
    {
        $curso = $this->criarCurso();
        $gestor = $this->usuarioComCargo('manager');
        $comandos = app(GestaoCursoComandos::class);

        $comandos->definirVagasDoCurso($curso, 30, $gestor);
        $comandos->definirPeriodoDoCurso($curso, now()->toDateString(), now()->addMonth()->toDateString(), $gestor);

        $pagina = app(GestaoCursoConsulta::class)->historico($curso, 15, 1);
        $itens = collect($pagina->items());

        $this->assertGreaterThanOrEqual(2, $itens->count());
        $this->assertSame($gestor->name, $itens->first()['autor_nome']);
        // Mais recente primeiro: o ultimo evento gravado (periodo) vem antes do de vagas.
        $this->assertSame(TipoEventoCurso::PERIODO_ALTERADO->value, $itens->first()['tipo']);
    }

    private function conexaoDoTreinamento(): string
    {
        return \App\Modules\Treinamento\Support\ConexaoTreinamento::nome();
    }
}
