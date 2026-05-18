<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTOs;

/**
 * DTO para dados de Vistoria em uma ocorrência RAT.
 * Mapeia a estrutura aninhada do frontend (solicitante, imovel, estrutura, moradores,
 * infraestrutura, tecnico, patologias, encaminhamentos, bens_afetados, orgaos_acionados)
 * para as colunas v_* do banco de dados (rat_relato_vistoria).
 */
readonly class RatVistoriaDTO
{
    public function __construct(
        public ?int    $id = null,
        // Solicitante
        public ?string $vSolicitanteNome      = null,
        public ?string $vSolicitanteCpf       = null,
        public ?string $vSolicitanteTelefone  = null,
        public ?string $vSolicitanteEndereco  = null,
        public ?string $vSolicitanteBairro    = null,
        public ?string $vSolicitanteCep       = null,
        // Local e imóvel
        public ?string $vLocalEndereco                  = null,
        public ?string $vLocalComplemento               = null,
        public ?string $vTipoArea                       = null,
        public ?string $vTipoAreaDescricao              = null,
        public ?string $vTipoImovel                     = null,
        public ?string $vTipoImovelOutroDescricao       = null,
        public ?string $vTipoConstrucao                 = null,
        public ?string $vTipoConstrucaoOutroDescricao   = null,
        public ?string $vTipoEdificacao                 = null,
        public ?string $vTipoDestinacao                 = null,
        public ?string $vTipoTerrenoRelevo              = null,
        public ?string $vSistemaEstrutural              = null,
        public ?int    $vNumeroPavimentos               = null,
        public ?string $vEstadoConservacao              = null,
        public ?string $vRegimeOcupacao                 = null,
        public ?string $vTipoLocalizacao                = null,
        // Moradores
        public ?string $vProprietarioMorador    = null,
        public ?string $vContatoTelefone        = null,
        public ?int    $vNumeroMoradores        = null,
        public ?bool   $vHaIdosos              = null,
        public ?bool   $vHaCriancas            = null,
        public ?string $vHaDificuldadeLocomocao = null,
        // Localização do imóvel
        public ?string $vEnderecoImovel = null,
        public ?string $vBairro         = null,
        public ?int    $vMunicipio      = null,
        public ?string $vCep            = null,
        public ?float  $vLatitude       = null,
        public ?float  $vLongitude      = null,
        // Infraestrutura e acesso
        public ?string $vAbastecimentoAgua      = null,
        public ?string $vEsgotamentoSanitario   = null,
        public ?string $vDranagemSuperficial    = null,
        public ?string $vSistemaViarioAcesso    = null,
        public ?string $vTipoRevestimento       = null,
        public ?string $vCondicoesAcesso        = null,
        public ?int    $vNumeroMoradiasTereno   = null,
        public ?string $vDistanciaEncosta       = null,
        // Aspectos técnicos
        public ?string $vMaterialConstrutivo      = null,
        public ?string $vConservacaoEstrutural    = null,
        public ?string $vElementosEstruturais     = null,
        public ?string $vElementosConstrutivos    = null,
        public ?string $vAgentesPotencializadores = null,
        public ?string $vProcessosGeodinamicos    = null,
        public ?string $vDanosEstruturais         = null,
        public ?string $vManutencaoUsoOcupacao    = null,
        public ?string $vHistorico                = null,
        // Patologias (booleanos)
        public ?bool   $vPatologiaRachaduras             = null,
        public ?bool   $vPatologiaTrincas                = null,
        public ?bool   $vPatologiaFissurasEstruturais    = null,
        public ?bool   $vPatologiaDeformacoesEstruturais = null,
        public ?bool   $vPatologiaInfiltracoes           = null,
        public ?bool   $vPatologiaCorrosaoArmaduras      = null,
        public ?bool   $vPatologiaDesagregacao           = null,
        public ?bool   $vPatologiaEflorescencia          = null,
        public ?bool   $vPatologiaDesplacamento          = null,
        public ?bool   $vPatologiaFundacoes              = null,
        public ?bool   $vPatologiaInstabilidadeTalude    = null,
        public ?bool   $vPatologiaMovimentacaoSolo       = null,
        public ?bool   $vPatologiaTombamentoMuralhas     = null,
        public ?bool   $vPatologiaInundacoes             = null,
        public ?bool   $vPatologiaAlagamentos            = null,
        public ?bool   $vPatologiaEnxurradas             = null,
        public ?bool   $vPatologiaMadeira                = null,
        public ?bool   $vPatologiaElementosNaoEstruturais= null,
        public ?bool   $vPatologiaFalhaDrenagem          = null,
        public ?bool   $vPatologiaQuedaArvores           = null,
        public ?bool   $vPatologiaOutros                 = null,
        public ?string $vPatologiaOutrosDescricao        = null,
        // Encaminhamentos (booleanos)
        public ?bool   $vEncInterdicaoParcial      = null,
        public ?bool   $vEncInterdicaoTotal        = null,
        public ?bool   $vEncRemocaoTemporaria      = null,
        public ?bool   $vEncRemocaoDefinitiva      = null,
        public ?bool   $vEncIsolamentoArea         = null,
        public ?bool   $vEncDesocupacaoAbrigo      = null,
        public ?bool   $vEncNotificacaoResponsavel = null,
        public ?bool   $vEncContratacaoResponsavel = null,
        public ?bool   $vEncComunicacaoOrgaos      = null,
        public ?bool   $vEncApoioSocial            = null,
        public ?bool   $vEncOutros                 = null,
        public ?string $vEncOutrosDescricao        = null,
        // Bens afetados (booleanos)
        public ?bool   $vBensResidencia        = null,
        public ?bool   $vBensMuros             = null,
        public ?bool   $vBensViasPublicas      = null,
        public ?bool   $vBensPontes            = null,
        public ?bool   $vBensViadutos          = null,
        public ?bool   $vBensComercios         = null,
        public ?bool   $vBensGalpoes           = null,
        public ?bool   $vBensPrediosPublicos   = null,
        public ?bool   $vBensEdificiosPublicos = null,
        public ?bool   $vBensOutros            = null,
        public ?string $vBensOutrosDescricao   = null,
        // Órgãos acionados
        public ?bool   $vOrgaoCopasa               = null,
        public ?bool   $vOrgaoCemig                = null,
        public ?bool   $vOrgaoSecretariaMunicipal  = null,
        public ?bool   $vOrgaoDefesaCivilEstadual  = null,
        public ?bool   $vOrgaoDnit                 = null,
        public ?string $vOrgaoPm                   = null,
        public ?string $vOrgaoBm                   = null,
        public ?bool   $vOrgaoCrea                 = null,
        public ?bool   $vOrgaoEmater               = null,
        public ?bool   $vOrgaoSeapa                = null,
        public ?bool   $vOrgaoOutros               = null,
        public ?string $vOrgaoOutrosDescricao      = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $sol    = $data['solicitante']      ?? [];
        $imo    = $data['imovel']           ?? [];
        $est    = $data['estrutura']        ?? [];
        $mor    = $data['moradores']        ?? [];
        $infra  = $data['infraestrutura']   ?? [];
        $tec    = $data['tecnico']          ?? [];
        $pat    = $data['patologias']       ?? [];
        $enc    = $data['encaminhamentos']  ?? [];
        $bens   = $data['bens_afetados']    ?? [];
        $orgaos = $data['orgaos_acionados'] ?? [];

        return new self(
            id: $data['id'] ?? null,

            // Solicitante
            vSolicitanteNome:     $data['v_solicitante_nome']     ?? $sol['nome']     ?? null,
            vSolicitanteCpf:      $data['v_solicitante_cpf']      ?? $sol['cpf']      ?? null,
            vSolicitanteTelefone: $data['v_solicitante_telefone'] ?? $sol['telefone'] ?? null,
            vSolicitanteEndereco: $data['v_solicitante_endereco'] ?? $sol['endereco'] ?? null,
            vSolicitanteBairro:   $data['v_solicitante_bairro']   ?? $sol['bairro']   ?? null,
            vSolicitanteCep:      $data['v_solicitante_cep']      ?? $sol['cep']      ?? null,

            // Local e imóvel
            vLocalEndereco:               $data['v_local_endereco']                ?? null,
            vLocalComplemento:            $data['v_local_complemento']             ?? $imo['complemento'] ?? null,
            vTipoArea:                    $data['v_tipo_area']                     ?? $est['tipo_area']           ?? null,
            vTipoAreaDescricao:           $data['v_tipo_area_descricao']           ?? null,
            vTipoImovel:                  $data['v_tipo_imovel']                   ?? $est['tipo_imovel']         ?? null,
            vTipoImovelOutroDescricao:    $data['v_tipo_imovel_outro_descricao']   ?? null,
            vTipoConstrucao:              $data['v_tipo_construcao']               ?? $est['tipo_construcao']     ?? null,
            vTipoConstrucaoOutroDescricao:$data['v_tipo_construcao_outro_descricao'] ?? null,
            vTipoEdificacao:              $data['v_tipo_edificacao']               ?? $est['tipo_edificacao']     ?? null,
            vTipoDestinacao:              $data['v_tipo_destinacao']               ?? $est['tipo_destinacao']     ?? null,
            vTipoTerrenoRelevo:           $data['v_tipo_terreno_relevo']           ?? $est['tipo_terreno_relevo'] ?? null,
            vSistemaEstrutural:           $data['v_sistema_estrutural']            ?? $est['sistema_estrutural']  ?? null,
            vNumeroPavimentos:            self::intOrNull($data['v_numero_pavimentos'] ?? $est['num_pavimentos'] ?? null),
            vEstadoConservacao:           $data['v_estado_conservacao']            ?? $est['estado_conservacao']  ?? null,
            vRegimeOcupacao:              $data['v_regime_ocupacao']               ?? $est['regime_ocupacao']     ?? null,
            vTipoLocalizacao:             $data['v_tipo_localizacao']              ?? $est['tipo_localizacao']    ?? null,

            // Moradores
            vProprietarioMorador:    $data['v_proprietario_morador']   ?? $mor['proprietario']  ?? null,
            vContatoTelefone:        $data['v_contato_telefone']       ?? $mor['telefone']      ?? null,
            vNumeroMoradores:        self::intOrNull($data['v_numero_moradores'] ?? $mor['num_moradores'] ?? null),
            vHaIdosos:               self::boolOrNull($data['v_ha_idosos']  ?? $mor['ha_idosos']  ?? null),
            vHaCriancas:             self::boolOrNull($data['v_ha_criancas'] ?? $mor['ha_criancas'] ?? null),
            vHaDificuldadeLocomocao: isset($mor['ha_pcd']) && $mor['ha_pcd'] ? 'sim' : ($data['v_ha_dificuldade_locomocao'] ?? null),

            // Localização do imóvel
            vEnderecoImovel: $data['v_endereco_imovel'] ?? $imo['endereco']  ?? null,
            vBairro:         $data['v_bairro']          ?? $imo['bairro']    ?? null,
            vMunicipio:      self::intOrNull($data['v_municipio'] ?? $imo['municipio'] ?? null),
            vCep:            $data['v_cep']             ?? $imo['cep']       ?? null,
            vLatitude:       isset($data['v_latitude'])  ? (float) $data['v_latitude']  : (isset($imo['latitude'])  ? (float) $imo['latitude']  : null),
            vLongitude:      isset($data['v_longitude']) ? (float) $data['v_longitude'] : (isset($imo['longitude']) ? (float) $imo['longitude'] : null),

            // Infraestrutura
            vAbastecimentoAgua:    $data['v_abastecimento_agua']     ?? $infra['abastecimento_agua']    ?? null,
            vEsgotamentoSanitario: $data['v_esgotamento_sanitario']  ?? $infra['esgotamento_sanitario'] ?? null,
            vDranagemSuperficial:  $data['v_drenagem_superficial']   ?? $infra['drenagem_superficial']  ?? null,
            vSistemaViarioAcesso:  $data['v_sistema_viario_acesso']  ?? $infra['sistema_viario_acesso'] ?? null,
            vTipoRevestimento:     $data['v_tipo_revestimento']      ?? $infra['tipo_revestimento']     ?? null,
            vCondicoesAcesso:      $data['v_condicoes_acesso']       ?? $infra['condicoes_acesso']      ?? null,
            vNumeroMoradiasTereno: self::intOrNull($data['v_numero_moradias_terreno'] ?? $infra['num_moradias_terreno'] ?? null),
            vDistanciaEncosta:     $data['v_distancia_encosta']      ?? $infra['distancia_encosta']     ?? null,

            // Aspectos técnicos
            vMaterialConstrutivo:      $data['v_material_construtivo']      ?? $tec['material_construtivo']      ?? null,
            vConservacaoEstrutural:    $data['v_conservacao_estrutural']    ?? $tec['conservacao_estrutural']    ?? null,
            vElementosEstruturais:     $data['v_elementos_estruturais']     ?? $tec['elementos_estruturais']     ?? null,
            vElementosConstrutivos:    $data['v_elementos_construtivos']    ?? $tec['elementos_construtivos']    ?? null,
            vAgentesPotencializadores: $data['v_agentes_potencializadores'] ?? $tec['agentes_potencializadores'] ?? null,
            vProcessosGeodinamicos:    $data['v_processos_geodinamicos']    ?? $tec['processos_geodinamicos']    ?? null,
            vDanosEstruturais:         $data['v_danos_estruturais']         ?? $tec['danos_estruturais']         ?? null,
            vManutencaoUsoOcupacao:    $data['v_manutencao_uso_ocupacao']   ?? $tec['manutencao_uso_ocupacao']   ?? null,
            vHistorico:                $data['v_historico']                 ?? $tec['historico']                 ?? $data['observacoes'] ?? null,

            // Patologias
            vPatologiaRachaduras:            self::boolOrNull($data['v_patologia_rachaduras']            ?? $pat['rachaduras']               ?? null),
            vPatologiaTrincas:               self::boolOrNull($data['v_patologia_trincas']               ?? $pat['trincas']                  ?? null),
            vPatologiaFissurasEstruturais:   self::boolOrNull($data['v_patologia_fissuras_estruturais']  ?? $pat['fissuras_estruturais']     ?? null),
            vPatologiaDeformacoesEstruturais:self::boolOrNull($data['v_patologia_deformacoes_estruturais']?? $pat['deformacoes_estruturais']  ?? null),
            vPatologiaInfiltracoes:          self::boolOrNull($data['v_patologia_infiltracoes']          ?? $pat['infiltracoes']             ?? null),
            vPatologiaCorrosaoArmaduras:     self::boolOrNull($data['v_patologia_corrosao_armaduras']    ?? $pat['corrosao_armaduras']       ?? null),
            vPatologiaDesagregacao:          self::boolOrNull($data['v_patologia_desagregacao']          ?? $pat['desagregacao']             ?? null),
            vPatologiaEflorescencia:         self::boolOrNull($data['v_patologia_eflorescencia']         ?? $pat['eflorescencia']            ?? null),
            vPatologiaDesplacamento:         self::boolOrNull($data['v_patologia_desplacamento']         ?? $pat['desplacamento']            ?? null),
            vPatologiaFundacoes:             self::boolOrNull($data['v_patologia_fundacoes']             ?? $pat['fundacoes']                ?? null),
            vPatologiaInstabilidadeTalude:   self::boolOrNull($data['v_patologia_instabilidade_talude']  ?? $pat['instabilidade_talude']     ?? null),
            vPatologiaMovimentacaoSolo:      self::boolOrNull($data['v_patologia_movimentacao_solo']     ?? $pat['movimentacao_solo']        ?? null),
            vPatologiaTombamentoMuralhas:    self::boolOrNull($data['v_patologia_tombamento_muralhas']   ?? $pat['tombamento_muralhas']      ?? null),
            vPatologiaInundacoes:            self::boolOrNull($data['v_patologia_inundacoes']            ?? $pat['inundacoes']               ?? null),
            vPatologiaAlagamentos:           self::boolOrNull($data['v_patologia_alagamentos']           ?? $pat['alagamentos']              ?? null),
            vPatologiaEnxurradas:            self::boolOrNull($data['v_patologia_enxurradas']            ?? $pat['enxurradas']               ?? null),
            vPatologiaMadeira:               self::boolOrNull($data['v_patologia_madeira']               ?? $pat['madeira']                  ?? null),
            vPatologiaElementosNaoEstruturais:self::boolOrNull($data['v_patologia_elementos_nao_estruturais'] ?? $pat['elementos_nao_estruturais'] ?? null),
            vPatologiaFalhaDrenagem:         self::boolOrNull($data['v_patologia_falha_drenagem']        ?? $pat['falha_drenagem']           ?? null),
            vPatologiaQuedaArvores:          self::boolOrNull($data['v_patologia_queda_arvores']         ?? $pat['queda_arvores']            ?? null),
            vPatologiaOutros:                self::boolOrNull($data['v_patologia_outros']                ?? $pat['outros']                   ?? null),
            vPatologiaOutrosDescricao:       $data['v_patologia_outros_descricao']                       ?? $pat['outros_descricao']         ?? null,

            // Encaminhamentos
            vEncInterdicaoParcial:      self::boolOrNull($data['v_enc_interdicao_parcial']      ?? $enc['interdicao_parcial']      ?? null),
            vEncInterdicaoTotal:        self::boolOrNull($data['v_enc_interdicao_total']        ?? $enc['interdicao_total']        ?? null),
            vEncRemocaoTemporaria:      self::boolOrNull($data['v_enc_remocao_temporaria']      ?? $enc['remocao_temporaria']      ?? null),
            vEncRemocaoDefinitiva:      self::boolOrNull($data['v_enc_remocao_definitiva']      ?? $enc['remocao_definitiva']      ?? null),
            vEncIsolamentoArea:         self::boolOrNull($data['v_enc_isolamento_area']         ?? $enc['isolamento_area']         ?? null),
            vEncDesocupacaoAbrigo:      self::boolOrNull($data['v_enc_desocupacao_abrigo']      ?? $enc['desocupacao_abrigo']      ?? null),
            vEncNotificacaoResponsavel: self::boolOrNull($data['v_enc_notificacao_responsavel'] ?? $enc['notificacao_responsavel'] ?? null),
            vEncContratacaoResponsavel: self::boolOrNull($data['v_enc_contratacao_responsavel'] ?? $enc['contratacao_responsavel'] ?? null),
            vEncComunicacaoOrgaos:      self::boolOrNull($data['v_enc_comunicacao_orgaos']      ?? $enc['comunicacao_orgaos']      ?? null),
            vEncApoioSocial:            self::boolOrNull($data['v_enc_apoio_social']            ?? $enc['apoio_social']            ?? null),
            vEncOutros:                 self::boolOrNull($data['v_enc_outros']                  ?? $enc['outros']                  ?? null),
            vEncOutrosDescricao:        $data['v_enc_outros_descricao']                         ?? $enc['outros_descricao']        ?? null,

            // Bens afetados
            vBensResidencia:        self::boolOrNull($data['v_bens_residencia']        ?? $bens['residencia']         ?? null),
            vBensMuros:             self::boolOrNull($data['v_bens_muros']             ?? $bens['muros']              ?? null),
            vBensViasPublicas:      self::boolOrNull($data['v_bens_vias_publicas']     ?? $bens['vias_publicas']      ?? null),
            vBensPontes:            self::boolOrNull($data['v_bens_pontes']            ?? $bens['pontes']             ?? null),
            vBensViadutos:          self::boolOrNull($data['v_bens_viadutos']          ?? $bens['viadutos']           ?? null),
            vBensComercios:         self::boolOrNull($data['v_bens_comercios']         ?? $bens['comercios']          ?? null),
            vBensGalpoes:           self::boolOrNull($data['v_bens_galpoes']           ?? $bens['galpoes']            ?? null),
            vBensPrediosPublicos:   self::boolOrNull($data['v_bens_predios_publicos']  ?? $bens['predios_publicos']   ?? null),
            vBensEdificiosPublicos: self::boolOrNull($data['v_bens_edificios_publicos']?? $bens['edificios_publicos'] ?? null),
            vBensOutros:            self::boolOrNull($data['v_bens_outros']            ?? $bens['outros']             ?? null),
            vBensOutrosDescricao:   $data['v_bens_outros_descricao']                   ?? $bens['outros_descricao']   ?? null,

            // Órgãos acionados
            vOrgaoCopasa:               self::boolOrNull($data['v_orgao_copasa']               ?? $orgaos['copasa']               ?? null),
            vOrgaoCemig:                self::boolOrNull($data['v_orgao_cemig']                ?? $orgaos['cemig']                ?? null),
            vOrgaoSecretariaMunicipal:  self::boolOrNull($data['v_orgao_secretaria_municipal'] ?? $orgaos['secretaria_municipal'] ?? null),
            vOrgaoDefesaCivilEstadual:  self::boolOrNull($data['v_orgao_defesa_civil_estadual']?? $orgaos['defesa_civil_estadual']?? null),
            vOrgaoDnit:                 self::boolOrNull($data['v_orgao_dnit']                 ?? $orgaos['dnit']                 ?? null),
            vOrgaoPm:                   self::boolToSimOrNull($data['v_orgao_pm']              ?? $orgaos['pm']                   ?? null),
            vOrgaoBm:                   self::boolToSimOrNull($data['v_orgao_bm']              ?? $orgaos['bm']                   ?? null),
            vOrgaoCrea:                 self::boolOrNull($data['v_orgao_crea']                 ?? $orgaos['crea']                 ?? null),
            vOrgaoEmater:               self::boolOrNull($data['v_orgao_emater']               ?? $orgaos['emater']               ?? null),
            vOrgaoSeapa:                self::boolOrNull($data['v_orgao_seapa']                 ?? $orgaos['seapa']                ?? null),
            vOrgaoOutros:               self::boolOrNull($data['v_orgao_outros']               ?? $orgaos['outros']               ?? null),
            vOrgaoOutrosDescricao:      $data['v_orgao_outros_descricao']                      ?? $orgaos['outros_descricao']     ?? null,
        );
    }

    private static function intOrNull(mixed $v): ?int
    {
        if ($v === null || $v === '') return null;
        $int = filter_var($v, FILTER_VALIDATE_INT);
        return $int !== false ? $int : null;
    }

    private static function boolOrNull(mixed $v): ?bool
    {
        return $v !== null ? (bool) $v : null;
    }

    private static function boolToSimOrNull(mixed $v): ?string
    {
        if ($v === null || $v === '' || $v === false) return null;
        if ($v === true || $v === 1 || $v === '1') return 'sim';
        return is_string($v) ? $v : null;
    }

    public function toArray(): array
    {
        return array_filter([
            'v_solicitante_nome'                    => $this->vSolicitanteNome,
            'v_solicitante_cpf'                     => $this->vSolicitanteCpf,
            'v_solicitante_telefone'                => $this->vSolicitanteTelefone,
            'v_solicitante_endereco'                => $this->vSolicitanteEndereco,
            'v_solicitante_bairro'                  => $this->vSolicitanteBairro,
            'v_solicitante_cep'                     => $this->vSolicitanteCep,
            'v_local_endereco'                      => $this->vLocalEndereco,
            'v_local_complemento'                   => $this->vLocalComplemento,
            'v_tipo_area'                           => $this->vTipoArea,
            'v_tipo_area_descricao'                 => $this->vTipoAreaDescricao,
            'v_tipo_imovel'                         => $this->vTipoImovel,
            'v_tipo_imovel_outro_descricao'         => $this->vTipoImovelOutroDescricao,
            'v_tipo_construcao'                     => $this->vTipoConstrucao,
            'v_tipo_construcao_outro_descricao'     => $this->vTipoConstrucaoOutroDescricao,
            'v_tipo_edificacao'                     => $this->vTipoEdificacao,
            'v_tipo_destinacao'                     => $this->vTipoDestinacao,
            'v_tipo_terreno_relevo'                 => $this->vTipoTerrenoRelevo,
            'v_sistema_estrutural'                  => $this->vSistemaEstrutural,
            'v_numero_pavimentos'                   => $this->vNumeroPavimentos,
            'v_estado_conservacao'                  => $this->vEstadoConservacao,
            'v_regime_ocupacao'                     => $this->vRegimeOcupacao,
            'v_tipo_localizacao'                    => $this->vTipoLocalizacao,
            'v_proprietario_morador'                => $this->vProprietarioMorador,
            'v_contato_telefone'                    => $this->vContatoTelefone,
            'v_numero_moradores'                    => $this->vNumeroMoradores,
            'v_ha_idosos'                           => $this->vHaIdosos,
            'v_ha_criancas'                         => $this->vHaCriancas,
            'v_ha_dificuldade_locomocao'            => $this->vHaDificuldadeLocomocao,
            'v_endereco_imovel'                     => $this->vEnderecoImovel,
            'v_bairro'                              => $this->vBairro,
            'v_municipio'                           => $this->vMunicipio,
            'v_cep'                                 => $this->vCep,
            'v_latitude'                            => $this->vLatitude,
            'v_longitude'                           => $this->vLongitude,
            // Infraestrutura
            'v_abastecimento_agua'                  => $this->vAbastecimentoAgua,
            'v_esgotamento_sanitario'               => $this->vEsgotamentoSanitario,
            'v_drenagem_superficial'                => $this->vDranagemSuperficial,
            'v_sistema_viario_acesso'               => $this->vSistemaViarioAcesso,
            'v_tipo_revestimento'                   => $this->vTipoRevestimento,
            'v_condicoes_acesso'                    => $this->vCondicoesAcesso,
            'v_numero_moradias_terreno'             => $this->vNumeroMoradiasTereno,
            'v_distancia_encosta'                   => $this->vDistanciaEncosta,
            // Aspectos técnicos
            'v_material_construtivo'                => $this->vMaterialConstrutivo,
            'v_conservacao_estrutural'              => $this->vConservacaoEstrutural,
            'v_elementos_estruturais'               => $this->vElementosEstruturais,
            'v_elementos_construtivos'              => $this->vElementosConstrutivos,
            'v_agentes_potencializadores'           => $this->vAgentesPotencializadores,
            'v_processos_geodinamicos'              => $this->vProcessosGeodinamicos,
            'v_danos_estruturais'                   => $this->vDanosEstruturais,
            'v_manutencao_uso_ocupacao'             => $this->vManutencaoUsoOcupacao,
            'v_historico'                           => $this->vHistorico,
            // Patologias
            'v_patologia_rachaduras'                => $this->vPatologiaRachaduras,
            'v_patologia_trincas'                   => $this->vPatologiaTrincas,
            'v_patologia_fissuras_estruturais'      => $this->vPatologiaFissurasEstruturais,
            'v_patologia_deformacoes_estruturais'   => $this->vPatologiaDeformacoesEstruturais,
            'v_patologia_infiltracoes'              => $this->vPatologiaInfiltracoes,
            'v_patologia_corrosao_armaduras'        => $this->vPatologiaCorrosaoArmaduras,
            'v_patologia_desagregacao'              => $this->vPatologiaDesagregacao,
            'v_patologia_eflorescencia'             => $this->vPatologiaEflorescencia,
            'v_patologia_desplacamento'             => $this->vPatologiaDesplacamento,
            'v_patologia_fundacoes'                 => $this->vPatologiaFundacoes,
            'v_patologia_instabilidade_talude'      => $this->vPatologiaInstabilidadeTalude,
            'v_patologia_movimentacao_solo'         => $this->vPatologiaMovimentacaoSolo,
            'v_patologia_tombamento_muralhas'       => $this->vPatologiaTombamentoMuralhas,
            'v_patologia_inundacoes'                => $this->vPatologiaInundacoes,
            'v_patologia_alagamentos'               => $this->vPatologiaAlagamentos,
            'v_patologia_enxurradas'                => $this->vPatologiaEnxurradas,
            'v_patologia_madeira'                   => $this->vPatologiaMadeira,
            'v_patologia_elementos_nao_estruturais' => $this->vPatologiaElementosNaoEstruturais,
            'v_patologia_falha_drenagem'            => $this->vPatologiaFalhaDrenagem,
            'v_patologia_queda_arvores'             => $this->vPatologiaQuedaArvores,
            'v_patologia_outros'                    => $this->vPatologiaOutros,
            'v_patologia_outros_descricao'          => $this->vPatologiaOutrosDescricao,
            // Encaminhamentos
            'v_enc_interdicao_parcial'              => $this->vEncInterdicaoParcial,
            'v_enc_interdicao_total'                => $this->vEncInterdicaoTotal,
            'v_enc_remocao_temporaria'              => $this->vEncRemocaoTemporaria,
            'v_enc_remocao_definitiva'              => $this->vEncRemocaoDefinitiva,
            'v_enc_isolamento_area'                 => $this->vEncIsolamentoArea,
            'v_enc_desocupacao_abrigo'              => $this->vEncDesocupacaoAbrigo,
            'v_enc_notificacao_responsavel'         => $this->vEncNotificacaoResponsavel,
            'v_enc_contratacao_responsavel'         => $this->vEncContratacaoResponsavel,
            'v_enc_comunicacao_orgaos'              => $this->vEncComunicacaoOrgaos,
            'v_enc_apoio_social'                    => $this->vEncApoioSocial,
            'v_enc_outros'                          => $this->vEncOutros,
            'v_enc_outros_descricao'                => $this->vEncOutrosDescricao,
            // Bens afetados
            'v_bens_residencia'                     => $this->vBensResidencia,
            'v_bens_muros'                          => $this->vBensMuros,
            'v_bens_vias_publicas'                  => $this->vBensViasPublicas,
            'v_bens_pontes'                         => $this->vBensPontes,
            'v_bens_viadutos'                       => $this->vBensViadutos,
            'v_bens_comercios'                      => $this->vBensComercios,
            'v_bens_galpoes'                        => $this->vBensGalpoes,
            'v_bens_predios_publicos'               => $this->vBensPrediosPublicos,
            'v_bens_edificios_publicos'             => $this->vBensEdificiosPublicos,
            'v_bens_outros'                         => $this->vBensOutros,
            'v_bens_outros_descricao'               => $this->vBensOutrosDescricao,
            // Órgãos acionados
            'v_orgao_copasa'                        => $this->vOrgaoCopasa,
            'v_orgao_cemig'                         => $this->vOrgaoCemig,
            'v_orgao_secretaria_municipal'          => $this->vOrgaoSecretariaMunicipal,
            'v_orgao_defesa_civil_estadual'         => $this->vOrgaoDefesaCivilEstadual,
            'v_orgao_dnit'                          => $this->vOrgaoDnit,
            'v_orgao_pm'                            => $this->vOrgaoPm,
            'v_orgao_bm'                            => $this->vOrgaoBm,
            'v_orgao_crea'                          => $this->vOrgaoCrea,
            'v_orgao_emater'                        => $this->vOrgaoEmater,
            'v_orgao_seapa'                         => $this->vOrgaoSeapa,
            'v_orgao_outros'                        => $this->vOrgaoOutros,
            'v_orgao_outros_descricao'              => $this->vOrgaoOutrosDescricao,
        ], fn ($v) => $v !== null && $v !== '');
    }
}
