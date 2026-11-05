<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Jobs;

use App\Modules\PowerBi\Exceptions\ModuloEmExecucaoException;
use App\Modules\PowerBi\Support\CatalogoBi;
use App\Modules\PowerBi\Support\ExecutorMaterializacao;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Materializa um modulo na fila redis-powerbi. O lock por modulo vive no
 * ExecutorMaterializacao (compartilhado com o comando): duas execucoes do
 * mesmo modulo nunca rodam juntas; a segunda sai em silencio.
 */
final class MaterializarModuloJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 900;

    public int $tries = 1;

    public function __construct(public readonly string $modulo)
    {
        $this->onConnection('redis-powerbi');
    }

    public function handle(CatalogoBi $catalogo, ExecutorMaterializacao $executor): void
    {
        $materializador = $catalogo->materializador($this->modulo);
        if ($materializador === null) {
            return;
        }

        try {
            $executor->materializar($materializador);
        } catch (ModuloEmExecucaoException) {
            // Outra execucao do mesmo modulo em curso: a proxima janela cobre.
        }
    }
}
