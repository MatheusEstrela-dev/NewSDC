<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Enums;

/** Estado de uma rodada de sincronizacao com o AD. */
enum EstadoSincronizacaoAd: string
{
    case SOLICITADA = 'solicitada';
    case EXECUTANDO = 'executando';
    case CONCLUIDA = 'concluida';
    case ABORTADA = 'abortada';
    case FALHOU = 'falhou';

    public function label(): string
    {
        return match ($this) {
            self::SOLICITADA => 'Solicitada',
            self::EXECUTANDO => 'Executando',
            self::CONCLUIDA => 'Concluída',
            self::ABORTADA => 'Abortada',
            self::FALHOU => 'Falhou',
        };
    }

    public function emAndamento(): bool
    {
        return $this === self::SOLICITADA || $this === self::EXECUTANDO;
    }
}
