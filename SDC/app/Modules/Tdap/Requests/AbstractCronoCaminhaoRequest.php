<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Base SOLID para alocacao de Caminhao em Cronograma.
 *
 * Diferenca contextual entre Store e Update:
 *   - Store: usuario informa cronograma_id + caminhao_id (alocacao nova).
 *   - Update: cronograma_id e caminhao_id NAO podem mudar (sao a chave da
 *             alocacao); apenas comunidade_id/agua_prevista/num_viagens/ordem
 *             sao editaveis. Subclasses controlam via includeVinculo().
 */
abstract class AbstractCronoCaminhaoRequest extends FormRequest
{
    /**
     * Inclui validacao de cronograma_id + caminhao_id no payload?
     * Store: true (requer ambos).
     * Update: false (chave da alocacao e imutavel).
     */
    abstract protected function includeVinculo(): bool;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'comunidade_id' => ['nullable', 'integer'],
            'agua_prevista' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'num_viagens'   => ['required', 'integer', 'min:1', 'max:10000'],
            'ordem'         => ['nullable', 'integer', 'min:0', 'max:255'],
        ];

        if ($this->includeVinculo()) {
            $rules['cronograma_id'] = ['required', 'integer', Rule::exists('tdap_cronogramas', 'id')->whereNull('deleted_at')];
            $rules['caminhao_id']   = ['required', 'integer', Rule::exists('tdap_caminhoes', 'id')->whereNull('deleted_at')];
        }

        return $rules;
    }
}
