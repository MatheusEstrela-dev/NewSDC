<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Requests;

use App\Modules\Plantao\Enums\NivelCombustivel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Retirada da chave. Espelha o MovimentacaoSaidaRequest com uma diferenca que e
 * a razao de ser da reserva: NAO aceita `condutor_id`. O condutor e sempre o
 * dono da reserva -- aceitar o campo permitiria retirar a chave em nome de
 * outra pessoa, que e exatamente o que a agenda obrigatoria impede.
 */
class ChaveCheckinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'saida_hodometro' => ['required', 'integer', 'min:0'],
            'saida_combustivel' => ['required', Rule::enum(NivelCombustivel::class)],
            'destino' => ['nullable', 'string', 'max:160'],
            'motivo' => ['nullable', 'string', 'max:160'],
            'plantao_id' => ['nullable', 'integer', 'exists:plantoes,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'saida_hodometro.required' => 'Informe o hodometro no momento da retirada.',
            'saida_combustivel.required' => 'Informe o nivel de combustivel.',
        ];
    }
}
