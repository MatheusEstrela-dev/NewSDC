<?php

declare(strict_types=1);

namespace App\Modules\Plantao\DTOs;

use App\Modules\Plantao\Models\ViaturaMovimentacao;

class MovimentacaoDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $viatura_id,
        public readonly string $prefixo,
        public readonly string $placa,
        public readonly ?string $condutor_nome,
        public readonly ?string $saida_em,
        public readonly ?int $saida_hodometro,
        public readonly ?string $saida_combustivel_label,
        public readonly ?string $retorno_em,
        public readonly ?int $retorno_hodometro,
        public readonly ?string $retorno_combustivel_label,
        public readonly ?string $destino,
        public readonly ?string $motivo,
        public readonly ?string $alteracoes,
        public readonly string $status_valor,
        public readonly string $status_label,
    ) {
    }

    public static function fromModel(ViaturaMovimentacao $movimentacao): self
    {
        return new self(
            id: $movimentacao->id,
            viatura_id: $movimentacao->viatura_id,
            prefixo: $movimentacao->viatura?->prefixo ?? '',
            placa: $movimentacao->viatura?->placa ?? '',
            condutor_nome: $movimentacao->condutor?->name ?? $movimentacao->condutor_nome,
            saida_em: $movimentacao->saida_em?->format('d/m/Y H:i'),
            saida_hodometro: $movimentacao->saida_hodometro,
            saida_combustivel_label: $movimentacao->saida_combustivel?->label(),
            retorno_em: $movimentacao->retorno_em?->format('d/m/Y H:i'),
            retorno_hodometro: $movimentacao->retorno_hodometro,
            retorno_combustivel_label: $movimentacao->retorno_combustivel?->label(),
            destino: $movimentacao->destino,
            motivo: $movimentacao->motivo,
            alteracoes: $movimentacao->alteracoes,
            status_valor: $movimentacao->status?->value ?? '',
            status_label: $movimentacao->status?->label() ?? '',
        );
    }

    public static function collection(iterable $items): array
    {
        return array_map(
            fn (ViaturaMovimentacao $item) => self::fromModel($item),
            is_array($items) ? $items : iterator_to_array($items)
        );
    }
}
