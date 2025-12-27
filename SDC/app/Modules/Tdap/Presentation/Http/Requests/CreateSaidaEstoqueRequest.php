<?php

namespace App\Modules\Tdap\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSaidaEstoqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:tdap_products,id',
            'quantidade' => 'required|integer|min:1',
            'data_movimentacao' => 'nullable|date',
            'origem' => 'nullable|string|max:255',
            'destino' => 'nullable|string|max:255',
            'solicitante_id' => 'nullable|integer|exists:users,id',
            'responsavel_id' => 'required|integer|exists:users,id',
            'documento_referencia' => 'nullable|string|max:255',
            'observacoes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'O produto é obrigatório',
            'product_id.exists' => 'Produto não encontrado',
            'quantidade.required' => 'A quantidade é obrigatória',
            'quantidade.min' => 'A quantidade deve ser maior que zero',
            'responsavel_id.required' => 'O responsável é obrigatório',
            'responsavel_id.exists' => 'Responsável não encontrado',
        ];
    }
}
