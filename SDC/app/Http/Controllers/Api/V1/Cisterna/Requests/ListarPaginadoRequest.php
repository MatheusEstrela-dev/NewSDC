<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests\Api;

/**
 * Lotes e ordens de servico: paginacao, sem filtro proprio alem do lote.
 */
class ListarPaginadoRequest extends FiltroApiRequest
{
    /**
     * @return array<string, mixed>
     */
    protected function regrasDoFiltro(): array
    {
        return [
            'lote_id' => ['sometimes', 'integer', 'exists:cisterna_lotes,id'],
        ];
    }
}
