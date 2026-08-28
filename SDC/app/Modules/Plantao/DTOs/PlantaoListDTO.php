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
        // Decidido no backend (PlantaoService::podeEditar): dono + turno
        // ATIVO, ou excecao de supervisao. O frontend so le a flag, nao
        // recalcula quem e dono de que.
        public readonly bool $pode_editar,
    ) {
    }

    public static function fromModel($plantao, bool $podeEditar = false): self
    {
        $nome = $plantao->plantonista_nome ?? 'N/A';
        $parts = explode(' ', $nome);
        $avatar = strtoupper(
            substr($parts[0] ?? '', 0, 1) . substr(end($parts) ?: '', 0, 1)
        );

        $periodoLabel = $plantao->tipoTurno?->label() ?? $plantao->periodo ?? '';
        $statusLabel = $plantao->status?->label() ?? $plantao->status ?? '';

        return new self(
            id: $plantao->id,
            data: $plantao->data?->format('d/m/Y') ?? '',
            plantonista_nome: $nome,
            avatar: $avatar,
            periodo: $periodoLabel,
            status: $statusLabel,
            observacoes: $plantao->observacoes,
            pode_editar: $podeEditar,
        );
    }

    /**
     * @param  (callable(mixed):bool)|null  $podeEditarResolver  decide o
     *         `pode_editar` por item; sem resolver, ninguem edita (default
     *         seguro para chamadores que ainda nao passam usuario).
     */
    public static function collection(iterable $items, ?callable $podeEditarResolver = null): array
    {
        return array_map(
            fn ($item) => self::fromModel($item, $podeEditarResolver ? $podeEditarResolver($item) : false),
            is_array($items) ? $items : iterator_to_array($items)
        );
    }
}
