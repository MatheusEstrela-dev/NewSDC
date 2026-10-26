<?php

declare(strict_types=1);

namespace App\Modules\Tdap\DTOs;

/**
 * Plano de PMDA que a consulta do cronograma mostra.
 *
 * So leitura: o TDAP nao decide status. Quem monta isto e a implementacao
 * do contrato no modulo PMDA, a mesma que diz qual plano esta vigente.
 */
final readonly class ResumoPmdaDTO
{
    /**
     * @param  list<array{id: int, nome: string, tipo_nome: string, capacidade: float, pmda_plano_id: int, protocolo: ?string}>  $pontos
     */
    public function __construct(
        public int $id,
        public int $municipioId,
        public ?string $protocolo,
        public string $status,
        public string $statusLabel,
        public string $statusCor,
        public ?string $dataAprov,
        public bool $vigente,
        public ?int $vigenteId,
        public ?string $vigenteProtocolo,
        public bool $excluido,
        public array $pontos,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'                 => $this->id,
            'municipio_id'       => $this->municipioId,
            'protocolo'          => $this->protocolo,
            'status'             => $this->status,
            'status_label'       => $this->statusLabel,
            'status_cor'         => $this->statusCor,
            'data_aprov'         => $this->dataAprov,
            'vigente'            => $this->vigente,
            'vigente_id'         => $this->vigenteId,
            'vigente_protocolo'  => $this->vigenteProtocolo,
            'excluido'           => $this->excluido,
            'pontos'             => $this->pontos,
        ];
    }
}
