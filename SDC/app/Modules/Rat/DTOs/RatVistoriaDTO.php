<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTOs;

use App\Modules\Shared\DTOs\Traits\LeituraComPresenca;
use App\Modules\Shared\DTOs\Traits\SafeCastingTrait;

/**
 * DTO para dados de Vistoria em uma ocorrência RAT.
 * Mapeia a estrutura aninhada do frontend (solicitante, imovel, estrutura, moradores,
 * infraestrutura, tecnico, patologias, encaminhamentos, bens_afetados, orgaos_acionados)
 * para as colunas v_* do banco de dados (rat_relato_vistoria).
 */
readonly class RatVistoriaDTO
{
    use LeituraComPresenca;
    use SafeCastingTrait;

    /**
     * Fonte unica das chaves aninhadas (grupo => chave => tipo) que fromArray()
     * le pelo segundo caminho do ler() ("grupo.chave"). O ReceiveRatBIRequest
     * gera as regras de validacao daqui: chave nova no fromArray entra tambem
     * neste mapa, senao a API externa a descarta no validated().
     *
     * Tipos: bool, int, latitude, longitude, misto (bool ou texto, ver
     * simOuTexto), text (ate 65535) e string:N (ate N caracteres).
     *
     * @var array<string, array<string, string>>
     */
    private const CAMPOS_GRUPOS = [
        'solicitante' => [
            'nome' => 'string:255', 'cpf' => 'string:11', 'telefone' => 'string:20',
            'endereco' => 'string:255', 'bairro' => 'string:100', 'cep' => 'string:8',
        ],
        'imovel' => [
            'endereco' => 'string:255', 'complemento' => 'string:255', 'bairro' => 'string:100',
            'municipio' => 'int', 'municipio_nome' => 'string:255', 'cep' => 'string:8',
            'latitude' => 'latitude', 'longitude' => 'longitude',
        ],
        'estrutura' => [
            'tipo_area' => 'string:50', 'tipo_imovel' => 'string:50', 'tipo_construcao' => 'string:50',
            'tipo_edificacao' => 'string:50', 'tipo_destinacao' => 'string:20',
            'tipo_terreno_relevo' => 'string:50', 'sistema_estrutural' => 'string:50',
            'num_pavimentos' => 'int', 'estado_conservacao' => 'string:20',
            'regime_ocupacao' => 'string:20', 'tipo_localizacao' => 'string:20',
        ],
        'moradores' => [
            'proprietario' => 'string:255', 'telefone' => 'string:20', 'num_moradores' => 'int',
            'ha_idosos' => 'bool', 'ha_criancas' => 'bool', 'ha_pcd' => 'misto',
        ],
        'infraestrutura' => [
            'abastecimento_agua' => 'string:10', 'esgotamento_sanitario' => 'string:10',
            'drenagem_superficial' => 'string:10', 'sistema_viario_acesso' => 'string:255',
            'tipo_revestimento' => 'string:50', 'condicoes_acesso' => 'string:500',
            'num_moradias_terreno' => 'int', 'distancia_encosta' => 'string:50',
        ],
        'tecnico' => [
            'material_construtivo' => 'string:255', 'conservacao_estrutural' => 'string:1000',
            'elementos_estruturais' => 'string:1000', 'elementos_construtivos' => 'string:1000',
            'agentes_potencializadores' => 'string:1000', 'processos_geodinamicos' => 'string:1000',
            'danos_estruturais' => 'string:20', 'manutencao_uso_ocupacao' => 'string:20',
            'historico' => 'text',
        ],
    ];

    /**
     * Grupos de marcacao (chave => bool, mais outros_descricao em texto).
     *
     * @var array<string, array<int, string>>
     */
    private const GRUPOS_BOOLEANOS = [
        'patologias' => [
            'rachaduras', 'trincas', 'fissuras_estruturais', 'deformacoes_estruturais', 'infiltracoes',
            'corrosao_armaduras', 'desagregacao', 'eflorescencia', 'desplacamento', 'fundacoes',
            'instabilidade_talude', 'movimentacao_solo', 'tombamento_muralhas', 'inundacoes',
            'alagamentos', 'enxurradas', 'madeira', 'elementos_nao_estruturais', 'falha_drenagem',
            'queda_arvores', 'outros',
        ],
        'encaminhamentos' => [
            'interdicao_parcial', 'interdicao_total', 'remocao_temporaria', 'remocao_definitiva',
            'isolamento_area', 'desocupacao_abrigo', 'notificacao_responsavel',
            'contratacao_responsavel', 'comunicacao_orgaos', 'apoio_social', 'outros',
        ],
        'bens_afetados' => [
            'residencia', 'muros', 'vias_publicas', 'pontes', 'viadutos', 'comercios', 'galpoes',
            'predios_publicos', 'edificios_publicos', 'outros',
        ],
        'orgaos_acionados' => [
            'copasa', 'cemig', 'secretaria_municipal', 'defesa_civil_estadual', 'dnit', 'crea',
            'emater', 'seapa', 'outros',
        ],
    ];

    /** Chaves de primeiro nivel da vistoria lidas como alternativa (v_historico). */
    public const CAMPOS_RAIZ = ['observacoes' => 'text'];

    /**
     * Mapa canonico grupo => chave => tipo dos caminhos "grupo.chave" do fromArray().
     *
     * @return array<string, array<string, string>>
     */
    public static function camposAninhados(): array
    {
        $grupos = self::CAMPOS_GRUPOS;

        foreach (self::GRUPOS_BOOLEANOS as $grupo => $chaves) {
            $grupos[$grupo] = array_fill_keys($chaves, 'bool') + ['outros_descricao' => 'text'];
        }

        // PM e BM aceitam booleano ou texto (simOuTexto).
        $grupos['orgaos_acionados'] += ['pm' => 'misto', 'bm' => 'misto'];

        return $grupos;
    }

    public function __construct(
        // UUID: as tabelas RAT usam uuid desde 2026_05_18_000001.
        public ?string $id = null,
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
        public ?string $vEnderecoImovel  = null,
        public ?string $vBairro          = null,
        public ?int    $vMunicipio       = null,
        public ?string $vMunicipioNome   = null,
        public ?string $vCep             = null,
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
        /** @var array<int, string> colunas v_* enviadas no payload */
        public array   $presentes                  = [],
    ) {}

    public static function fromArray(array $data): self
    {
        // Colunas que vieram no payload, plano (v_*) ou aninhado (grupo.chave):
        // so elas vao ao toArray(), entao o que nao foi enviado fica intacto.
        $p = [];

        return new self(
            id: self::asStringOrNull($data['id'] ?? null),

            // Solicitante
            vSolicitanteNome:     self::asStringOrNull(self::ler($data, $p, 'v_solicitante_nome', 'solicitante.nome')),
            vSolicitanteCpf:      self::asStringOrNull(self::ler($data, $p, 'v_solicitante_cpf', 'solicitante.cpf')),
            vSolicitanteTelefone: self::asStringOrNull(self::ler($data, $p, 'v_solicitante_telefone', 'solicitante.telefone')),
            vSolicitanteEndereco: self::asStringOrNull(self::ler($data, $p, 'v_solicitante_endereco', 'solicitante.endereco')),
            vSolicitanteBairro:   self::asStringOrNull(self::ler($data, $p, 'v_solicitante_bairro', 'solicitante.bairro')),
            vSolicitanteCep:      self::asStringOrNull(self::ler($data, $p, 'v_solicitante_cep', 'solicitante.cep')),

            // Local e imóvel
            vLocalEndereco:               self::asStringOrNull(self::ler($data, $p, 'v_local_endereco')),
            vLocalComplemento:            self::asStringOrNull(self::ler($data, $p, 'v_local_complemento', 'imovel.complemento')),
            vTipoArea:                    self::asStringOrNull(self::ler($data, $p, 'v_tipo_area', 'estrutura.tipo_area')),
            vTipoAreaDescricao:           self::asStringOrNull(self::ler($data, $p, 'v_tipo_area_descricao')),
            vTipoImovel:                  self::asStringOrNull(self::ler($data, $p, 'v_tipo_imovel', 'estrutura.tipo_imovel')),
            vTipoImovelOutroDescricao:    self::asStringOrNull(self::ler($data, $p, 'v_tipo_imovel_outro_descricao')),
            vTipoConstrucao:              self::asStringOrNull(self::ler($data, $p, 'v_tipo_construcao', 'estrutura.tipo_construcao')),
            vTipoConstrucaoOutroDescricao:self::asStringOrNull(self::ler($data, $p, 'v_tipo_construcao_outro_descricao')),
            vTipoEdificacao:              self::asStringOrNull(self::ler($data, $p, 'v_tipo_edificacao', 'estrutura.tipo_edificacao')),
            vTipoDestinacao:              self::asStringOrNull(self::ler($data, $p, 'v_tipo_destinacao', 'estrutura.tipo_destinacao')),
            vTipoTerrenoRelevo:           self::asStringOrNull(self::ler($data, $p, 'v_tipo_terreno_relevo', 'estrutura.tipo_terreno_relevo')),
            vSistemaEstrutural:           self::asStringOrNull(self::ler($data, $p, 'v_sistema_estrutural', 'estrutura.sistema_estrutural')),
            vNumeroPavimentos:            self::asIntOrNull(self::ler($data, $p, 'v_numero_pavimentos', 'estrutura.num_pavimentos')),
            vEstadoConservacao:           self::asStringOrNull(self::ler($data, $p, 'v_estado_conservacao', 'estrutura.estado_conservacao')),
            vRegimeOcupacao:              self::asStringOrNull(self::ler($data, $p, 'v_regime_ocupacao', 'estrutura.regime_ocupacao')),
            vTipoLocalizacao:             self::asStringOrNull(self::ler($data, $p, 'v_tipo_localizacao', 'estrutura.tipo_localizacao')),

            // Moradores
            vProprietarioMorador:    self::asStringOrNull(self::ler($data, $p, 'v_proprietario_morador', 'moradores.proprietario')),
            vContatoTelefone:        self::asStringOrNull(self::ler($data, $p, 'v_contato_telefone', 'moradores.telefone')),
            vNumeroMoradores:        self::asIntOrNull(self::ler($data, $p, 'v_numero_moradores', 'moradores.num_moradores')),
            vHaIdosos:               self::asBoolOrNull(self::ler($data, $p, 'v_ha_idosos', 'moradores.ha_idosos')),
            vHaCriancas:             self::asBoolOrNull(self::ler($data, $p, 'v_ha_criancas', 'moradores.ha_criancas')),
            vHaDificuldadeLocomocao: self::simOuTexto(self::ler($data, $p, 'v_ha_dificuldade_locomocao', 'moradores.ha_pcd')),

            // Localização do imóvel
            vEnderecoImovel: self::asStringOrNull(self::ler($data, $p, 'v_endereco_imovel', 'imovel.endereco')),
            vBairro:         self::asStringOrNull(self::ler($data, $p, 'v_bairro', 'imovel.bairro')),
            vMunicipio:      self::asIntOrNull(self::ler($data, $p, 'v_municipio', 'imovel.municipio')),
            vMunicipioNome:  self::asStringOrNull(self::ler($data, $p, 'v_municipio_nome', 'imovel.municipio_nome')),
            vCep:            self::asStringOrNull(self::ler($data, $p, 'v_cep', 'imovel.cep')),
            vLatitude:       self::asFloatOrNull(self::ler($data, $p, 'v_latitude', 'imovel.latitude')),
            vLongitude:      self::asFloatOrNull(self::ler($data, $p, 'v_longitude', 'imovel.longitude')),

            // Infraestrutura
            vAbastecimentoAgua:    self::asStringOrNull(self::ler($data, $p, 'v_abastecimento_agua', 'infraestrutura.abastecimento_agua')),
            vEsgotamentoSanitario: self::asStringOrNull(self::ler($data, $p, 'v_esgotamento_sanitario', 'infraestrutura.esgotamento_sanitario')),
            vDranagemSuperficial:  self::asStringOrNull(self::ler($data, $p, 'v_drenagem_superficial', 'infraestrutura.drenagem_superficial')),
            vSistemaViarioAcesso:  self::asStringOrNull(self::ler($data, $p, 'v_sistema_viario_acesso', 'infraestrutura.sistema_viario_acesso')),
            vTipoRevestimento:     self::asStringOrNull(self::ler($data, $p, 'v_tipo_revestimento', 'infraestrutura.tipo_revestimento')),
            vCondicoesAcesso:      self::asStringOrNull(self::ler($data, $p, 'v_condicoes_acesso', 'infraestrutura.condicoes_acesso')),
            vNumeroMoradiasTereno: self::asIntOrNull(self::ler($data, $p, 'v_numero_moradias_terreno', 'infraestrutura.num_moradias_terreno')),
            vDistanciaEncosta:     self::asStringOrNull(self::ler($data, $p, 'v_distancia_encosta', 'infraestrutura.distancia_encosta')),

            // Aspectos técnicos
            vMaterialConstrutivo:      self::asStringOrNull(self::ler($data, $p, 'v_material_construtivo', 'tecnico.material_construtivo')),
            vConservacaoEstrutural:    self::asStringOrNull(self::ler($data, $p, 'v_conservacao_estrutural', 'tecnico.conservacao_estrutural')),
            vElementosEstruturais:     self::asStringOrNull(self::ler($data, $p, 'v_elementos_estruturais', 'tecnico.elementos_estruturais')),
            vElementosConstrutivos:    self::asStringOrNull(self::ler($data, $p, 'v_elementos_construtivos', 'tecnico.elementos_construtivos')),
            vAgentesPotencializadores: self::asStringOrNull(self::ler($data, $p, 'v_agentes_potencializadores', 'tecnico.agentes_potencializadores')),
            vProcessosGeodinamicos:    self::asStringOrNull(self::ler($data, $p, 'v_processos_geodinamicos', 'tecnico.processos_geodinamicos')),
            vDanosEstruturais:         self::asStringOrNull(self::ler($data, $p, 'v_danos_estruturais', 'tecnico.danos_estruturais')),
            vManutencaoUsoOcupacao:    self::asStringOrNull(self::ler($data, $p, 'v_manutencao_uso_ocupacao', 'tecnico.manutencao_uso_ocupacao')),
            vHistorico:                self::asStringOrNull(self::ler($data, $p, 'v_historico', 'tecnico.historico', 'observacoes')),

            // Patologias
            vPatologiaRachaduras:            self::asBoolOrNull(self::ler($data, $p, 'v_patologia_rachaduras', 'patologias.rachaduras')),
            vPatologiaTrincas:               self::asBoolOrNull(self::ler($data, $p, 'v_patologia_trincas', 'patologias.trincas')),
            vPatologiaFissurasEstruturais:   self::asBoolOrNull(self::ler($data, $p, 'v_patologia_fissuras_estruturais', 'patologias.fissuras_estruturais')),
            vPatologiaDeformacoesEstruturais:self::asBoolOrNull(self::ler($data, $p, 'v_patologia_deformacoes_estruturais', 'patologias.deformacoes_estruturais')),
            vPatologiaInfiltracoes:          self::asBoolOrNull(self::ler($data, $p, 'v_patologia_infiltracoes', 'patologias.infiltracoes')),
            vPatologiaCorrosaoArmaduras:     self::asBoolOrNull(self::ler($data, $p, 'v_patologia_corrosao_armaduras', 'patologias.corrosao_armaduras')),
            vPatologiaDesagregacao:          self::asBoolOrNull(self::ler($data, $p, 'v_patologia_desagregacao', 'patologias.desagregacao')),
            vPatologiaEflorescencia:         self::asBoolOrNull(self::ler($data, $p, 'v_patologia_eflorescencia', 'patologias.eflorescencia')),
            vPatologiaDesplacamento:         self::asBoolOrNull(self::ler($data, $p, 'v_patologia_desplacamento', 'patologias.desplacamento')),
            vPatologiaFundacoes:             self::asBoolOrNull(self::ler($data, $p, 'v_patologia_fundacoes', 'patologias.fundacoes')),
            vPatologiaInstabilidadeTalude:   self::asBoolOrNull(self::ler($data, $p, 'v_patologia_instabilidade_talude', 'patologias.instabilidade_talude')),
            vPatologiaMovimentacaoSolo:      self::asBoolOrNull(self::ler($data, $p, 'v_patologia_movimentacao_solo', 'patologias.movimentacao_solo')),
            vPatologiaTombamentoMuralhas:    self::asBoolOrNull(self::ler($data, $p, 'v_patologia_tombamento_muralhas', 'patologias.tombamento_muralhas')),
            vPatologiaInundacoes:            self::asBoolOrNull(self::ler($data, $p, 'v_patologia_inundacoes', 'patologias.inundacoes')),
            vPatologiaAlagamentos:           self::asBoolOrNull(self::ler($data, $p, 'v_patologia_alagamentos', 'patologias.alagamentos')),
            vPatologiaEnxurradas:            self::asBoolOrNull(self::ler($data, $p, 'v_patologia_enxurradas', 'patologias.enxurradas')),
            vPatologiaMadeira:               self::asBoolOrNull(self::ler($data, $p, 'v_patologia_madeira', 'patologias.madeira')),
            vPatologiaElementosNaoEstruturais:self::asBoolOrNull(self::ler($data, $p, 'v_patologia_elementos_nao_estruturais', 'patologias.elementos_nao_estruturais')),
            vPatologiaFalhaDrenagem:         self::asBoolOrNull(self::ler($data, $p, 'v_patologia_falha_drenagem', 'patologias.falha_drenagem')),
            vPatologiaQuedaArvores:          self::asBoolOrNull(self::ler($data, $p, 'v_patologia_queda_arvores', 'patologias.queda_arvores')),
            vPatologiaOutros:                self::asBoolOrNull(self::ler($data, $p, 'v_patologia_outros', 'patologias.outros')),
            vPatologiaOutrosDescricao:       self::asStringOrNull(self::ler($data, $p, 'v_patologia_outros_descricao', 'patologias.outros_descricao')),

            // Encaminhamentos
            vEncInterdicaoParcial:      self::asBoolOrNull(self::ler($data, $p, 'v_enc_interdicao_parcial', 'encaminhamentos.interdicao_parcial')),
            vEncInterdicaoTotal:        self::asBoolOrNull(self::ler($data, $p, 'v_enc_interdicao_total', 'encaminhamentos.interdicao_total')),
            vEncRemocaoTemporaria:      self::asBoolOrNull(self::ler($data, $p, 'v_enc_remocao_temporaria', 'encaminhamentos.remocao_temporaria')),
            vEncRemocaoDefinitiva:      self::asBoolOrNull(self::ler($data, $p, 'v_enc_remocao_definitiva', 'encaminhamentos.remocao_definitiva')),
            vEncIsolamentoArea:         self::asBoolOrNull(self::ler($data, $p, 'v_enc_isolamento_area', 'encaminhamentos.isolamento_area')),
            vEncDesocupacaoAbrigo:      self::asBoolOrNull(self::ler($data, $p, 'v_enc_desocupacao_abrigo', 'encaminhamentos.desocupacao_abrigo')),
            vEncNotificacaoResponsavel: self::asBoolOrNull(self::ler($data, $p, 'v_enc_notificacao_responsavel', 'encaminhamentos.notificacao_responsavel')),
            vEncContratacaoResponsavel: self::asBoolOrNull(self::ler($data, $p, 'v_enc_contratacao_responsavel', 'encaminhamentos.contratacao_responsavel')),
            vEncComunicacaoOrgaos:      self::asBoolOrNull(self::ler($data, $p, 'v_enc_comunicacao_orgaos', 'encaminhamentos.comunicacao_orgaos')),
            vEncApoioSocial:            self::asBoolOrNull(self::ler($data, $p, 'v_enc_apoio_social', 'encaminhamentos.apoio_social')),
            vEncOutros:                 self::asBoolOrNull(self::ler($data, $p, 'v_enc_outros', 'encaminhamentos.outros')),
            vEncOutrosDescricao:        self::asStringOrNull(self::ler($data, $p, 'v_enc_outros_descricao', 'encaminhamentos.outros_descricao')),

            // Bens afetados
            vBensResidencia:        self::asBoolOrNull(self::ler($data, $p, 'v_bens_residencia', 'bens_afetados.residencia')),
            vBensMuros:             self::asBoolOrNull(self::ler($data, $p, 'v_bens_muros', 'bens_afetados.muros')),
            vBensViasPublicas:      self::asBoolOrNull(self::ler($data, $p, 'v_bens_vias_publicas', 'bens_afetados.vias_publicas')),
            vBensPontes:            self::asBoolOrNull(self::ler($data, $p, 'v_bens_pontes', 'bens_afetados.pontes')),
            vBensViadutos:          self::asBoolOrNull(self::ler($data, $p, 'v_bens_viadutos', 'bens_afetados.viadutos')),
            vBensComercios:         self::asBoolOrNull(self::ler($data, $p, 'v_bens_comercios', 'bens_afetados.comercios')),
            vBensGalpoes:           self::asBoolOrNull(self::ler($data, $p, 'v_bens_galpoes', 'bens_afetados.galpoes')),
            vBensPrediosPublicos:   self::asBoolOrNull(self::ler($data, $p, 'v_bens_predios_publicos', 'bens_afetados.predios_publicos')),
            vBensEdificiosPublicos: self::asBoolOrNull(self::ler($data, $p, 'v_bens_edificios_publicos', 'bens_afetados.edificios_publicos')),
            vBensOutros:            self::asBoolOrNull(self::ler($data, $p, 'v_bens_outros', 'bens_afetados.outros')),
            vBensOutrosDescricao:   self::asStringOrNull(self::ler($data, $p, 'v_bens_outros_descricao', 'bens_afetados.outros_descricao')),

            // Órgãos acionados
            vOrgaoCopasa:               self::asBoolOrNull(self::ler($data, $p, 'v_orgao_copasa', 'orgaos_acionados.copasa')),
            vOrgaoCemig:                self::asBoolOrNull(self::ler($data, $p, 'v_orgao_cemig', 'orgaos_acionados.cemig')),
            vOrgaoSecretariaMunicipal:  self::asBoolOrNull(self::ler($data, $p, 'v_orgao_secretaria_municipal', 'orgaos_acionados.secretaria_municipal')),
            vOrgaoDefesaCivilEstadual:  self::asBoolOrNull(self::ler($data, $p, 'v_orgao_defesa_civil_estadual', 'orgaos_acionados.defesa_civil_estadual')),
            vOrgaoDnit:                 self::asBoolOrNull(self::ler($data, $p, 'v_orgao_dnit', 'orgaos_acionados.dnit')),
            vOrgaoPm:                   self::simOuTexto(self::ler($data, $p, 'v_orgao_pm', 'orgaos_acionados.pm')),
            vOrgaoBm:                   self::simOuTexto(self::ler($data, $p, 'v_orgao_bm', 'orgaos_acionados.bm')),
            vOrgaoCrea:                 self::asBoolOrNull(self::ler($data, $p, 'v_orgao_crea', 'orgaos_acionados.crea')),
            vOrgaoEmater:               self::asBoolOrNull(self::ler($data, $p, 'v_orgao_emater', 'orgaos_acionados.emater')),
            vOrgaoSeapa:                self::asBoolOrNull(self::ler($data, $p, 'v_orgao_seapa', 'orgaos_acionados.seapa')),
            vOrgaoOutros:               self::asBoolOrNull(self::ler($data, $p, 'v_orgao_outros', 'orgaos_acionados.outros')),
            vOrgaoOutrosDescricao:      self::asStringOrNull(self::ler($data, $p, 'v_orgao_outros_descricao', 'orgaos_acionados.outros_descricao')),
            presentes: $p,
        );
    }

    /**
     * PM, BM e PcD: o formulario manda booleano (marcado = "sim"); integracoes
     * podem mandar texto. Booleano falso, inclusive a string "false", limpa.
     */
    private static function simOuTexto(mixed $v): ?string
    {
        $bool = self::asBoolOrNull($v);

        if ($bool !== null) {
            return $bool ? 'sim' : null;
        }

        return self::asStringOrNull($v);
    }

    /**
     * Somente as colunas enviadas: ausente preserva o banco, null/'' limpa,
     * false e 0 gravam (contrato de update parcial do plano_dtos.md).
     */
    public function toArray(): array
    {
        return self::somentePresentes([
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
            'v_municipio_nome'                      => $this->vMunicipioNome,
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
        ], $this->presentes);
    }
}
