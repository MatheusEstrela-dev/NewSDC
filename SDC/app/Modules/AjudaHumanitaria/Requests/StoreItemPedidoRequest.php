<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Requests;

use App\Modules\AjudaHumanitaria\Enums\TipoItemPedido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Inclusao de material no pedido (RN-08).
 *
 * A autorizacao depende do tipo: o item Solicitado e do municipio e exige
 * permissao de edicao; o Liberado e do CEDEC e exige a permissao propria.
 * Quem valida o momento em que cada tipo pode entrar e o ItemPedidoService,
 * pela RN-09.
 */
class StoreItemPedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $usuario = $this->user();

        if ($usuario === null) {
            return false;
        }

        return $this->ehItemLiberado()
            ? $usuario->can('humanitaria.pedidos.liberar_itens')
            : $usuario->can('humanitaria.pedidos.edit');
    }

    public function ehItemLiberado(): bool
    {
        return $this->input('tipo') === TipoItemPedido::Liberado->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'material_ah_id'       => ['nullable', 'integer', Rule::exists('materiais_ah', 'id')],
            'descricao_item'       => ['required', 'string', 'max:255'],
            'codigo'               => ['nullable', 'string', 'max:30'],
            'qtd'                  => ['required', 'integer', 'min:1'],
            'qtd_familia_atendida' => ['required', 'integer', 'min:0'],
            'tipo'                 => ['required', Rule::enum(TipoItemPedido::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'descricao_item.required' => 'Selecione o material.',
            'qtd.min'                 => 'A quantidade deve ser maior que zero.',
        ];
    }
}
