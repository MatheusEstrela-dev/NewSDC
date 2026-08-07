<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Contracts;

/**
 * Veredito de uma verificacao de regra.
 *
 * Quando bloqueia, carrega o motivo em texto pronto para exibicao ao usuario.
 */
final readonly class ResultadoGuarda
{
    private function __construct(
        public bool $permitido,
        public ?string $motivo,
    ) {}

    public static function permitir(): self
    {
        return new self(permitido: true, motivo: null);
    }

    public static function bloquear(string $motivo): self
    {
        return new self(permitido: false, motivo: $motivo);
    }
}
