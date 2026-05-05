<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RatEnvolvidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id'                              => ['nullable', 'integer'],
            'seq'                             => ['nullable', 'integer', 'min:1'],
            // Dados gerais do envolvimento
            'g_tipo_pessoa'                   => ['nullable', 'string', 'max:50'],
            'g_lesao_grau'                    => ['nullable', 'string', 'max:50'],
            'g_envolvido_tipo'                => ['nullable', 'string', 'max:50'],
            'g_envolvido_orgao'               => ['nullable', 'string', 'max:100'],
            'g_envolvido_uf'                  => ['nullable', 'string', 'max:2'],
            'g_envolvido_matricula'           => ['nullable', 'string', 'max:50'],
            'g_envolvido_presenca'            => ['nullable', 'boolean'],
            'g_envolvido_servico'             => ['nullable', 'boolean'],
            // Dados pessoais
            'p_tipo'                          => ['nullable', 'string', 'max:50'],
            'p_numero'                        => ['nullable', 'string', 'max:50'],
            'p_orgao_expedidor'               => ['nullable', 'string', 'max:50'],
            'p_nome_completo'                 => ['nullable', 'string', 'max:255'],
            'p_nome_fantasia'                 => ['nullable', 'string', 'max:255'],
            'p_data_nascimento'               => ['nullable', 'date'],
            'p_cpf'                           => ['nullable', 'string', 'regex:/^\d{11}$/'],
            'p_nome_mae'                      => ['nullable', 'string', 'max:255'],
            'p_nome_pai'                      => ['nullable', 'string', 'max:255'],
            'p_ocupacao_atual'                => ['nullable', 'string', 'max:100'],
            'p_escolaridade'                  => ['nullable', 'string', 'max:50'],
            'p_cor_raca'                      => ['nullable', 'string', 'max:50'],
            'p_sexo'                          => ['nullable', 'string', 'max:20'],
            'p_estado_civil'                  => ['nullable', 'string', 'max:30'],
            'p_orientacao_sexual'             => ['nullable', 'string', 'max:50'],
            'p_identidade_genero'             => ['nullable', 'string', 'max:50'],
            'p_nome_social'                   => ['nullable', 'string', 'max:255'],
            'p_nacionalidade'                 => ['nullable', 'string', 'max:50'],
            'p_pais_origem'                   => ['nullable', 'string', 'max:100'],
            'p_naturalidade_uf'               => ['nullable', 'string', 'max:2'],
            'p_turista'                       => ['nullable', 'boolean'],
            'p_situacao_rua'                  => ['nullable', 'boolean'],
            // Endereço
            'p_end_cep'                       => ['nullable', 'string', 'max:8'],
            'p_end_pais'                      => ['nullable', 'string', 'max:50'],
            'p_end_estado_uf'                 => ['nullable', 'string', 'max:2'],
            'p_end_municipio'                 => ['nullable', 'string', 'max:100'],
            'p_end_bairro'                    => ['nullable', 'string', 'max:100'],
            'p_end_logradouro'                => ['nullable', 'string', 'max:255'],
            'p_end_numero'                    => ['nullable', 'string', 'max:20'],
            'p_end_complemento'               => ['nullable', 'string', 'max:100'],
            // Contato
            'p_telefone_residencial'          => ['nullable', 'string', 'max:20'],
            'p_telefone_comercial'            => ['nullable', 'string', 'max:20'],
            'p_email'                         => ['nullable', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'p_cpf.regex' => 'CPF deve conter somente 11 dígitos numéricos.',
        ];
    }
}
