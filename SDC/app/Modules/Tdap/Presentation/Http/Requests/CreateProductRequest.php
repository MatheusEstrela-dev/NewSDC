<?php

namespace App\Modules\Tdap\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => 'required|string|max:50|unique:tdap_products,codigo',
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'tipo' => 'required|string|in:cesta_basica,kit_limpeza,colchao,outros',
            'eh_composto' => 'boolean',
            'volume_unitario_m3' => 'nullable|numeric|min:0',
            'peso_unitario_kg' => 'nullable|numeric|min:0',
            'estoque_minimo' => 'nullable|integer|min:0',
            'estoque_maximo' => 'nullable|integer|min:0',
            'estrategia_armazenamento' => 'nullable|string|in:fifo,fefo,lifo',
            'grupo_risco' => 'required|string|in:ALIMENTO,QUIMICO,GERAL',
            'dias_alerta_validade' => 'nullable|integer|min:1',
            'observacoes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'O código é obrigatório',
            'codigo.unique' => 'Este código já está em uso',
            'nome.required' => 'O nome é obrigatório',
            'tipo.required' => 'O tipo é obrigatório',
            'tipo.in' => 'Tipo inválido',
            'grupo_risco.required' => 'O grupo de risco é obrigatório',
            'grupo_risco.in' => 'Grupo de risco inválido',
        ];
    }
}
