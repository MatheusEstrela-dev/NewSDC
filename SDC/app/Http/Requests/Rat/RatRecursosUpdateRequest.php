<?php

declare(strict_types=1);

namespace App\Http\Requests\Rat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida dados de atualização parcial de recursos em ocorrência RAT.
 * Todos os campos são opcionais (PATCH semântico).
 */
class RatRecursosUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recurso_tipo'       => ['sometimes', 'string', Rule::in(['viatura', 'pe', 'aereo', 'aquatico', 'outro'])],
            'recurso_problemas'  => ['sometimes', 'nullable', 'boolean'],
            'recurso_descricao'  => ['sometimes', 'nullable', 'string'],
            'viatura_tipo'       => ['sometimes', 'nullable', 'string', 'max:100'],
            'viatura_placa'      => ['sometimes', 'nullable', 'string', 'max:20'],
            'viatura_prefixo'    => ['sometimes', 'nullable', 'string', 'max:50'],
            'viatura_padrao'     => ['sometimes', 'nullable', 'string', 'max:50'],
            'viatura_orgao'      => ['sometimes', 'nullable', 'string', 'max:255'],
            'viatura_descricao'  => ['sometimes', 'nullable', 'string'],
            'viatura_saida'      => ['sometimes', 'nullable', 'date'],
            'viatura_chegada'    => ['sometimes', 'nullable', 'date'],
            'viatura_km'         => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'viatura_quantidade' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'viatura_condicao'   => ['sometimes', 'nullable', 'string', Rule::in(['otima', 'boa', 'regular', 'ruim', 'inoperante'])],
            'guarnicao'                 => ['sometimes', 'nullable', 'array'],
            'guarnicao.*.nome_completo' => ['required_with:guarnicao', 'string', 'max:255'],
            'guarnicao.*.matricula'     => ['nullable', 'string', 'max:50'],
            'guarnicao.*.is_condutor'   => ['nullable', 'boolean'],
        ];
    }
}
