<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests;

class UpdateOrdemServicoRequest extends StoreOrdemServicoRequest
{
    public function authorize(): bool
    {
        $os = $this->route('ordemServico');

        return $os !== null && ($this->user()?->can('update', $os) ?? false);
    }
}
