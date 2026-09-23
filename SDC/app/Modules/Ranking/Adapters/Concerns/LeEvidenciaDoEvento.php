<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Adapters\Concerns;

use DateTimeImmutable;
use DateTimeInterface;

trait LeEvidenciaDoEvento
{
    private function id(mixed $valor): ?int
    {
        if (! is_int($valor) && ! (is_string($valor) && preg_match('/^[1-9][0-9]*$/D', $valor))) {
            return null;
        }

        $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : $id;
    }

    private function data(mixed $valor): ?DateTimeImmutable
    {
        if ($valor instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($valor);
        }

        // Apenas instantes absolutos: "now" ou datas relativas nao sao prova.
        if (! is_string($valor) || ! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/D', $valor)) {
            return null;
        }

        try {
            $data = new DateTimeImmutable($valor);
            $erros = DateTimeImmutable::getLastErrors();

            return $erros !== false && ($erros['warning_count'] > 0 || $erros['error_count'] > 0) ? null : $data;
        } catch (\Throwable) {
            return null;
        }
    }
}
