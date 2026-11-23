<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services\Versoes;

use App\Modules\Treinamento\Models\Treinamento;

/** Rascunho da nova versao e se ele ja existia (retomado) ou acabou de ser criado. */
final class ResultadoNovaVersao
{
    public function __construct(
        public readonly Treinamento $rascunho,
        public readonly bool $retomado,
    ) {}
}
