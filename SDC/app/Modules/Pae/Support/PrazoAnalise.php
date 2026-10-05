<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Enums\SituacaoPrazo;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Prazo de 300 dias da CEDEC para decidir sobre o PAE (Resolucao GMG 83/2024, Art. 9).
 *
 * REGRA (decisao da CEDEC de 2026-10-02):
 *   limite = notificacao da FEAM + 300 dias corridos + dias pausados
 *   pausa  = diligencia aberta (Art. 11), da emissao da notificacao ate a
 *            devolutiva: o tempo do empreendedor nao conta contra a CEDEC.
 *
 * A renovacao automatica deixa a notificacao anterior sem devolutiva; por isso
 * uma notificacao sem devolutiva termina na emissao da seguinte, e os dias
 * pausados sao a UNIAO dos intervalos, sem contar sobreposicao duas vezes.
 *
 * `$hoje` e injetavel para teste puro.
 */
final class PrazoAnalise
{
    public const PRAZO_DIAS = 300;

    public const JANELA_PROXIMO_DIAS = 10;

    /** Analise encerrada: o prazo deixa de ser cobrado. */
    public const STATUS_ENCERRADOS = [
        PaeProtocoloStatus::APROVADO,
        PaeProtocoloStatus::CCPAE,
        PaeProtocoloStatus::ATIVO_3_ANOS,
        PaeProtocoloStatus::REPROVADO,
        PaeProtocoloStatus::REVOGADO,
    ];

    /**
     * @param  list<array{dt_notificacao: mixed, dt_devolutiva: mixed}>  $notificacoes
     * @return list<array{inicio: CarbonImmutable, fim: ?CarbonImmutable}>
     */
    public static function intervalosDeNotificacoes(array $notificacoes): array
    {
        $ordenadas = [];
        foreach ($notificacoes as $n) {
            $inicio = Datas::dia($n['dt_notificacao'] ?? null);
            if ($inicio !== null) {
                $ordenadas[] = ['inicio' => $inicio, 'devolutiva' => Datas::dia($n['dt_devolutiva'] ?? null)];
            }
        }
        usort($ordenadas, fn (array $a, array $b): int => $a['inicio']->getTimestamp() <=> $b['inicio']->getTimestamp());

        $intervalos = [];
        foreach ($ordenadas as $i => $n) {
            $intervalos[] = [
                'inicio' => $n['inicio'],
                'fim' => $n['devolutiva'] ?? ($ordenadas[$i + 1]['inicio'] ?? null),
            ];
        }

        return $intervalos;
    }

    /**
     * @param  list<array{inicio: CarbonImmutable, fim: ?CarbonImmutable}>  $intervalos
     */
    public static function diasPausados(array $intervalos, ?CarbonInterface $hoje = null): int
    {
        $hoje = Datas::hoje($hoje);
        $faixas = [];
        foreach ($intervalos as $intervalo) {
            $fim = $intervalo['fim'] ?? $hoje;
            if ($fim->greaterThan($intervalo['inicio'])) {
                $faixas[] = [$intervalo['inicio'], $fim];
            }
        }
        usort($faixas, fn (array $a, array $b): int => $a[0]->getTimestamp() <=> $b[0]->getTimestamp());

        $total = 0;
        $atual = null;
        foreach ($faixas as $faixa) {
            if ($atual === null) {
                $atual = $faixa;
                continue;
            }
            if ($faixa[0]->lessThanOrEqualTo($atual[1])) {
                $atual[1] = $faixa[1]->greaterThan($atual[1]) ? $faixa[1] : $atual[1];
                continue;
            }
            $total += self::dias($atual[0], $atual[1]);
            $atual = $faixa;
        }

        return $atual === null ? $total : $total + self::dias($atual[0], $atual[1]);
    }

    /**
     * @param  list<array{inicio: CarbonImmutable, fim: ?CarbonImmutable}>  $intervalos
     */
    public static function estaPausado(array $intervalos): bool
    {
        foreach ($intervalos as $intervalo) {
            if ($intervalo['fim'] === null) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array{inicio: CarbonImmutable, fim: ?CarbonImmutable}>  $intervalos
     */
    public static function limite(mixed $dtNotificacaoFeam, array $intervalos, ?CarbonInterface $hoje = null): ?CarbonImmutable
    {
        $feam = Datas::dia($dtNotificacaoFeam);

        return $feam?->addDays(self::PRAZO_DIAS + self::diasPausados($intervalos, $hoje));
    }

    public static function situacao(
        PaeProtocoloStatus $status,
        mixed $limite,
        bool $pausado,
        ?CarbonInterface $hoje = null,
    ): SituacaoPrazo {
        if (in_array($status, self::STATUS_ENCERRADOS, true)) {
            return SituacaoPrazo::OK;
        }

        $limite = Datas::dia($limite);
        if ($limite === null) {
            return SituacaoPrazo::SEM_DATA;
        }

        $hoje = Datas::hoje($hoje);
        if ($limite->lessThan($hoje)) {
            return SituacaoPrazo::VENCIDO;
        }
        if ($pausado) {
            return SituacaoPrazo::PAUSADO;
        }

        return self::dias($hoje, $limite) <= self::JANELA_PROXIMO_DIAS ? SituacaoPrazo::PROXIMO : SituacaoPrazo::OK;
    }

    /** Carbon 3: diffInDays tem sinal e devolve float. */
    private static function dias(CarbonImmutable $de, CarbonImmutable $ate): int
    {
        return (int) round($de->diffInDays($ate));
    }
}
