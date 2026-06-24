<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRepresentanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pmda.representantes.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'nome'     => ['required', 'string', 'max:100'],
            'tel'      => ['nullable', 'string', 'max:20'],
            'email'    => ['nullable', 'email', 'max:110'],
            'cpf'      => ['nullable', 'string', 'max:14'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
        ];
    }
}
