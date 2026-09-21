<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Enums;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Janela de apuracao do placar.
 *
 * Instantes sao persistidos em UTC, mas os limites de calendario sao
 * delimitados em America/Sao_Paulo. Intervalos sao sempre [inicio, fim).
 */
enum TipoPeriodo: string
{
    case Mes = 'mes';
    case Ano = 'ano';
    case Acumulado = 'acumulado';

    public function label(): string
    {
        return match ($this) {
            self::Mes       => 'Mes atual',
            self::Ano       => 'Ano atual',
            self::Acumulado => 'Acumulado',
        };
    }

    public const FUSO_CALENDARIO = 'America/Sao_Paulo';

    /**
     * Chave estavel do periodo, usada em cache e em snapshot.
     * Ex.: 'mes:2026-09', 'ano:2026', 'acumulado'.
     */
    public function chave(DateTimeImmutable $referencia): string
    {
        $local = $referencia->setTimezone(new DateTimeZone(self::FUSO_CALENDARIO));

        return match ($this) {
            self::Mes       => 'mes:' . $local->format('Y-m'),
            self::Ano       => 'ano:' . $local->format('Y'),
            self::Acumulado => 'acumulado',
        };
    }

    /**
     * Limites do periodo em UTC, no formato [inicio inclusivo, fim exclusivo].
     * Acumulado nao tem inicio: retorna null na primeira posicao.
     *
     * @return array{0: ?DateTimeImmutable, 1: ?DateTimeImmutable}
     */
    public function limites(DateTimeImmutable $referencia): array
    {
        $fuso = new DateTimeZone(self::FUSO_CALENDARIO);
        $utc = new DateTimeZone('UTC');
        $local = $referencia->setTimezone($fuso);

        return match ($this) {
            self::Mes => [
                $local->modify('first day of this month')->setTime(0, 0)->setTimezone($utc),
                $local->modify('first day of next month')->setTime(0, 0)->setTimezone($utc),
            ],
            self::Ano => [
                $local->setDate((int) $local->format('Y'), 1, 1)->setTime(0, 0)->setTimezone($utc),
                $local->setDate((int) $local->format('Y') + 1, 1, 1)->setTime(0, 0)->setTimezone($utc),
            ],
            self::Acumulado => [null, null],
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
