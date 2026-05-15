<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

class UpdateLoteRequest extends AbstractLoteRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.lotes.edit') ?? false;
    }

    protected function ignoreId(): ?int
    {
        return (int) $this->route('lote') ?: null;
    }
}
