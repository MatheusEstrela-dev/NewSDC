<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Console;

use App\Modules\Medalhao\Jobs\IngerirFonteJob;
use App\Modules\Medalhao\Registry\IngestorRegistry;
use Illuminate\Console\Command;

class IngerirCommand extends Command
{
    protected $signature = 'medalhao:ingerir {grupo : Grupo de fontes, ex.: sismos}';

    protected $description = 'Despacha a coleta das fontes de um grupo para a fila medalhao';

    public function handle(IngestorRegistry $registry): int
    {
        $grupo = (string) $this->argument('grupo');
        $chaves = $registry->chavesDoGrupo($grupo);

        if ($chaves === []) {
            $this->error("Nenhuma fonte registrada para o grupo: {$grupo}");

            return self::FAILURE;
        }

        foreach ($chaves as $chave) {
            IngerirFonteJob::dispatch($chave);
            $this->line("Coleta despachada: {$chave}");
        }

        $this->info(sprintf('%d fonte(s) do grupo %s na fila.', count($chaves), $grupo));

        return self::SUCCESS;
    }
}
