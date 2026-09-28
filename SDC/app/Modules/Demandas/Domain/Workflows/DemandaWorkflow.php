<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Domain\Workflows;

use App\Modules\Demandas\Domain\Events\DemandaResolvidaV1;
use App\Modules\Demandas\Domain\Events\StatusAlteradoV1;
use App\Modules\Demandas\Domain\Exceptions\TransicaoProibidaException;
use App\Modules\Demandas\Domain\Guards\ExigeAtribuicaoParaProgresso;
use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Support\ContextoImportacao;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class DemandaWorkflow
{
    public function __construct(
        private readonly ExigeAtribuicaoParaProgresso $atribuicao,
        private readonly ContextoImportacao $contexto,
    ) {}

    public function transitar(Demanda $demanda, StatusDemanda $novoStatus, int $usuarioId, ?CarbonInterface $momento = null): Demanda
    {
        return DB::transaction(function () use ($demanda, $novoStatus, $usuarioId, $momento): Demanda {
            $demanda = Demanda::query()->lockForUpdate()->findOrFail($demanda->getKey());
            $anterior = $demanda->status;
            if (! $anterior->canTransitionTo($novoStatus)) {
                throw TransicaoProibidaException::entre($anterior->label(), $novoStatus->label());
            }
            $this->atribuicao->check($demanda, $novoStatus);

            $demanda->status = $novoStatus;
            if ($novoStatus === StatusDemanda::RESOLVIDA) {
                $resolvidoEm = $momento ?? now();
                $demanda->resolvido_em = $resolvidoEm;
                $demanda->tempo_total_resolucao = (int) round(abs($demanda->created_at->diffInMinutes($resolvidoEm)));
            } elseif ($anterior === StatusDemanda::RESOLVIDA) {
                $demanda->resolvido_em = null;
                $demanda->tempo_total_resolucao = null;
            }
            $demanda->save();

            // Carga do legado nao produz evento de negocio: importacao nao e
            // acao de usuario, e o Ranking (e outros consumidores) nao pode
            // pontuar nem notificar retroativamente por causa de uma migracao.
            if (! $this->contexto->ativo()) {
                DB::afterCommit(static function () use ($demanda, $anterior, $novoStatus, $usuarioId): void {
                    event(StatusAlteradoV1::create($demanda->id, $anterior->value, $novoStatus->value, $usuarioId));
                    if ($novoStatus === StatusDemanda::RESOLVIDA) {
                        event(DemandaResolvidaV1::create($demanda->id, $usuarioId));
                    }
                });
            }

            return $demanda;
        });
    }

    /**
     * Leva a demanda ate o status alvo pelo menor caminho da maquina de estados.
     *
     * Existe porque a tela fala em etapas: "Em andamento" a partir de aberta sao
     * dois passos (aberta -> em_analise -> em_progresso). Uma transacao so, para
     * que um guard barrando no meio nao deixe a demanda num passo intermediario.
     */
    public function conduzirAte(Demanda $demanda, StatusDemanda $alvo, int $usuarioId, ?CarbonInterface $momento = null): Demanda
    {
        return DB::transaction(function () use ($demanda, $alvo, $usuarioId, $momento): Demanda {
            $atual = Demanda::query()->lockForUpdate()->findOrFail($demanda->getKey());
            foreach (self::caminho($atual->status, $alvo) as $passo) {
                $atual = $this->transitar($atual, $passo, $usuarioId, $passo === $alvo ? $momento : null);
            }

            return $atual;
        });
    }

    /**
     * Busca em largura sobre getAllowedTransitions(). Sete estados: custo nulo.
     *
     * @return list<StatusDemanda>
     */
    public static function caminho(StatusDemanda $de, StatusDemanda $para): array
    {
        if ($de === $para) {
            return [];
        }

        $fila = [[$de, []]];
        $visitados = [$de->value => true];
        while ($fila !== []) {
            [$status, $trilha] = array_shift($fila);
            foreach ($status->getAllowedTransitions() as $proximo) {
                if (isset($visitados[$proximo->value])) {
                    continue;
                }
                $novaTrilha = [...$trilha, $proximo];
                if ($proximo === $para) {
                    return $novaTrilha;
                }
                $visitados[$proximo->value] = true;
                $fila[] = [$proximo, $novaTrilha];
            }
        }

        throw TransicaoProibidaException::entre($de->label(), $para->label());
    }
}
