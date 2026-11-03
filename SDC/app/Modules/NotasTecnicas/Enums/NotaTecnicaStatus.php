<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Enums;

/** Ciclo de vida de uma nota tecnica. */
enum NotaTecnicaStatus: string
{
    case Rascunho = 'rascunho';
    case Publicada = 'publicada';
    case Revogada = 'revogada';

    public function label(): string
    {
        return match ($this) {
            self::Rascunho  => 'Rascunho',
            self::Publicada => 'Publicada',
            self::Revogada  => 'Revogada',
        };
    }

    /** Variante do Atoms/Badge/Badge.vue usada para o status. */
    public function variante(): string
    {
        return match ($this) {
            self::Rascunho  => 'neutral',
            self::Publicada => 'success',
            self::Revogada  => 'danger',
        };
    }

    /** Rascunho so aparece para quem edita; publicada e revogada, para todo leitor. */
    public function visivelParaLeitor(): bool
    {
        return $this !== self::Rascunho;
    }

    /** @return list<string> */
    public static function visiveisParaLeitor(): array
    {
        return array_values(array_map(
            static fn (self $status): string => $status->value,
            array_filter(self::cases(), static fn (self $status): bool => $status->visivelParaLeitor()),
        ));
    }
}
