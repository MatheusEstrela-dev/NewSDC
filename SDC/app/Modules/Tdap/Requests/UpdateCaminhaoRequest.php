<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use App\Modules\Tdap\Requests\Concerns\ResolveIdDaRota;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class UpdateCaminhaoRequest extends AbstractCaminhaoRequest
{
    // Mesmo defeito do UpdateAtaRequest: `route('caminhao')` e o Model, e o
    // `(int)` estourava antes de qualquer validacao acontecer.
    use ResolveIdDaRota;

    public function authorize(): bool
    {
        return $this->user()?->can('tdap.caminhoes.edit') ?? false;
    }

    protected function placaUniqueRule(): Unique
    {
        return Rule::unique('tdap_caminhoes', 'placa')
            ->ignore($this->idDaRota('caminhao'))
            ->whereNull('deleted_at');
    }
}
