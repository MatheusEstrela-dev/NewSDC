<?php

namespace App\Logging;

class LogCustomMessage
{
    /**
     * Invoke the handler
     */
    public function __invoke(array $record): array
    {
        return $record;
    }
}
