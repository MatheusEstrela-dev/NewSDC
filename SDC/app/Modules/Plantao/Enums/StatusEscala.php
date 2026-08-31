<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Enums;

enum StatusEscala: string
{
    case RASCUNHO = 'RASCUNHO';
    case PUBLICADA = 'PUBLICADA';
    case ARQUIVADA = 'ARQUIVADA';

    public function label(): string
    {
        return match ($this) {
            self::RASCUNHO => 'Rascunho',
            self::PUBLICADA => 'Publicada',
            self::ARQUIVADA => 'Arquivada',
        };
    }

    /**
     * Escala visivel para o plantonista comum e que ja notificou os escalados.
     * Em rascunho nada e avisado: o montador ainda esta mexendo.
     */
    public function publicada(): bool
    {
        return $this === self::PUBLICADA;
    }

    /**
     * Escala arquivada e historico: nao aceita mais alteracao de vaga.
     */
    public function editavel(): bool
    {
        return $this !== self::ARQUIVADA;
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases()
        );
    }
}
