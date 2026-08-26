<?php

declare(strict_types=1);

namespace App\Modules\Plantao\DTOs;

use App\Modules\Plantao\Models\Viatura;

class ViaturaListDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $prefixo,
        public readonly string $placa,
        public readonly string $modelo,
        public readonly ?string $marca,
        public readonly string $localizacao,
        public readonly string $localizacao_valor,
        public readonly bool $exclusiva_sobreaviso,
        public readonly string $status,
        public readonly string $status_valor,
        public readonly ?int $hodometro,
        public readonly ?string $combustivel_label,
        public readonly int $combustivel_percentual,
        public readonly ?string $ultimo_condutor_nome,
        public readonly bool $ativo,
        public readonly ?string $observacoes,
    ) {
    }

    public static function fromModel(Viatura $viatura): self
    {
        return new self(
            id: $viatura->id,
            prefixo: $viatura->prefixo,
            placa: $viatura->placa,
            modelo: $viatura->modelo,
            marca: $viatura->marca,
            localizacao: $viatura->localizacao?->label() ?? '',
            localizacao_valor: $viatura->localizacao?->value ?? '',
            exclusiva_sobreaviso: (bool) $viatura->exclusiva_sobreaviso,
            status: $viatura->status?->label() ?? '',
            status_valor: $viatura->status?->value ?? '',
            hodometro: $viatura->hodometro_atual,
            combustivel_label: $viatura->nivel_combustivel?->label(),
            combustivel_percentual: $viatura->nivel_combustivel?->percentual() ?? 0,
            ultimo_condutor_nome: $viatura->ultimo_condutor_nome,
            ativo: (bool) $viatura->ativo,
            observacoes: $viatura->observacoes,
        );
    }

    public static function collection(iterable $items): array
    {
        return array_map(
            fn(Viatura $item) => self::fromModel($item),
            is_array($items) ? $items : iterator_to_array($items)
        );
    }
}
