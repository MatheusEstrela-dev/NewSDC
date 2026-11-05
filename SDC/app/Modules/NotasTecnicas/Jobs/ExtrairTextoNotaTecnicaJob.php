<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Jobs;

use App\Modules\NotasTecnicas\Models\NotaTecnicaVersao;
use App\Modules\NotasTecnicas\Services\NotaTecnicaIndexacaoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Indexa o texto do PDF de uma versao para a busca no conteudo. Recebe o id, nao
 * o model: a versao pode ter sido apagada (nota excluida) ate o job rodar.
 *
 * Fila `low`: indexar pode esperar, o upload ja respondeu. Timeout abaixo dos
 * 120s do worker, que mata o processo inteiro se o job passar disso.
 *
 * Falha nao se propaga: com fila `sync` (testes, dev sem worker) uma excecao
 * aqui derrubaria o proprio upload da nota. O erro vai para o log e para
 * failed_jobs; `php artisan notas-tecnicas:reindexar` refaz as pendentes.
 */
class ExtrairTextoNotaTecnicaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 110;

    public function __construct(public readonly int $versaoId)
    {
        $this->onQueue('low');
    }

    public function handle(NotaTecnicaIndexacaoService $indexacao): void
    {
        $versao = NotaTecnicaVersao::query()->find($this->versaoId);

        if ($versao === null) {
            return;
        }

        try {
            $indexacao->indexar($versao);
        } catch (Throwable $erro) {
            report($erro);
            $this->fail($erro);
        }
    }
}
