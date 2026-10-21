<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Observers;

use App\Modules\Tdap\Models\CronoViagem;
use App\Modules\Tdap\Services\HistoricoService;

class CronoViagemObserver
{
    public function __construct(
        private readonly HistoricoService $historicoService,
    ) {}

    public function created(CronoViagem $viagem): void
    {
        $this->historicoService->registrar(
            tipoEvento: 'viagem.registrada',
            entity: $viagem,
            obs: "Viagem registrada em {$viagem->data_registro?->format('d/m/Y H:i')}.",
            payload: [
                'crono_caminhao_id' => $viagem->crono_caminhao_id,
                'data_registro'     => $viagem->data_registro?->toIso8601String(),
            ],
        );
    }

    public function updated(CronoViagem $viagem): void
    {
        if ($viagem->wasChanged('confirmado') && $viagem->confirmado !== CronoViagem::CONFIRMACAO_PENDENTE) {
            $this->registrarDecisaoDoMunicipio($viagem);
        }

        if (! $viagem->wasChanged('validado')) {
            return;
        }

        $statusAnterior = $viagem->getOriginal('validado');
        $statusNovo = $viagem->validado;

        // pendente -> aprovada
        if ($statusAnterior === null && $statusNovo === 1) {
            $this->historicoService->registrar(
                tipoEvento: 'viagem.aprovada',
                entity: $viagem,
                obs: 'Viagem aprovada.',
                payload: [
                    'data_aprovacao'    => $viagem->data_aprovacao?->toIso8601String(),
                    'user_validacao_id' => $viagem->user_validacao_id,
                    'obs_aprovacao'     => $viagem->obs_aprovacao,
                ],
            );

            return;
        }

        // pendente -> rejeitada
        if ($statusAnterior === null && $statusNovo === 0) {
            $this->historicoService->registrar(
                tipoEvento: 'viagem.rejeitada',
                entity: $viagem,
                obs: 'Viagem rejeitada. '.($viagem->obs_aprovacao ?? ''),
                payload: [
                    'data_aprovacao'    => $viagem->data_aprovacao?->toIso8601String(),
                    'user_validacao_id' => $viagem->user_validacao_id,
                    'obs_aprovacao'     => $viagem->obs_aprovacao,
                ],
            );
        }
    }

    private function registrarDecisaoDoMunicipio(CronoViagem $viagem): void
    {
        $confirmada = $viagem->confirmado === CronoViagem::CONFIRMACAO_CONFIRMADA;

        $this->historicoService->registrar(
            tipoEvento: $confirmada ? 'viagem.confirmada' : 'viagem.reprovada_municipio',
            entity: $viagem,
            obs: $confirmada
                ? 'Municipio confirmou o recebimento da viagem.'
                : 'Municipio reprovou a viagem. '.($viagem->obs_confirmacao ?? ''),
            payload: [
                'confirmado_em'   => $viagem->confirmado_em?->toIso8601String(),
                'confirmado_por'  => $viagem->confirmado_por,
                'obs_confirmacao' => $viagem->obs_confirmacao,
            ],
        );
    }

    public function deleted(CronoViagem $viagem): void
    {
        $this->historicoService->registrar(
            tipoEvento: 'viagem.removida',
            entity: $viagem,
            obs: "Viagem removida (era status: {$viagem->status}).",
        );
    }
}
