<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida dados para criação/armazenamento de nova ocorrência RAT.
 *
 * Aceita tanto ISO (YYYY-MM-DDTHH:mm) quanto DD/MM/YYYY HH:MM
 *
 * Fluxo: Store → RatUnifiedController → RatService → RatWriteService → Model
 */
class StoreRatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ═══════════════════════════════════════════════════════════════════
            // DADOS GERAIS - Essencial para RAT
            // ═══════════════════════════════════════════════════════════════════

            // Data e horário da ocorrência
            'data_fato' => [
                'required',
                'string',
                'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}|^\d{2}\/\d{2}\/\d{4}\s\d{2}:\d{2}$/',
            ],

            // Datas opcionais
            'data_inicio_atividade' => [
                'nullable',
                'string',
                'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}|^\d{2}\/\d{2}\/\d{4}\s\d{2}:\d{2}$/',
            ],
            'data_termino_atividade' => [
                'nullable',
                'string',
                'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}|^\d{2}\/\d{2}\/\d{4}\s\d{2}:\d{2}$/',
            ],

            // ═══════════════════════════════════════════════════════════════════
            // CLASSIFICAÇÃO
            // ═══════════════════════════════════════════════════════════════════
            'nat_codigo' => ['nullable', 'string', 'max:50'],
            'nat_cobrade_id' => ['nullable', 'integer', 'min:1'],
            'nat_nome_operacao' => ['nullable', 'string', 'max:500'],

            // ═══════════════════════════════════════════════════════════════════
            // COMUNICAÇÃO
            // ═══════════════════════════════════════════════════════════════════
            'com_ocorrencia_data' => [
                'required',
                'string',
                'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}|^\d{2}\/\d{2}\/\d{4}\s\d{2}:\d{2}$/',
            ],
            'com_ocorrencia_atendimento' => [
                'required',
                'string',
                'in:telefone,radio,pessoal,sistema,email,outro',
            ],
            'com_telefonista' => ['nullable', 'string', 'max:100'],
            'com_numero_telefone' => ['nullable', 'string', 'max:20'],

            // ═══════════════════════════════════════════════════════════════════
            // LOCALIZAÇÃO
            // ═══════════════════════════════════════════════════════════════════
            'local_pais' => ['required', 'string', 'max:100'],
            'local_uf' => ['required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'local_municipio' => ['required', 'string', 'max:150'],

            // Endereço detalhado
            'local_cep' => ['nullable', 'string', 'regex:/^\d{5}-?\d{3}$/'],
            'local_logradouro' => ['required', 'string', 'max:255'],
            'local_numero' => ['nullable', 'string', 'max:20'],
            'local_bairro' => ['required', 'string', 'max:150'],
            'local_complemento' => ['nullable', 'string', 'max:300'],
            'local_km' => ['nullable', 'string', 'max:20'],
            'local_cruzamento' => ['nullable', 'string', 'max:255'],
            'local_ponto_referencia' => ['nullable', 'string', 'max:300'],

            // Coordenadas GPS (opcional)
            'local_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'local_longitude' => ['nullable', 'numeric', 'between:-180,180'],

            // ═══════════════════════════════════════════════════════════════════
            // BOLETIM DE OCORRÊNCIA
            // ═══════════════════════════════════════════════════════════════════
            'numero_bos' => ['nullable', 'string', 'max:50'],
            'prazo_edicao' => ['nullable', 'date_format:Y-m-d'],

            // ═══════════════════════════════════════════════════════════════════
            // ENVOLVIDOS (array de objetos)
            // ═══════════════════════════════════════════════════════════════════
            'envolvidos' => ['nullable', 'array'],
            'envolvidos.*.nome' => ['nullable', 'string', 'max:255'],
            'envolvidos.*.cpf_cnpj' => ['nullable', 'string', 'max:20'],
            'envolvidos.*.funcao' => ['nullable', 'string', 'max:150'],
            'envolvidos.*.orgao' => ['nullable', 'string', 'max:150'],
            'envolvidos.*.telefone' => ['nullable', 'string', 'max:20'],
            'envolvidos.*.email' => ['nullable', 'email', 'max:255'],

            // ═══════════════════════════════════════════════════════════════════
            // RECURSOS (array de objetos)
            // ═══════════════════════════════════════════════════════════════════
            'recursos' => ['nullable', 'array'],
            'recursos.*.tipo' => ['nullable', 'string', 'max:100'],
            'recursos.*.quantidade' => ['nullable', 'integer', 'min:0'],
            'recursos.*.descricao' => ['nullable', 'string', 'max:500'],
            'recursos.*.valor' => ['nullable', 'numeric', 'min:0'],

            // ═══════════════════════════════════════════════════════════════════
            // VISTORIA (objeto único)
            // ═══════════════════════════════════════════════════════════════════
            'vistoria.data_vistoria' => ['nullable', 'date_format:Y-m-d'],
            'vistoria.responsavel' => ['nullable', 'string', 'max:255'],
            'vistoria.constatacoes' => ['nullable', 'string'],
            'vistoria.recomendacoes' => ['nullable', 'string'],

            // ═══════════════════════════════════════════════════════════════════
            // OBSERVAÇÕES GERAIS
            // ═══════════════════════════════════════════════════════════════════
            'observacoes' => ['nullable', 'string'],
            'notas_tecnicas' => ['nullable', 'string'],
        ];
    }

    /**
     * Mensagens customizadas de validação em Português
     */
    public function messages(): array
    {
        return [
            'data_fato.required' => 'Data do fato é obrigatória.',
            'data_fato.regex' => 'Formato de data inválido. Use YYYY-MM-DDTHH:mm ou DD/MM/YYYY HH:MM.',

            'com_ocorrencia_data.required' => 'Data da comunicação é obrigatória.',
            'com_ocorrencia_atendimento.required' => 'Tipo de atendimento é obrigatório.',
            'com_ocorrencia_atendimento.in' => 'Tipo de atendimento inválido.',

            'local_pais.required' => 'País é obrigatório.',
            'local_uf.required' => 'Estado (UF) é obrigatório.',
            'local_uf.size' => 'UF deve ter 2 caracteres.',
            'local_municipio.required' => 'Município é obrigatório.',
            'local_logradouro.required' => 'Logradouro é obrigatório.',
            'local_bairro.required' => 'Bairro é obrigatório.',

            'local_latitude.between' => 'Latitude deve estar entre -90 e 90.',
            'local_longitude.between' => 'Longitude deve estar entre -180 e 180.',
            'local_cep.regex' => 'Formato de CEP inválido.',

            'envolvidos.*.email.email' => 'Email do envolvido inválido.',
            'recursos.*.quantidade.integer' => 'Quantidade de recursos deve ser um número inteiro.',
            'recursos.*.valor.numeric' => 'Valor deve ser um número.',
        ];
    }

    /**
     * Attributes customizados para mensagens
     */
    public function attributes(): array
    {
        return [
            'data_fato' => 'data do fato',
            'local_pais' => 'país',
            'local_uf' => 'estado (UF)',
            'local_municipio' => 'município',
            'local_logradouro' => 'logradouro',
            'local_bairro' => 'bairro',
            'com_ocorrencia_data' => 'data da comunicação',
            'com_ocorrencia_atendimento' => 'tipo de atendimento',
        ];
    }
}
