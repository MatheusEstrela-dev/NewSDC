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
        public ?string $descricao               = null,
        public ?string $comTelefoneContato      = null,
        public ?string $comNomeSolicitante      = null,
        public ?string $localMunicipioNome      = null,
    ) {}

    /**
     * Cria o DTO a partir do array retornado por FormRequest::validated().
     */
    public static function fromArray(array $data): self
    {
        // Se os dados vierem aninhados (comunicacao, local, endereco), faz o merge para o nível raiz
        // para facilitar o mapeamento no DTO.
        $merged = array_merge(
            $data['dadosGerais'] ?? [],
            $data['comunicacao'] ?? [],
            $data['local'] ?? [],
            $data['endereco'] ?? [],
            $data // Mantém os campos que já estão na raiz
        );

        return new self(
            dataFato:                 $merged['data_fato'] ?? $merged['data_ocorrencia'] ?? $merged['data'] ?? null,
            dataInicioAtividade:      $merged['data_inicio_atividade']      ?? null,
            dataTerminoAtividade:     $merged['data_termino_atividade']     ?? null,
            natCodigo:                $merged['nat_codigo'] ?? $merged['natureza'] ?? null,
            natCobradeId:             isset($merged['nat_cobrade_id'])
                                          ? (int) $merged['nat_cobrade_id']
                                          : null,
            natOcorrencia:            $merged['nat_ocorrencia']             ?? null,
            natNomeOperacao:          $merged['nat_nome_operacao']          ?? null,
            uniResponsavelMunicipio:  $merged['uni_responsavel_municipio']  ?? null,
            uniResponsavelCodigo:     $merged['uni_responsavel_codigo']     ?? null,
            uniResponsavelUnidade:    $merged['uni_responsavel_unidade']    ?? null,
            uniBoCodUnidade:          $merged['uni_bo_cod_unidade']         ?? null,
            uniBoAno:                 isset($merged['uni_bo_ano'])
                                          ? (int) $merged['uni_bo_ano']
                                          : null,
            uniBoSequencial:          $merged['uni_bo_sequencial']          ?? null,
            comOcorrenciaData:        $merged['com_ocorrencia_data']        ?? $merged['data_comunicacao'] ?? $merged['data'] ?? null,
            comOcorrenciaAtendimento: $merged['com_ocorrencia_atendimento'] ?? $merged['tipo_solicitacao'] ?? $merged['atendimento'] ?? null,
            localPais:                $merged['local_pais']                 ?? $merged['pais_id'] ?? $merged['pais'] ?? null,
            localEstadoUf:            $merged['local_estadouf'] ?? $merged['local_estado_uf'] ?? $merged['uf'] ?? null,
            localMunicipio:           $merged['local_municipio'] ?? $merged['municipio_id'] ?? $merged['cidade'] ?? $merged['municipio'] ?? null,
            localCep:                 $merged['local_cep']                  ?? $merged['cep'] ?? null,
            localLogradouro:          $merged['local_logradouro'] ?? $merged['local_logradoura_1'] ?? $merged['logradouro'] ?? null,
            localBairro:              $merged['local_bairro']               ?? $merged['bairro'] ?? null,
            localComplemento:         $merged['local_complemento']          ?? $merged['complemento'] ?? null,
            localNumero:              $merged['local_numero']               ?? $merged['numero'] ?? null,
            localKm:                  $merged['local_km']                   ?? $merged['km'] ?? null,
            localCruzamento:          $merged['local_cruzamento']           ?? null,
            localPontoReferencia:    $merged['local_ponto_referencia']     ?? $merged['ponto_referencia'] ?? null,
            localLatitude:           isset($merged['local_latitude'])
                                          ? (float) $merged['local_latitude']
                                          : (isset($merged['latitude']) ? (float) $merged['latitude'] : null),
            localLongitude:          isset($merged['local_longitude'])
                                          ? (float) $merged['local_longitude']
                                          : (isset($merged['longitude']) ? (float) $merged['longitude'] : null),
            localOcorrenciaTipo:     $merged['local_ocorrencia_tipo']      ?? $merged['tipo_localizacao'] ?? $merged['tipo'] ?? null,
            localOcorrenciaEstradas: $merged['local_ocorrencia_estradas_rodovias'] ?? $merged['estradas_rodovias'] ?? null,
            localUnidadeMilitar:     $merged['local_unidade_militar']      ?? $merged['unidade_militar'] ?? null,
            temVistoria:             (bool) ($merged['tem_vistoria']       ?? false),
            descricao:               $merged['descricao']                   ?? null,
            comTelefoneContato:      $merged['telefone_contato']            ?? $merged['com_telefone_contato'] ?? null,
            comNomeSolicitante:      $merged['nome_solicitante']            ?? $merged['com_nome_solicitante'] ?? null,
            localMunicipioNome:      !empty($merged['municipio_nome'])      ? $merged['municipio_nome']        : null,
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
            'descricao'                   => $this->descricao,
            'com_telefone_contato'        => $this->comTelefoneContato,
            'com_nome_solicitante'        => $this->comNomeSolicitante,
            'local_municipio_nome'        => $this->localMunicipioNome,
        ], fn ($v) => $v !== null && $v !== '');
    }
}
