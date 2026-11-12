<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services\Conclusao;

use App\Modules\Treinamento\Contracts\CriterioConclusao;
use App\Modules\Treinamento\DTOs\ResultadoConclusao;
use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Services\SituacaoProvaService;
use App\Modules\Treinamento\Services\TrilhaService;

/**
 * Curso EAD: inscricao aprovada + todas as pastas concluidas (spec 8.4).
 * Aproveitamento = media das notas finais das provas do curso.
 *
 * Quem ja concluiu fica congelado (spec 8.7): com concluido_em gravado, o
 * resultado e o gravado, e a trilha atual nao e consultada.
 */
final class ConclusaoPorTrilha implements CriterioConclusao
{
    public function __construct(
        private readonly TrilhaService $trilha,
        private readonly SituacaoProvaService $provas,
    ) {}

    public function avaliar(Inscricao $inscricao): ?ResultadoConclusao
    {
        if ($inscricao->status !== StatusInscricao::APROVADA) {
            return null;
        }

        if ($inscricao->concluido_em !== null) {
            return new ResultadoConclusao($inscricao->concluido_em, $inscricao->aproveitamento);
        }

        if (! $this->trilha->concluida($inscricao)) {
            return null;
        }

        return new ResultadoConclusao(now(), $this->aproveitamento($inscricao));
    }

    private function aproveitamento(Inscricao $inscricao): ?float
    {
        $notas = [];
        foreach ($this->provas->daInscricao($inscricao) as $situacao) {
            if ($situacao->notaFinal !== null) {
                $notas[] = $situacao->notaFinal;
            }
        }

        return $notas === [] ? null : round(array_sum($notas) / count($notas), 2);
    }
}
