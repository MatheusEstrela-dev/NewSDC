<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AvaliarDcoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pae.protocolos.validar') ?? false;
    }

    public function rules(): array
    {
        return [
            'resultado' => ['required', Rule::in(['aplicavel', 'nao_aplicavel'])],
            'fundamentacao' => ['required', 'string', 'max:5000'],
            'num_sei' => ['required', 'string', 'max:100'],
            'chave_idempotencia' => ['required', 'uuid'],
        ];
    }
}
