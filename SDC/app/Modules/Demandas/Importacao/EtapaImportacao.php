<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Importacao;

interface EtapaImportacao
{
    /** Nome curto usado em --etapa (usuarios, catalogo, chamados, historico, comentarios, anexos). */
    public function nome(): string;

    public function executar(bool $dryRun, ?string $desde, int $lote): RelatorioEtapa;
}
