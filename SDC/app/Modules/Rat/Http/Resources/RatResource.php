<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource completo para visualização/edição/impressão detalhada de um RAT.
 * Mapeia colunas do banco de dados para os nomes de campos esperados pelo frontend.
 */
class RatResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var \App\Modules\Rat\Models\RatOcorrencia $this */

        // Dados gerais da ocorrência (rat_relato_dados_gerais)
        $dg = $this->dados_gerais ?? [];

        // Vistoria (rat_relato_vistoria)
        $vis = $this->vistoria ?? [];

        // Anexos da tabela rat_anexos (via relacionamento ratAnexos)
        $anexos = [];
        if ($this->relationLoaded('ratAnexos')) {
            $anexos = $this->ratAnexos->map(fn ($a) => [
                'id'             => $a->id,
                'nome_original'  => $a->nome_original,
                'nome_arquivo'   => $a->nome_arquivo,
                'mime_type'      => $a->mime_type,
                'tamanho_bytes'  => $a->tamanho_bytes,
                'url'            => $a->url,
                'categoria'      => $a->categoria instanceof \BackedEnum ? $a->categoria->value : $a->categoria,
                'descricao'      => $a->descricao,
                'created_at'     => $a->created_at?->toIso8601String(),
            ])->toArray();
        }

        return [
            'id'           => $this->id,
            'protocolo'    => $this->protocolo ?? $this->numero_bos ?? null,
            'numero_bos'   => $this->numero_bos,
            'status'       => $this->status,
            'status_label' => $this->status === 1 ? 'Finalizado' : 'Rascunho',
            'tem_vistoria' => (bool) ($dg['tem_vistoria'] ?? false),

            // ── Dados Gerais (campos que a aba Dados Gerais lê via props.rat.dados_gerais) ──
            'dados_gerais' => [
                'data_fato'                  => $dg['data_fato'] ?? null,
                'data_inicio_atividade'      => $dg['data_inicio_atividade'] ?? null,
                'data_termino_atividade'     => $dg['data_termino_atividade'] ?? null,
                'nat_codigo'                 => $dg['nat_codigo'] ?? null,
                'nat_cobrade_id'             => $dg['nat_cobrade_id'] ?? null,
                'nat_ocorrencia'             => $dg['nat_ocorrencia'] ?? null,
                'nat_nome_operacao'          => $dg['nat_nome_operacao'] ?? null,
                'uni_responsavel_municipio'  => $dg['uni_responsavel_municipio'] ?? null,
                'uni_responsavel_codigo'     => $dg['uni_responsavel_codigo'] ?? null,
                'uni_responsavel_unidade'    => $dg['uni_responsavel_unidade'] ?? null,
                'uni_bo_cod_unidade'         => $dg['uni_bo_cod_unidade'] ?? null,
                'uni_bo_ano'                 => $dg['uni_bo_ano'] ?? null,
                'uni_bo_sequencial'          => $dg['uni_bo_sequencial'] ?? null,
                'descricao'                  => $dg['descricao'] ?? null,
                'tem_vistoria'               => (bool) ($dg['tem_vistoria'] ?? false),
            ],

            // ── Comunicação: nomes que RatCommunicationSection.vue usa ──
            'comunicacao' => [
                'data_comunicacao' => $dg['com_ocorrencia_data'] ?? null,
                'tipo_solicitacao' => $dg['com_ocorrencia_atendimento'] ?? null,
            ],

            // ── Local: nomes que RatLocationSection.vue usa ──
            'local' => [
                'pais'       => $dg['local_pais'] ?? 'BR',
                'pais_id'    => $dg['local_pais'] ?? 'BR',
                'uf'         => $dg['local_estadouf'] ?? null,
                'municipio_id' => $dg['local_municipio'] ?? null,
            ],

            // ── Endereço: nomes que RatAddressSection.vue usa ──
            'endereco' => [
                'cep'             => $dg['local_cep'] ?? null,
                'logradouro'      => $dg['local_logradoura_1'] ?? null,
                'bairro'          => $dg['local_bairro'] ?? null,
                'numero'          => $dg['local_numero'] ?? null,
                'complemento'     => $dg['local_complemento'] ?? null,
                'km'              => $dg['local_km'] ?? null,
                'cruzamento'      => $dg['local_cruzamento'] ?? null,
                'ponto_referencia' => $dg['local_ponto_referencia'] ?? null,
                'tipo_localizacao' => $dg['local_ocorrencia_tipo'] ?? null,
                'latitude'        => $dg['local_latitude'] ?? null,
                'longitude'       => $dg['local_longitude'] ?? null,
            ],

            // ── Recursos: nomes que RatResources.vue / RatResourcesSection.vue usam ──
            'recursos' => collect($this->recursos ?? [])->map(function ($c) {
                if (is_array($c)) {
                    $c = (object) $c;
                }
                if (!$c) return null;

                $saida   = !empty($c->viatura_saida)   && $c->viatura_saida   !== '0' ? Carbon::parse($c->viatura_saida)->format('Y-m-d\TH:i')   : null;
                $chegada = !empty($c->viatura_chegada) && $c->viatura_chegada !== '0' ? Carbon::parse($c->viatura_chegada)->format('Y-m-d\TH:i') : null;

                return [
                    'id'               => $c->id ?? null,
                    'seq'              => $c->seq ?? null,
                    'tipo_recurso'     => ($c->recurso_tipo ?? null) === 'pe' ? 'pessoal' : ($c->recurso_tipo ?? null),
                    'categoria'        => $c->viatura_tipo ?? null,
                    'identificacao'    => $c->viatura_placa ?? null,
                    'orgao_responsavel'=> $c->viatura_orgao ?? null,
                    'data_saida'       => $saida,
                    'data_chegada'     => $chegada,
                    'km_percorrido'    => $c->viatura_km ?? null,
                    'local_origem'     => $c->viatura_local_origem ?? null,
                    'local_destino'    => $c->viatura_local_destino ?? null,
                    'quantidade'       => $c->viatura_quantidade ?? null,
                    'condicao'         => ($c->viatura_condicao ?? null) === 'boa' ? 'operacional' : ($c->viatura_condicao ?? null),
                    'descricao'        => $c->recurso_descricao ?? null,
                    'agentes'          => isset($c->agentes) ? collect($c->agentes)->map(fn ($a) => [
                        'id'            => is_array($a) ? ($a['id'] ?? null)            : ($a->id ?? null),
                        'corporacao'    => is_array($a) ? ($a['corporacao'] ?? null)    : ($a->corporacao ?? null),
                        'masp'          => is_array($a) ? ($a['masp'] ?? null)          : ($a->masp ?? null),
                        'nome_completo' => is_array($a) ? ($a['nome_completo'] ?? null) : ($a->nome_completo ?? null),
                        'funcao'        => is_array($a) ? ($a['funcao'] ?? null)        : ($a->funcao ?? null),
                    ])->toArray() : [],
                ];
            })->filter()->values()->toArray(),

            // ── Envolvidos: nomes que RatEnvolvidosSection.vue usa ──
            'envolvidos' => collect($this->envolvidos ?? [])->map(function ($c) {
                if (!is_array($c)) {
                    $c = is_object($c) ? (array) $c : [];
                }
                return [
                    'id'               => $c['id'] ?? null,
                    'tipo_envolvimento' => $c['g_tipo_pessoa'] ?? null,
                    'nome'             => $c['p_nome_completo'] ?? null,
                    'rg'               => $c['p_numero'] ?? null,
                    'cpf'              => $c['p_cpf'] ?? null,
                    'sexo'             => $c['p_sexo'] ?? null,
                    'data_nascimento'  => $c['p_data_nascimento'] ?? null,
                    'telefone'         => $c['p_telefone_residencial'] ?? null,
                    'cep'              => $c['p_end_cep'] ?? null,
                    'endereco'         => $c['p_end_logradouro'] ?? null,
                    'bairro'           => $c['p_end_bairro'] ?? null,
                    'municipio'        => $c['p_end_municipio'] ?? null,
                    'uf'               => $c['p_end_estado_uf'] ?? null,
                ];
            })->filter()->values()->toArray(),

            // ── Vistoria: estrutura aninhada que RatVistoriaSection.vue usa ──
            'vistoria' => empty($vis) ? null : [
                'solicitante' => [
                    'nome'     => $vis['v_solicitante_nome'] ?? null,
                    'cpf'      => $vis['v_solicitante_cpf'] ?? null,
                    'telefone' => $vis['v_solicitante_telefone'] ?? null,
                    'endereco' => $vis['v_solicitante_endereco'] ?? null,
                    'bairro'   => $vis['v_solicitante_bairro'] ?? null,
                    'cep'      => $vis['v_solicitante_cep'] ?? null,
                ],
                'imovel' => [
                    'endereco' => $vis['v_endereco_imovel'] ?? null,
                    'bairro'   => $vis['v_bairro'] ?? null,
                    'municipio'=> $vis['v_municipio'] ?? null,
                    'cep'      => $vis['v_cep'] ?? null,
                ],
                'estrutura' => [
                    'tipo_imovel'       => $vis['v_tipo_imovel'] ?? null,
                    'tipo_construcao'   => $vis['v_tipo_construcao'] ?? null,
                    'tipo_destinacao'   => $vis['v_tipo_destinacao'] ?? null,
                    'tipo_edificacao'   => $vis['v_tipo_edificacao'] ?? null,
                    'sistema_estrutural'=> $vis['v_sistema_estrutural'] ?? null,
                    'num_pavimentos'    => $vis['v_numero_pavimentos'] ?? null,
                    'estado_conservacao'=> $vis['v_estado_conservacao'] ?? null,
                    'regime_ocupacao'   => $vis['v_regime_ocupacao'] ?? null,
                ],
                'moradores' => [
                    'proprietario' => $vis['v_proprietario_morador'] ?? null,
                    'telefone'     => $vis['v_contato_telefone'] ?? null,
                    'num_moradores'=> $vis['v_numero_moradores'] ?? null,
                    'ha_idosos'    => (bool) ($vis['v_ha_idosos'] ?? false),
                    'ha_criancas'  => (bool) ($vis['v_ha_criancas'] ?? false),
                    'ha_pcd'       => !empty($vis['v_ha_dificuldade_locomocao']),
                ],
                'patologias' => [
                    'rachaduras'               => (bool) ($vis['v_patologia_rachaduras'] ?? false),
                    'trincas'                  => (bool) ($vis['v_patologia_trincas'] ?? false),
                    'fissuras_estruturais'     => (bool) ($vis['v_patologia_fissuras_estruturais'] ?? false),
                    'deformacoes_estruturais'  => (bool) ($vis['v_patologia_deformacoes_estruturais'] ?? false),
                    'infiltracoes'             => (bool) ($vis['v_patologia_infiltracoes'] ?? false),
                    'corrosao_armaduras'       => (bool) ($vis['v_patologia_corrosao_armaduras'] ?? false),
                    'desagregacao'             => (bool) ($vis['v_patologia_desagregacao'] ?? false),
                    'eflorescencia'            => (bool) ($vis['v_patologia_eflorescencia'] ?? false),
                    'desplacamento'            => (bool) ($vis['v_patologia_desplacamento'] ?? false),
                    'fundacoes'                => (bool) ($vis['v_patologia_fundacoes'] ?? false),
                    'instabilidade_talude'     => (bool) ($vis['v_patologia_instabilidade_talude'] ?? false),
                    'movimentacao_solo'        => (bool) ($vis['v_patologia_movimentacao_solo'] ?? false),
                    'tombamento_muralhas'      => (bool) ($vis['v_patologia_tombamento_muralhas'] ?? false),
                    'inundacoes'               => (bool) ($vis['v_patologia_inundacoes'] ?? false),
                    'alagamentos'              => (bool) ($vis['v_patologia_alagamentos'] ?? false),
                    'enxurradas'               => (bool) ($vis['v_patologia_enxurradas'] ?? false),
                    'madeira'                  => (bool) ($vis['v_patologia_madeira'] ?? false),
                    'elementos_nao_estruturais'=> (bool) ($vis['v_patologia_elementos_nao_estruturais'] ?? false),
                    'falha_drenagem'           => (bool) ($vis['v_patologia_falha_drenagem'] ?? false),
                    'queda_arvores'            => (bool) ($vis['v_patologia_queda_arvores'] ?? false),
                    'outros'                   => (bool) ($vis['v_patologia_outros'] ?? false),
                    'outros_descricao'         => $vis['v_patologia_outros_descricao'] ?? null,
                ],
                'observacoes' => $vis['v_historico'] ?? null,
            ],

            // ── Histórico (JSON column em rat_ocorrencias) ──
            'historico' => $this->historico ?? [],

            // ── Anexos (rat_anexos table via relationship) ──
            'anexos' => $anexos,

            // ── Meta ──
            'criado_por'     => $this->creator?->name ?? 'Sistema',
            'atualizado_por' => $this->updater?->name ?? null,
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}
