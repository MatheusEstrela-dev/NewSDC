<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Enums;

use Carbon\CarbonImmutable;

/**
 * Janela do grafico "Entregas realizadas" do dashboard do TDAP.
 *
 * Cada caso define o intervalo atual (terminando hoje) e a granularidade da
 * serie: 30 dias vai dia a dia, os de meses vao mes a mes -- 365 pontos diarios
 * nao cabem num grafico de linha legivel. O periodo anterior, usado na
 * comparacao, tem sempre o mesmo comprimento e termina onde o atual comeca.
 */
enum PeriodoEntregas: string
{
    case TrintaDias = '30d';
    case SeisMeses = '6m';
    case UmAno = '12m';

    /** Selecao inicial da tela (mesma do desenho). */
    public static function padrao(): self
    {
        return self::SeisMeses;
    }

    public function label(): string
    {
        return match ($this) {
            self::TrintaDias => '30 dias',
            self::SeisMeses  => '6 meses',
            self::UmAno      => '1 ano',
        };
    }

    /** Unidade do `date_trunc` do Postgres: valor fixo do enum, nunca entrada do usuario. */
    public function granularidade(): string
    {
        return $this === self::TrintaDias ? 'day' : 'month';
    }

    /** Primeiro instante da janela atual; o mes/dia corrente conta como o ultimo ponto. */
    public function inicio(CarbonImmutable $agora): CarbonImmutable
    {
        return match ($this) {
            self::TrintaDias => $agora->startOfDay()->subDays(29),
            self::SeisMeses  => $agora->startOfMonth()->subMonths(5),
            self::UmAno      => $agora->startOfMonth()->subMonths(11),
        };
    }

    /**
     * Desloca a data um periodo inteiro para tras.
     *
     * A janela anterior e [recuar(inicio), recuar(agora)]: o mesmo trecho ja
     * decorrido, e nao o periodo anterior fechado -- no dia 6, comparar 5 meses
     * e 6 dias contra 6 meses cheios acusaria queda que nao houve. NoOverflow
     * porque 31/10 menos 6 meses e 30/04, nao 01/05.
     */
    public function recuar(CarbonImmutable $data): CarbonImmutable
    {
        return match ($this) {
            self::TrintaDias => $data->subDays(30),
            self::SeisMeses  => $data->subMonthsNoOverflow(6),
            self::UmAno      => $data->subMonthsNoOverflow(12),
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $c) => ['value' => $c->value, 'label' => $c->label()],
            self::cases(),
        );
    }
}
