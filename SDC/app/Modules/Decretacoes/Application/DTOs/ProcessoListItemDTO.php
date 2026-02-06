<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Application\DTOs;

use App\Modules\Decretacoes\Domain\Entities\Processo;

/**
 * DTO: Item de lista de Processos
 * Versão leve para listagem (sem relações pesadas)
 */
final class ProcessoListItemDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $nProtocoloFide,
        public readonly string $status,
        public readonly string $origem,
        public readonly ?string $tipoDesastre,
        public readonly ?string $municipioNome,
        public readonly ?string $dataEntrada,
        public readonly ?string $dataVencimento,
        public readonly ?int $diasRestantes,
        public readonly ?string $vigenciaStatus,
    ) {
    }

    public static function fromModel(Processo $processo): self
    {
        return new self(
            id: $processo->id,
            nProtocoloFide: $processo->n_protocolo_fide ?? '',
            status: $processo->status ?? '',
            origem: $processo->origem ?? '',
            tipoDesastre: $processo->tipoDesastre?->nome ?? null,
            municipioNome: $processo->municipio?->nome ?? null,
            dataEntrada: $processo->data_entrada?->format('d/m/Y'),
            dataVencimento: $processo->data_vencimento_decreto?->format('d/m/Y'),
            diasRestantes: $processo->dias_restantes ?? null,
            vigenciaStatus: $processo->vigencia_status ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'n_protocolo_fide' => $this->nProtocoloFide,
            'status' => $this->status,
            'origem' => $this->origem,
            'tipo_desastre' => $this->tipoDesastre,
            'municipio_nome' => $this->municipioNome,
            'data_entrada' => $this->dataEntrada,
            'data_vencimento' => $this->dataVencimento,
            'dias_restantes' => $this->diasRestantes,
            'vigencia_status' => $this->vigenciaStatus,
        ];
    }
}
