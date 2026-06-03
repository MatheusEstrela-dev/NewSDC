<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida dados de um evento no histórico de uma ocorrência RAT.
 */
class RatHistoricoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Tipo de Evento
            'tipo_evento'             => ['nullable', 'string', Rule::in(['criado', 'atualizado', 'comentário', 'anexo_adicionado', 'status_mudado', 'finalizado', 'cancelado', 'outro'])],

            // Descrição
            'descricao'               => ['nullable', 'string', 'max:5000'],

            // Data/Hora (opcional, usa current se não informado)
            'data_evento'             => ['nullable', 'string', 'regex:/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}$/'],

            // Usuário responsável (preenchido automaticamente pelo middleware)
            'usuario_id'              => ['nullable', 'integer'],

            // Detalhes adicionais
            'detalhes'                => ['nullable', 'array'],

            // Observações sobre clima, condições, etc.
            'clima'                   => ['nullable', 'string', Rule::in(['chuva', 'vento_forte', 'nevoeiro', 'tempestade', 'ensolarado', 'nublado', 'normal'])],

            // Resultado
            'resultado_operacao'      => ['nullable', 'string', Rule::in(['sucesso', 'parcial', 'falha', 'suspenso', 'outro'])],

            // Métricas
            'pessoas_atendidas'       => ['nullable', 'integer', 'min:0'],
            'vitimas_resgatadas'      => ['nullable', 'integer', 'min:0'],
            'imoveis_vistoriados'     => ['nullable', 'integer', 'min:0'],
            'familias_desalojadas'    => ['nullable', 'integer', 'min:0'],

            // Encaminhamentos
            'encaminhamentos'         => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
