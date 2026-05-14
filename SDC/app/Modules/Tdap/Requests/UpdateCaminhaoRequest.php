<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class UpdateCaminhaoRequest extends AbstractCaminhaoRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.caminhoes.edit') ?? false;
    }

    protected function placaUniqueRule(): Unique
    {
        return Rule::unique('tdap_caminhoes', 'placa')
            ->ignore((int) $this->route('caminhao'))
            ->whereNull('deleted_at');
    }
}
