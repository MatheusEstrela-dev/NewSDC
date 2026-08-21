<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use App\Modules\Tdap\Requests\Concerns\ResolveIdDaRota;

class UpdateLoteRequest extends AbstractLoteRequest
{
    /**
     * Este era o unico Update*Request que tratava o parametro-Model, com
     * codigo proprio. A logica virou o trait ResolveIdDaRota e agora os cinco
     * (prestador, ata, caminhao, cronograma, lote) usam a mesma.
     */
    use ResolveIdDaRota;

    public function authorize(): bool
    {
        return $this->user()?->can('tdap.lotes.edit') ?? false;
    }

    protected function ignoreId(): ?int
    {
        return $this->idDaRota('lote');
    }
}
