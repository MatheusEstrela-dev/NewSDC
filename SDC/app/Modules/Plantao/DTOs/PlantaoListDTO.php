<?php

declare(strict_types=1);

namespace App\Modules\Plantao\DTOs;

class PlantaoListDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $data,
        public readonly string $plantonista_nome,
        public readonly string $avatar,
        public readonly string $periodo,
        public readonly string $status,
        public readonly ?string $observacoes,
    ) {
    }

    public static function fromModel($plantao): self
    {
        $nome = $plantao->plantonista_nome ?? 'N/A';
        $parts = explode(' ', $nome);
        $avatar = strtoupper(
            substr($parts[0] ?? '', 0, 1) . substr(end($parts) ?: '', 0, 1)
        );

        $periodoLabel = $plantao->periodo?->label() ?? $plantao->periodo ?? '';
        $statusLabel = $plantao->status?->label() ?? $plantao->status ?? '';

        return new self(
            id: $plantao->id,
            data: $plantao->data?->format('d/m/Y') ?? '',
            plantonista_nome: $nome,
            avatar: $avatar,
            periodo: $periodoLabel,
            status: $statusLabel,
            observacoes: $plantao->observacoes,
        );
    }

    public static function collection(iterable $items): array
    {
        return array_map(
            fn($item) => self::fromModel($item),
            is_array($items) ? $items : iterator_to_array($items)
        );
    }
}
