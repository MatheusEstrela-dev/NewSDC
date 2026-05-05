<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RatVistoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Solicitante
            'v_solicitante_nome'             => ['nullable', 'string', 'max:255'],
            'v_solicitante_cpf'              => ['nullable', 'string', 'regex:/^\d{11}$/'],
            'v_solicitante_telefone'         => ['nullable', 'string', 'max:20', 'regex:/^\d+$/'],
            'v_solicitante_endereco'         => ['nullable', 'string', 'max:255'],
            'v_solicitante_bairro'           => ['nullable', 'string', 'max:100'],
            'v_solicitante_cep'              => ['nullable', 'string', 'max:8', 'regex:/^\d{8}$/'],

            // Imóvel e local
            'v_local_endereco'               => ['nullable', 'string', 'max:255'],
            'v_local_complemento'            => ['nullable', 'string', 'max:100'],
            'v_tipo_area'                    => ['nullable', 'string', 'max:50'],
            'v_tipo_area_descricao'          => ['nullable', 'string', 'max:255'],
            'v_tipo_imovel'                  => ['nullable', 'string', 'max:50'],
            'v_tipo_imovel_outro_descricao'  => ['nullable', 'string', 'max:255'],
            'v_tipo_construcao'              => ['nullable', 'string', 'max:50'],
            'v_tipo_edificacao'              => ['nullable', 'string', 'max:50'],
            'v_tipo_terreno_relevo'          => ['nullable', 'string', 'max:50'],
            'v_sistema_estrutural'           => ['nullable', 'string', 'max:100'],
            'v_numero_pavimentos'            => ['nullable', 'integer', 'min:0'],
            'v_estado_conservacao'           => ['nullable', 'string', 'max:50'],
            'v_regime_ocupacao'              => ['nullable', 'string', 'max:50'],

            // Moradores
            'v_proprietario_morador'         => ['nullable', 'string', 'max:255'],
            'v_contato_telefone'             => ['nullable', 'string', 'max:20', 'regex:/^\d+$/'],
            'v_numero_moradores'             => ['nullable', 'integer', 'min:0'],
            'v_ha_idosos'                    => ['nullable', 'boolean'],
            'v_ha_criancas'                  => ['nullable', 'boolean'],
            'v_ha_dificuldade_locomocao'     => ['nullable', 'string', 'max:50'],

            // Localização
            'v_endereco_imovel'              => ['nullable', 'string', 'max:255'],
            'v_bairro'                       => ['nullable', 'string', 'max:100'],
            'v_municipio'                    => ['nullable', 'string', 'max:100'],
            'v_cep'                          => ['nullable', 'string', 'max:8', 'regex:/^\d{8}$/'],
            'v_latitude'                     => ['nullable', 'numeric'],
            'v_longitude'                    => ['nullable', 'numeric'],

            // Encaminhamentos (booleanos)
            'v_enc_interdicao_parcial'       => ['nullable', 'boolean'],
            'v_enc_interdicao_total'         => ['nullable', 'boolean'],
            'v_enc_remocao_temporaria'       => ['nullable', 'boolean'],
            'v_enc_remocao_definitiva'       => ['nullable', 'boolean'],
            'v_enc_isolamento_area'          => ['nullable', 'boolean'],
            'v_enc_desocupacao_abrigo'       => ['nullable', 'boolean'],
            'v_enc_outros'                   => ['nullable', 'boolean'],
            'v_enc_outros_descricao'         => ['nullable', 'string', 'max:500'],

            // Patologias (booleanos)
            'v_patologia_rachaduras'         => ['nullable', 'boolean'],
            'v_patologia_trincas'            => ['nullable', 'boolean'],
            'v_patologia_fissuras_estruturais' => ['nullable', 'boolean'],
            'v_patologia_deformacoes_estruturais' => ['nullable', 'boolean'],
            'v_patologia_infiltracoes'       => ['nullable', 'boolean'],
            'v_patologia_instabilidade_talude' => ['nullable', 'boolean'],
            'v_patologia_inundacoes'         => ['nullable', 'boolean'],
            'v_patologia_alagamentos'        => ['nullable', 'boolean'],
            'v_patologia_outros'             => ['nullable', 'boolean'],
            'v_patologia_outros_descricao'   => ['nullable', 'string', 'max:500'],

            // Bens afetados (booleanos)
            'v_bens_residencia'              => ['nullable', 'boolean'],
            'v_bens_muros'                   => ['nullable', 'boolean'],
            'v_bens_vias_publicas'           => ['nullable', 'boolean'],
            'v_bens_outros'                  => ['nullable', 'boolean'],
            'v_bens_outros_descricao'        => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'v_solicitante_cpf.regex'    => 'CPF deve conter somente 11 dígitos numéricos.',
            'v_solicitante_cep.regex'    => 'CEP deve conter somente 8 dígitos numéricos.',
            'v_cep.regex'                => 'CEP deve conter somente 8 dígitos numéricos.',
            'v_contato_telefone.regex'   => 'Telefone deve conter somente números.',
        ];
    }
}
