<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Application\DTOs;

/**
 * DTO: Dados de Entrada para criação de Processo
 * Seguindo papiro.md - DADOS DE ENTRADA
 */
final class ProcessoInputDTO
{
    public function __construct(
        // Identificação
        public readonly int $tipoDesastreId,
        public readonly int $cobradeId,
        public readonly string $origem,
        public readonly int $municipioId,
        public readonly int $redecId,
        public readonly string $situacaoAnormalidade,

        // Datas
        public readonly string $dataEntrada,
        public readonly string $dataOcorrencia,
        public readonly string $dataVencimentoDecreto,

        // Status
        public readonly string $status,
        public readonly ?int $analistaId,

        // Decreto Municipal
        public readonly ?string $nDecretoMunicipal,
        public readonly ?string $dataDecretoMunicipal,
        public readonly ?string $dataPublicacaoDecretoMunicipal,
        public readonly ?int $prazoVigenciaDecreto,

        // Reconhecimento Estadual
        public readonly ?string $nDecretoEstadual,
        public readonly ?string $dataDecretoEstadual,
        public readonly ?string $nEdicaoDomg,
        public readonly ?string $dataPublicacaoDomg,

        // Reconhecimento Federal
        public readonly ?string $nPortariaFederal,
        public readonly ?string $dataPortariaFederal,
        public readonly ?string $nEdicaoDou,
        public readonly ?string $dataPublicacaoDou,

        // Info Adicional
        public readonly string $nProcessoSei,
        public readonly ?string $observacoes,
    ) {
    }

    /**
     * Cria DTO a partir de array validado
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tipoDesastreId: (int) $data['tipo_desastre_id'],
            cobradeId: (int) $data['cobrade_id'],
            origem: $data['origem'],
            municipioId: (int) $data['municipio_id'],
            redecId: (int) $data['redec_id'],
            situacaoAnormalidade: $data['situacao_anormalidade'],
            dataEntrada: $data['data_entrada'],
            dataOcorrencia: $data['data_ocorrencia'],
            dataVencimentoDecreto: $data['data_vencimento_decreto'],
            status: $data['status'],
            analistaId: isset($data['analista_id']) ? (int) $data['analista_id'] : null,
            nDecretoMunicipal: $data['n_decreto_municipal'] ?? null,
            dataDecretoMunicipal: $data['data_decreto_municipal'] ?? null,
            dataPublicacaoDecretoMunicipal: $data['data_publicacao_decreto_municipal'] ?? null,
            prazoVigenciaDecreto: isset($data['prazo_vigencia_decreto']) ? (int) $data['prazo_vigencia_decreto'] : null,
            nDecretoEstadual: $data['n_decreto_estadual'] ?? null,
            dataDecretoEstadual: $data['data_decreto_estadual'] ?? null,
            nEdicaoDomg: $data['n_edicao_domg'] ?? null,
            dataPublicacaoDomg: $data['data_publicacao_domg'] ?? null,
            nPortariaFederal: $data['n_portaria_federal'] ?? null,
            dataPortariaFederal: $data['data_portaria_federal'] ?? null,
            nEdicaoDou: $data['n_edicao_dou'] ?? null,
            dataPublicacaoDou: $data['data_publicacao_dou'] ?? null,
            nProcessoSei: $data['n_processo_sei'],
            observacoes: $data['observacoes'] ?? null,
        );
    }

    /**
     * Converte DTO para array para persistência
     */
    public function toArray(): array
    {
        return [
            'tipo_desastre_id' => $this->tipoDesastreId,
            'cobrade_id' => $this->cobradeId,
            'origem' => $this->origem,
            'municipio_id' => $this->municipioId,
            'redec_id' => $this->redecId,
            'situacao_anormalidade' => $this->situacaoAnormalidade,
            'data_entrada' => $this->dataEntrada,
            'data_ocorrencia' => $this->dataOcorrencia,
            'data_vencimento_decreto' => $this->dataVencimentoDecreto,
            'status' => $this->status,
            'analista_id' => $this->analistaId,
            'n_decreto_municipal' => $this->nDecretoMunicipal,
            'data_decreto_municipal' => $this->dataDecretoMunicipal,
            'data_publicacao_decreto_municipal' => $this->dataPublicacaoDecretoMunicipal,
            'prazo_vigencia_decreto' => $this->prazoVigenciaDecreto,
            'n_decreto_estadual' => $this->nDecretoEstadual,
            'data_decreto_estadual' => $this->dataDecretoEstadual,
            'n_edicao_domg' => $this->nEdicaoDomg,
            'data_publicacao_domg' => $this->dataPublicacaoDomg,
            'n_portaria_federal' => $this->nPortariaFederal,
            'data_portaria_federal' => $this->dataPortariaFederal,
            'n_edicao_dou' => $this->nEdicaoDou,
            'data_publicacao_dou' => $this->dataPublicacaoDou,
            'n_processo_sei' => $this->nProcessoSei,
            'observacoes' => $this->observacoes,
        ];
    }
}
