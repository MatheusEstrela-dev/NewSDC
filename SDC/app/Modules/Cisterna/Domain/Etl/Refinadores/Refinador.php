<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl\Refinadores;

interface Refinador
{
    /**
     * Rotulo do recurso no cisterna_etl_log.
     */
    public function recurso(): string;

    public function tabelaLegado(): string;

    /**
     * @param  array<string, mixed>  $doc  Linha crua do legado.
     */
    public function refinar(array $doc, int $legacyId, bool $dryRun): void;
}
