<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Core\Events\DomainEvent;
use App\Core\Outbox\OutboxDispatcher;
use App\Modules\Tdap\Domain\Events\CronogramaAtivadoV1;
use App\Modules\Tdap\DTOs\CronogramaDTO;
use App\Modules\Tdap\Models\Cronograma;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CronogramaService
{
    public function __construct(
        private readonly OutboxDispatcher $outbox,
        private readonly HistoricoService $historico,
    ) {}


    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listar(int $perPage = 15, array $filtros = []): LengthAwarePaginator
    {
        return Cronograma::query()
            ->with([
                'ata:id,numero',
                'lote:id,numero,nome',
                'municipio:id,nome,uf',
                'prestador:id,nome,cnpj',
            ])
            ->withCount('caminhoes')
            // volume_contratado/volume_entregue somam colunas dos caminhoes
            // alocados; sem o withSum o accessor faria um SELECT por linha.
            ->withSum('caminhoes', 'agua_prevista')
            ->withSum('caminhoes', 'agua_entregue')
            ->when(
                ($filtros['estado'] ?? null) === 'arquivado',
                fn ($q) => $q->arquivado(),
                fn ($q) => $q->naoArquivado(),
            )
            ->when($filtros['estado'] ?? null, function ($q, $estado) {
                match ($estado) {
                    'rascunho'  => $q->rascunho(),
                    'ativo'     => $q->ativo(),
                    'encerrado' => $q->encerrado(),
                    default     => null,
                };
            })
            ->when($filtros['ata_id'] ?? null, fn ($q, $id) => $q->where('ata_id', (int) $id))
            ->when($filtros['prestador_id'] ?? null, fn ($q, $id) => $q->where('prestador_id', (int) $id))
            ->when($filtros['municipio_id'] ?? null, fn ($q, $id) => $q->where('municipio_id', (int) $id))
            ->when($filtros['search'] ?? null, fn ($q, $termo) => $q->buscar((string) $termo))
            ->orderByDesc('dt_inicio')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Linhas planas para exportacao CSV (respeita os filtros da listagem).
     *
     * @param  array<string, mixed>  $filtros
     * @return array<int, array<string, mixed>>
     */
    public function exportar(array $filtros = []): array
    {
        $rows = Cronograma::query()
            ->with([
                'ata:id,numero',
                'lote:id,numero',
                'municipio:id,nome,uf',
                'prestador:id,nome,cnpj',
            ])
            ->withCount('caminhoes')
            // volume_contratado/volume_entregue somam colunas dos caminhoes
            // alocados; sem o withSum o accessor faria um SELECT por linha.
            ->withSum('caminhoes', 'agua_prevista')
            ->withSum('caminhoes', 'agua_entregue')
            ->when(
                ($filtros['estado'] ?? null) === 'arquivado',
                fn ($q) => $q->arquivado(),
                fn ($q) => $q->naoArquivado(),
            )
            ->when($filtros['estado'] ?? null, function ($q, $estado) {
                match ($estado) {
                    'rascunho'  => $q->rascunho(),
                    'ativo'     => $q->ativo(),
                    'encerrado' => $q->encerrado(),
                    default     => null,
                };
            })
            ->when($filtros['ata_id'] ?? null, fn ($q, $id) => $q->where('ata_id', (int) $id))
            ->when($filtros['prestador_id'] ?? null, fn ($q, $id) => $q->where('prestador_id', (int) $id))
            ->when($filtros['municipio_id'] ?? null, fn ($q, $id) => $q->where('municipio_id', (int) $id))
            ->when($filtros['search'] ?? null, fn ($q, $termo) => $q->buscar((string) $termo))
            ->when($filtros['data_inicio'] ?? null, fn ($q, $d) => $q->whereDate('dt_inicio', '>=', (string) $d))
            ->when($filtros['data_fim'] ?? null, fn ($q, $d) => $q->whereDate('dt_inicio', '<=', (string) $d))
            ->orderByDesc('dt_inicio')
            ->get();

        return $rows->map(fn (Cronograma $c) => [
            'Numero'                 => $c->numero,
            'Estado'                 => $c->estado,
            'Vigencia Inicio'        => $c->dt_inicio?->format('d/m/Y'),
            'Vigencia Fim'           => $c->dt_final?->format('d/m/Y'),
            'Ata'                    => $c->ata?->numero,
            'Lote'                   => $c->lote?->numero,
            'Municipio'              => $c->municipio?->nome,
            'UF'                     => $c->municipio?->uf,
            'Prestador'              => $c->prestador?->nome,
            'CNPJ'                   => $c->prestador?->cnpj,
            // Contratado = soma da agua prevista dos caminhoes alocados. Esta
            // coluna trazia `fator` (<= 0,60 em toda a base) e passava a
            // impressao de que o cronograma nao movia agua nenhuma.
            'Volume Contratado (m3)' => number_format($c->volume_contratado, 2, ',', '.'),
            'Volume Entregue (m3)'   => number_format($c->volume_entregue, 2, ',', '.'),
            'Execucao (%)'           => number_format($c->percentual_entregue, 2, ',', '.'),
            'Caminhoes'              => (int) $c->caminhoes_count,
            'Consumo Diario'         => number_format((float) $c->consumo_diario, 2, ',', '.'),
            'Dias'                   => (int) $c->dias,
            'Fator'                  => number_format((float) $c->fator, 2, ',', '.'),
            'Arquivado'              => $c->arquivado_em ? 'Sim' : 'Nao',
        ])->all();
    }

    public function obter(int $id): Cronograma
    {
        return Cronograma::query()
            ->with([
                'ata:id,numero,dt_inicio,dt_final',
                'lote:id,numero,nome',
                'municipio:id,nome,uf',
                'prestador:id,nome,cnpj,email',
                'user:id,name,email',
                'pontoCaptacao:id,nome,tipo,municipio_id',
                'caminhoes.caminhao:id,placa,marca,modelo,capacidade_m3',
                'comprovantes',
            ])
            ->withCount(['caminhoes'])
            ->withSum('caminhoes', 'agua_prevista')
            ->withSum('caminhoes', 'agua_entregue')
            ->findOrFail($id);
    }

    public function criar(CronogramaDTO $dto): Cronograma
    {
        return DB::transaction(function () use ($dto): Cronograma {
            $data = $dto->toArray();
            $data['user_id'] = Auth::id();
            $data['ativo'] = false;

            return Cronograma::create($data);
        });
    }

    public function atualizar(int $id, CronogramaDTO $dto): Cronograma
    {
        return DB::transaction(function () use ($id, $dto): Cronograma {
            $cronograma = Cronograma::findOrFail($id);
            if ($cronograma->ativo) {
                throw new \DomainException('Cronograma ativo nao pode ter cabecalho editado. Encerre ou crie novo.');
            }
            $cronograma->update($dto->toArray());

            return $cronograma->fresh();
        });
    }

    public function deletar(int $id): bool
    {
        $cronograma = Cronograma::findOrFail($id);
        if ($cronograma->ativo) {
            throw new \DomainException('Cronograma ativo nao pode ser excluido. Encerre antes.');
        }

        return (bool) $cronograma->delete();
    }

    /**
     * Arquiva o cronograma (estado distinto do soft delete).
     * Idempotente: arquivar um cronograma ja arquivado nao gera novo evento.
     */
    public function arquivar(int $id): Cronograma
    {
        return DB::transaction(function () use ($id): Cronograma {
            $cronograma = Cronograma::findOrFail($id);
            if (! $cronograma->arquivado_em) {
                $cronograma->arquivado_em = now();
                $cronograma->save();
                $this->historico->registrar('cronograma.arquivado', $cronograma, 'Cronograma arquivado.');
            }

            return $cronograma->fresh();
        });
    }

    public function desarquivar(int $id): Cronograma
    {
        return DB::transaction(function () use ($id): Cronograma {
            $cronograma = Cronograma::findOrFail($id);
            if ($cronograma->arquivado_em) {
                $cronograma->arquivado_em = null;
                $cronograma->save();
                $this->historico->registrar('cronograma.desarquivado', $cronograma, 'Cronograma desarquivado.');
            }

            return $cronograma->fresh();
        });
    }

    /**
     * Verifica se cronograma pode ser ativado.
     * Regra Fase 4 (vistoria vigente) entra com flag aqui.
     *
     * @return array{bool, ?string}
     */
    public function podeAtivar(Cronograma $cronograma): array
    {
        if ($cronograma->ativo) {
            return [false, 'Cronograma ja esta ativo.'];
        }

        if ($cronograma->encerrado_em) {
            return [false, 'Cronograma encerrado nao pode ser reativado.'];
        }

        $cronograma->loadCount('caminhoes');
        if ((int) $cronograma->caminhoes_count === 0) {
            return [false, 'Cronograma exige ao menos 1 caminhao alocado.'];
        }

        // GUARD Vistoria (Fase 4) - regra real ligada:
        // todos os caminhoes alocados precisam ter vistoria aprovada vigente (<= 12 meses).
        $cronograma->load(['caminhoes.caminhao.vistoriaVigente']);
        foreach ($cronograma->caminhoes as $cc) {
            if (! $cc->caminhao?->vistoriaVigente) {
                $placa = $cc->caminhao?->placa ?? "id={$cc->caminhao_id}";
                return [false, "Caminhao {$placa} nao tem vistoria aprovada vigente (12 meses). Registre/aprove vistoria antes."];
            }
        }

        return [true, null];
    }

    public function ativar(int $id): Cronograma
    {
        return DB::transaction(function () use ($id): Cronograma {
            $cronograma = Cronograma::query()->lockForUpdate()->findOrFail($id);

            [$ok, $erro] = $this->podeAtivar($cronograma);
            if (! $ok) {
                throw new \DomainException($erro ?? 'Cronograma nao pode ser ativado.');
            }

            $cronograma->ativo = true;
            $cronograma->ativado_em = now();
            $cronograma->stored_prestador = $cronograma->prestador?->only(['id', 'nome', 'cnpj', 'email']);
            $cronograma->stored_municipio = $cronograma->municipio?->only(['id', 'nome', 'uf']);
            $cronograma->stored_caminhoes = $cronograma->caminhoes()
                ->with('caminhao:id,placa,marca,modelo,capacidade_m3')
                ->get()
                ->map(fn ($cc) => [
                    'id'             => $cc->id,
                    'caminhao_id'    => $cc->caminhao_id,
                    'placa'          => $cc->caminhao?->placa,
                    'capacidade_m3'  => (float) ($cc->caminhao?->capacidade_m3 ?? 0),
                    'agua_prevista'  => (float) $cc->agua_prevista,
                    'num_viagens'    => (int) $cc->num_viagens,
                ])->toArray();
            $cronograma->save();

            $fresh = $cronograma->fresh(['prestador', 'municipio', 'ata', 'lote']);

            // Emite CronogramaAtivadoV1 no outbox (mesma transacao do save).
            // Listener idempotente EnviarEmailCronogramaListener consome
            // o evento e envia o e-mail de forma assincrona/retryable.
            $this->outbox->persist(new CronogramaAtivadoV1(
                eventId:       DomainEvent::newId(),
                aggregateType: 'cronograma',
                aggregateId:   (string) $fresh->id,
                occurredAt:    new \DateTimeImmutable(),
                metadata: [
                    'cronograma_id' => $fresh->id,
                    'numero'        => $fresh->numero,
                    'prestador_id'  => $fresh->prestador_id,
                    'municipio_id'  => $fresh->municipio_id,
                    'ativado_em'    => $fresh->ativado_em?->toIso8601String(),
                ],
            ));

            return $fresh;
        });
    }

    public function encerrar(int $id, ?string $obs = null): Cronograma
    {
        return DB::transaction(function () use ($id, $obs): Cronograma {
            $cronograma = Cronograma::findOrFail($id);
            if (! $cronograma->ativo) {
                throw new \DomainException('Apenas cronogramas ativos podem ser encerrados.');
            }

            $cronograma->ativo = false;
            $cronograma->encerrado_em = now();
            if ($obs) {
                $cronograma->observacao = trim(($cronograma->observacao ?? '') . "\n[ENCERRAMENTO] " . $obs);
            }
            $cronograma->save();

            return $cronograma->fresh();
        });
    }

    /**
     * Estende a vigencia sem criar novo cronograma.
     *
     * A regra `dt_inicio_prorrogacao >= dt_final` ja existia no
     * AbstractCronogramaRequest, mas a rota de prorrogacao tem validacao
     * propria e mais fraca: dava para "prorrogar" para uma janela ANTERIOR ao
     * fim da vigencia original, encurtando o prazo em vez de estende-lo (as
     * datas efetivas dao precedencia a prorrogacao).
     */
    public function prorrogar(int $id, string $dtInicio, string $dtFinal, ?string $justificativa = null): Cronograma
    {
        return DB::transaction(function () use ($id, $dtInicio, $dtFinal, $justificativa): Cronograma {
            $cronograma = Cronograma::findOrFail($id);
            if (! $cronograma->ativo) {
                throw new \DomainException('Apenas cronogramas ativos podem ser prorrogados.');
            }
            if ($cronograma->encerrado_em) {
                throw new \DomainException('Cronograma encerrado nao pode ser prorrogado.');
            }

            $inicioProrrogacao = Carbon::parse($dtInicio)->startOfDay();
            $finalProrrogacao = Carbon::parse($dtFinal)->startOfDay();

            if ($finalProrrogacao->lt($inicioProrrogacao)) {
                throw new \DomainException('A data final da prorrogacao nao pode ser anterior a inicial.');
            }

            if ($cronograma->dt_final !== null && $inicioProrrogacao->lt($cronograma->dt_final->copy()->startOfDay())) {
                throw new \DomainException(
                    'A prorrogacao deve comecar em ou depois do fim da vigencia atual ('
                    .$cronograma->dt_final->format('d/m/Y').').'
                );
            }

            $cronograma->update([
                'dt_inicio_prorrogacao' => $dtInicio,
                'dt_final_prorrogacao'  => $dtFinal,
                'justificativa'         => $justificativa
                    ? trim(($cronograma->justificativa ?? '') . "\n[PRORROGACAO] " . $justificativa)
                    : $cronograma->justificativa,
            ]);

            return $cronograma->fresh();
        });
    }

    /**
     * @return array<string, int|float>
     */
    public function obterEstatisticas(): array
    {
        $row = Cronograma::query()
            ->selectRaw('
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE ativo = TRUE AND encerrado_em IS NULL) AS ativos,
                COUNT(*) FILTER (WHERE ativo = FALSE AND encerrado_em IS NULL) AS rascunhos,
                COUNT(*) FILTER (WHERE encerrado_em IS NOT NULL) AS encerrados
            ')
            ->first();

        // Volume ativo = agua prevista dos caminhoes dos cronogramas ativos.
        // Antes era SUM(fator), que somava grandezas de no maximo 0,60 e
        // mostrava "0,6 m3 ativos" para a operacao inteira. A soma vem da
        // tabela dos caminhoes porque e la que o volume por rota e definido.
        $volumes = DB::table('tdap_crono_caminhoes as cc')
            ->join('tdap_cronogramas as c', 'c.id', '=', 'cc.cronograma_id')
            ->whereNull('cc.deleted_at')
            ->whereNull('c.deleted_at')
            ->where('c.ativo', true)
            ->whereNull('c.encerrado_em')
            ->selectRaw('
                COALESCE(SUM(cc.agua_prevista), 0) AS previsto,
                COALESCE(SUM(cc.agua_entregue), 0) AS entregue
            ')
            ->first();

        $previsto = (float) ($volumes->previsto ?? 0);
        $entregue = (float) ($volumes->entregue ?? 0);

        return [
            'total'             => (int) ($row->total ?? 0),
            'ativos'            => (int) ($row->ativos ?? 0),
            'rascunhos'         => (int) ($row->rascunhos ?? 0),
            'encerrados'        => (int) ($row->encerrados ?? 0),
            'volume_ativo_m3'   => round($previsto, 2),
            'volume_entregue_m3' => round($entregue, 2),
            'execucao_percentual' => $previsto > 0 ? round(($entregue / $previsto) * 100, 2) : 0.0,
        ];
    }
}
