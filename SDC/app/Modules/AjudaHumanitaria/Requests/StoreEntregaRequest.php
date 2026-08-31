<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Entrega de material a um beneficiario (RN-17).
 *
 * O limite de quantidade nao esta aqui de proposito: quem verifica o saldo do
 * item e a specification SaldoEntregaBeneficiarios (RN-18), que compara com o
 * que ja foi entregue. Uma regra de validacao fixa nao teria essa informacao.
 */
class StoreEntregaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('humanitaria.prestacao.lancar') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'prestacao_conta_item_id' => ['required', 'integer', Rule::exists('prestacao_conta_itens', 'id')],
            'nome_beneficiario'       => ['required', 'string', 'max:255'],
            'rg'                      => ['nullable', 'string', 'max:30'],
            'comunidade'              => ['nullable', 'string', 'max:255'],
            'qtd'                     => ['required', 'integer', 'min:1'],
            'data_entrega'            => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome_beneficiario.required' => 'Informe o nome do beneficiário.',
            'qtd.min'                    => 'A quantidade entregue deve ser maior que zero.',
            'data_entrega.required'      => 'Informe a data da entrega.',
        ];
    }
}
