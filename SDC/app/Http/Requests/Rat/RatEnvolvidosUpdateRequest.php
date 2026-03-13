<?php

declare(strict_types=1);

namespace App\Http\Requests\Rat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida dados de atualização parcial de um envolvido em ocorrência RAT.
 * Todos os campos são opcionais (PATCH semântico).
 */
class RatEnvolvidosUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'g_tipo_pessoa'                  => ['sometimes', 'string', 'max:191'],
            'g_lesao_grau'                   => ['sometimes', 'nullable', 'string', 'max:191'],
            'g_atendimento_vitima_repassada' => ['sometimes', 'nullable', 'string', 'max:191'],
            'g_envolvido_presenca'           => ['sometimes', 'nullable', 'boolean'],
            'g_envolvido_tipo'               => ['sometimes', 'nullable', 'string', 'max:191'],
            'g_envolvido_orgao'              => ['sometimes', 'nullable', 'string', 'max:191'],
            'g_envolvido_uf'                 => ['sometimes', 'nullable', 'string', 'size:2'],
            'g_envolvido_matricula'          => ['sometimes', 'nullable', 'string', 'max:191'],
            'g_envolvido_servico'            => ['sometimes', 'nullable', 'boolean'],
            'p_nome_completo'                => ['sometimes', 'string', 'max:191'],
            'p_nome_fantasia'                => ['sometimes', 'nullable', 'string', 'max:191'],
            'p_data_nascimento'              => ['sometimes', 'nullable', 'date'],
            'p_cpf'                          => ['sometimes', 'nullable', 'string', 'max:14'],
            'p_sexo'                         => ['sometimes', 'nullable', 'string', Rule::in(['M', 'F', 'O', 'NI'])],
            'p_estado_civil'                 => ['sometimes', 'nullable', 'string', 'max:191'],
            'p_end_cep'                      => ['sometimes', 'nullable', 'string', 'max:9'],
            'p_end_estado_uf'                => ['sometimes', 'nullable', 'string', 'size:2'],
        ];
    }
}
