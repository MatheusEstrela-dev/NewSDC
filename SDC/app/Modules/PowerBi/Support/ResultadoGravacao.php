<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Support;

/** Contagens de uma gravacao em lote. */
final class ResultadoGravacao
{
    public function __construct(
        public int $lidas = 0,
        public int $gravadas = 0,
        public int $removidas = 0,
    ) {
    }

    public function inalteradas(): int
    {
        return max(0, $this->lidas - $this->gravadas);
    }

    public function somar(self $outro): void
    {
        $this->lidas += $outro->lidas;
        $this->gravadas += $outro->gravadas;
        $this->removidas += $outro->removidas;
    }
}
