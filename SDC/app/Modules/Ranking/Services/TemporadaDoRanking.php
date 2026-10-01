<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Services;

use App\Modules\Ranking\DTOs\FiltroPlacar;
use App\Modules\Ranking\Enums\EscopoPlacar;
use App\Modules\Ranking\Enums\TipoPeriodo;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Temporada corrente do ranking (trimestre civil) e o resultado do usuario na
 * temporada anterior, para a tela anunciar a virada: "nova rodada comecou, na
 * passada voce terminou em Xo".
 *
 * O resultado anterior sai da mesma projecao do placar (RankingReadService::
 * resumo sobre o periodo anterior), nao de snapshot: a temporada fechada nao
 * recebe mais lancamentos, entao o saldo dela ja e o resultado final.
 *
 * Sempre no escopo do usuario: a mensagem fala do desempenho DELE, qualquer
 * que seja a visao aberta na tela.
 *
 * CUIDADO COM OCTANE: stateless; o instante chega por argumento.
 */
final class TemporadaDoRanking
{
    /**
     * @return array{
     *     chave: string, inicio: string, fim: string, dias_restantes: int,
     *     anterior: array{chave: string, pontos: int, posicao: ?int, faixa: string}|null
     * }
     */
    public function descrever(int $usuarioId, RankingReadService $leitura, LeaderboardQuery $placar, DateTimeImmutable $agora): array
    {
        $tipo = TipoPeriodo::Trimestre;
        $fuso = new DateTimeZone(TipoPeriodo::FUSO_CALENDARIO);
        [$inicio, $fim] = $tipo->limites($agora);

        return [
            'chave' => $tipo->chave($agora),
            'inicio' => $inicio->setTimezone($fuso)->format('Y-m-d'),
            // Fim exclusivo no banco; a tela mostra o ultimo dia da temporada.
            'fim' => $fim->setTimezone($fuso)->modify('-1 day')->format('Y-m-d'),
            'dias_restantes' => max(0, (int) ceil(($fim->getTimestamp() - $agora->getTimestamp()) / 86400)),
            'anterior' => $this->resultadoNaTemporadaAnterior(EscopoPlacar::Usuario, $usuarioId, $leitura, $placar, $agora),
        ];
    }

    /**
     * Resultado final de uma entidade na ultima temporada FECHADA: a faixa que
     * ela nao perde mais. Usado no aviso de nova rodada e, no Resgate, como a
     * faixa que libera premios (decisao D2 do plano de resgate).
     *
     * Null quando a entidade nao pontuou na temporada anterior.
     *
     * @return array{chave: string, pontos: int, posicao: ?int, faixa: string}|null
     */
    public function resultadoNaTemporadaAnterior(EscopoPlacar $escopo, int $entidadeId, RankingReadService $leitura, LeaderboardQuery $placar, DateTimeImmutable $agora): ?array
    {
        [$inicio] = TipoPeriodo::Trimestre->limites($agora);
        // Um dia antes do inicio cai, por definicao, na temporada anterior.
        $chaveAnterior = TipoPeriodo::Trimestre->chave($inicio->modify('-1 day'));
        $resultado = $leitura->resumo(
            $entidadeId,
            new FiltroPlacar($escopo, $chaveAnterior, 'all', 1, FiltroPlacar::POR_PAGINA_PADRAO, $leitura->geracao()),
            $placar,
        );

        return $resultado['pontos'] > 0 ? [
            'chave' => $chaveAnterior,
            'pontos' => $resultado['pontos'],
            'posicao' => $resultado['posicao'],
            'faixa' => $resultado['faixa'],
        ] : null;
    }
}
