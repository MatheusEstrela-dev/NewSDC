<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Enums;

/**
 * Natureza de um item do cronograma (treinamento.curso_cronogramas.tipo).
 * Tem CHECK no banco: lista curta e estavel, usada para colorir o calendario.
 */
enum TipoEventoCronograma: string
{
    case AULA = 'AULA';
    case PROVA = 'PROVA';
    case ENTREGA = 'ENTREGA';
    case ENCONTRO = 'ENCONTRO';
    case OUTRO = 'OUTRO';

    public function getLabel(): string
    {
        return match ($this) {
            self::AULA => 'Aula',
            self::PROVA => 'Prova',
            self::ENTREGA => 'Entrega',
            self::ENCONTRO => 'Encontro',
            self::OUTRO => 'Outro',
        };
    }

    public function getBadgeColor(): string
    {
        return match ($this) {
            self::AULA => 'blue',
            self::PROVA => 'purple',
            self::ENTREGA => 'yellow',
            self::ENCONTRO => 'green',
            self::OUTRO => 'gray',
        };
    }

    /**
     * @return list<array{value: string, label: string, color: string}>
     */
    public static function toSelectArray(): array
    {
        return array_map(
            fn (self $tipo) => [
                'value' => $tipo->value,
                'label' => $tipo->getLabel(),
                'color' => $tipo->getBadgeColor(),
            ],
            self::cases(),
        );
    }
}
