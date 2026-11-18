<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests\Gestao;

use App\Modules\Treinamento\Enums\TipoEventoCronograma;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Edicao de item do cronograma. Todo campo e opcional (so o que vier e
 * alterado); autorizacao fica na rota.
 */
class CronogramaItemUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['sometimes', 'required', 'string', 'max:160'],
            'descricao' => ['nullable', 'string'],
            'tipo' => ['sometimes', 'required', 'string', new Enum(TipoEventoCronograma::class)],
            'inicio' => ['sometimes', 'required', 'date'],
            'fim' => ['nullable', 'date', 'after_or_equal:inicio'],
            'local' => ['nullable', 'string', 'max:255'],
        ];
    }
}
