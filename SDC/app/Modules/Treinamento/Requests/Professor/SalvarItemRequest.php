<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests\Professor;

use App\Modules\Treinamento\Enums\TipoItem;
use App\Modules\Treinamento\Models\ModuloItem;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Criacao (tipo vem do corpo) e edicao (tipo e o do item da rota, imutavel).
 */
class SalvarItemRequest extends FormRequest
{
    private const MAX_KB = 51200;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $item = $this->route('item');
        $edicao = $item instanceof ModuloItem;
        $tipo = $edicao ? $item->tipo->value : $this->input('tipo');
        $pdf = $tipo === TipoItem::PDF->value;

        $regras = [
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'obrigatorio' => ['required', 'boolean'],
            'total_paginas' => $pdf
                ? [$edicao ? 'sometimes' : 'required', 'integer', 'min:1']
                : ['nullable', 'integer', 'min:1'],
            'arquivo' => $pdf
                ? [$edicao ? 'nullable' : 'required', 'file', 'mimes:pdf', 'max:'.self::MAX_KB]
                : ['nullable', 'file', 'max:'.self::MAX_KB],
        ];

        if ($tipo === TipoItem::PROVA->value) {
            $regras['nota_minima'] = ['sometimes', 'numeric', 'between:0,100'];
            $regras['max_tentativas'] = ['sometimes', 'integer', 'between:1,20'];
        }

        if (! $edicao) {
            $regras['tipo'] = ['required', 'in:'.implode(',', array_column(TipoItem::cases(), 'value'))];
        }

        return $regras;
    }
}
