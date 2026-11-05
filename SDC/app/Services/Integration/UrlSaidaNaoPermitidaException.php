<?php

declare(strict_types=1);

namespace App\Services\Integration;

use InvalidArgumentException;

/** URL de saida recusada pela politica anti-SSRF. */
final class UrlSaidaNaoPermitidaException extends InvalidArgumentException
{
}
