<?php

declare(strict_types=1);

namespace Tests\Feature\Treinamento;

use App\Modules\Treinamento\Enums\StatusExibicaoCurso;
use App\Modules\Treinamento\Enums\StatusTreinamento;
use App\Modules\Treinamento\Models\Treinamento;

/**
 * Issue #86, bloco 4.3: status de exibicao Publicado, Cancelado, Planejado (e
 * a precedencia com Em andamento/Concluido) via StatusExibicaoCurso::derivar()
 * e Treinamento::statusDeExibicao().
 *
 * Precedencia documentada em StatusExibicaoCurso: CANCELADO > CONCLUIDO >
 * EM_ANDAMENTO > (PLANEJADO + publicado = PUBLICADO) > PLANEJADO.
 */
class StatusExibicaoCursoTest extends TreinamentoGestorTestCase
{
    public function test_planejado_sem_publicacao_e_planejado(): void
    {
        $this->assertSame(
            StatusExibicaoCurso::PLANEJADO,
            StatusExibicaoCurso::derivar(StatusTreinamento::PLANEJADO, false),
        );
    }

    public function test_planejado_publicado_vira_publicado(): void
    {
        $this->assertSame(
            StatusExibicaoCurso::PUBLICADO,
            StatusExibicaoCurso::derivar(StatusTreinamento::PLANEJADO, true),
        );
    }

    public function test_em_andamento_tem_precedencia_sobre_publicado(): void
    {
        // Mesmo com publicado=true, EM_ANDAMENTO vence (a precedencia olha o
        // status primeiro: nunca mostra "Publicado" para um curso que ja comecou).
        $this->assertSame(
            StatusExibicaoCurso::EM_ANDAMENTO,
            StatusExibicaoCurso::derivar(StatusTreinamento::EM_ANDAMENTO, true),
        );
    }

    public function test_concluido_tem_precedencia_sobre_em_andamento_e_publicado(): void
    {
        $this->assertSame(
            StatusExibicaoCurso::CONCLUIDO,
            StatusExibicaoCurso::derivar(StatusTreinamento::CONCLUIDO, true),
        );
    }

    public function test_cancelado_tem_a_maior_precedencia_de_todas(): void
    {
        // CANCELADO vence mesmo com publicado=true: e a primeira regra da cadeia.
        $this->assertSame(
            StatusExibicaoCurso::CANCELADO,
            StatusExibicaoCurso::derivar(StatusTreinamento::CANCELADO, true),
        );
    }

    public function test_treinamento_statusdeexibicao_usa_estapublicado_do_modulo(): void
    {
        $curso = $this->criarCurso(['status' => StatusTreinamento::PLANEJADO->value]);
        $this->assertSame(StatusExibicaoCurso::PLANEJADO, $curso->statusDeExibicao());

        $curso->publicarNoPortal();
        $this->assertSame(StatusExibicaoCurso::PUBLICADO, $curso->fresh()->statusDeExibicao());
    }

    public function test_treinamento_cancelado_fica_cancelado_na_exibicao_mesmo_publicado(): void
    {
        $curso = $this->criarCurso(['status' => StatusTreinamento::PLANEJADO->value]);
        $curso->publicarNoPortal();
        $curso->refresh();

        $curso->update(['status' => StatusTreinamento::CANCELADO->value]);

        $this->assertSame(StatusExibicaoCurso::CANCELADO, $curso->fresh()->statusDeExibicao());
    }

    public function test_situacao_pausado_desativado_e_ortogonal_ao_status_de_exibicao(): void
    {
        // Um curso em andamento e pausado continua mostrando "Em andamento":
        // situacao (Pausado/Desativado) nao entra na cadeia de precedencia.
        $curso = $this->criarCurso(['status' => StatusTreinamento::EM_ANDAMENTO->value]);
        // 'situacao' nao esta no $fillable de Treinamento de proposito (so o
        // ciclo escreve nela, via query builder): update() direto na querybuilder,
        // igual a CicloDoCursoService.
        Treinamento::query()->whereKey($curso->id)->update(['situacao' => 'PAUSADO']);

        $this->assertSame(StatusExibicaoCurso::EM_ANDAMENTO, $curso->fresh()->statusDeExibicao());
        $this->assertSame('PAUSADO', $curso->fresh()->situacaoEfetiva()->value);
    }
}
