<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

class UpdateCronoCaminhaoRequest extends AbstractCronoCaminhaoRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.cronogramas.edit') ?? false;
    }

    protected function includeVinculo(): bool
    {
        return false;
    }
}
