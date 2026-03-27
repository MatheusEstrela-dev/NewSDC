<?php

declare(strict_types=1);

namespace App\DTOs\Rat;

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
        public ?string $localCruzamento         = null,
    ) {}

    /**
     * Cria o DTO a partir do array retornado por FormRequest::validated().
     */
    public static function fromArray(array $data): self
    {
        return new self(
            dataFato:                 $data['data_fato']                  ?? null,
            dataInicioAtividade:      $data['data_inicio_atividade']      ?? null,
            dataTerminoAtividade:     $data['data_termino_atividade']     ?? null,
            natCodigo:                $data['nat_codigo']                 ?? null,
            natCobradeId:             isset($data['nat_cobrade_id'])
                                          ? (int) $data['nat_cobrade_id']
                                          : null,
            natOcorrencia:            $data['nat_ocorrencia']             ?? null,
            natNomeOperacao:          $data['nat_nome_operacao']          ?? null,
            uniResponsavelMunicipio:  $data['uni_responsavel_municipio']  ?? null,
            uniResponsavelCodigo:     $data['uni_responsavel_codigo']     ?? null,
            uniResponsavelUnidade:    $data['uni_responsavel_unidade']    ?? null,
            uniBoCodUnidade:          $data['uni_bo_cod_unidade']         ?? null,
            uniBoAno:                 isset($data['uni_bo_ano'])
                                          ? (int) $data['uni_bo_ano']
                                          : null,
            uniBoSequencial:          $data['uni_bo_sequencial']          ?? null,
            comOcorrenciaData:        $data['com_ocorrencia_data']        ?? null,
            comOcorrenciaAtendimento: $data['com_ocorrencia_atendimento'] ?? null,
            localPais:                $data['local_pais']                 ?? null,
            localEstadoUf:            $data['local_estadouf']             ?? null,
            localMunicipio:           $data['local_municipio']            ?? null,
            localCep:                 $data['local_cep']                  ?? null,
            localLogradouro:          $data['local_logradoura_1']         ?? null,
            localBairro:              $data['local_bairro']               ?? null,
            localCruzamento:          $data['local_cruzamento']           ?? null,
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
            'local_cruzamento'            => $this->localCruzamento,
        ], fn ($v) => $v !== null);
    }
}
