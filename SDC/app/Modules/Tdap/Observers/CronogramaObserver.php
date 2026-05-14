<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Observers;

use App\Modules\Tdap\Models\Cronograma;
use App\Modules\Tdap\Services\HistoricoService;

class CronogramaObserver
{
    public function __construct(
        private readonly HistoricoService $historicoService,
    ) {}

    public function created(Cronograma $cronograma): void
    {
        $this->historicoService->registrar(
            tipoEvento: 'cronograma.criado',
            entity: $cronograma,
            obs: "Cronograma {$cronograma->numero} criado em rascunho.",
            payload: [
                'numero'       => $cronograma->numero,
                'ata_id'       => $cronograma->ata_id,
                'lote_id'      => $cronograma->lote_id,
                'municipio_id' => $cronograma->municipio_id,
                'prestador_id' => $cronograma->prestador_id,
                'dt_inicio'    => $cronograma->dt_inicio?->toDateString(),
                'dt_final'     => $cronograma->dt_final?->toDateString(),
            ],
        );
    }

    public function updated(Cronograma $cronograma): void
    {
        // Transicao de estado rascunho -> ativo
        if ($cronograma->wasChanged('ativo') && $cronograma->ativo) {
            $this->historicoService->registrar(
                tipoEvento: 'cronograma.ativado',
                entity: $cronograma,
                obs: "Cronograma {$cronograma->numero} ativado.",
                payload: [
                    'ativado_em'      => $cronograma->ativado_em?->toIso8601String(),
                    'stored_caminhoes' => $cronograma->stored_caminhoes,
                ],
            );

            return;
        }

        // Encerramento
        if ($cronograma->wasChanged('encerrado_em') && $cronograma->encerrado_em) {
            $this->historicoService->registrar(
                tipoEvento: 'cronograma.encerrado',
                entity: $cronograma,
                obs: "Cronograma {$cronograma->numero} encerrado.",
                payload: ['encerrado_em' => $cronograma->encerrado_em?->toIso8601String()],
            );

            return;
        }

        // Prorrogacao
        if ($cronograma->wasChanged(['dt_inicio_prorrogacao', 'dt_final_prorrogacao'])
            && ($cronograma->dt_inicio_prorrogacao || $cronograma->dt_final_prorrogacao)) {
            $this->historicoService->registrar(
                tipoEvento: 'cronograma.prorrogado',
                entity: $cronograma,
                obs: "Cronograma {$cronograma->numero} prorrogado.",
                payload: [
                    'dt_inicio_prorrogacao' => $cronograma->dt_inicio_prorrogacao?->toDateString(),
                    'dt_final_prorrogacao'  => $cronograma->dt_final_prorrogacao?->toDateString(),
                ],
            );
        }
    }
}
