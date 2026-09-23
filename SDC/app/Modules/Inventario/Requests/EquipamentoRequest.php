<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EquipamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->isMethod('post')
            ? 'inventario.equipamentos.create'
            : 'inventario.equipamentos.edit');
    }

    public function rules(): array
    {
        $id = $this->route('equipamento');
        return [
            'nome' => ['required', 'string', 'max:255'],
            'patrimonio' => ['required', 'string', 'max:100', Rule::unique('inventario_ti_equipamentos', 'patrimonio')->ignore($id)],
            'numero_serie' => ['nullable', 'string', 'max:150'],
            'ramal' => ['nullable', 'string', 'max:30'],
            'categoria_id' => ['nullable', 'integer', 'exists:inventario_ti_categorias,id'],
            'unidade' => ['nullable', 'string', 'max:150'],
            'diretoria' => ['nullable', 'string', 'max:150'],
            'emprestavel' => ['required', 'boolean'],
            'quantidade' => ['required', 'integer', 'min:1', 'max:100000'],
            'observacao' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
