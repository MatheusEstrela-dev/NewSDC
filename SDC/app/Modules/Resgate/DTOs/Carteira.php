<?php

declare(strict_types=1);

namespace App\Modules\Resgate\DTOs;

use App\Modules\Resgate\Enums\EscopoCarteira;
use DateTimeImmutable;

/**
 * Composicao da carteira de resgate de um ente num instante.
 *
 * `saldoResgatavel` = maduro - reservado - debitado. Pode ser NEGATIVO: um
 * credito ja consumido que depois e estornado deixa a carteira devendo, e novos
 * resgates ficam bloqueados ate compensar (plano, secao 2.2).
 *
 * As parcelas fora do saldo (em carencia, em ajuste, demonstracao) aparecem
 * para o ente entender por que um ponto do placar ainda nao e resgatavel.
 */
final readonly class Carteira
{
    public function __construct(
        public EscopoCarteira $escopo,
        public int $enteId,
        public DateTimeImmutable $calculadaEm,
        public int $carenciaDias,
        public int $maduro,
        public int $emCarencia,
        public ?DateTimeImmutable $proximaLiberacao,
        public int $emAjuste,
        public int $demonstracao,
        public int $reservado,
        public int $debitado,
        // Reservado + debitado por pedidos de DEMONSTRACAO.
        public int $comprometidoDemonstracao = 0,
    ) {}

    public function saldoResgatavel(): int
    {
        return $this->maduro - $this->reservado - $this->debitado;
    }

    /**
     * Saldo de demonstracao (so homologacao): pontos demo sem carencia, menos
     * o que pedidos demo ja comprometeram. Nunca soma ao saldo real.
     */
    public function saldoDemonstracao(): int
    {
        return $this->demonstracao - $this->comprometidoDemonstracao;
    }

    /** Saldo que um pedido pode consumir, conforme ele seja real ou demonstracao. */
    public function saldoPara(bool $demonstracao): int
    {
        return $demonstracao ? $this->saldoDemonstracao() : $this->saldoResgatavel();
    }

    /** @return array<string, mixed> */
    public function paraArray(): array
    {
        return [
            'escopo' => $this->escopo->value,
            'ente_id' => $this->enteId,
            'calculada_em' => $this->calculadaEm->format(DATE_ATOM),
            'carencia_dias' => $this->carenciaDias,
            'saldo_resgatavel' => $this->saldoResgatavel(),
            'maduro' => $this->maduro,
            'em_carencia' => $this->emCarencia,
            'proxima_liberacao' => $this->proximaLiberacao?->format(DATE_ATOM),
            'em_ajuste' => $this->emAjuste,
            'demonstracao' => $this->demonstracao,
            'reservado' => $this->reservado,
            'debitado' => $this->debitado,
            'saldo_demonstracao' => $this->saldoDemonstracao(),
        ];
    }
}
