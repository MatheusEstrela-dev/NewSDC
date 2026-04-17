<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida dados de um recurso empregado em uma ocorrência RAT.
 */
class RatRecursoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Classificação
            'tipo_recurso'            => ['required', 'string', Rule::in(['veículo', 'pessoal', 'material', 'equipamento', 'outro'])],
            'categoria'               => ['required', 'string', 'max:100'],
            'orgao_responsavel'       => ['required', 'string', 'max:255'],
            'identificacao'           => ['required', 'string', 'max:100'],
            'descricao'               => ['nullable', 'string', 'max:500'],

            // Condutor (para veículos)
            'condutor'                => ['nullable', 'string', 'max:255'],

            // Deslocamento
            'local_origem'            => ['nullable', 'string', 'max:255'],
            'local_destino'           => ['nullable', 'string', 'max:255'],
            'data_hora_saida'         => ['nullable', 'string', 'regex:/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}$/'],
            'data_hora_chegada'       => ['nullable', 'string', 'regex:/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}$/'],
            'km_percorrido'           => ['nullable', 'numeric', 'min:0', 'max:99999.99'],

            // Operacional
            'quantidade'              => ['required', 'integer', 'min:1', 'max:9999'],
            'capacidade'              => ['nullable', 'string', 'max:100'],
            'condicao'                => ['nullable', 'string', Rule::in(['operacional', 'parcialmente operacional', 'inoperacional', 'danificado'])],

            // Responsável
            'operador_responsavel'    => ['nullable', 'string', 'max:255'],
            'contato_emergencia'      => ['nullable', 'string', 'max:20'],

            // Observações
            'observacoes'             => ['nullable', 'string', 'max:500'],

            // Agentes/Integrantes
            'agentes'                 => ['nullable', 'array'],
            'agentes.*.id'            => ['nullable', 'integer'],
            'agentes.*.nome'          => ['required', 'string', 'max:255'],
            'agentes.*.cargo'         => ['nullable', 'string', 'max:100'],
            'agentes.*.matricula'     => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_recurso.required'      => 'Tipo de recurso é obrigatório.',
            'categoria.required'         => 'Categoria é obrigatória.',
            'orgao_responsavel.required' => 'Órgão responsável é obrigatório.',
            'identificacao.required'     => 'Identificação/Placa/Matrícula é obrigatória.',
            'quantidade.required'        => 'Quantidade é obrigatória.',
        ];
    }
}
