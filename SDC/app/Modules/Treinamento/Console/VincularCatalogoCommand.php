<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Console;

use App\Modules\Treinamento\Services\CatalogoService;
use Illuminate\Console\Command;

/**
 * Liga as turmas antigas (sem curso) ao catalogo pelo titulo normalizado.
 * Com --dry-run so mostra o que seria feito.
 */
class VincularCatalogoCommand extends Command
{
    protected $signature = 'treinamento:vincular-catalogo {--dry-run : Mostra o relatorio sem gravar}';

    protected $description = 'Agrupa as turmas sem curso pelo titulo, cria os cursos do catalogo e liga as turmas.';

    public function handle(CatalogoService $catalogo): int
    {
        $simular = (bool) $this->option('dry-run');
        $relatorio = $catalogo->vincularPorTitulo($simular);

        $this->table(
            ['Curso', 'Ja existia', 'Turmas'],
            array_map(fn (array $g) => [$g['curso'], $g['curso_existente'] ? 'sim' : 'nao', $g['turmas']], $relatorio['grupos'])
        );

        foreach ($relatorio['ead_ignorados'] as $ignorado) {
            $this->warn("EAD ignorado (o curso ja tem um EAD; use Nova versao): #{$ignorado['id']} {$ignorado['titulo']}");
        }

        $grupos = count($relatorio['grupos']);
        $this->info(($simular ? 'Simulacao: ' : '')."grupos {$grupos}, cursos criados {$relatorio['cursos_criados']}, turmas ligadas {$relatorio['turmas_ligadas']}");

        return self::SUCCESS;
    }
}
