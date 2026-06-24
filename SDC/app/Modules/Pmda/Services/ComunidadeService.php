<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Services;

use App\Modules\Pmda\Enums\PmdaStatus;
use App\Modules\Pmda\Models\PmdaComunidade;
use App\Modules\Pmda\Models\PmdaPlano;

class ComunidadeService
{
    /** Status em que uma comunidade e considerada "em uso" e nao pode estar em outro plano. */
    private const STATUS_ATIVOS = [
        PmdaStatus::RASCUNHO->value,
        PmdaStatus::COMPLETO->value,
        PmdaStatus::EM_ANALISE->value,
        PmdaStatus::APROVADO->value,
        PmdaStatus::ATENDIDO->value,
    ];

    public function __construct(private readonly PmdaPlanoService $planos) {}

    public function adicionar(PmdaPlano $plano, array $data): PmdaComunidade
    {
        $comunidadeId = $data['comunidade_id'] ?? null;

        if ($comunidadeId !== null && $this->jaEmPlanoAtivo((int) $comunidadeId, $plano->id)) {
            throw new \DomainException('Esta comunidade já está vinculada a outro PMDA ativo.');
        }

        $comunidade = $plano->comunidades()->create($data);
        $this->planos->recalcularStatus($plano);

        return $comunidade;
    }

    public function remover(PmdaComunidade $comunidade): void
    {
        $plano = $comunidade->plano;
        $comunidade->delete();
        if ($plano) {
            $this->planos->recalcularStatus($plano);
        }
    }

    private function jaEmPlanoAtivo(int $comunidadeId, int $planoIdAtual): bool
    {
        return PmdaComunidade::query()
            ->where('comunidade_id', $comunidadeId)
            ->where('pmda_plano_id', '!=', $planoIdAtual)
            ->whereHas('plano', fn ($q) => $q->whereIn('status', self::STATUS_ATIVOS))
            ->exists();
    }
}
