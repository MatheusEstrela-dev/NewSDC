<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

class StoreVistoriaRequest extends AbstractVistoriaRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.vistorias.create') ?? false;
    }
}
