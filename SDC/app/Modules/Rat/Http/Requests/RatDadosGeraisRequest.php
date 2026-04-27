<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida dados gerais de uma ocorrência RAT.
 */
class RatDadosGeraisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Datas e Horários
            'data_fato'               => ['required', 'string', 'regex:/^\d{2}\/\d{2}\/\d{4}/'],
            'data_inicio_atividade'   => ['nullable', 'string', 'regex:/^\d{2}\/\d{2}\/\d{4}/'],
            'data_termino_atividade'  => ['nullable', 'string', 'regex:/^\d{2}\/\d{2}\/\d{4}/'],

            // Classificação
            'nat_codigo'              => ['nullable', 'string', 'max:50'],
            'nat_cobrade_id'          => ['nullable', 'integer'],
            'nat_nome_operacao'       => ['nullable', 'string', 'max:255'],

            // Comunicação
            'com_data_comunicacao'    => ['required', 'string', 'regex:/^\d{2}\/\d{2}\/\d{4}/'],
            'com_tipo_atendimento'    => ['required', 'string', Rule::in(['telefone', 'radio', 'pessoal', 'sistema', 'email', 'outro'])],

            // Localização
            'local_pais'              => ['required', 'string', 'max:50'],
            'local_uf'                => ['required', 'string', 'size:2'],
            'local_municipio'         => ['required', 'string', 'max:150'],

            // Endereço Detalhado
            'local_cep'               => ['nullable', 'string', 'max:10', 'regex:/^\d{5}-?\d{3}$/'],
            'local_logradouro'        => ['required', 'string', 'max:255'],
            'local_numero'            => ['nullable', 'string', 'max:20'],
            'local_bairro'            => ['required', 'string', 'max:150'],
            'local_complemento'       => ['nullable', 'string', 'max:255'],
            'local_km'                => ['nullable', 'string', 'max:20'],
            'local_cruzamento'        => ['nullable', 'string', 'max:255'],
            'local_ponto_referencia'  => ['nullable', 'string', 'max:255'],
            'local_tipo_localizacao'  => ['required', 'string', Rule::in(['urbana', 'rural', 'rodovia', 'estrada', 'mata', 'montanha', 'rio', 'lago', 'outro'])],

            // Coordenadas
            'local_latitude'          => ['nullable', 'numeric', 'between:-90,90'],
            'local_longitude'         => ['nullable', 'numeric', 'between:-180,180'],

            // Unidade Responsável
            'uni_responsavel'         => ['required', 'string', 'max:255'],

            // Vistoria
            'tem_vistoria'            => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'data_fato.required'           => 'Data do fato é obrigatória.',
            'data_fato.regex'              => 'Data do fato deve estar no formato dd/mm/aaaa.',
            'com_data_comunicacao.required' => 'Data da comunicação é obrigatória.',
            'local_uf.required'            => 'Estado/UF é obrigatório.',
            'local_municipio.required'     => 'Município é obrigatório.',
            'local_logradouro.required'    => 'Logradouro é obrigatório.',
            'local_bairro.required'        => 'Bairro é obrigatório.',
            'uni_responsavel.required'     => 'Unidade responsável é obrigatória.',
        ];
    }
}
