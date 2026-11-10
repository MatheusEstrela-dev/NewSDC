<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests\Professor;

use Illuminate\Foundation\Http\FormRequest;

class SalvarPastaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'carga_horaria' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }
}
