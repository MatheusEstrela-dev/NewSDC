<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarCategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('demandas.chamados.manage');
    }

    public function rules(): array
    {
        $categoria = $this->route('categoria');
        $sometimes = $categoria !== null ? ['sometimes'] : [];
        $pai = Rule::exists('demanda_categorias', 'id')->whereNull('parent_id');
        if ($categoria !== null) {
            $pai = $pai->whereNot('id', $categoria->id);
        }

        return [
            'nome' => [...$sometimes, 'required', 'string', 'max:100'],
            'descricao' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'parent_id' => ['sometimes', 'nullable', 'integer', $pai],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }
}
