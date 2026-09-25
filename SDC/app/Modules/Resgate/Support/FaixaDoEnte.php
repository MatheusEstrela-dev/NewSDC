<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Support;

use App\Modules\Ranking\Enums\FaixaRanking;
use App\Modules\Ranking\Services\LeaderboardQuery;
use App\Modules\Ranking\Services\RankingReadService;
use App\Modules\Ranking\Services\TemporadaDoRanking;
use App\Modules\Resgate\Enums\EscopoCarteira;
use DateTimeImmutable;

/**
 * Faixa que LIBERA premios: a do ente na ultima temporada FECHADA (decisao D2).
 * Fechada, ela nao oscila mais - o direito nao muda no meio do processo.
 * Unico ponto de calculo para vitrine e pedido.
 */
final class FaixaDoEnte
{
    public function __construct(
        private readonly TemporadaDoRanking $temporada,
        private readonly RankingReadService $leitura,
    ) {}

    /** @return array{chave: string, pontos: int, posicao: ?int, faixa: string}|null */
    public function naTemporadaFechada(EscopoCarteira $escopo, int $enteId, DateTimeImmutable $agora): ?array
    {
        return $this->temporada->resultadoNaTemporadaAnterior(
            $escopo->escopoDoPlacar(), $enteId, $this->leitura, app(LeaderboardQuery::class), $agora,
        );
    }

    /** A faixa alcancada cobre a exigida? Comparacao pelo corte de pontos. */
    public static function alcanca(string $alcancada, string $exigida): bool
    {
        return FaixaRanking::from($alcancada)->pontosMinimos() >= FaixaRanking::from($exigida)->pontosMinimos();
    }
}
