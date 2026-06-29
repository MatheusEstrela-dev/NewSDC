<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejeitarComunidadeSolicitacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pmda.comunidades.aprovar') ?? false;
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'max:255'],
        ];
    }
}
