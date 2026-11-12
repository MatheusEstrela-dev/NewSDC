<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Jobs;

use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Services\CertificadoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Emite o certificado de uma inscricao que acabou de concluir o curso EAD,
 * fora do request do aluno. Idempotente (emitirSeElegivel).
 */
class EmitirCertificadoDaInscricaoJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $inscricaoId)
    {
        $this->onQueue('low');
    }

    public function handle(CertificadoService $certificadoService): void
    {
        $inscricao = Inscricao::query()->with('treinamento')->find($this->inscricaoId);

        if ($inscricao !== null) {
            $certificadoService->emitirSeElegivel($inscricao);
        }
    }
}
