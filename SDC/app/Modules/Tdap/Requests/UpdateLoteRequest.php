<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use App\Modules\Tdap\Models\Lote;

class UpdateLoteRequest extends AbstractLoteRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.lotes.edit') ?? false;
    }

    /**
     * A rota usa binding implicito, entao `route('lote')` devolve o model —
     * converter o objeto direto para int estourava Error na validacao.
     */
    protected function ignoreId(): ?int
    {
        $lote = $this->route('lote');

        if ($lote instanceof Lote) {
            return $lote->id;
        }

        return (int) $lote ?: null;
    }
}
