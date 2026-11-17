<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Enums;

/**
 * Sexo declarado na inscricao, usado so nos indicadores das Capacitacoes.
 * Ausencia (NULL na coluna) = nao informado; nao ha caso para isso.
 */
enum Sexo: string
{
    case FEMININO = 'F';
    case MASCULINO = 'M';

    public function getLabel(): string
    {
        return match ($this) {
            self::FEMININO => 'Feminino',
            self::MASCULINO => 'Masculino',
        };
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn (self $sexo) => [
                'value' => $sexo->value,
                'label' => $sexo->getLabel(),
            ],
            self::cases()
        );
    }
}
