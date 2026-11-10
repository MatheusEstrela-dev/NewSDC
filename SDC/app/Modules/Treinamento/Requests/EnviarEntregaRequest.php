<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnviarEntregaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'arquivo' => ['required', 'file', 'max:51200'],
            'comentario' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'arquivo.required' => 'Anexe o arquivo do projeto.',
            'arquivo.max' => 'O arquivo pode ter no maximo 50 MB.',
        ];
    }
}
