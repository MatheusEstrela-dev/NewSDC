<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests\Api;

class ListarComunidadesRequest extends FiltroApiRequest
{
    /**
     * @return array<string, mixed>
     */
    protected function regrasDoFiltro(): array
    {
        return [
            'municipio_id' => ['sometimes', 'integer', 'exists:municipios,id'],
            'search' => ['sometimes', 'string', 'max:70'],
            'apenas_ativas' => ['sometimes', 'boolean'],
        ];
    }
}
