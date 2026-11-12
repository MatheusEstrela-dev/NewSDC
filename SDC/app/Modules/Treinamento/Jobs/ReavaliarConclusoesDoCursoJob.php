<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Jobs;

use App\Modules\Treinamento\Services\ReavaliacaoDeConclusaoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Reavalia a conclusao dos alunos de um curso EAD depois que o professor muda
 * o que a trilha exige (excluir item ou pasta, tornar item opcional).
 * Idempotente (a conclusao so e gravada uma vez). Unico por curso enquanto
 * pendente: varias edicoes seguidas enfileiram um so job (o que ja comecou
 * a rodar nao bloqueia o proximo).
 */
class ReavaliarConclusoesDoCursoJob implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public int $uniqueFor = 600;

    public function __construct(public int $treinamentoId)
    {
        $this->onQueue('low');
    }

    public function uniqueId(): string
    {
        return (string) $this->treinamentoId;
    }

    public function handle(ReavaliacaoDeConclusaoService $reavaliacao): void
    {
        $reavaliacao->doCurso($this->treinamentoId);
    }
}
