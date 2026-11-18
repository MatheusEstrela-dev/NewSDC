<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests\Gestao;

use App\Modules\Treinamento\Enums\TipoEventoCronograma;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Novo item do cronograma do curso. Autorizacao fica
 * na rota.
 */
class CronogramaItemStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:160'],
            'descricao' => ['nullable', 'string'],
            'tipo' => ['nullable', 'string', new Enum(TipoEventoCronograma::class)],
            'inicio' => ['required', 'date'],
            'fim' => ['nullable', 'date', 'after_or_equal:inicio'],
            'local' => ['nullable', 'string', 'max:255'],
        ];
    }
}
