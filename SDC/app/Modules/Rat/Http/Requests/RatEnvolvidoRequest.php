<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida dados de um envolvido em uma ocorrência RAT.
 */
class RatEnvolvidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Tipo de Envolvimento
            'g_tipo_pessoa'           => ['required', 'string', Rule::in(['vítima', 'agressor', 'testemunha', 'agente', 'outro'])],

            // Dados Pessoais
            'p_nome_completo'         => ['required', 'string', 'max:255'],
            'p_cpf'                   => ['nullable', 'string', 'regex:/^\d{3}\.?\d{3}\.?\d{3}-?\d{2}$/'],
            'p_rg'                    => ['nullable', 'string', 'max:20'],
            'p_data_nascimento'       => ['nullable', 'string', 'regex:/^\d{2}\/\d{2}\/\d{4}$/'],
            'p_sexo'                  => ['nullable', 'string', Rule::in(['M', 'F'])],
            'p_escolaridade'          => ['nullable', 'string', 'max:100'],
            'p_profissao'             => ['nullable', 'string', 'max:100'],

            // Contato
            'p_telefone'              => ['nullable', 'string', 'max:20'],
            'p_email'                 => ['nullable', 'email', 'max:255'],

            // Endereço
            'p_end_cep'               => ['nullable', 'string', 'max:10'],
            'p_end_logradouro'        => ['nullable', 'string', 'max:255'],
            'p_end_numero'            => ['nullable', 'string', 'max:20'],
            'p_end_bairro'            => ['nullable', 'string', 'max:150'],
            'p_end_cidade'            => ['nullable', 'string', 'max:150'],
            'p_end_uf'                => ['nullable', 'string', 'size:2'],

            // Lições/Lesões
            'g_lesao_grau'            => ['nullable', 'string', Rule::in(['leve', 'moderada', 'grave', 'morte'])],
        ];
    }

    public function messages(): array
    {
        return [
            'g_tipo_pessoa.required'      => 'Tipo de envolvimento é obrigatório.',
            'p_nome_completo.required'    => 'Nome completo é obrigatório.',
            'p_cpf.regex'                 => 'CPF inválido.',
            'p_data_nascimento.regex'     => 'Data deve estar no formato dd/mm/aaaa.',
        ];
    }
}
