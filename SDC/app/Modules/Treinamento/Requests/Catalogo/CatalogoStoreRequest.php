<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests\Catalogo;

use Illuminate\Foundation\Http\FormRequest;

class CatalogoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('treinamento.cursos.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'sigla' => ['nullable', 'string', 'max:30'],
            'descricao' => ['nullable', 'string'],
            'ativo' => ['nullable', 'boolean'],
        ];
    }
}
