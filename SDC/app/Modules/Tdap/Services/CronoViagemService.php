<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Core\Events\DomainEvent;
use App\Core\Outbox\OutboxDispatcher;
use App\Modules\Tdap\Domain\Events\ViagemValidadaV1;
use App\Modules\Tdap\DTOs\CronoViagemDTO;
use App\Modules\Tdap\Models\CronoCaminhao;
use App\Modules\Tdap\Models\CronoViagem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CronoViagemService
{
    public function __construct(
        private readonly CronoCaminhaoService $cronoCaminhaoService,
        private readonly OutboxDispatcher $outbox,
    ) {}

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listarDoCaminhao(int $cronoCaminhaoId, array $filtros = []): Collection
    {
        return CronoViagem::query()
            ->doCaminhao($cronoCaminhaoId)
            ->with(['validador:id,name'])
            ->when($filtros['status'] ?? null, function ($q, $status) {
                match ($status) {
                    'pendente'  => $q->pendente(),
                    'aprovada'  => $q->aprovada(),
                    'rejeitada' => $q->rejeitada(),
                    default     => null,
                };
            })
            ->orderByDesc('data_registro')
            ->get();
    }

    public function listarPendentesValidacao(int $perPage = 25): LengthAwarePaginator
    {
        return CronoViagem::query()
            ->with([
                'cronoCaminhao.cronograma:id,numero',
                'cronoCaminhao.caminhao:id,placa,marca,modelo',
            ])
            ->pendente()
            ->whereHas('cronoCaminhao')
            ->orderBy('data_registro')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function registrar(CronoViagemDTO $dto): CronoViagem
    {
        return DB::transaction(function () use ($dto): CronoViagem {
            $cc = CronoCaminhao::query()
                ->with('cronograma:id,ativo,encerrado_em')
                ->findOrFail($dto->crono_caminhao_id);

            $cronograma = $cc->cronograma;

            if (! $cronograma?->ativo) {
                throw new \DomainException('So e possivel registrar viagens em cronogramas ativos.');
            }
            if ($cronograma->encerrado_em) {
                throw new \DomainException('Cronograma encerrado nao aceita novas viagens.');
            }

            /*
             * NAO ha checagem de `data_registro` dentro da vigencia do
             * cronograma, e isso e deliberado: 1.165 das 3.808 viagens da base
             * (31%) tem data anterior ao dt_inicio do proprio cronograma. Ou o
             * acervo legado esta errado, ou `data_registro` nao significa "dia
             * da viagem" nesta operacao. Ligar a regra aqui, com esse numero,
             * quebraria o registro de viagem em producao -- decisao de negocio,
             * nao de implementacao.
             */
            return CronoViagem::create([
                'crono_caminhao_id' => $dto->crono_caminhao_id,
                'data_registro'     => $dto->data_registro,
                'obs'               => $dto->obs,
                'validado'          => CronoViagem::STATUS_PENDENTE,
            ]);
        });
    }

    /**
     * Aprova ou rejeita uma viagem pendente.
     *
     * Na APROVACAO emite ViagemValidadaV1 no outbox, na mesma transacao do
     * update. O evento estava registrado no TdapServiceProvider e tinha tres
     * consumidores (projecao do processo, historico e EncerramentoSaga), mas
     * ninguem o publicava: a projecao `tdap_processo_projecoes` nunca somava
     * viagem, e o processo nunca saia de EM_EXECUCAO por conta propria.
     *
     * Rejeicao nao emite: nada foi entregue, nao ha execucao a acumular.
     */
    public function validar(int $id, bool $aprovada, ?string $obsAprovacao = null): CronoViagem
    {
        return DB::transaction(function () use ($id, $aprovada, $obsAprovacao): CronoViagem {
            $viagem = CronoViagem::query()->lockForUpdate()->findOrFail($id);

            if ($viagem->validado !== null) {
                throw new \DomainException('Viagem ja foi validada anteriormente.');
            }

            $viagem->update([
                'validado'          => $aprovada ? CronoViagem::STATUS_APROVADA : CronoViagem::STATUS_REJEITADA,
                'data_aprovacao'    => now(),
                'obs_aprovacao'     => $obsAprovacao,
                'user_validacao_id' => Auth::id(),
            ]);

            // Recalcula agregados do CronoCaminhao parent
            $this->cronoCaminhaoService->recalcularEntregas($viagem->crono_caminhao_id);

            if ($aprovada) {
                $this->publicarViagemValidada($viagem);
            }

            return $viagem->fresh();
        });
    }

    public function remover(int $id): bool
    {
        $viagem = CronoViagem::findOrFail($id);
        if ($viagem->validado === CronoViagem::STATUS_APROVADA) {
            throw new \DomainException('Viagem aprovada nao pode ser excluida. Rejeite primeiro.');
        }

        return DB::transaction(function () use ($viagem): bool {
            $cronoCaminhaoId = $viagem->crono_caminhao_id;
            $ok = (bool) $viagem->delete();
            $this->cronoCaminhaoService->recalcularEntregas($cronoCaminhaoId);

            return $ok;
        });
    }

    /**
     * Monta e persiste ViagemValidadaV1 com o contexto que os consumidores
     * esperam (ver ViagemValidadaV1::payload()).
     *
     * `processo_id` pode ser nulo: cronograma solto, sem ProcessoTdap, e caso
     * legitimo -- a saga simplesmente ignora o evento.
     */
    private function publicarViagemValidada(CronoViagem $viagem): void
    {
        $contexto = CronoCaminhao::query()
            ->with(['cronograma:id,processo_tdap_id', 'caminhao:id,capacidade_m3'])
            ->find($viagem->crono_caminhao_id);

        $this->outbox->persist(new ViagemValidadaV1(
            eventId:       DomainEvent::newId(),
            aggregateType: 'crono_viagem',
            aggregateId:   (string) $viagem->id,
            occurredAt:    new \DateTimeImmutable(),
            metadata: [
                'viagem_id'         => $viagem->id,
                'crono_caminhao_id' => $viagem->crono_caminhao_id,
                'cronograma_id'     => $contexto?->cronograma_id,
                'processo_id'       => $contexto?->cronograma?->processo_tdap_id,
                'capacidade_m3'     => (float) ($contexto?->caminhao?->capacidade_m3 ?? 0),
                'user_id'           => Auth::id(),
            ],
        ));
    }
}
