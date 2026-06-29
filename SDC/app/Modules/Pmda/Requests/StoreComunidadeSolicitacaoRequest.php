<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComunidadeSolicitacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pmda.comunidades.solicitar') ?? false;
    }

    public function rules(): array
    {
        return [
            'nome'      => ['required', 'string', 'max:150'],
            'latitude'  => ['nullable', 'string', 'max:30'],
            'longitude' => ['nullable', 'string', 'max:30'],
        ];
    }
}
