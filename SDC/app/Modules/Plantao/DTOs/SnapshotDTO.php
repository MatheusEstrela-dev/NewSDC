<?php

declare(strict_types=1);

namespace App\Modules\Plantao\DTOs;

use App\Modules\Plantao\Models\ViaturaSnapshot;

class SnapshotDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $viatura_id,
        public readonly string $prefixo,
        public readonly string $placa,
        public readonly int $hodometro,
        public readonly string $combustivel_label,
        public readonly int $combustivel_percentual,
        public readonly ?string $alteracoes,
        public readonly ?string $ultimo_condutor_nome,
        public readonly ?string $anotacao,
        public readonly bool $em_condicoes,
    ) {
    }

    public static function fromModel(ViaturaSnapshot $snapshot): self
    {
        return new self(
            id: $snapshot->id,
            viatura_id: $snapshot->viatura_id,
            prefixo: $snapshot->prefixo,
            placa: $snapshot->placa,
            hodometro: $snapshot->hodometro,
            combustivel_label: $snapshot->nivel_combustivel?->label() ?? '',
            combustivel_percentual: $snapshot->nivel_combustivel?->percentual() ?? 0,
            alteracoes: $snapshot->alteracoes,
            ultimo_condutor_nome: $snapshot->ultimo_condutor_nome,
            anotacao: $snapshot->anotacao,
            em_condicoes: (bool) $snapshot->em_condicoes,
        );
    }

    public static function collection(iterable $items): array
    {
        return array_map(
            fn (ViaturaSnapshot $item) => self::fromModel($item),
            is_array($items) ? $items : iterator_to_array($items)
        );
    }
}
