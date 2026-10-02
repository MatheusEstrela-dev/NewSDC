<?php

declare(strict_types=1);

namespace App\Modules\Tdap\DTOs;

/**
 * Ponto de captacao listado no PMDA vigente de um municipio.
 *
 * E o que o TDAP precisa saber do PMDA para aceitar o ponto num cronograma:
 * de que plano ele veio. Os dados do ponto em si (nome, tipo, capacidade) o
 * TDAP le da propria tabela compartilhada, pelo model PontoCaptacao.
 */
final readonly class VinculoPmdaDTO
{
    public function __construct(
        public int $pontoId,
        public int $municipioId,
        public int $pmdaPlanoId,
        public ?string $protocolo,
    ) {}
}
