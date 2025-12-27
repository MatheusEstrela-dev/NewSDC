<?php

namespace App\Modules\Tdap\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRecebimentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ordem_compra_id' => 'nullable|integer',
            'nota_fiscal' => 'required|string|max:100',
            'placa_veiculo' => 'required|string|max:20',
            'transportadora' => 'nullable|string|max:255',
            'motorista_nome' => 'required|string|max:255',
            'motorista_documento' => 'nullable|string|max:20',
            'doca_descarga' => 'nullable|string|max:20',
            'data_chegada' => 'nullable|date',
            'observacoes' => 'nullable|string',
            'itens' => 'required|array|min:1',
            'itens.*.product_id' => 'required|integer|exists:tdap_products,id',
            'itens.*.quantidade_nota' => 'required|integer|min:1',
            'itens.*.quantidade_conferida' => 'required|integer|min:0',
            'itens.*.numero_lote' => 'nullable|string|max:100',
            'itens.*.data_fabricacao' => 'nullable|date',
            'itens.*.data_validade' => 'nullable|date',
            'itens.*.tem_avaria' => 'boolean',
            'itens.*.tipo_avaria' => 'nullable|string',
            'itens.*.quantidade_avariada' => 'nullable|integer|min:0',
            'itens.*.observacoes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'nota_fiscal.required' => 'A nota fiscal é obrigatória',
            'placa_veiculo.required' => 'A placa do veículo é obrigatória',
            'motorista_nome.required' => 'O nome do motorista é obrigatório',
            'itens.required' => 'É necessário informar pelo menos um item',
            'itens.min' => 'É necessário informar pelo menos um item',
        ];
    }
}
