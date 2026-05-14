<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StorePrestadorRequest extends AbstractPrestadorRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.prestadores.create') ?? false;
    }

    protected function cnpjUniqueRule(): Unique
    {
        return Rule::unique('tdap_prestadores', 'cnpj')->whereNull('deleted_at');
    }
}
