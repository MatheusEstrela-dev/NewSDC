<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests\Professor;

use Illuminate\Foundation\Http\FormRequest;

class CorrigirEntregaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'aprovar' => ['required', 'boolean'],
            'parecer' => ['nullable', 'string', 'max:2000', 'required_if:aprovar,false,0'],
        ];
    }

    public function messages(): array
    {
        return [
            'parecer.required_if' => 'Informe o parecer para devolver a entrega.',
        ];
    }
}
