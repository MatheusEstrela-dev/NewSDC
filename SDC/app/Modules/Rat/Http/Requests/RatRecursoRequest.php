<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RatRecursoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Tipo alinhado com o enum do banco: viatura|pe|aereo|aquatico|outro
            // O DTO também aceita "pessoal" e mapeia para "pe"
            'recurso_tipo'          => ['nullable', 'string', Rule::in(['viatura', 'pe', 'aereo', 'aquatico', 'outro', 'pessoal'])],
            'tipo_recurso'          => ['nullable', 'string', Rule::in(['viatura', 'pe', 'aereo', 'aquatico', 'outro', 'pessoal'])],
            'seq'                   => ['nullable', 'integer', 'min:1'],
            'recurso_problemas'     => ['nullable', 'boolean'],
            'recurso_descricao'     => ['nullable', 'string', 'max:1000'],
            'descricao'             => ['nullable', 'string', 'max:1000'],

            // Viatura
            'viatura_tipo'          => ['nullable', 'string', 'max:100'],
            'categoria'             => ['nullable', 'string', 'max:100'],
            'viatura_placa'         => ['nullable', 'string', 'max:20'],
            'identificacao'         => ['nullable', 'string', 'max:100'],
            'viatura_prefixo'       => ['nullable', 'string', 'max:50'],
            'viatura_padrao'        => ['nullable', 'string', 'max:50'],
            'viatura_orgao'         => ['nullable', 'string', 'max:255'],
            'orgao_responsavel'     => ['nullable', 'string', 'max:255'],
            'viatura_descricao'     => ['nullable', 'string', 'max:1000'],

            // Deslocamento
            'viatura_saida'         => ['nullable', 'string'],
            'data_hora_saida'       => ['nullable', 'string'],
            'viatura_chegada'       => ['nullable', 'string'],
            'data_hora_chegada'     => ['nullable', 'string'],
            'viatura_km'            => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'km_percorrido'         => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'viatura_local_origem'  => ['nullable', 'string', 'max:255'],
            'local_origem'          => ['nullable', 'string', 'max:255'],
            'viatura_local_destino' => ['nullable', 'string', 'max:255'],
            'local_destino'         => ['nullable', 'string', 'max:255'],

            // Operacional — enum do banco: otima|boa|regular|ruim|inoperante
            'viatura_quantidade'    => ['nullable', 'integer', 'min:1'],
            'quantidade'            => ['nullable', 'integer', 'min:1'],
            'viatura_capacidade'    => ['nullable', 'string', 'max:100'],
            'capacidade'            => ['nullable', 'string', 'max:100'],
            'viatura_condicao'      => ['nullable', 'string', Rule::in(['otima', 'boa', 'regular', 'ruim', 'inoperante', 'operacional', 'manutencao'])],
            'condicao'              => ['nullable', 'string', Rule::in(['otima', 'boa', 'regular', 'ruim', 'inoperante', 'operacional', 'manutencao'])],

            // Operador
            'viatura_operador'      => ['nullable', 'string', 'max:255'],
            'operador_responsavel'  => ['nullable', 'string', 'max:255'],
            'operador_masp'         => ['nullable', 'string', 'max:20'],
            'operador_is_condutor'  => ['nullable', 'boolean'],
            'viatura_contato'       => ['nullable', 'string', 'max:50'],
            'contato_emergencia'    => ['nullable', 'string', 'max:20'],

            // Agentes da guarnição
            'agentes'               => ['nullable', 'array'],
            'agentes.*.id'          => ['nullable', 'integer'],
            'agentes.*.nome_completo' => ['nullable', 'string', 'max:255'],
            'agentes.*.nome'        => ['nullable', 'string', 'max:255'],
            'agentes.*.masp'        => ['nullable', 'string', 'max:20'],
            'agentes.*.matricula'   => ['nullable', 'string', 'max:50'],
            'agentes.*.pg_cargo'    => ['nullable', 'string', 'max:100'],
            'agentes.*.cargo'       => ['nullable', 'string', 'max:100'],
            'agentes.*.orgao'       => ['nullable', 'string', 'max:100'],
            'agentes.*.unidade'     => ['nullable', 'string', 'max:100'],
            'agentes.*.funcao'      => ['nullable', 'string', 'max:100'],
            'agentes.*.is_condutor' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'recurso_tipo.in' => 'Tipo de recurso inválido. Use: viatura, pe, aereo, aquatico, outro.',
            'viatura_condicao.in' => 'Condição inválida. Use: otima, boa, regular, ruim, inoperante.',
        ];
    }
}
