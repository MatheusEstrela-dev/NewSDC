<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Cisterna\Requests;

use App\Modules\Cisterna\DTOs\NotificacaoDTO;
use Illuminate\Validation\Rule;

class ListarNotificacoesRequest extends FiltroApiRequest
{
    /**
     * @return array<string, mixed>
     */
    protected function regrasDoFiltro(): array
    {
        return [
            // Alias curto, nao o FQCN: o consumidor nao precisa conhecer a
            // estrutura interna. NotificacaoResource devolve o mesmo alias.
            'notificavel_type' => ['sometimes', Rule::in(array_keys(NotificacaoDTO::TIPOS_PERMITIDOS))],
            'notificavel_id' => ['sometimes', 'integer', 'required_with:notificavel_type'],
            'apenas_pendentes' => ['sometimes', 'boolean'],
        ];
    }
}
