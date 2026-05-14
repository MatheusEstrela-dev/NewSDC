<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreCronogramaRequest extends AbstractCronogramaRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.cronogramas.create') ?? false;
    }

    protected function numeroUniqueRule(): Unique
    {
        return Rule::unique('tdap_cronogramas', 'numero')->whereNull('deleted_at');
    }
}
