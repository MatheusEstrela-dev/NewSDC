<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests;

class UpdateComunidadeRequest extends StoreComunidadeRequest
{
    public function authorize(): bool
    {
        $comunidade = $this->route('comunidade');

        return $comunidade !== null && ($this->user()?->can('update', $comunidade) ?? false);
    }
}
