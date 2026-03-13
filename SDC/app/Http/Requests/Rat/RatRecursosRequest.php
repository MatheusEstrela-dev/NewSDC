<?php

declare(strict_types=1);

namespace App\Http\Requests\Rat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida dados de criação de recursos empregados em ocorrência RAT.
 */
class RatRecursosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recurso_tipo'       => ['required', 'string', Rule::in(['viatura', 'pe', 'aereo', 'aquatico', 'outro'])],
            'recurso_problemas'  => ['nullable', 'boolean'],
            'recurso_descricao'  => ['nullable', 'string', 'max:65535'],
            // Dados da viatura
            'viatura_tipo'       => ['nullable', 'string', 'max:100'],
            'viatura_placa'      => ['nullable', 'string', 'max:20'],
            'viatura_prefixo'    => ['nullable', 'string', 'max:50'],
            'viatura_padrao'     => ['nullable', 'string', 'max:50'],
            'viatura_orgao'      => ['nullable', 'string', 'max:255'],
            'viatura_descricao'  => ['nullable', 'string'],
            'viatura_saida'      => ['nullable', 'date'],
            'viatura_chegada'    => ['nullable', 'date', 'after_or_equal:viatura_saida'],
            'viatura_km'         => ['nullable', 'numeric', 'min:0'],
            'viatura_quantidade' => ['nullable', 'integer', 'min:1'],
            'viatura_condicao'   => ['nullable', 'string', Rule::in(['otima', 'boa', 'regular', 'ruim', 'inoperante'])],
            // Componentes da guarnição (array aninhado)
            'guarnicao'                          => ['nullable', 'array'],
            'guarnicao.*.nome_completo'          => ['required_with:guarnicao', 'string', 'max:255'],
            'guarnicao.*.matricula'              => ['nullable', 'string', 'max:50'],
            'guarnicao.*.corporacao'             => ['nullable', 'string', 'max:100'],
            'guarnicao.*.is_condutor'            => ['nullable', 'boolean'],
        ];
    }
}
