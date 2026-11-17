<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\DTOs;

use Carbon\CarbonInterface;

/**
 * Situacao de um CPF diante da regra das inscricoes por curso.
 */
final class SituacaoDeReinscricao
{
    /**
     * @param  list<array{inscricao_id: int, treinamento_id: int, titulo: string, status: string, data: string}>  $inscricoes
     */
    public function __construct(
        public readonly int $contagem,
        public readonly int $limite,
        public readonly bool $bloqueado,
        public readonly ?CarbonInterface $bloqueadoAte,
        public readonly array $inscricoes,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'contagem' => $this->contagem,
            'limite' => $this->limite,
            'bloqueado' => $this->bloqueado,
            'bloqueado_ate' => $this->bloqueadoAte?->toDateString(),
            'inscricoes' => $this->inscricoes,
        ];
    }
}
