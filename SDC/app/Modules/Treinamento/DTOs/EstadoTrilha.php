<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\DTOs;

final readonly class EstadoTrilha
{
    /**
     * @param  list<EstadoPasta>  $pastas
     */
    public function __construct(
        public float $percentual,
        public ?int $proximoItemId,
        public int $concluidos,
        public int $total,
        public array $pastas,
    ) {}

    /** Todos os itens exigidos cumpridos: mesma condicao do ConclusaoPorTrilha. */
    public function completa(): bool
    {
        return $this->total > 0 && $this->concluidos >= $this->total;
    }

    /**
     * Percentual do curso para o aluno, com a avaliacao (etapa final
     * obrigatoria) contando como mais uma etapa: trilha completa sem avaliar
     * nao chega a 100%. Mesma conta da sala (SalaDeAulaTemplate).
     */
    public function percentualDoCurso(bool $etapaFinalCumprida): float
    {
        return $this->total === 0
            ? 0.0
            : round(100 * ($this->concluidos + ($etapaFinalCumprida ? 1 : 0)) / ($this->total + 1), 1);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'percentual' => $this->percentual,
            'proximo_item_id' => $this->proximoItemId,
            'concluidos' => $this->concluidos,
            'total' => $this->total,
            'pastas' => array_map(fn (EstadoPasta $p) => $p->toArray(), $this->pastas),
        ];
    }
}
