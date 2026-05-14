<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

class StoreLoteRequest extends AbstractLoteRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.lotes.create') ?? false;
    }

    protected function ignoreId(): ?int
    {
        return null;
    }
}
