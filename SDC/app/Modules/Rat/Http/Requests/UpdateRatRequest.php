<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida e normaliza os dados de atualização completa/parcial de um RAT.
 *
 * SRP : única responsabilidade — validar input de atualização.
 */
class UpdateRatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Dados Gerais
            'dadosGerais'                        => ['nullable', 'array'],
            'dadosGerais.data_fato'              => ['nullable', 'string'],
            'dadosGerais.data_inicio_atividade'  => ['nullable', 'string'],
            'dadosGerais.data_termino_atividade' => ['nullable', 'string'],
            'dadosGerais.nat_cobrade_id'         => ['nullable'],
            'dadosGerais.nat_nome_operacao'      => ['nullable', 'string', 'max:255'],
            'dadosGerais.tem_vistoria'           => ['nullable', 'boolean'],

            // Comunicação
            'comunicacao'                           => ['nullable', 'array'],
            'comunicacao.tipo_solicitacao'          => ['nullable', 'string', Rule::in(['telefone', 'radio', 'pessoal', 'sistema', 'email', 'outro'])],
            'comunicacao.data_comunicacao'          => ['nullable', 'string'],
            'comunicacao.telefone_contato'          => ['nullable', 'string', 'max:20'],
            'comunicacao.nome_solicitante'          => ['nullable', 'string', 'max:255'],

            // Local
            'local'                   => ['nullable', 'array'],
            'local.uf'                => ['nullable', 'string', 'size:2'],
            'local.pais_id'           => ['nullable', 'integer'],
            'local.municipio_id'      => ['nullable'],

            // Endereço
            'endereco'                       => ['nullable', 'array'],
            'endereco.cep'                   => ['nullable', 'string', 'max:10'],
            'endereco.logradouro'            => ['nullable', 'string', 'max:255'],
            'endereco.numero'               => ['nullable', 'string', 'max:20'],
            'endereco.complemento'          => ['nullable', 'string', 'max:255'],
            'endereco.bairro'               => ['nullable', 'string', 'max:150'],
            'endereco.km'                   => ['nullable', 'string', 'max:20'],
            'endereco.tipo_localizacao'     => ['nullable', 'string', Rule::in(['urbana', 'rural', 'rodovia', 'estrada', 'mata', 'montanha', 'rio', 'lago', 'outros'])],
            'endereco.latitude'             => ['nullable', 'numeric', 'between:-90,90'],
            'endereco.longitude'            => ['nullable', 'numeric', 'between:-180,180'],

            // Sub-entidades livres (arrays JSON)
            'recursos'   => ['nullable', 'array'],
            'envolvidos' => ['nullable', 'array'],
            'vistoria'   => ['nullable', 'array'],
            'historico'  => ['nullable', 'array'],
            'anexos'     => ['nullable', 'array'],
        ];
    }
}

