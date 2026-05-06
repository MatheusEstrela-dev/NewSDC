<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTOs;

/**
 * DTO para dados de Vistoria em uma ocorrência RAT.
 * Mapeia a estrutura aninhada do frontend (solicitante, imovel, estrutura, moradores, patologias)
 * para as colunas v_* do banco de dados.
 */
readonly class RatVistoriaDTO
{
    public function __construct(
        public ?int    $id = null,
        // Solicitante
        public ?string $vSolicitanteNome = null,
        public ?string $vSolicitanteCpf = null,
        public ?string $vSolicitanteTelefone = null,
        public ?string $vSolicitanteEndereco = null,
        public ?string $vSolicitanteBairro = null,
        public ?string $vSolicitanteCep = null,
        // Local e imóvel
        public ?string $vLocalEndereco = null,
        public ?string $vLocalComplemento = null,
        public ?string $vTipoArea = null,
        public ?string $vTipoAreaDescricao = null,
        public ?string $vTipoImovel = null,
        public ?string $vTipoImovelOutroDescricao = null,
        public ?string $vTipoConstrucao = null,
        public ?string $vTipoEdificacao = null,
        public ?string $vTipoDestinacao = null,
        public ?string $vTipoTerrenoRelevo = null,
        public ?string $vSistemaEstrutural = null,
        public ?int    $vNumeroPavimentos = null,
        public ?string $vEstadoConservacao = null,
        public ?string $vRegimeOcupacao = null,
        // Moradores
        public ?string $vProprietarioMorador = null,
        public ?string $vContatoTelefone = null,
        public ?int    $vNumeroMoradores = null,
        public ?bool   $vHaIdosos = null,
        public ?bool   $vHaCriancas = null,
        public ?string $vHaDificuldadeLocomocao = null,
        // Localização do imóvel vistoriado
        public ?string $vEnderecoImovel = null,
        public ?string $vBairro = null,
        public ?string $vMunicipio = null,
        public ?string $vCep = null,
        public ?float  $vLatitude = null,
        public ?float  $vLongitude = null,
        // Patologias (booleanos)
        public ?bool   $vPatologiaRachaduras = null,
        public ?bool   $vPatologiaTrincas = null,
        public ?bool   $vPatologiaFissurasEstruturais = null,
        public ?bool   $vPatologiaDeformacoesEstruturais = null,
        public ?bool   $vPatologiaInfiltracoes = null,
        public ?bool   $vPatologiaCorrosaoArmaduras = null,
        public ?bool   $vPatologiaDesagregacao = null,
        public ?bool   $vPatologiaEflorescencia = null,
        public ?bool   $vPatologiaDesplacamento = null,
        public ?bool   $vPatologiaFundacoes = null,
        public ?bool   $vPatologiaInstabilidadeTalude = null,
        public ?bool   $vPatologiaMovimentacaoSolo = null,
        public ?bool   $vPatologiaTombamentoMuralhas = null,
        public ?bool   $vPatologiaInundacoes = null,
        public ?bool   $vPatologiaAlagamentos = null,
        public ?bool   $vPatologiaEnxurradas = null,
        public ?bool   $vPatologiaMadeira = null,
        public ?bool   $vPatologiaElementosNaoEstruturais = null,
        public ?bool   $vPatologiaFalhaDrenagem = null,
        public ?bool   $vPatologiaQuedaArvores = null,
        public ?bool   $vPatologiaOutros = null,
        public ?string $vPatologiaOutrosDescricao = null,
        // Campos extras preservados para outros v_* não mapeados
        public ?array  $extraFields = [],
    ) {}

    public static function fromArray(array $data): self
    {
        // Suporte à estrutura aninhada do frontend
        $sol = $data['solicitante'] ?? [];
        $imo = $data['imovel'] ?? [];
        $est = $data['estrutura'] ?? [];
        $mor = $data['moradores'] ?? [];
        $pat = $data['patologias'] ?? [];

        return new self(
            id:                              $data['id']                                 ?? null,
            vSolicitanteNome:                $data['v_solicitante_nome']                 ?? $sol['nome'] ?? null,
            vSolicitanteCpf:                 $data['v_solicitante_cpf']                  ?? $sol['cpf'] ?? null,
            vSolicitanteTelefone:            $data['v_solicitante_telefone']             ?? $sol['telefone'] ?? null,
            vSolicitanteEndereco:            $data['v_solicitante_endereco']             ?? $sol['endereco'] ?? null,
            vSolicitanteBairro:              $data['v_solicitante_bairro']               ?? $sol['bairro'] ?? null,
            vSolicitanteCep:                 $data['v_solicitante_cep']                  ?? $sol['cep'] ?? null,
            vLocalEndereco:                  $data['v_local_endereco']                   ?? null,
            vLocalComplemento:               $data['v_local_complemento']                ?? null,
            vTipoArea:                       $data['v_tipo_area']                        ?? null,
            vTipoAreaDescricao:              $data['v_tipo_area_descricao']              ?? null,
            vTipoImovel:                     $data['v_tipo_imovel']                      ?? $est['tipo_imovel'] ?? null,
            vTipoImovelOutroDescricao:       $data['v_tipo_imovel_outro_descricao']      ?? null,
            vTipoConstrucao:                 $data['v_tipo_construcao']                  ?? $est['tipo_construcao'] ?? null,
            vTipoEdificacao:                 $data['v_tipo_edificacao']                  ?? $est['tipo_edificacao'] ?? null,
            vTipoDestinacao:                 $data['v_tipo_destinacao']                  ?? $est['tipo_destinacao'] ?? null,
            vTipoTerrenoRelevo:              $data['v_tipo_terreno_relevo']              ?? null,
            vSistemaEstrutural:              $data['v_sistema_estrutural']               ?? $est['sistema_estrutural'] ?? null,
            vNumeroPavimentos:               isset($data['v_numero_pavimentos'])         ? (int) $data['v_numero_pavimentos'] : (isset($est['num_pavimentos']) ? (int) $est['num_pavimentos'] : null),
            vEstadoConservacao:              $data['v_estado_conservacao']               ?? $est['estado_conservacao'] ?? null,
            vRegimeOcupacao:                 $data['v_regime_ocupacao']                  ?? $est['regime_ocupacao'] ?? null,
            vProprietarioMorador:            $data['v_proprietario_morador']             ?? $mor['proprietario'] ?? null,
            vContatoTelefone:                $data['v_contato_telefone']                 ?? $mor['telefone'] ?? null,
            vNumeroMoradores:                isset($data['v_numero_moradores'])          ? (int) $data['v_numero_moradores'] : (isset($mor['num_moradores']) ? (int) $mor['num_moradores'] : null),
            vHaIdosos:                       isset($data['v_ha_idosos'])                 ? (bool) $data['v_ha_idosos'] : (isset($mor['ha_idosos']) ? (bool) $mor['ha_idosos'] : null),
            vHaCriancas:                     isset($data['v_ha_criancas'])               ? (bool) $data['v_ha_criancas'] : (isset($mor['ha_criancas']) ? (bool) $mor['ha_criancas'] : null),
            vHaDificuldadeLocomocao:         $data['v_ha_dificuldade_locomocao']         ?? ($mor['ha_pcd'] ?? null ? 'sim' : null),
            vEnderecoImovel:                 $data['v_endereco_imovel']                  ?? $imo['endereco'] ?? null,
            vBairro:                         $data['v_bairro']                           ?? $imo['bairro'] ?? null,
            vMunicipio:                      $data['v_municipio']                        ?? $imo['municipio'] ?? null,
            vCep:                            $data['v_cep']                              ?? $imo['cep'] ?? null,
            vLatitude:                       isset($data['v_latitude'])                  ? (float) $data['v_latitude'] : null,
            vLongitude:                      isset($data['v_longitude'])                 ? (float) $data['v_longitude'] : null,
            // Patologias — frontend envia como { patologias: { rachaduras: true, ... } }
            vPatologiaRachaduras:            isset($data['v_patologia_rachaduras'])      ? (bool) $data['v_patologia_rachaduras'] : (isset($pat['rachaduras']) ? (bool) $pat['rachaduras'] : null),
            vPatologiaTrincas:               isset($data['v_patologia_trincas'])         ? (bool) $data['v_patologia_trincas'] : (isset($pat['trincas']) ? (bool) $pat['trincas'] : null),
            vPatologiaFissurasEstruturais:   isset($data['v_patologia_fissuras_estruturais']) ? (bool) $data['v_patologia_fissuras_estruturais'] : (isset($pat['fissuras_estruturais']) ? (bool) $pat['fissuras_estruturais'] : null),
            vPatologiaDeformacoesEstruturais:isset($data['v_patologia_deformacoes_estruturais']) ? (bool) $data['v_patologia_deformacoes_estruturais'] : (isset($pat['deformacoes_estruturais']) ? (bool) $pat['deformacoes_estruturais'] : null),
            vPatologiaInfiltracoes:          isset($data['v_patologia_infiltracoes'])    ? (bool) $data['v_patologia_infiltracoes'] : (isset($pat['infiltracoes']) ? (bool) $pat['infiltracoes'] : null),
            vPatologiaCorrosaoArmaduras:     isset($data['v_patologia_corrosao_armaduras']) ? (bool) $data['v_patologia_corrosao_armaduras'] : (isset($pat['corrosao_armaduras']) ? (bool) $pat['corrosao_armaduras'] : null),
            vPatologiaDesagregacao:          isset($data['v_patologia_desagregacao'])    ? (bool) $data['v_patologia_desagregacao'] : (isset($pat['desagregacao']) ? (bool) $pat['desagregacao'] : null),
            vPatologiaEflorescencia:         isset($data['v_patologia_eflorescencia'])   ? (bool) $data['v_patologia_eflorescencia'] : (isset($pat['eflorescencia']) ? (bool) $pat['eflorescencia'] : null),
            vPatologiaDesplacamento:         isset($data['v_patologia_desplacamento'])   ? (bool) $data['v_patologia_desplacamento'] : (isset($pat['desplacamento']) ? (bool) $pat['desplacamento'] : null),
            vPatologiaFundacoes:             isset($data['v_patologia_fundacoes'])       ? (bool) $data['v_patologia_fundacoes'] : (isset($pat['fundacoes']) ? (bool) $pat['fundacoes'] : null),
            vPatologiaInstabilidadeTalude:   isset($data['v_patologia_instabilidade_talude']) ? (bool) $data['v_patologia_instabilidade_talude'] : (isset($pat['instabilidade_talude']) ? (bool) $pat['instabilidade_talude'] : null),
            vPatologiaMovimentacaoSolo:      isset($data['v_patologia_movimentacao_solo']) ? (bool) $data['v_patologia_movimentacao_solo'] : (isset($pat['movimentacao_solo']) ? (bool) $pat['movimentacao_solo'] : null),
            vPatologiaTombamentoMuralhas:    isset($data['v_patologia_tombamento_muralhas']) ? (bool) $data['v_patologia_tombamento_muralhas'] : (isset($pat['tombamento_muralhas']) ? (bool) $pat['tombamento_muralhas'] : null),
            vPatologiaInundacoes:            isset($data['v_patologia_inundacoes'])      ? (bool) $data['v_patologia_inundacoes'] : (isset($pat['inundacoes']) ? (bool) $pat['inundacoes'] : null),
            vPatologiaAlagamentos:           isset($data['v_patologia_alagamentos'])     ? (bool) $data['v_patologia_alagamentos'] : (isset($pat['alagamentos']) ? (bool) $pat['alagamentos'] : null),
            vPatologiaEnxurradas:            isset($data['v_patologia_enxurradas'])      ? (bool) $data['v_patologia_enxurradas'] : (isset($pat['enxurradas']) ? (bool) $pat['enxurradas'] : null),
            vPatologiaMadeira:               isset($data['v_patologia_madeira'])         ? (bool) $data['v_patologia_madeira'] : (isset($pat['madeira']) ? (bool) $pat['madeira'] : null),
            vPatologiaElementosNaoEstruturais:isset($data['v_patologia_elementos_nao_estruturais']) ? (bool) $data['v_patologia_elementos_nao_estruturais'] : (isset($pat['elementos_nao_estruturais']) ? (bool) $pat['elementos_nao_estruturais'] : null),
            vPatologiaFalhaDrenagem:         isset($data['v_patologia_falha_drenagem'])  ? (bool) $data['v_patologia_falha_drenagem'] : (isset($pat['falha_drenagem']) ? (bool) $pat['falha_drenagem'] : null),
            vPatologiaQuedaArvores:          isset($data['v_patologia_queda_arvores'])   ? (bool) $data['v_patologia_queda_arvores'] : (isset($pat['queda_arvores']) ? (bool) $pat['queda_arvores'] : null),
            vPatologiaOutros:                isset($data['v_patologia_outros'])           ? (bool) $data['v_patologia_outros'] : (isset($pat['outros']) ? (bool) $pat['outros'] : null),
            vPatologiaOutrosDescricao:       $data['v_patologia_outros_descricao']       ?? $pat['outros_descricao'] ?? null,
            // Outros campos não mapeados explicitamente
            extraFields:                     array_diff_key($data, array_flip([
                'id', 'solicitante', 'imovel', 'estrutura', 'moradores', 'patologias',
            ])),
        );
    }

    public function toArray(): array
    {
        $base = [
            'v_solicitante_nome'                  => $this->vSolicitanteNome,
            'v_solicitante_cpf'                   => $this->vSolicitanteCpf,
            'v_solicitante_telefone'              => $this->vSolicitanteTelefone,
            'v_solicitante_endereco'              => $this->vSolicitanteEndereco,
            'v_solicitante_bairro'                => $this->vSolicitanteBairro,
            'v_solicitante_cep'                   => $this->vSolicitanteCep,
            'v_local_endereco'                    => $this->vLocalEndereco,
            'v_local_complemento'                 => $this->vLocalComplemento,
            'v_tipo_area'                         => $this->vTipoArea,
            'v_tipo_area_descricao'               => $this->vTipoAreaDescricao,
            'v_tipo_imovel'                       => $this->vTipoImovel,
            'v_tipo_imovel_outro_descricao'       => $this->vTipoImovelOutroDescricao,
            'v_tipo_construcao'                   => $this->vTipoConstrucao,
            'v_tipo_edificacao'                   => $this->vTipoEdificacao,
            'v_tipo_destinacao'                   => $this->vTipoDestinacao,
            'v_tipo_terreno_relevo'               => $this->vTipoTerrenoRelevo,
            'v_sistema_estrutural'                => $this->vSistemaEstrutural,
            'v_numero_pavimentos'                 => $this->vNumeroPavimentos,
            'v_estado_conservacao'                => $this->vEstadoConservacao,
            'v_regime_ocupacao'                   => $this->vRegimeOcupacao,
            'v_proprietario_morador'              => $this->vProprietarioMorador,
            'v_contato_telefone'                  => $this->vContatoTelefone,
            'v_numero_moradores'                  => $this->vNumeroMoradores,
            'v_ha_idosos'                         => $this->vHaIdosos,
            'v_ha_criancas'                       => $this->vHaCriancas,
            'v_ha_dificuldade_locomocao'          => $this->vHaDificuldadeLocomocao,
            'v_endereco_imovel'                   => $this->vEnderecoImovel,
            'v_bairro'                            => $this->vBairro,
            'v_municipio'                         => $this->vMunicipio,
            'v_cep'                               => $this->vCep,
            'v_latitude'                          => $this->vLatitude,
            'v_longitude'                         => $this->vLongitude,
            'v_patologia_rachaduras'              => $this->vPatologiaRachaduras,
            'v_patologia_trincas'                 => $this->vPatologiaTrincas,
            'v_patologia_fissuras_estruturais'    => $this->vPatologiaFissurasEstruturais,
            'v_patologia_deformacoes_estruturais' => $this->vPatologiaDeformacoesEstruturais,
            'v_patologia_infiltracoes'            => $this->vPatologiaInfiltracoes,
            'v_patologia_corrosao_armaduras'      => $this->vPatologiaCorrosaoArmaduras,
            'v_patologia_desagregacao'            => $this->vPatologiaDesagregacao,
            'v_patologia_eflorescencia'           => $this->vPatologiaEflorescencia,
            'v_patologia_desplacamento'           => $this->vPatologiaDesplacamento,
            'v_patologia_fundacoes'               => $this->vPatologiaFundacoes,
            'v_patologia_instabilidade_talude'    => $this->vPatologiaInstabilidadeTalude,
            'v_patologia_movimentacao_solo'       => $this->vPatologiaMovimentacaoSolo,
            'v_patologia_tombamento_muralhas'     => $this->vPatologiaTombamentoMuralhas,
            'v_patologia_inundacoes'              => $this->vPatologiaInundacoes,
            'v_patologia_alagamentos'             => $this->vPatologiaAlagamentos,
            'v_patologia_enxurradas'              => $this->vPatologiaEnxurradas,
            'v_patologia_madeira'                 => $this->vPatologiaMadeira,
            'v_patologia_elementos_nao_estruturais' => $this->vPatologiaElementosNaoEstruturais,
            'v_patologia_falha_drenagem'          => $this->vPatologiaFalhaDrenagem,
            'v_patologia_queda_arvores'           => $this->vPatologiaQuedaArvores,
            'v_patologia_outros'                  => $this->vPatologiaOutros,
            'v_patologia_outros_descricao'        => $this->vPatologiaOutrosDescricao,
        ];

        // Mescla campos extras que possuem chaves de colunas v_* válidas
        $extraV = array_filter($this->extraFields ?? [], fn ($k) => str_starts_with($k, 'v_'), ARRAY_FILTER_USE_KEY);

        return array_filter(array_merge($extraV, $base), fn ($v) => $v !== null);
    }
}
