<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida dados de vistoria técnica de uma ocorrência RAT.
 */
class RatVistoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Data e Localização
            'data_vistoria'           => ['required', 'string', 'regex:/^\d{2}\/\d{2}\/\d{4}$/'],
            'local_vistoria'          => ['required', 'string', 'max:500'],

            // Profissional
            'profissional_nome'       => ['required', 'string', 'max:255'],
            'profissional_cargo'      => ['required', 'string', 'max:100'],
            'profissional_orgao'      => ['required', 'string', 'max:255'],
            'profissional_registro'   => ['nullable', 'string', 'max:50'],

            // Descrição de Danos
            'descricao_danos'         => ['nullable', 'string', 'max:2000'],

            // Parecer Técnico
            'parecer_tecnico'         => ['nullable', 'string', 'max:2000'],

            // Recomendações
            'recomendacoes'           => ['nullable', 'string', 'max:2000'],

            // Classificação de Risco
            'grau_risco'              => ['nullable', 'string', Rule::in(['baixo', 'médio', 'alto', 'crítico'])],

            // Fotos/Documentos
            'anexos'                  => ['nullable', 'array'],
            'anexos.*.id'             => ['nullable', 'integer'],
            'anexos.*.arquivo'        => ['nullable', 'string', 'max:255'],
            'anexos.*.tipo'           => ['nullable', 'string', Rule::in(['imagem', 'documento', 'vídeo', 'audio'])],
            'anexos.*.descricao'      => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'data_vistoria.required'      => 'Data da vistoria é obrigatória.',
            'local_vistoria.required'     => 'Local da vistoria é obrigatório.',
            'profissional_nome.required'  => 'Nome do profissional é obrigatório.',
            'profissional_cargo.required' => 'Cargo do profissional é obrigatório.',
            'profissional_orgao.required' => 'Órgão do profissional é obrigatório.',
        ];
    }
}
