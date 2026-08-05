<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Jobs;

use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\CertificadoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Disparado quando um treinamento e finalizado: emite o certificado de toda
 * inscricao aprovada que ja atingiu a frequencia minima, sem travar o
 * request HTTP da finalizacao com centenas de checagens sincronas.
 */
class EmitirCertificadosTreinamentoJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;
    public int $timeout = 300;

    public function __construct(public int $treinamentoId)
    {
    }

    public function handle(CertificadoService $certificadoService): void
    {
        $treinamento = Treinamento::find($this->treinamentoId);

        if (!$treinamento) {
            return;
        }

        $treinamento->inscricoes()
            ->where('status', StatusInscricao::APROVADA->value)
            ->with('treinamento')
            ->chunkById(100, function ($inscricoes) use ($certificadoService) {
                foreach ($inscricoes as $inscricao) {
                    $certificadoService->emitirSeElegivel($inscricao);
                }
            });
    }
}
