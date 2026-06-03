<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class UpdatePrestadorRequest extends AbstractPrestadorRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.prestadores.edit') ?? false;
    }

    protected function cnpjUniqueRule(): Unique
    {
        return Rule::unique('tdap_prestadores', 'cnpj')
            ->ignore((int) $this->route('prestador'))
            ->whereNull('deleted_at');
    }
}
