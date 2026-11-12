<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Console;

use App\Modules\Treinamento\Services\ReavaliacaoDeConclusaoService;
use Illuminate\Console\Command;

/**
 * Reavaliacao retroativa da conclusao dos cursos EAD (spec 8.4): alunos que
 * terminaram antes da Fase 3 ou cuja trilha ficou completa por edicao do
 * curso. Idempotente: conclusao ja gravada nunca e tocada.
 */
class ReavaliarConclusoesCommand extends Command
{
    protected $signature = 'treinamento:avaliar-conclusoes {treinamento? : ID do curso; sem ele, todos os cursos EAD}';

    protected $description = 'Reavalia a conclusao das inscricoes EAD ainda sem conclusao e enfileira os certificados que faltam (idempotente).';

    public function handle(ReavaliacaoDeConclusaoService $reavaliacao): int
    {
        $id = $this->argument('treinamento');

        $curso = $id === null ? null : (int) $id;
        // Emissoes pendentes primeiro: as conclusoes desta passada ja enfileiram a propria emissao.
        $emissoes = $reavaliacao->emissoesPendentes($curso);
        $total = $reavaliacao->doCurso($curso);

        $this->info("Conclusoes registradas: {$total}");
        $this->info("Emissoes de certificado reenfileiradas: {$emissoes}");

        return self::SUCCESS;
    }
}
