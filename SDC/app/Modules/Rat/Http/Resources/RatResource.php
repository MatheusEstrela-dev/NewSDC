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

        $dt = static fn ($v) => $v ? Carbon::parse($v)->format('Y-m-d\TH:i') : null;

        return [
            'id'             => $this->id,
            'sequencial_ano' => $this->sequencial_ano,
            'protocolo'      => $this->protocolo ?? $this->numero_bos ?? null,
            'numero_bos'     => $this->numero_bos,
            'municipio'      => $dg['local_municipio_nome'] ?? $dg['local_municipio'] ?? $dg['uni_responsavel_municipio'] ?? null,
            'status'         => match((int) $this->status) {
                1       => 'finalizado',
                default => 'em_andamento',
            },
            'status_label'   => match((int) $this->status) {
                1       => 'Finalizado',
                default => 'Em Andamento',
            },
            'tem_vistoria' => (bool) ($dg['tem_vistoria'] ?? false),

            // ── Dados Gerais (campos que a aba Dados Gerais lê via props.rat.dados_gerais) ──
            'dados_gerais' => [
                'data_fato'                  => $dt($dg['data_fato'] ?? null),
                'data_inicio_atividade'      => $dt($dg['data_inicio_atividade'] ?? null),
                'data_termino_atividade'     => $dt($dg['data_termino_atividade'] ?? null),
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
                // Campos de comunicação usados pelo BoletimDadosGerais
                'data_comunicacao'           => $dt($dg['com_ocorrencia_data'] ?? null),
                'com_ocorrencia_atendimento' => $dg['com_ocorrencia_atendimento'] ?? null,
                // Campos de local/endereço usados pelo BoletimDadosGerais
                'local_municipio'            => $dg['local_municipio'] ?? null,
                'local_pais'                 => $dg['local_pais'] ?? null,
                'local_estadouf'             => $dg['local_estadouf'] ?? null,
                'local_cep'                  => $dg['local_cep'] ?? null,
                'local_logradoura_1'         => $dg['local_logradoura_1'] ?? null,
                'local_bairro'               => $dg['local_bairro'] ?? null,
                'local_complemento'          => $dg['local_complemento'] ?? null,
                'local_numero'               => $dg['local_numero'] ?? null,
                'local_km'                   => $dg['local_km'] ?? null,
                'local_cruzamento'           => $dg['local_cruzamento'] ?? null,
                'local_ponto_referencia'     => $dg['local_ponto_referencia'] ?? null,
                'local_ocorrencia_tipo'      => $dg['local_ocorrencia_tipo'] ?? null,
            ],

            // ── Comunicação: nomes que RatCommunicationSection.vue usa ──
            'comunicacao' => [
                'data_comunicacao'  => $dt($dg['com_ocorrencia_data'] ?? null),
                'tipo_solicitacao'  => $dg['com_ocorrencia_atendimento'] ?? null,
                'telefone_contato'  => $dg['com_telefone_contato'] ?? null,
                'nome_solicitante'  => $dg['com_nome_solicitante'] ?? null,
            ],

            // ── Local: nomes que RatLocationSection.vue usa ──
            'local' => [
                'pais'           => $dg['local_pais'] ?? 'BR',
                'pais_id'        => $dg['local_pais'] ?? 'BR',
                'uf'             => $dg['local_estadouf'] ?? null,
                'municipio'      => $dg['local_municipio_nome'] ?? $dg['local_municipio'] ?? null,
                'municipio_id'   => $dg['local_municipio'] ?? null,
                'municipio_nome' => $dg['local_municipio_nome'] ?? null,
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
                $tipoRecurso = ($c->recurso_tipo ?? null) === 'pe' ? 'pessoal' : ($c->recurso_tipo ?? null);
                $condicao    = ($c->viatura_condicao ?? null) === 'boa' ? 'operacional' : ($c->viatura_condicao ?? null);

                return [
                    'id'               => $c->id ?? null,
                    'seq'              => $c->seq ?? null,
                    // Nomes usados pelo formulário de edição
                    'tipo_recurso'     => $tipoRecurso,
                    'categoria'        => $c->viatura_tipo ?? null,
                    'identificacao'    => $c->viatura_placa ?? $c->viatura_prefixo ?? null,
                    'orgao_responsavel'=> $c->viatura_orgao ?? null,
                    'data_saida'       => $saida,
                    'data_chegada'     => $chegada,
                    'km_percorrido'    => $c->viatura_km ?? null,
                    'local_origem'     => $c->viatura_local_origem ?? null,
                    'local_destino'    => $c->viatura_local_destino ?? null,
                    'quantidade'       => $c->viatura_quantidade ?? null,
                    'capacidade'       => $c->viatura_capacidade ?? null,
                    'condicao'         => $condicao,
                    'operador'         => $c->viatura_operador ?? null,
                    'contato_emergencia' => $c->viatura_contato ?? null,
                    'observacoes'      => $c->viatura_descricao ?? null,
                    'descricao'        => $c->recurso_descricao ?? null,
                    // Aliases com prefixo r_* usados pelo BoletimRecursos.vue
                    'r_tipo_recurso'        => $tipoRecurso,
                    'r_categoria'           => $c->viatura_tipo ?? null,
                    'r_orgao_responsavel'   => $c->viatura_orgao ?? null,
                    'r_identificacao'       => $c->viatura_placa ?? $c->viatura_prefixo ?? null,
                    'r_descricao'           => $c->recurso_descricao ?? null,
                    'r_data_saida'          => $saida,
                    'r_hora_saida'          => $saida,
                    'r_data_chegada'        => $chegada,
                    'r_hora_chegada'        => $chegada,
                    'r_km_percorrido'       => $c->viatura_km ?? null,
                    'r_quantidade'          => $c->viatura_quantidade ?? null,
                    'r_origem'              => $c->viatura_local_origem ?? null,
                    'r_destino'             => $c->viatura_local_destino ?? null,
                    'r_capacidade'          => $c->viatura_capacidade ?? null,
                    'r_condicao'            => $condicao,
                    'r_operador_responsavel'=> $c->viatura_operador ?? null,
                    'r_contato'             => $c->viatura_contato ?? null,
                    // Campos diretos usados no v-if e exibição do BoletimRecursos.vue
                    'viatura_tipo'          => $c->viatura_tipo ?? null,
                    'viatura_placa'         => $c->viatura_placa ?? null,
                    'viatura_orgao'         => $c->viatura_orgao ?? null,
                    'viatura_descricao'     => $c->viatura_descricao ?? null,
                    'recurso_descricao'     => $c->recurso_descricao ?? null,
                    'agentes'          => isset($c->agentes) ? collect($c->agentes)->map(fn ($a) => [
                        'id'            => is_array($a) ? ($a['id'] ?? null)            : ($a->id ?? null),
                        'corporacao'    => is_array($a) ? ($a['corporacao'] ?? null)    : ($a->corporacao ?? null),
                        'matricula'     => is_array($a) ? ($a['matricula'] ?? null)     : ($a->matricula ?? null),
                        'masp'          => is_array($a) ? ($a['masp'] ?? null)          : ($a->masp ?? null),
                        'nome_completo' => is_array($a) ? ($a['nome_completo'] ?? null) : ($a->nome_completo ?? null),
                        'pg_cargo'      => is_array($a) ? ($a['pg_cargo'] ?? null)      : ($a->pg_cargo ?? null),
                        'orgao'         => is_array($a) ? ($a['orgao'] ?? null)         : ($a->orgao ?? null),
                        'unidade'       => is_array($a) ? ($a['unidade'] ?? null)       : ($a->unidade ?? null),
                        'funcao'        => is_array($a) ? ($a['funcao'] ?? null)        : ($a->funcao ?? null),
                        'is_condutor'   => is_array($a) ? (bool) ($a['is_condutor'] ?? false) : (bool) ($a->is_condutor ?? false),
                    ])->toArray() : [],
                ];
            })->filter()->values()->toArray(),

            // ── Envolvidos: nomes que RatEnvolvidosSection.vue usa ──
            'envolvidos' => collect($this->envolvidos ?? [])->map(function ($c) {
                if (!is_array($c)) {
                    $c = is_object($c) ? (array) $c : [];
                }
                // Mantém todos os campos raw (p_*/g_*) que BoletimEnvolvidos.vue usa,
                // e adiciona aliases para o formulário de edição.
                return array_merge($c, [
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
                    // Campos adicionais
                    'p_uf'             => $c['p_uf'] ?? null,
                    'p_idade_aparente' => $c['p_idade_aparente'] ?? null,
                ]);
            })->filter()->values()->toArray(),

            // ── Vistoria: estrutura aninhada que RatVistoriaSection.vue usa ──
            'vistoria' => empty($vis) ? null : [
                'solicitante' => [
                    'nome'     => $vis['v_solicitante_nome']     ?? null,
                    'cpf'      => $vis['v_solicitante_cpf']      ?? null,
                    'telefone' => $vis['v_solicitante_telefone'] ?? null,
                    'endereco' => $vis['v_solicitante_endereco'] ?? null,
                    'bairro'   => $vis['v_solicitante_bairro']   ?? null,
                    'cep'      => $vis['v_solicitante_cep']      ?? null,
                ],
                'imovel' => [
                    'endereco'    => $vis['v_endereco_imovel'] ?? null,
                    'complemento' => $vis['v_local_complemento'] ?? null,
                    'bairro'      => $vis['v_bairro']          ?? null,
                    'municipio'   => $vis['v_municipio']       ?? null,
                    'cep'         => $vis['v_cep']             ?? null,
                    'latitude'    => $vis['v_latitude']        ?? null,
                    'longitude'   => $vis['v_longitude']       ?? null,
                ],
                'moradores' => [
                    'proprietario'  => $vis['v_proprietario_morador'] ?? null,
                    'telefone'      => $vis['v_contato_telefone']     ?? null,
                    'num_moradores' => $vis['v_numero_moradores']     ?? null,
                    'ha_idosos'     => (bool) ($vis['v_ha_idosos']   ?? false),
                    'ha_criancas'   => (bool) ($vis['v_ha_criancas'] ?? false),
                    'ha_pcd'        => !empty($vis['v_ha_dificuldade_locomocao']),
                ],
                'estrutura' => [
                    'tipo_area'           => $vis['v_tipo_area']           ?? null,
                    'tipo_imovel'         => $vis['v_tipo_imovel']         ?? null,
                    'tipo_construcao'     => $vis['v_tipo_construcao']     ?? null,
                    'tipo_destinacao'     => $vis['v_tipo_destinacao']     ?? null,
                    'tipo_edificacao'     => $vis['v_tipo_edificacao']     ?? null,
                    'sistema_estrutural'  => $vis['v_sistema_estrutural']  ?? null,
                    'tipo_terreno_relevo' => $vis['v_tipo_terreno_relevo'] ?? null,
                    'tipo_localizacao'    => $vis['v_tipo_localizacao']    ?? null,
                    'num_pavimentos'      => $vis['v_numero_pavimentos']   ?? null,
                    'estado_conservacao'  => $vis['v_estado_conservacao']  ?? null,
                    'regime_ocupacao'     => $vis['v_regime_ocupacao']     ?? null,
                ],
                'infraestrutura' => [
                    'abastecimento_agua'    => $vis['v_abastecimento_agua']     ?? null,
                    'esgotamento_sanitario' => $vis['v_esgotamento_sanitario']  ?? null,
                    'drenagem_superficial'  => $vis['v_drenagem_superficial']   ?? null,
                    'sistema_viario_acesso' => $vis['v_sistema_viario_acesso']  ?? null,
                    'tipo_revestimento'     => $vis['v_tipo_revestimento']      ?? null,
                    'condicoes_acesso'      => $vis['v_condicoes_acesso']       ?? null,
                    'num_moradias_terreno'  => $vis['v_numero_moradias_terreno']?? null,
                    'distancia_encosta'     => $vis['v_distancia_encosta']      ?? null,
                ],
                'tecnico' => [
                    'material_construtivo'      => $vis['v_material_construtivo']       ?? null,
                    'danos_estruturais'         => $vis['v_danos_estruturais']          ?? null,
                    'manutencao_uso_ocupacao'   => $vis['v_manutencao_uso_ocupacao']    ?? null,
                    'conservacao_estrutural'    => $vis['v_conservacao_estrutural']     ?? null,
                    'elementos_estruturais'     => $vis['v_elementos_estruturais']      ?? null,
                    'elementos_construtivos'    => $vis['v_elementos_construtivos']     ?? null,
                    'agentes_potencializadores' => $vis['v_agentes_potencializadores']  ?? null,
                    'processos_geodinamicos'    => $vis['v_processos_geodinamicos']     ?? null,
                    'historico'                 => $vis['v_historico']                  ?? null,
                ],
                'patologias' => [
                    'rachaduras'                => (bool) ($vis['v_patologia_rachaduras']                ?? false),
                    'trincas'                   => (bool) ($vis['v_patologia_trincas']                   ?? false),
                    'fissuras_estruturais'      => (bool) ($vis['v_patologia_fissuras_estruturais']      ?? false),
                    'deformacoes_estruturais'   => (bool) ($vis['v_patologia_deformacoes_estruturais']   ?? false),
                    'infiltracoes'              => (bool) ($vis['v_patologia_infiltracoes']              ?? false),
                    'corrosao_armaduras'        => (bool) ($vis['v_patologia_corrosao_armaduras']        ?? false),
                    'desagregacao'              => (bool) ($vis['v_patologia_desagregacao']              ?? false),
                    'eflorescencia'             => (bool) ($vis['v_patologia_eflorescencia']             ?? false),
                    'desplacamento'             => (bool) ($vis['v_patologia_desplacamento']             ?? false),
                    'fundacoes'                 => (bool) ($vis['v_patologia_fundacoes']                 ?? false),
                    'instabilidade_talude'      => (bool) ($vis['v_patologia_instabilidade_talude']      ?? false),
                    'movimentacao_solo'         => (bool) ($vis['v_patologia_movimentacao_solo']         ?? false),
                    'tombamento_muralhas'       => (bool) ($vis['v_patologia_tombamento_muralhas']       ?? false),
                    'inundacoes'                => (bool) ($vis['v_patologia_inundacoes']                ?? false),
                    'alagamentos'               => (bool) ($vis['v_patologia_alagamentos']               ?? false),
                    'enxurradas'                => (bool) ($vis['v_patologia_enxurradas']                ?? false),
                    'madeira'                   => (bool) ($vis['v_patologia_madeira']                   ?? false),
                    'elementos_nao_estruturais' => (bool) ($vis['v_patologia_elementos_nao_estruturais'] ?? false),
                    'falha_drenagem'            => (bool) ($vis['v_patologia_falha_drenagem']            ?? false),
                    'queda_arvores'             => (bool) ($vis['v_patologia_queda_arvores']             ?? false),
                    'outros'                    => (bool) ($vis['v_patologia_outros']                    ?? false),
                    'outros_descricao'          => $vis['v_patologia_outros_descricao']                  ?? null,
                ],
                'encaminhamentos' => [
                    'interdicao_parcial'      => (bool) ($vis['v_enc_interdicao_parcial']      ?? false),
                    'interdicao_total'        => (bool) ($vis['v_enc_interdicao_total']        ?? false),
                    'remocao_temporaria'      => (bool) ($vis['v_enc_remocao_temporaria']      ?? false),
                    'remocao_definitiva'      => (bool) ($vis['v_enc_remocao_definitiva']      ?? false),
                    'isolamento_area'         => (bool) ($vis['v_enc_isolamento_area']         ?? false),
                    'desocupacao_abrigo'      => (bool) ($vis['v_enc_desocupacao_abrigo']      ?? false),
                    'notificacao_responsavel' => (bool) ($vis['v_enc_notificacao_responsavel'] ?? false),
                    'contratacao_responsavel' => (bool) ($vis['v_enc_contratacao_responsavel'] ?? false),
                    'comunicacao_orgaos'      => (bool) ($vis['v_enc_comunicacao_orgaos']      ?? false),
                    'apoio_social'            => (bool) ($vis['v_enc_apoio_social']            ?? false),
                    'outros'                  => (bool) ($vis['v_enc_outros']                  ?? false),
                    'outros_descricao'        => $vis['v_enc_outros_descricao']                ?? null,
                ],
                'bens_afetados' => [
                    'residencia'         => (bool) ($vis['v_bens_residencia']         ?? false),
                    'muros'              => (bool) ($vis['v_bens_muros']              ?? false),
                    'vias_publicas'      => (bool) ($vis['v_bens_vias_publicas']      ?? false),
                    'pontes'             => (bool) ($vis['v_bens_pontes']             ?? false),
                    'viadutos'           => (bool) ($vis['v_bens_viadutos']           ?? false),
                    'comercios'          => (bool) ($vis['v_bens_comercios']          ?? false),
                    'galpoes'            => (bool) ($vis['v_bens_galpoes']            ?? false),
                    'predios_publicos'   => (bool) ($vis['v_bens_predios_publicos']   ?? false),
                    'edificios_publicos' => (bool) ($vis['v_bens_edificios_publicos'] ?? false),
                    'outros'             => (bool) ($vis['v_bens_outros']             ?? false),
                    'outros_descricao'   => $vis['v_bens_outros_descricao']           ?? null,
                ],
                'orgaos_acionados' => [
                    'copasa'                => (bool) ($vis['v_orgao_copasa']               ?? false),
                    'cemig'                 => (bool) ($vis['v_orgao_cemig']                ?? false),
                    'secretaria_municipal'  => (bool) ($vis['v_orgao_secretaria_municipal'] ?? false),
                    'defesa_civil_estadual' => (bool) ($vis['v_orgao_defesa_civil_estadual']?? false),
                    'dnit'                  => (bool) ($vis['v_orgao_dnit']                 ?? false),
                    'pm'                    => $vis['v_orgao_pm']                            ?? '',
                    'bm'                    => $vis['v_orgao_bm']                            ?? '',
                    'crea'                  => (bool) ($vis['v_orgao_crea']                 ?? false),
                    'emater'                => (bool) ($vis['v_orgao_emater']               ?? false),
                    'seapa'                 => (bool) ($vis['v_orgao_seapa']                ?? false),
                    'outros'                => (bool) ($vis['v_orgao_outros']               ?? false),
                    'outros_descricao'      => $vis['v_orgao_outros_descricao']             ?? null,
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
