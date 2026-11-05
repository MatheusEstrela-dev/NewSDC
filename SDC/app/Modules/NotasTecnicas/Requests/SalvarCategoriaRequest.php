<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Criar ou renomear pasta. Mover de pai so na criacao (parent_id). */
class SalvarCategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', 'integer', 'exists:nota_tecnica_categorias,id'],
        ];
    }
}
