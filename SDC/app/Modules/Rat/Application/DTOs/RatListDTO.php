<?php

namespace App\Modules\Rat\Application\DTOs;

class RatListDTO
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $protocolo,
        public readonly string $status,
        public readonly ?string $municipio,
        public readonly ?string $dataFato,
        public readonly ?string $criadoPor,
        public readonly int $recursosCount,
        public readonly int $envolvidosCount,
        public readonly int $anexosCount,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            protocolo: $data['protocolo'] ?? null,
            status: $data['status'] ?? 'rascunho',
            municipio: $data['local']['municipio'] ?? null,
            dataFato: $data['dadosGerais']['data_fato'] ?? null,
            criadoPor: $data['criado_por'] ?? 'Sistema',
            recursosCount: count($data['recursos'] ?? []),
            envolvidosCount: count($data['envolvidos'] ?? []),
            anexosCount: count($data['anexos'] ?? []),
            createdAt: $data['created_at'] ?? now()->toDateTimeString(),
            updatedAt: $data['updated_at'] ?? now()->toDateTimeString(),
        );
    }
}

