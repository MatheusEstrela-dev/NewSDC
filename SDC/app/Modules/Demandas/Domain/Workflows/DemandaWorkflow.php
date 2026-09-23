<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Domain\Workflows;

use App\Modules\Demandas\Domain\Events\DemandaResolvidaV1;
use App\Modules\Demandas\Domain\Events\StatusAlteradoV1;
use App\Modules\Demandas\Domain\Guards\ExigeAtribuicaoParaProgresso;
use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Models\Demanda;
use DomainException;
use Illuminate\Support\Facades\DB;

final class DemandaWorkflow
{
    public function __construct(private readonly ExigeAtribuicaoParaProgresso $atribuicao) {}

    public function transitar(Demanda $demanda, StatusDemanda $novoStatus, int $usuarioId): Demanda
    {
        return DB::transaction(function () use ($demanda, $novoStatus, $usuarioId): Demanda {
            $demanda = Demanda::query()->lockForUpdate()->findOrFail($demanda->getKey());
            $anterior = $demanda->status;
            if (! $anterior->canTransitionTo($novoStatus)) {
                throw new DomainException('Transição de status não permitida.');
            }
            $this->atribuicao->check($demanda, $novoStatus);

            $demanda->status = $novoStatus;
            if ($novoStatus === StatusDemanda::RESOLVIDA) {
                $demanda->resolvido_em = now();
                $demanda->tempo_total_resolucao = $demanda->created_at?->diffInMinutes(now());
            } elseif ($anterior === StatusDemanda::RESOLVIDA) {
                $demanda->resolvido_em = null;
                $demanda->tempo_total_resolucao = null;
            }
            $demanda->save();

            DB::afterCommit(static function () use ($demanda, $anterior, $novoStatus, $usuarioId): void {
                event(StatusAlteradoV1::create($demanda->id, $anterior->value, $novoStatus->value, $usuarioId));
                if ($novoStatus === StatusDemanda::RESOLVIDA) {
                    event(DemandaResolvidaV1::create($demanda->id, $usuarioId));
                }
            });

            return $demanda;
        });
    }
}
