<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services\Conclusao;

use App\Modules\Treinamento\Contracts\CriterioConclusao;
use App\Modules\Treinamento\DTOs\ResultadoConclusao;
use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Enums\StatusTreinamento;
use App\Modules\Treinamento\Models\Inscricao;

/**
 * Eventos presenciais e online ao vivo: inscricao aprovada + treinamento
 * concluido + frequencia minima atingida. Nao ha nota (aproveitamento null).
 */
final class ConclusaoPorFrequencia implements CriterioConclusao
{
    public function avaliar(Inscricao $inscricao): ?ResultadoConclusao
    {
        $inscricao->loadMissing('treinamento');
        $treinamento = $inscricao->treinamento;

        $elegivel = $inscricao->status === StatusInscricao::APROVADA
            && $treinamento->status === StatusTreinamento::CONCLUIDO
            && $inscricao->estaAprovadoPorFrequencia();

        if (! $elegivel) {
            return null;
        }

        return new ResultadoConclusao($treinamento->finalizado_em ?? now(), null);
    }
}
