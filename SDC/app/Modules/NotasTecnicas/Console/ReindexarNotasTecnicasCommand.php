<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Console;

use App\Modules\NotasTecnicas\Jobs\ExtrairTextoNotaTecnicaJob;
use App\Modules\NotasTecnicas\Models\NotaTecnicaVersao;
use Illuminate\Console\Command;

/**
 * Enfileira a extracao de texto das versoes. Sem opcao, so as que ainda nao
 * foram indexadas (upload anterior a Fase 4, ou job que falhou de vez).
 */
class ReindexarNotasTecnicasCommand extends Command
{
    protected $signature = 'notas-tecnicas:reindexar
        {--todas : Reindexa tambem as versoes que ja tem texto}
        {--sync : Roda agora, no processo do comando, em vez de enfileirar}';

    protected $description = 'Extrai o texto dos PDFs das notas tecnicas para a busca no conteudo';

    public function handle(): int
    {
        $query = NotaTecnicaVersao::query()->select('id');

        if (! $this->option('todas')) {
            $query->whereNull('conteudo_texto');
        }

        $total = 0;

        $query->chunkById(200, function ($versoes) use (&$total): void {
            foreach ($versoes as $versao) {
                $this->option('sync')
                    ? ExtrairTextoNotaTecnicaJob::dispatchSync($versao->id)
                    : ExtrairTextoNotaTecnicaJob::dispatch($versao->id);
                $total++;
            }
        });

        $this->info($this->option('sync')
            ? "{$total} versao(oes) indexada(s)."
            : "{$total} versao(oes) enviada(s) para a fila low.");

        return self::SUCCESS;
    }
}
