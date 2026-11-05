<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Exceptions;

use RuntimeException;

/** A fonte esta em modo primario e POWERBI_FONTE_EXIGE_STANDBY=true. */
final class FonteNaoEhStandbyException extends RuntimeException
{
    public static function nova(): self
    {
        return new self('A fonte de BI nao esta em recovery (nao e standby) e POWERBI_FONTE_EXIGE_STANDBY=true: materializacao recusada.');
    }
}
