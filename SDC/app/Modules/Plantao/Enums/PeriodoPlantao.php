<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Enums;

enum PeriodoPlantao: string
{
    case DIURNO = 'DIURNO';
    case NOTURNO = 'NOTURNO';
    case EXTRAORDINARIO = 'EXTRAORDINARIO';

    /**
     * Horario real praticado pelo plantao CEDEC: dois turnos de 10h.
     * A lacuna 02h-06h e coberta por sobreaviso, nao por plantao presencial.
     */
    public function label(): string
    {
        return match ($this) {
            self::DIURNO => '06:00hs as 16:00hs',
            self::NOTURNO => '16:00hs as 02:00hs',
            self::EXTRAORDINARIO => 'Extraordinario',
        };
    }

    /**
     * Forma abreviada usada no cabecalho do relatorio de passagem de servico.
     */
    public function labelCurto(): string
    {
        return match ($this) {
            self::DIURNO => '06h às 16h',
            self::NOTURNO => '16h às 02h',
            self::EXTRAORDINARIO => 'Extraordinario',
        };
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $periodo) => [
                'value' => $periodo->value,
                'label' => $periodo->label(),
            ],
            self::cases()
        );
    }
}
