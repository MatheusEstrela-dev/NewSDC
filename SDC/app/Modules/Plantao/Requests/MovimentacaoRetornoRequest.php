<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Requests;

use App\Modules\Plantao\Enums\NivelCombustivel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MovimentacaoRetornoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'retorno_hodometro' => ['required', 'integer', 'min:0'],
            'retorno_combustivel' => ['required', Rule::enum(NivelCombustivel::class)],
            'alteracoes' => ['nullable', 'string', 'max:2000'],
            'retorno_em' => ['nullable', 'date'],
        ];
    }
}
