<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Contrato da API de vistoria (POST/PUT rat/{ocorrencia}/vistoria).
 *
 * Uma regra para CADA coluna v_* que o RatVistoriaDTO grava: o controller
 * entrega validated() ao DTO, entao chave sem regra seria descartada. Tipos e
 * tamanhos seguem rat_relato_vistoria; CPF, CEP e telefone mantem o formato
 * so-digitos que o frontend ja envia.
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
            'v_solicitante_nome'                     => ['nullable', 'string', 'max:255'],
            'v_solicitante_cpf'                      => ['nullable', 'string', 'regex:/^\d{11}$/'],
            'v_solicitante_telefone'                 => ['nullable', 'string', 'max:20', 'regex:/^\d+$/'],
            'v_solicitante_endereco'                 => ['nullable', 'string', 'max:255'],
            'v_solicitante_bairro'                   => ['nullable', 'string', 'max:100'],
            'v_solicitante_cep'                      => ['nullable', 'string', 'regex:/^\d{8}$/'],
            'v_local_endereco'                       => ['nullable', 'string', 'max:255'],
            'v_local_complemento'                    => ['nullable', 'string', 'max:255'],
            'v_tipo_area'                            => ['nullable', 'string', 'max:50'],
            'v_tipo_area_descricao'                  => ['nullable', 'string', 'max:255'],
            'v_tipo_imovel'                          => ['nullable', 'string', 'max:50'],
            'v_tipo_imovel_outro_descricao'          => ['nullable', 'string', 'max:255'],
            'v_tipo_construcao'                      => ['nullable', 'string', 'max:50'],
            'v_tipo_construcao_outro_descricao'      => ['nullable', 'string', 'max:255'],
            'v_tipo_edificacao'                      => ['nullable', 'string', 'max:50'],
            'v_tipo_destinacao'                      => ['nullable', 'string', 'max:20'],
            'v_tipo_terreno_relevo'                  => ['nullable', 'string', 'max:50'],
            'v_sistema_estrutural'                   => ['nullable', 'string', 'max:50'],
            'v_numero_pavimentos'                    => ['nullable', 'integer', 'min:0', 'max:32767'],
            'v_estado_conservacao'                   => ['nullable', 'string', 'max:20'],
            'v_regime_ocupacao'                      => ['nullable', 'string', 'max:20'],
            'v_tipo_localizacao'                     => ['nullable', 'string', 'max:20'],
            'v_proprietario_morador'                 => ['nullable', 'string', 'max:255'],
            'v_contato_telefone'                     => ['nullable', 'string', 'max:20', 'regex:/^\d+$/'],
            'v_numero_moradores'                     => ['nullable', 'integer', 'min:0', 'max:32767'],
            'v_ha_idosos'                            => ['nullable', 'string', 'max:10'],
            'v_ha_criancas'                          => ['nullable', 'string', 'max:10'],
            'v_ha_dificuldade_locomocao'             => ['nullable', 'string', 'max:10'],
            'v_endereco_imovel'                      => ['nullable', 'string', 'max:255'],
            'v_bairro'                               => ['nullable', 'string', 'max:100'],
            'v_municipio'                            => ['nullable', 'integer', 'min:1'],
            'v_municipio_nome'                       => ['nullable', 'string', 'max:255'],
            'v_cep'                                  => ['nullable', 'string', 'regex:/^\d{8}$/'],
            'v_latitude'                             => ['nullable', 'numeric', 'between:-90,90'],
            'v_longitude'                            => ['nullable', 'numeric', 'between:-180,180'],
            'v_abastecimento_agua'                   => ['nullable', 'string', 'max:10'],
            'v_esgotamento_sanitario'                => ['nullable', 'string', 'max:10'],
            'v_drenagem_superficial'                 => ['nullable', 'string', 'max:10'],
            'v_sistema_viario_acesso'                => ['nullable', 'string', 'max:255'],
            'v_tipo_revestimento'                    => ['nullable', 'string', 'max:50'],
            'v_condicoes_acesso'                     => ['nullable', 'string', 'max:500'],
            'v_numero_moradias_terreno'              => ['nullable', 'integer', 'min:0', 'max:32767'],
            'v_distancia_encosta'                    => ['nullable', 'string', 'max:50'],
            'v_material_construtivo'                 => ['nullable', 'string', 'max:255'],
            'v_conservacao_estrutural'               => ['nullable', 'string', 'max:1000'],
            'v_elementos_estruturais'                => ['nullable', 'string', 'max:1000'],
            'v_elementos_construtivos'               => ['nullable', 'string', 'max:1000'],
            'v_agentes_potencializadores'            => ['nullable', 'string', 'max:1000'],
            'v_processos_geodinamicos'               => ['nullable', 'string', 'max:1000'],
            'v_danos_estruturais'                    => ['nullable', 'string', 'max:20'],
            'v_manutencao_uso_ocupacao'              => ['nullable', 'string', 'max:20'],
            'v_historico'                            => ['nullable', 'string', 'max:65535'],
            'v_patologia_rachaduras'                 => ['nullable', 'boolean'],
            'v_patologia_trincas'                    => ['nullable', 'boolean'],
            'v_patologia_fissuras_estruturais'       => ['nullable', 'boolean'],
            'v_patologia_deformacoes_estruturais'    => ['nullable', 'boolean'],
            'v_patologia_infiltracoes'               => ['nullable', 'boolean'],
            'v_patologia_corrosao_armaduras'         => ['nullable', 'boolean'],
            'v_patologia_desagregacao'               => ['nullable', 'boolean'],
            'v_patologia_eflorescencia'              => ['nullable', 'boolean'],
            'v_patologia_desplacamento'              => ['nullable', 'boolean'],
            'v_patologia_fundacoes'                  => ['nullable', 'boolean'],
            'v_patologia_instabilidade_talude'       => ['nullable', 'boolean'],
            'v_patologia_movimentacao_solo'          => ['nullable', 'boolean'],
            'v_patologia_tombamento_muralhas'        => ['nullable', 'boolean'],
            'v_patologia_inundacoes'                 => ['nullable', 'boolean'],
            'v_patologia_alagamentos'                => ['nullable', 'boolean'],
            'v_patologia_enxurradas'                 => ['nullable', 'boolean'],
            'v_patologia_madeira'                    => ['nullable', 'boolean'],
            'v_patologia_elementos_nao_estruturais'  => ['nullable', 'boolean'],
            'v_patologia_falha_drenagem'             => ['nullable', 'boolean'],
            'v_patologia_queda_arvores'              => ['nullable', 'boolean'],
            'v_patologia_outros'                     => ['nullable', 'boolean'],
            'v_patologia_outros_descricao'           => ['nullable', 'string', 'max:65535'],
            'v_enc_interdicao_parcial'               => ['nullable', 'boolean'],
            'v_enc_interdicao_total'                 => ['nullable', 'boolean'],
            'v_enc_remocao_temporaria'               => ['nullable', 'boolean'],
            'v_enc_remocao_definitiva'               => ['nullable', 'boolean'],
            'v_enc_isolamento_area'                  => ['nullable', 'boolean'],
            'v_enc_desocupacao_abrigo'               => ['nullable', 'boolean'],
            'v_enc_notificacao_responsavel'          => ['nullable', 'boolean'],
            'v_enc_contratacao_responsavel'          => ['nullable', 'boolean'],
            'v_enc_comunicacao_orgaos'               => ['nullable', 'boolean'],
            'v_enc_apoio_social'                     => ['nullable', 'boolean'],
            'v_enc_outros'                           => ['nullable', 'boolean'],
            'v_enc_outros_descricao'                 => ['nullable', 'string', 'max:65535'],
            'v_bens_residencia'                      => ['nullable', 'boolean'],
            'v_bens_muros'                           => ['nullable', 'boolean'],
            'v_bens_vias_publicas'                   => ['nullable', 'boolean'],
            'v_bens_pontes'                          => ['nullable', 'boolean'],
            'v_bens_viadutos'                        => ['nullable', 'boolean'],
            'v_bens_comercios'                       => ['nullable', 'boolean'],
            'v_bens_galpoes'                         => ['nullable', 'boolean'],
            'v_bens_predios_publicos'                => ['nullable', 'boolean'],
            'v_bens_edificios_publicos'              => ['nullable', 'boolean'],
            'v_bens_outros'                          => ['nullable', 'boolean'],
            'v_bens_outros_descricao'                => ['nullable', 'string', 'max:65535'],
            'v_orgao_copasa'                         => ['nullable', 'boolean'],
            'v_orgao_cemig'                          => ['nullable', 'boolean'],
            'v_orgao_secretaria_municipal'           => ['nullable', 'boolean'],
            'v_orgao_defesa_civil_estadual'          => ['nullable', 'boolean'],
            'v_orgao_dnit'                           => ['nullable', 'boolean'],
            'v_orgao_pm'                             => ['nullable', 'string', 'max:255'],
            'v_orgao_bm'                             => ['nullable', 'string', 'max:255'],
            'v_orgao_crea'                           => ['nullable', 'boolean'],
            'v_orgao_emater'                         => ['nullable', 'boolean'],
            'v_orgao_seapa'                          => ['nullable', 'boolean'],
            'v_orgao_outros'                         => ['nullable', 'boolean'],
            'v_orgao_outros_descricao'               => ['nullable', 'string', 'max:65535'],
        ];
    }

    public function messages(): array
    {
        return [
            'v_solicitante_cpf.regex'    => 'CPF deve conter somente 11 dígitos numéricos.',
            'v_solicitante_cep.regex'    => 'CEP deve conter somente 8 dígitos numéricos.',
            'v_cep.regex'                => 'CEP deve conter somente 8 dígitos numéricos.',
            'v_contato_telefone.regex'   => 'Telefone deve conter somente números.',
            'v_solicitante_telefone.regex' => 'Telefone deve conter somente números.',
        ];
    }
}
