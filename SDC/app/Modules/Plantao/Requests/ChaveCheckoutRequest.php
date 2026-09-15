<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Requests;

use App\Modules\Plantao\Enums\NivelCombustivel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChaveCheckoutRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'retorno_hodometro.required' => 'Informe o hodometro na devolucao.',
            'retorno_combustivel.required' => 'Informe o nivel de combustivel.',
        ];
    }
}
