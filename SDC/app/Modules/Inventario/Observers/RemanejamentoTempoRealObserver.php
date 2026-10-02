<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Observers;

use App\Modules\Inventario\Models\Remanejamento;
use App\Modules\Shared\Events\RecursoAtualizado;

/**
 * Avisa a listagem de movimentacoes que um lote mudou. Todo caminho de escrita
 * (registrar, desfazer, editar, chamado, SEPLAG) salva o cabecalho do lote, entao
 * observar so ele cobre todos. O evento e ShouldDispatchAfterCommit.
 */
final class RemanejamentoTempoRealObserver
{
    public const RECURSO = 'inventario-remanejamentos';

    public function saved(Remanejamento $remanejamento): void
    {
        RecursoAtualizado::dispatch(self::RECURSO);
    }

    public function deleted(Remanejamento $remanejamento): void
    {
        RecursoAtualizado::dispatch(self::RECURSO);
    }
}
