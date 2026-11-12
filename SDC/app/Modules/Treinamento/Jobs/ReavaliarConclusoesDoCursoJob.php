<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Jobs;

use App\Modules\Treinamento\Services\ReavaliacaoDeConclusaoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Reavalia a conclusao dos alunos de um curso EAD depois que o professor muda
 * o que a trilha exige (excluir item ou pasta, tornar item opcional).
 * Idempotente (a conclusao so e gravada uma vez).
 */
class ReavaliarConclusoesDoCursoJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public int $treinamentoId)
    {
        $this->onQueue('low');
    }

    public function handle(ReavaliacaoDeConclusaoService $reavaliacao): void
    {
        $reavaliacao->doCurso($this->treinamentoId);
    }
}
