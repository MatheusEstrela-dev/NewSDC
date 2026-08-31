<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Estoque;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Um lancamento no ledger de estoque.
 *
 * quantidade e string de proposito: float perde precisao em decimal(14,3), e o
 * valor viaja intacto ate o Postgres, que faz a aritmetica em numeric. O sinal
 * carrega o sentido do movimento, entao nao existe um campo "entrada ou saida"
 * separado que possa divergir do numero.
 */
final readonly class MovimentoEstoque
{
    public function __construct(
        public int $materialAhId,
        public int $depositoId,
        public string $quantidade,
        public string $tipo,
        public ?string $origemTipo = null,
        public ?int $origemId = null,
        public ?int $registradoPor = null,
        public ?CarbonImmutable $ocorridoEm = null,
    ) {
        if (! is_numeric($this->quantidade) || (float) $this->quantidade === 0.0) {
            throw new InvalidArgumentException('Quantidade do movimento deve ser numerica e diferente de zero.');
        }

        // Espelha o CHECK ajuda_h_mov_origem_ck: a origem e um par, nao dois
        // campos independentes.
        if (($this->origemTipo === null) !== ($this->origemId === null)) {
            throw new InvalidArgumentException('origemTipo e origemId devem ser informados juntos.');
        }
    }
}
