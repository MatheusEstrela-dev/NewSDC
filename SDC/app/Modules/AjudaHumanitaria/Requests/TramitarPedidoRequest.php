<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Requests;

use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Mudanca de status do pedido (RN-12).
 *
 * A validacao aqui e apenas de forma: o status alvo existe e a observacao
 * cabe no campo. Se a transicao e legitima quem decide e o PedidoAhWorkflow,
 * pelo TramitacaoService.
 */
class TramitarPedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('humanitaria.pedidos.tramitar') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status_alvo' => ['required', 'integer', Rule::enum(StatusPedidoAh::class)],
            'observacao'  => ['nullable', 'string', 'max:200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status_alvo.required' => 'Escolha para onde o pedido deve seguir.',
            'observacao.max'       => 'A observação deve ter no máximo 200 caracteres.',
        ];
    }
}
