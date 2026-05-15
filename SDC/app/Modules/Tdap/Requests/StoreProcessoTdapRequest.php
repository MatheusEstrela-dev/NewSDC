<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreProcessoTdapRequest extends AbstractProcessoTdapRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.processos.create') ?? false;
    }

    protected function numeroUniqueRule(): Unique
    {
        return Rule::unique('tdap_processos', 'numero')->whereNull('deleted_at');
    }
}
