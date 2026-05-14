<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class UpdateCronogramaRequest extends AbstractCronogramaRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.cronogramas.edit') ?? false;
    }

    protected function numeroUniqueRule(): Unique
    {
        return Rule::unique('tdap_cronogramas', 'numero')
            ->ignore((int) $this->route('cronograma'))
            ->whereNull('deleted_at');
    }
}
