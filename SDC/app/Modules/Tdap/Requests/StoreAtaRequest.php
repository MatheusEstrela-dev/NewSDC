<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreAtaRequest extends AbstractAtaRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.atas.create') ?? false;
    }

    protected function numeroUniqueRule(): Unique
    {
        return Rule::unique('tdap_atas', 'numero')->whereNull('deleted_at');
    }
}
