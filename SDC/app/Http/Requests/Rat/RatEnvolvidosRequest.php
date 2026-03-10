<?php

declare(strict_types=1);

namespace App\Http\Requests\Rat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida os dados de criação de um envolvido em ocorrência RAT.
 */
class RatEnvolvidosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Dados gerais do envolvimento
            'g_tipo_pessoa'                    => ['required', 'string', 'max:191'],
            'g_lesao_grau'                     => ['nullable', 'string', 'max:191'],
            'g_atendimento_vitima_repassada'   => ['nullable', 'string', 'max:191'],
            'g_envolvido_presenca'             => ['nullable', 'boolean'],
            'g_envolvido_tipo'                 => ['nullable', 'string', 'max:191'],
            'g_envolvido_orgao'                => ['nullable', 'string', 'max:191'],
            'g_envolvido_uf'                   => ['nullable', 'string', 'size:2'],
            'g_envolvido_matricula'            => ['nullable', 'string', 'max:191'],
            'g_envolvido_servico'              => ['nullable', 'boolean'],
            // Dados pessoais
            'p_tipo'                           => ['nullable', 'string', 'max:191'],
            'p_numero'                         => ['nullable', 'string', 'max:191'],
            'p_orgao_expedidor'                => ['nullable', 'string', 'max:191'],
            'p_nome_completo'                  => ['required', 'string', 'max:191'],
            'p_nome_fantasia'                  => ['nullable', 'string', 'max:191'],
            'p_data_nascimento'                => ['nullable', 'date'],
            'p_cpf'                            => ['nullable', 'string', 'max:14'],
            'p_nome_mae'                       => ['nullable', 'string', 'max:191'],
            'p_sexo'                           => ['nullable', 'string', Rule::in(['M', 'F', 'O', 'NI'])],
            'p_estado_civil'                   => ['nullable', 'string', 'max:191'],
            'p_nacionalidade'                  => ['nullable', 'string', 'max:191'],
            // Endereço
            'p_end_cep'                        => ['nullable', 'string', 'max:9'],
            'p_end_pais'                       => ['nullable', 'string', 'max:191'],
            'p_end_estado_uf'                  => ['nullable', 'string', 'size:2'],
        ];
    }
}
