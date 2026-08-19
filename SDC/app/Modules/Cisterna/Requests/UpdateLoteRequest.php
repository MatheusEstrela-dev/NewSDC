<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests;

class UpdateLoteRequest extends StoreLoteRequest
{
    public function authorize(): bool
    {
        $lote = $this->route('lote');

        return $lote !== null && ($this->user()?->can('update', $lote) ?? false);
    }
}
