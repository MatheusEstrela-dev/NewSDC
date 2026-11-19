<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Enums;

/**
 * Nota da avaliacao de conclusao do curso: barra de satisfacao de 5 niveis,
 * do vermelho (Pessimo) ao verde (Excelente). O CHECK da coluna
 * curso_avaliacoes.nota espelha a faixa 1..5.
 */
enum NotaAvaliacaoCurso: int
{
    case PESSIMO = 1;
    case RUIM = 2;
    case REGULAR = 3;
    case BOM = 4;
    case EXCELENTE = 5;

    public function getLabel(): string
    {
        return match ($this) {
            self::PESSIMO => 'Péssimo',
            self::RUIM => 'Ruim',
            self::REGULAR => 'Regular',
            self::BOM => 'Bom',
            self::EXCELENTE => 'Excelente',
        };
    }

    /** Cor do segmento na barra (o front traduz para as classes do Tailwind). */
    public function getColor(): string
    {
        return match ($this) {
            self::PESSIMO => 'red',
            self::RUIM => 'orange',
            self::REGULAR => 'yellow',
            self::BOM => 'lime',
            self::EXCELENTE => 'green',
        };
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn (self $nota) => [
                'value' => $nota->value,
                'label' => $nota->getLabel(),
                'color' => $nota->getColor(),
            ],
            self::cases()
        );
    }
}
