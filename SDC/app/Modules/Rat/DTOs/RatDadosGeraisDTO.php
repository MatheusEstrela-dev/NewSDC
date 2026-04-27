<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTOs;

/**
 * DTO de entrada para criar/atualizar os dados gerais de uma ocorrência RAT
 * (tabela rat_relato_dados_gerais).
 *
 * Mapeia os campos validados por RatDadosGeraisRequest, garantindo tipagem
 * estrita entre a camada HTTP e RatRelatoService / RatOcorrenciaService.
 */
readonly class RatDadosGeraisDTO
{
    /**
     * Converte ISO datetime (YYYY-MM-DDTHH:mm) para dd/mm/aaaa hh:mm
     */
    private static function convertIsoDateTime(?string $isoDateTime): ?string
    {
        if (!$isoDateTime) return null;

        // Se já está em formato dd/mm/aaaa hh:mm, retorna como está
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}/', $isoDateTime)) {
            return $isoDateTime;
        }

        // Se é ISO datetime (YYYY-MM-DDTHH:mm), converte
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/', $isoDateTime, $matches)) {
            return "{$matches[3]}/{$matches[2]}/{$matches[1]} {$matches[4]}:{$matches[5]}";
        }

        return $isoDateTime;
    }

    public function __construct(
        // Datas e Horários
        public ?string $dataFato               = null,
        public ?string $dataInicioAtividade    = null,
        public ?string $dataTerminoAtividade   = null,

        // Natureza e Códigos
        public ?string $natCodigo              = null,
        public ?int    $natCobradeId           = null,
        public ?string $natOcorrencia          = null,
        public ?string $natNomeOperacao        = null,

        // Unidade Responsável e BO
        public ?string $uniResponsavelMunicipio = null,
        public ?string $uniResponsavelCodigo    = null,
        public ?string $uniResponsavelUnidade   = null,
        public ?string $uniBoCodUnidade         = null,
        public ?int    $uniBoAno                = null,
        public ?string $uniBoSequencial         = null,

        // Comunicação
        public ?string $comOcorrenciaData       = null,
        public ?string $comOcorrenciaAtendimento = null,

        // Localização e Endereço
        public ?string $localPais               = null,
        public ?string $localEstadoUf           = null,
        public ?string $localMunicipio          = null,
        public ?string $localCep                = null,
        public ?string $localLogradouro         = null,
        public ?string $localBairro             = null,
        public ?string $localComplemento        = null,
        public ?string $localNumero             = null,
        public ?string $localKm                 = null,
        public ?string $localCruzamento         = null,
        public ?string $localPontoReferencia    = null,
        public ?float  $localLatitude           = null,
        public ?float  $localLongitude          = null,
        public ?string $localOcorrenciaTipo     = null,
        public ?string $localOcorrenciaEstradas = null,
        public ?string $localUnidadeMilitar     = null,
        public ?bool   $temVistoria             = false,
    ) {}

    /**
     * Cria o DTO a partir do array retornado por FormRequest::validated().
     *
     * Espera estrutura aninhada:
     * {
     *   "dadosGerais": { "data_fato": "...", "nat_cobrade_id": ... },
     *   "comunicacao": { "com_ocorrencia_data": "...", ... },
     *   "local": { "local_pais": "...", "local_uf": "...", ... },
     *   "endereco": { "local_cep": "...", "local_logradouro": "...", ... }
     * }
     */
    public static function fromArray(array $data): self
    {
        // Extrai cada seção aninhada com segurança, garantindo que campos individuais sejam extraídos
        $dadosGerais = is_array($data['dadosGerais'] ?? null) ? $data['dadosGerais'] : [];
        $comunicacao = is_array($data['comunicacao'] ?? null) ? $data['comunicacao'] : [];
        $local = is_array($data['local'] ?? null) ? $data['local'] : [];
        $endereco = is_array($data['endereco'] ?? null) ? $data['endereco'] : [];

        // Campos na raiz (fallback para compatibilidade)
        $root = array_filter($data, fn($k) => !in_array($k, ['dadosGerais', 'comunicacao', 'local', 'endereco']), ARRAY_FILTER_USE_KEY);

        return new self(
            dataFato:                 self::convertIsoDateTime($dadosGerais['data_fato'] ?? $root['data_fato'] ?? null),
            dataInicioAtividade:      self::convertIsoDateTime($dadosGerais['data_inicio_atividade'] ?? $root['data_inicio_atividade'] ?? null),
            dataTerminoAtividade:     self::convertIsoDateTime($dadosGerais['data_termino_atividade'] ?? $root['data_termino_atividade'] ?? null),
            natCodigo:                $dadosGerais['nat_codigo'] ?? $root['nat_codigo'] ?? $root['natureza'] ?? null,
            natCobradeId:             isset($dadosGerais['nat_cobrade_id'])
                                          ? (int) $dadosGerais['nat_cobrade_id']
                                          : (isset($root['nat_cobrade_id']) ? (int) $root['nat_cobrade_id'] : null),
            natOcorrencia:            $dadosGerais['nat_ocorrencia'] ?? $root['nat_ocorrencia'] ?? null,
            natNomeOperacao:          $dadosGerais['nat_nome_operacao'] ?? $root['nat_nome_operacao'] ?? null,
            uniResponsavelMunicipio:  $dadosGerais['uni_responsavel_municipio'] ?? $root['uni_responsavel_municipio'] ?? null,
            uniResponsavelCodigo:     $dadosGerais['uni_responsavel_codigo'] ?? $root['uni_responsavel_codigo'] ?? null,
            uniResponsavelUnidade:    $dadosGerais['uni_responsavel_unidade'] ?? $root['uni_responsavel_unidade'] ?? null,
            uniBoCodUnidade:          $dadosGerais['uni_bo_cod_unidade'] ?? $root['uni_bo_cod_unidade'] ?? null,
            uniBoAno:                 isset($dadosGerais['uni_bo_ano'])
                                          ? (int) $dadosGerais['uni_bo_ano']
                                          : (isset($root['uni_bo_ano']) ? (int) $root['uni_bo_ano'] : null),
            uniBoSequencial:          $dadosGerais['uni_bo_sequencial'] ?? $root['uni_bo_sequencial'] ?? null,
            comOcorrenciaData:        self::convertIsoDateTime($comunicacao['com_ocorrencia_data'] ?? $root['com_ocorrencia_data'] ?? null),
            comOcorrenciaAtendimento: $comunicacao['com_ocorrencia_atendimento'] ?? $root['com_ocorrencia_atendimento'] ?? null,
            localPais:                $local['local_pais'] ?? $root['local_pais'] ?? $root['pais_id'] ?? null,
            localEstadoUf:            $local['local_uf'] ?? $local['local_estadouf'] ?? $local['local_estado_uf'] ?? $root['local_uf'] ?? $root['uf'] ?? null,
            localMunicipio:           $local['local_municipio'] ?? $root['local_municipio'] ?? $root['city'] ?? $root['municipio'] ?? null,
            localCep:                 $endereco['local_cep'] ?? $root['local_cep'] ?? $root['cep'] ?? null,
            localLogradouro:          $endereco['local_logradouro'] ?? $root['local_logradouro'] ?? $root['logradouro'] ?? null,
            localBairro:              $endereco['local_bairro'] ?? $root['local_bairro'] ?? $root['bairro'] ?? null,
            localComplemento:         $endereco['local_complemento'] ?? $root['local_complemento'] ?? $root['complemento'] ?? null,
            localNumero:              $endereco['local_numero'] ?? $root['local_numero'] ?? $root['numero'] ?? null,
            localKm:                  $endereco['local_km'] ?? $root['local_km'] ?? $root['km'] ?? null,
            localCruzamento:          $endereco['local_cruzamento'] ?? $root['local_cruzamento'] ?? null,
            localPontoReferencia:    $endereco['local_ponto_referencia'] ?? $root['local_ponto_referencia'] ?? $root['ponto_referencia'] ?? null,
            localLatitude:           isset($endereco['local_latitude'])
                                          ? (float) $endereco['local_latitude']
                                          : (isset($root['local_latitude']) ? (float) $root['local_latitude'] : null),
            localLongitude:          isset($endereco['local_longitude'])
                                          ? (float) $endereco['local_longitude']
                                          : (isset($root['local_longitude']) ? (float) $root['local_longitude'] : null),
            localOcorrenciaTipo:     $root['local_ocorrencia_tipo'] ?? $root['tipo'] ?? null,
            localOcorrenciaEstradas: $root['local_ocorrencia_estradas_rodovias'] ?? $root['estradas_rodovias'] ?? null,
            localUnidadeMilitar:     $root['local_unidade_militar'] ?? $root['unidade_militar'] ?? null,
            temVistoria:             (bool) ($dadosGerais['tem_vistoria'] ?? $root['tem_vistoria'] ?? false),
        );
    }

    /**
     * Converte para array com as chaves esperadas pelo Eloquent/BD.
     * Campos nulos são excluídos.
     */
    public function toArray(): array
    {
        return array_filter([
            'data_fato'                   => $this->dataFato,
            'data_inicio_atividade'       => $this->dataInicioAtividade,
            'data_termino_atividade'      => $this->dataTerminoAtividade,
            'nat_codigo'                  => $this->natCodigo,
            'nat_cobrade_id'              => $this->natCobradeId,
            'nat_ocorrencia'              => $this->natOcorrencia,
            'nat_nome_operacao'           => $this->natNomeOperacao,
            'uni_responsavel_municipio'   => $this->uniResponsavelMunicipio,
            'uni_responsavel_codigo'      => $this->uniResponsavelCodigo,
            'uni_responsavel_unidade'     => $this->uniResponsavelUnidade,
            'uni_bo_cod_unidade'          => $this->uniBoCodUnidade,
            'uni_bo_ano'                  => $this->uniBoAno,
            'uni_bo_sequencial'           => $this->uniBoSequencial,
            'com_ocorrencia_data'         => $this->comOcorrenciaData,
            'com_ocorrencia_atendimento'  => $this->comOcorrenciaAtendimento,
            'local_pais'                  => $this->localPais,
            'local_estadouf'              => $this->localEstadoUf,
            'local_municipio'             => $this->localMunicipio,
            'local_cep'                   => $this->localCep,
            'local_logradoura_1'          => $this->localLogradouro,
            'local_bairro'                => $this->localBairro,
            'local_complemento'           => $this->localComplemento,
            'local_numero'                => $this->localNumero,
            'local_km'                    => $this->localKm,
            'local_cruzamento'            => $this->localCruzamento,
            'local_ponto_referencia'      => $this->localPontoReferencia,
            'local_latitude'              => $this->localLatitude,
            'local_longitude'             => $this->localLongitude,
            'local_ocorrencia_tipo'       => $this->localOcorrenciaTipo,
            'local_ocorrencia_estradas_rodovias' => $this->localOcorrenciaEstradas,
            'local_unidade_militar'       => $this->localUnidadeMilitar,
            'tem_vistoria'                => $this->temVistoria,
        ], fn ($v) => $v !== null);
    }
}
