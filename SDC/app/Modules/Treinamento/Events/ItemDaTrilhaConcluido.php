<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Um item da trilha passou a CONCLUIDO para a inscricao (PDF/video lido ate o
 * fim, projeto aprovado, prova aprovada). Disparado so na transicao, pelo
 * ProgressoService, e so depois do commit: quem ouve ve o progresso gravado.
 *
 * Ponto unico para avaliar a conclusao do curso: ver
 * Listeners\AvaliarConclusaoDaInscricao.
 */
final class ItemDaTrilhaConcluido implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $inscricaoId,
        public readonly int $moduloItemId,
    ) {}
}
