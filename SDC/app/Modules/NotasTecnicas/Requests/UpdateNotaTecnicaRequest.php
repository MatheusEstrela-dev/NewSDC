<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Dados descritivos da nota. O PDF nao passa por aqui: versao nova e outra rota. */
class UpdateNotaTecnicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'categoria_id' => ['required', 'integer', 'exists:nota_tecnica_categorias,id'],
            'resumo' => ['nullable', 'string', 'max:5000'],
            'vigencia_inicio' => ['nullable', 'date'],
            'rotas_relacionadas' => ['nullable', 'array'],
            'rotas_relacionadas.*' => ['string', Rule::in(array_keys(config('notas_tecnicas.telas_relacionadas', [])))],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'titulo' => 'título',
            'categoria_id' => 'pasta',
            'vigencia_inicio' => 'início da vigência',
        ];
    }
}
