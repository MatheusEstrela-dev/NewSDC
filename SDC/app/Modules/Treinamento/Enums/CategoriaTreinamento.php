<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Enums;

enum CategoriaTreinamento: string
{
    case EVENTO = 'EVENTO';
    case CURSO = 'CURSO';

    public function getLabel(): string
    {
        return match ($this) {
            self::EVENTO => 'Evento',
            self::CURSO => 'Curso',
        };
    }

    public function getLabelPlural(): string
    {
        return match ($this) {
            self::EVENTO => 'Eventos',
            self::CURSO => 'Cursos',
        };
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $categoria) => [
                'value' => $categoria->value,
                'label' => $categoria->getLabel(),
            ],
            self::cases()
        );
    }
}
