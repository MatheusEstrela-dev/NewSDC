<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreCaminhaoRequest extends AbstractCaminhaoRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.caminhoes.create') ?? false;
    }

    protected function placaUniqueRule(): Unique
    {
        return Rule::unique('tdap_caminhoes', 'placa')->whereNull('deleted_at');
    }
}
