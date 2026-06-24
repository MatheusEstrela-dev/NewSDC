<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\Models\RatOcorrencia;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Service responsável por normalizar dados de RAT para exportação Power BI.
 *
 * FLUXO: Request (filtros) → RatQueryService → RatOcorrencia[] → array nested
 *
 * Padrão idêntico ao ProcessoExportBIService (Decretações):
 * - getNormalizedDataForPowerBI() como único ponto de entrada público
 * - Retorna estrutura nested: dados_gerais / recursos / envolvidos / vistoria / historico
 * - Datas no formato "Y-m-d H:i:s" para compatibilidade com Power BI
 */
class RatExportBIService
{
    public function __construct(
        private readonly RatQueryService $queryService,
    ) {}

    /**
     * Retorna lista de RATs no formato nested para Power BI.
     */
    public function getNormalizedDataForPowerBI(Request $request): array
    {
        $ocorrencias = $this->queryService->applyFilters($request)->get();

        if ($ocorrencias->isEmpty()) {
            return [];
        }

        return $ocorrencias
            ->map(fn (RatOcorrencia $o) => $this->buildRow($o))
            ->filter()
            ->values()
            ->toArray();
    }

    // =========================================================================
    // PRIVADOS
    // =========================================================================

    private function buildRow(RatOcorrencia $ocorrencia): array
    {
        $dg  = is_array($ocorrencia->dados_gerais) ? $ocorrencia->dados_gerais : [];
        $vis = is_array($ocorrencia->vistoria)      ? $ocorrencia->vistoria     : [];

        return [
            'dados_gerais' => $this->buildDadosGerais($ocorrencia, $dg),
            'recursos'     => $this->buildRecursos($ocorrencia),
            'envolvidos'   => $this->buildEnvolvidos($ocorrencia),
            'vistoria'     => empty($vis) ? null : $this->buildVistoria($vis),
            'historico'    => $this->buildHistorico($ocorrencia, $dg),
        ];
    }

    private function buildDadosGerais(RatOcorrencia $ocorrencia, array $dg): array
    {
        $dataFato = $this->parseDateTime($dg['data_fato'] ?? null);

        return [
            'id'                    => $ocorrencia->id,
            'numero_bos'            => $ocorrencia->numero_bos,
            'status'                => (int) $ocorrencia->status,
            'status_descricao'      => $ocorrencia->status === 1 ? 'Finalizado' : 'Em Andamento',
            'uf'                    => $dg['local_estadouf'] ?? null,
            'municipio'             => $this->formatMunicipio($dg),
            'data_fato'             => $dataFato,
            'ano'                   => $dataFato ? Carbon::parse($dataFato)->format('Y') : null,
            'mes'                   => $dataFato ? Carbon::parse($dataFato)->format('m') : null,
            'cod_cobrade'           => $dg['nat_codigo'] ?? $dg['nat_cobrade_id'] ?? null,
            'cobrade_descricao'     => $dg['nat_ocorrencia'] ?? null,
            'cod_ocorrencia'        => $dg['nat_codigo'] ?? null,
            'nome_operacao'         => $dg['nat_nome_operacao'] ?? null,
            'nome_unidade'          => $dg['uni_responsavel_unidade'] ?? null,
            'municipio_responsavel' => $dg['uni_responsavel_municipio'] ?? null,
            'codigo_unidade'        => $dg['uni_responsavel_codigo'] ?? null,
            'latitude'              => isset($dg['local_latitude'])  ? (float) $dg['local_latitude']  : null,
            'longitude'             => isset($dg['local_longitude']) ? (float) $dg['local_longitude'] : null,
            'local_pais'            => $dg['local_pais'] ?? null,
            'local_logradouro'      => $dg['local_logradoura_1'] ?? null,
            'local_bairro'          => $dg['local_bairro'] ?? null,
            'local_cep'             => $dg['local_cep'] ?? null,
            'local_numero'          => $dg['local_numero'] ?? null,
            'local_complemento'     => $dg['local_complemento'] ?? null,
            'local_tipo_ocorrencia' => $dg['local_ocorrencia_tipo'] ?? null,
            'data_inicio_atividade'  => $this->parseDateTime($dg['data_inicio_atividade'] ?? null),
            'data_termino_atividade' => $this->parseDateTime($dg['data_termino_atividade'] ?? null),
            'comunicacao_data'       => $this->parseDateTime($dg['com_ocorrencia_data'] ?? null),
            'comunicacao_atendimento' => $dg['com_ocorrencia_atendimento'] ?? null,
            'created_at'             => $ocorrencia->created_at?->format('Y-m-d H:i:s'),
            'updated_at'             => $ocorrencia->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function buildRecursos(RatOcorrencia $ocorrencia): array
    {
        $raw = is_array($ocorrencia->recursos) ? $ocorrencia->recursos : [];

        return collect($raw)->map(function ($r, $idx) {
            $r = is_array($r) ? (object) $r : $r;
            $tipoRecurso = ($r->recurso_tipo ?? null) === 'pe' ? 'pessoal' : ($r->recurso_tipo ?? null);

            return [
                'id'                  => $r->id ?? ($idx + 1),
                'seq'                 => $r->seq ?? $idx,
                'tipo_recurso'        => $tipoRecurso,
                'categoria'           => $r->viatura_tipo ?? null,
                'numero_viatura'      => $r->viatura_prefixo ?? $r->viatura_placa ?? null,
                'placa'               => $r->viatura_placa ?? null,
                'padrao'              => null,
                'orgao'               => $r->viatura_orgao ?? null,
                'descricao'           => $r->recurso_descricao ?? $r->viatura_descricao ?? null,
                'problemas'           => false,
                'descricao_problemas' => null,
            ];
        })->filter()->values()->toArray();
    }

    private function buildEnvolvidos(RatOcorrencia $ocorrencia): array
    {
        $raw = is_array($ocorrencia->envolvidos) ? $ocorrencia->envolvidos : [];

        return collect($raw)->map(function ($e, $idx) {
            $e = is_array($e) ? $e : (array) $e;

            return [
                'id'                   => $e['id'] ?? ($idx + 1),
                'tipo_pessoa'          => $e['g_tipo_pessoa'] ?? null,
                'tipo_envolvimento'    => null,
                'codigo_envolvimento'  => null,
                'nome'                 => $e['p_nome_completo'] ?? null,
                'cpf'                  => $e['p_cpf'] ?? null,
                'email'                => $e['p_email'] ?? null,
                'telefone_residencial' => $e['p_telefone_residencial'] ?? null,
                'telefone_comercial'   => $e['p_telefone_comercial'] ?? null,
                'sexo'                 => $e['p_sexo'] ?? null,
                'estado_civil'         => $e['p_estado_civil'] ?? null,
                'data_nascimento'      => $e['p_data_nascimento'] ?? null,
                'naturalidade_uf'      => $e['p_naturalidade_uf'] ?? null,
                'nacionalidade'        => $e['p_nacionalidade'] ?? null,
                'pais_origem'          => $e['p_pais_origem'] ?? null,
                'condicao_fisica'      => $e['p_condicao_fisica'] ?? null,
                'lesao_grau'           => $e['p_lesao_grau'] ?? null,
                'morador_rua'          => (bool) ($e['p_morador_rua'] ?? false),
                'estrangeiro'          => (bool) ($e['p_estrangeiro'] ?? false),
                'turista'              => (bool) ($e['p_turista'] ?? false),
                'e_militar_policial'   => (bool) ($e['p_e_militar_policial'] ?? false),
                'tipo_militar'         => $e['p_tipo_militar'] ?? '',
                'orgao_militar'        => $e['p_orgao_militar'] ?? '',
                'uf_orgao'             => $e['p_uf_orgao'] ?? '',
                'matricula_militar'    => $e['p_matricula_militar'] ?? '',
                'em_servico'           => (bool) ($e['p_em_servico'] ?? false),
                'cor_raca'             => $e['p_cor_raca'] ?? null,
                'escolaridade'         => $e['p_escolaridade'] ?? null,
                'ocupacao'             => $e['p_ocupacao'] ?? null,
                'orientacao_sexual'    => $e['p_orientacao_sexual'] ?? null,
                'identidade_genero'    => $e['p_identidade_genero'] ?? null,
                'nome_social'          => $e['p_nome_social'] ?? null,
                'endereco_municipio'   => $e['p_end_municipio'] ?? null,
                'endereco_uf'          => $e['p_end_estado_uf'] ?? null,
                'endereco_cep'         => $e['p_end_cep'] ?? null,
                'endereco_logradouro'  => $e['p_end_logradouro'] ?? null,
                'endereco_numero'      => $e['p_end_numero'] ?? null,
                'endereco_bairro'      => $e['p_end_bairro'] ?? null,
                'endereco_complemento' => $e['p_end_complemento'] ?? null,
            ];
        })->filter()->values()->toArray();
    }

    private function buildVistoria(array $vis): array
    {
        return [
            'id'         => $vis['id'] ?? null,
            'solicitante' => [
                'nome'     => $vis['v_solicitante_nome']     ?? null,
                'cpf'      => $vis['v_solicitante_cpf']      ?? null,
                'telefone' => $vis['v_solicitante_telefone'] ?? null,
                'endereco' => $vis['v_solicitante_endereco'] ?? null,
                'bairro'   => $vis['v_solicitante_bairro']   ?? null,
                'cep'      => $vis['v_solicitante_cep']      ?? null,
            ],
            'local' => [
                'endereco'          => $vis['v_endereco_imovel']  ?? null,
                'complemento'       => $vis['v_local_complemento'] ?? null,
                'tipo_area'         => $vis['v_tipo_area']         ?? null,
                'tipo_area_descricao' => null,
            ],
            'imovel' => [
                'tipo_imovel'                    => $vis['v_tipo_imovel']         ?? null,
                'tipo_imovel_outro_descricao'    => null,
                'tipo_construcao'                => $vis['v_tipo_construcao']     ?? null,
                'tipo_construcao_outro_descricao' => $vis['v_tipo_construcao_outro'] ?? null,
                'tipo_edificacao'                => $vis['v_tipo_edificacao']     ?? null,
                'tipo_terreno_relevo'            => $vis['v_tipo_terreno_relevo'] ?? null,
                'sistema_estrutural'             => $vis['v_sistema_estrutural']  ?? null,
                'numero_pavimentos'              => isset($vis['v_numero_pavimentos']) ? (int) $vis['v_numero_pavimentos'] : null,
                'estado_conservacao'             => $vis['v_estado_conservacao']  ?? null,
                'regime_ocupacao'                => $vis['v_regime_ocupacao']     ?? null,
                'endereco_imovel'                => $vis['v_endereco_imovel']    ?? null,
                'bairro'                         => $vis['v_bairro']             ?? null,
                'municipio'                      => $vis['v_municipio']           ?? null,
                'cep'                            => $vis['v_cep']                ?? null,
                'latitude'                       => $vis['v_latitude']            ?? null,
                'longitude'                      => $vis['v_longitude']           ?? null,
            ],
            'proprietario_morador' => [
                'nome'                    => $vis['v_proprietario_morador'] ?? null,
                'telefone'                => $vis['v_contato_telefone']     ?? null,
                'numero_moradores'        => isset($vis['v_numero_moradores']) ? (int) $vis['v_numero_moradores'] : null,
                'ha_idosos'               => $vis['v_ha_idosos']              ?? 'nao',
                'ha_criancas'             => $vis['v_ha_criancas']            ?? 'nao',
                'ha_dificuldade_locomocao' => empty($vis['v_ha_dificuldade_locomocao']) ? 'nao' : 'sim',
            ],
            'infraestrutura' => [
                'abastecimento_agua'    => $vis['v_abastecimento_agua']     ?? null,
                'esgotamento_sanitario' => $vis['v_esgotamento_sanitario']  ?? null,
                'drenagem_superficial'  => $vis['v_drenagem_superficial']   ?? null,
                'sistema_viario_acesso' => $vis['v_sistema_viario_acesso']  ?? null,
                'tipo_revestimento'     => $vis['v_tipo_revestimento']      ?? null,
                'condicoes_acesso'      => $vis['v_condicoes_acesso']       ?? null,
                'numero_moradias_terreno' => isset($vis['v_numero_moradias_terreno']) ? (int) $vis['v_numero_moradias_terreno'] : null,
                'distancia_encosta'     => $vis['v_distancia_encosta']      ?? null,
                'material_construtivo'  => $vis['v_material_construtivo']   ?? null,
                'conservacao_estrutural' => $vis['v_conservacao_estrutural'] ?? null,
            ],
            'analise_tecnica' => [
                'elementos_estruturais'     => $vis['v_elementos_estruturais']     ?? null,
                'elementos_construtivos'    => $vis['v_elementos_construtivos']    ?? null,
                'agentes_potencializadores' => $vis['v_agentes_potencializadores'] ?? null,
                'processos_geodinamicos'    => $vis['v_processos_geodinamicos']    ?? null,
            ],
            'patologias'          => $this->extractPatologias($vis),
            'bens_afetados'       => $this->extractBensAfetados($vis),
            'orgaos_acionados'    => $this->extractOrgaosAcionados($vis),
            'encaminhamentos'     => $this->extractEncaminhamentos($vis),
            'destinacao_localizacao' => [
                'tipo_destinacao' => $vis['v_tipo_destinacao'] ?? null,
                'tipo_localizacao' => $vis['v_tipo_localizacao'] ?? null,
            ],
            'historico' => $vis['v_historico'] ?? null,
        ];
    }

    private function buildHistorico(RatOcorrencia $ocorrencia, array $dg): array
    {
        return [
            'historico_ocorrencia' => !empty($dg['descricao']) ? 'sim' : 'nao',
            'criado_por' => [
                'id'    => $ocorrencia->creator?->id    ?? null,
                'nome'  => $ocorrencia->creator?->name  ?? 'Sistema',
                'email' => $ocorrencia->creator?->email ?? null,
            ],
            'criado_em'    => $ocorrencia->created_at?->format('Y-m-d H:i:s'),
            'atualizado_em' => $ocorrencia->updated_at?->format('Y-m-d H:i:s'),
            'prazo_edicao' => $ocorrencia->prazo_edicao?->format('Y-m-d H:i:s'),
        ];
    }

    // =========================================================================
    // HELPERS DE EXTRAÇÃO (boolean maps → string labels)
    // =========================================================================

    private function extractPatologias(array $vis): array
    {
        $map = [
            'v_patologia_rachaduras'              => 'Rachaduras',
            'v_patologia_trincas'                 => 'Trincas',
            'v_patologia_fissuras_estruturais'    => 'Fissuras Estruturais',
            'v_patologia_deformacoes_estruturais' => 'Deformações Estruturais',
            'v_patologia_infiltracoes'            => 'Infiltrações',
            'v_patologia_corrosao_armaduras'      => 'Corrosão de Armaduras',
            'v_patologia_desagregacao'            => 'Desagregação',
            'v_patologia_eflorescencia'           => 'Eflorescência',
            'v_patologia_desplacamento'           => 'Desplacamento',
            'v_patologia_fundacoes'               => 'Fundações',
            'v_patologia_instabilidade_talude'    => 'Instabilidade de Talude',
            'v_patologia_movimentacao_solo'       => 'Movimentação de Solo',
            'v_patologia_tombamento_muralhas'     => 'Tombamento de Muralhas',
            'v_patologia_inundacoes'              => 'Inundações',
            'v_patologia_alagamentos'             => 'Alagamentos',
            'v_patologia_enxurradas'              => 'Enxurradas',
            'v_patologia_madeira'                 => 'Danos em Madeira',
            'v_patologia_elementos_nao_estruturais' => 'Elementos Não Estruturais',
            'v_patologia_falha_drenagem'          => 'Falha de Drenagem',
            'v_patologia_queda_arvores'           => 'Queda de Árvores',
        ];
        $result = $this->boolMapToLabels($vis, $map);
        if (!empty($vis['v_patologia_outros'])) {
            $result[] = 'Outros' . ($vis['v_patologia_outros_descricao'] ? ': ' . $vis['v_patologia_outros_descricao'] : '');
        }
        return $result;
    }

    private function extractBensAfetados(array $vis): array
    {
        $map = [
            'v_bens_residencia'         => 'Residência',
            'v_bens_muros'              => 'Muros',
            'v_bens_vias_publicas'      => 'Vias Públicas',
            'v_bens_pontes'             => 'Pontes',
            'v_bens_viadutos'           => 'Viadutos',
            'v_bens_comercios'          => 'Comércios',
            'v_bens_galpoes'            => 'Galpões',
            'v_bens_predios_publicos'   => 'Prédios Públicos',
            'v_bens_edificios_publicos' => 'Edifícios Públicos',
        ];
        $result = $this->boolMapToLabels($vis, $map);
        if (!empty($vis['v_bens_outros'])) {
            $result[] = 'Outros' . ($vis['v_bens_outros_descricao'] ? ': ' . $vis['v_bens_outros_descricao'] : '');
        }
        return $result;
    }

    private function extractOrgaosAcionados(array $vis): array
    {
        $map = [
            'v_orgao_copasa'                => 'COPASA',
            'v_orgao_cemig'                 => 'CEMIG',
            'v_orgao_secretaria_municipal'  => 'Secretaria Municipal',
            'v_orgao_defesa_civil_estadual' => 'Defesa Civil Estadual',
            'v_orgao_dnit'                  => 'DNIT',
            'v_orgao_crea'                  => 'CREA',
            'v_orgao_emater'                => 'EMATER',
            'v_orgao_seapa'                 => 'SEAPA',
        ];
        $result = $this->boolMapToLabels($vis, $map);
        if (!empty($vis['v_orgao_pm'])) {
            $result[] = 'Polícia Militar';
        }
        if (!empty($vis['v_orgao_bm'])) {
            $result[] = 'Bombeiros';
        }
        if (!empty($vis['v_orgao_outros'])) {
            $result[] = 'Outros' . ($vis['v_orgao_outros_descricao'] ? ': ' . $vis['v_orgao_outros_descricao'] : '');
        }
        return $result;
    }

    private function extractEncaminhamentos(array $vis): array
    {
        $map = [
            'v_enc_interdicao_parcial'      => 'Interdição Parcial',
            'v_enc_interdicao_total'        => 'Interdição Total',
            'v_enc_remocao_temporaria'      => 'Remoção Temporária',
            'v_enc_remocao_definitiva'      => 'Remoção Definitiva',
            'v_enc_isolamento_area'         => 'Isolamento de Área',
            'v_enc_desocupacao_abrigo'      => 'Desocupação e Abrigo',
            'v_enc_notificacao_responsavel' => 'Notificação ao Responsável',
            'v_enc_contratacao_responsavel' => 'Contratação de Responsável Técnico',
            'v_enc_comunicacao_orgaos'      => 'Comunicação a Órgãos',
            'v_enc_apoio_social'            => 'Apoio Social',
        ];
        $result = $this->boolMapToLabels($vis, $map);
        if (!empty($vis['v_enc_outros'])) {
            $result[] = 'Outros' . ($vis['v_enc_outros_descricao'] ? ': ' . $vis['v_enc_outros_descricao'] : '');
        }
        return $result;
    }

    private function boolMapToLabels(array $vis, array $map): array
    {
        $result = [];
        foreach ($map as $key => $label) {
            if (!empty($vis[$key])) {
                $result[] = $label;
            }
        }
        return $result;
    }

    // =========================================================================
    // FORMATAÇÃO
    // =========================================================================

    private function formatMunicipio(array $dg): ?string
    {
        $codigo = $dg['local_municipio'] ?? null;
        $nome   = $dg['local_municipio_nome'] ?? null;

        if ($codigo && $nome) {
            return "$codigo - " . strtoupper($nome);
        }

        return $nome ?? ($codigo ? (string) $codigo : null);
    }

    /**
     * Converte qualquer string de data/datetime para "Y-m-d H:i:s".
     */
    private function parseDateTime(?string $value): ?string
    {
        if (empty($value) || $value === '0') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }
}
