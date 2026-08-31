<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use App\Modules\Tdap\Requests\Concerns\ResolveIdDaRota;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class UpdateAtaRequest extends AbstractAtaRequest
{
    // `$this->route('ata')` devolve o MODEL (SubstituteBindings ja rodou); o
    // `(int)` de antes lancava Error e o PUT respondia 500. Ver o trait.
    use ResolveIdDaRota;

    public function authorize(): bool
    {
        return $this->user()?->can('tdap.atas.edit') ?? false;
    }

    protected function numeroUniqueRule(): Unique
    {
        return Rule::unique('tdap_atas', 'numero')
            ->ignore($this->idDaRota('ata'))
            ->whereNull('deleted_at');
    }
}
