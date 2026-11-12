<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

use App\Modules\Treinamento\Contracts\CriterioConclusao;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\Conclusao\ConclusaoPorFrequencia;
use App\Modules\Treinamento\Services\Conclusao\ConclusaoPorTrilha;

/**
 * Escolhe o criterio de conclusao pelo tipo do treinamento. Resolucao direta
 * das classes concretas (sem registro em provider).
 */
final class CriteriosDeConclusao
{
    public function para(Treinamento $treinamento): CriterioConclusao
    {
        return $treinamento->isEad()
            ? app(ConclusaoPorTrilha::class)
            : app(ConclusaoPorFrequencia::class);
    }
}
