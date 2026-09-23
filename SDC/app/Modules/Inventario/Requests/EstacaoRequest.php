<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->isMethod('post')
            ? 'inventario.equipamentos.create'
            : 'inventario.equipamentos.edit');
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:120', Rule::unique('inventario_ti_estacoes')->ignore($this->route('estacao'))],
            'ponto_rede' => ['nullable', 'string', 'max:120'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
