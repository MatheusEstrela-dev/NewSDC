<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Contracts;

use App\Modules\Resgate\DTOs\Carteira;
use App\Modules\Resgate\Enums\EscopoCarteira;
use DateTimeImmutable;

/**
 * Leitura da carteira de resgate de um ente a partir do ledger do Ranking.
 *
 * Unico ponto em que o Resgate le `ranking.*`, e so para leitura: o Resgate
 * nunca escreve no ledger (plano, secao 8).
 */
interface SaldoResgatavel
{
    public function carteira(EscopoCarteira $escopo, int $enteId, DateTimeImmutable $agora): Carteira;
}
