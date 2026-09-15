<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Contracts;

use App\Modules\Medalhao\DTOs\PayloadBruto;

/**
 * Coleta o payload bruto de uma fonte externa (camada Bronze).
 *
 * Separado de NormalizadorSilver de proposito: uma implementacao pode entregar
 * apenas o bruto — por exemplo um wrapper de script Python para formatos que o
 * PHP nao le (GRIB2, NetCDF) — sem nunca tocar as camadas Silver ou Gold.
 */
interface FonteIngestor
{
    /** Identificador unico da fonte, ex.: 'usp-fdsn'. */
    public function chave(): string;

    /** Grupo de agendamento, ex.: 'sismos'. */
    public function grupo(): string;

    /** Formato do conteudo bruto, ex.: 'fdsn-text'. */
    public function formato(): string;

    public function coletar(): PayloadBruto;
}
