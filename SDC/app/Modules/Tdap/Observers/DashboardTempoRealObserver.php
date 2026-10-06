<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Observers;

use App\Modules\Shared\Events\RecursoAtualizado;
use Illuminate\Database\Eloquent\Model;

/**
 * Avisa o dashboard do TDAP aberto que algum numero dele mudou.
 *
 * Observa os models que alimentam o painel (registrado no TdapServiceProvider):
 * viagem (fila de validacao, entregas), alocacao de caminhao (viagens previstas,
 * volumes), cronograma (contagens, prazos, cobertura), prestador (cards) e
 * historico (atividade recente -- e por ele que caminhao e vistoria chegam).
 *
 * Uma acao costuma tocar mais de um deles (validar viagem salva a viagem,
 * recalcula a alocacao e grava historico): sao avisos repetidos de proposito,
 * que o useAtualizacaoAoVivo junta num reload so. Depender so do historico
 * seria mais enxuto, mas qualquer caminho que salvasse sem historico deixaria o
 * painel parado sem ninguem perceber.
 *
 * O evento so sai depois do commit (RecursoAtualizado e
 * ShouldDispatchAfterCommit) e nao leva dado: o dashboard rebusca pelo
 * controller, que aplica permissao e escopo.
 */
class DashboardTempoRealObserver
{
    public function saved(Model $model): void
    {
        $this->avisar();
    }

    public function deleted(Model $model): void
    {
        $this->avisar();
    }

    public function restored(Model $model): void
    {
        $this->avisar();
    }

    private function avisar(): void
    {
        RecursoAtualizado::dispatch('tdap');
    }
}
