<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Requests;

use App\Modules\Plantao\Enums\NivelCombustivel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MovimentacaoSaidaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'condutor_id' => ['required', 'integer', 'exists:users,id'],
            'saida_hodometro' => ['required', 'integer', 'min:0'],
            'saida_combustivel' => ['required', Rule::enum(NivelCombustivel::class)],
            'destino' => ['nullable', 'string', 'max:160'],
            'motivo' => ['nullable', 'string', 'max:160'],
            'plantao_id' => ['nullable', 'integer', 'exists:plantoes,id'],
            'saida_em' => ['nullable', 'date'],
        ];
    }
}
