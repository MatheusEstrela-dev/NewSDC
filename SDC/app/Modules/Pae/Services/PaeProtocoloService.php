<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Core\Events\DomainEvent;
use App\Core\Outbox\OutboxDispatcher;
use App\Models\User;
use App\Modules\Pae\Domain\Events\ProtocoloEnviadoV1;
use App\Modules\Pae\Domain\Exceptions\TransicaoProibidaException;
use App\Modules\Pae\Domain\Workflows\PaeProtocoloWorkflow;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeEmpnto;
use App\Modules\Pae\Models\PaeProtocolo;
use Carbon\CarbonImmutable;
use App\Modules\Pae\Support\CicloProtocolo;
use App\Modules\Pae\Support\TimelinePae;
use App\Modules\Shared\BaseService;
use App\Support\Database\PgCompat;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaeProtocoloService extends BaseService
{
    public function __construct(
        private readonly OutboxDispatcher $outbox,
        private readonly PaeProtocoloWorkflow $workflow,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PaeProtocolo::query()
            ->withCount(['comunicacoes as comunicacoes_pendentes_count' => fn ($q) => $q->where('status', 'pendente')])
            ->with([
                'analistaAtual:id,name',
                'empreendimento:id,pae_empdor_id,nome',
                'empreendimento.empdor:id,nome',
                'analise:id,pae_protocolo_id',
                'analise.notificacoes:id,pae_analise_id,dt_notificacao,dt_devolutiva',
                'decisaoAdmissibilidadeVigente' => fn ($consulta) => $consulta->select(
                    'pae_admissibilidade_decisoes.id',
                    'pae_admissibilidade_decisoes.protocolo_id',
                    'pae_admissibilidade_decisoes.tipo',
                    'pae_admissibilidade_decisoes.prazo_correcao_em',
                ),
            ]);

        $mostrarArquivados = filter_var($filters['arquivado'] ?? false, FILTER_VALIDATE_BOOL);
        $mostrarArquivados
            ? $query->where('arquivado', true)
            : $query->ativo();

        if (!empty($filters['restringir_ao_analista'])) {
            $query->where('analista_atual_id', $filters['restringir_ao_analista']);
        }

        $query = $this->applySearch($query, $filters['search'] ?? null, [
            'num_protocolo', 'sigibar', 'sei_numero', 'empnto_search',
        ]);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        switch ($filters['status_grupo'] ?? null) {
            case 'vencidos':
                $query->vencidos();
                break;
            case 'historico':
                $query->whereIn('status', [
                    PaeProtocoloStatus::APROVADO->value,
                    PaeProtocoloStatus::CCPAE->value,
                    PaeProtocoloStatus::ATIVO_3_ANOS->value,
                    PaeProtocoloStatus::REPROVADO->value,
                    PaeProtocoloStatus::REPROVADO_SUMARIAMENTE->value,
                ]);
                break;
            case 'ciclos_esgotados':
                $query->whereNotNull('ciclos_esgotados_em');
                break;
        }

        if (!empty($filters['analista_id'])) {
            $query->where('analista_atual_id', $filters['analista_id']);
        }

        if (!empty($filters['data_inicio'])) {
            $query->where('dt_entrada', '>=', $filters['data_inicio']);
        }

        if (!empty($filters['data_fim'])) {
            $query->where('dt_entrada', '<=', $filters['data_fim']);
        }

        $pagina = $query->orderBy('dt_entrada', 'desc')->paginate($perPage);
        $hoje = CarbonImmutable::today();
        $pagina->getCollection()->each(static function (PaeProtocolo $protocolo) use ($hoje): void {
            $decisao = $protocolo->decisaoAdmissibilidadeVigente;
            $protocolo->setAttribute('correcao_prazo_vencido', $decisao?->tipo === 'correcao_solicitada'
                && $decisao->prazo_correcao_em?->isBefore($hoje));
        });

        return $pagina;
    }

    public function findById(int $id): ?PaeProtocolo
    {
        return PaeProtocolo::with(['analistaAtual', 'tramitacoes.usuario', 'timeline.usuario'])->find($id);
    }

    public function gerarNumProtocolo(): string
    {
        return DB::transaction(function () {
            $this->travarSequencialProtocolo();

            $hoje = now()->format('d.m.Y');

            return sprintf('%s-%04d-001', $hoje, $this->proximoSequencialGlobal());
        });
    }

    private function travarSequencialProtocolo(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT pg_advisory_xact_lock(hashtext('pae_protocolo_seq'))");
        }
    }

    /**
     * Sequencial (NNNN) continuo e global: NAO reseta por dia. Retorna o maior
     * NNNN ja emitido (qualquer data) + 1. Protocolos relacionados reaproveitam
     * o NNNN do protocolo base (variam so no sufixo -SSS), portanto nao criam
     * novo NNNN e o max continua correto.
     */
    private function proximoSequencialGlobal(): int
    {
        $max = 0;

        PaeProtocolo::withTrashed()
            ->pluck('num_protocolo')
            ->each(function (?string $num) use (&$max) {
                if ($num !== null && preg_match('/^\d{2}\.\d{2}\.\d{4}-(\d{4})-\d{3}$/', $num, $m)) {
                    $max = max($max, (int) $m[1]);
                }
            });

        return $max + 1;
    }

    public function create(array $data, User $user): PaeProtocolo
    {
        // Fora da transacao de proposito: gerarNumProtocolo abre a sua e toma o
        // advisory lock do sequencial; aninhar viraria savepoint e o lock ficaria
        // retido ate o fim da criacao inteira, serializando os cadastros.
        $data['num_protocolo'] ??= $this->gerarNumProtocolo();

        return DB::transaction(function () use ($data, $user): PaeProtocolo {
            $protocolo = PaeProtocolo::create([
                ...$data,
                'status' => PaeProtocoloStatus::NOVO->value,
                'user_id' => $user->id,
                'created_by' => $user->id,
                'dt_entrada' => now()->toDateString(),
            ]);

            if (!empty($data['pae_empnto_id'])) {
                $empnto = PaeEmpnto::with('empdor:id,nome')->find($data['pae_empnto_id']);
                if ($empnto) {
                    $protocolo->update([
                        'empnto_search' => trim(($empnto->empdor?->nome ? $empnto->empdor->nome . ' - ' : '') . $empnto->nome),
                    ]);
                }
            }

            TimelinePae::registrar($protocolo, 'criacao', 'Protocolo criado no sistema SDC.', $user);

            $this->publicarProtocoloEnviado($protocolo, $user, 'protocolo');

            return $protocolo;
        });
    }

    /**
     * ProtocoloEnviadoV1 no outbox, na MESMA transacao do insert.
     *
     * Executor e creditado coincidem aqui e isso e regra, nao atalho: quem
     * registra o protocolo e quem responde por ele (created_by / user_id). O
     * analista so aparece depois, em atribuir(), e nao herda este marco.
     *
     * A prova de entrega e dt_entrada, coluna de marco -- nunca updated_at.
     */
    public function publicarProtocoloEnviado(PaeProtocolo $protocolo, User $user, string $origem): void
    {
        $this->outbox->persist(new ProtocoloEnviadoV1(
            eventId:       DomainEvent::newId(),
            aggregateType: 'pae_protocolo',
            aggregateId:   (string) $protocolo->id,
            occurredAt:    new \DateTimeImmutable(),
            metadata: [
                'protocolo_id'      => (int) $protocolo->id,
                'num_protocolo'     => $protocolo->num_protocolo,
                'ciclo'             => CicloProtocolo::de($protocolo->num_protocolo),
                'empreendimento_id' => $protocolo->pae_empnto_id === null ? null : (int) $protocolo->pae_empnto_id,
                'origem'            => $origem,
                'actor_user_id'     => (int) $user->id,
                'credited_user_id'  => $protocolo->created_by === null ? (int) $user->id : (int) $protocolo->created_by,
                // Nao existe prazo de envio de protocolo no PAE.
                'prazo_em'          => null,
                'entregue_em'       => $protocolo->dt_entrada?->toDateString(),
            ],
        ));
    }

    public function changeStatus(
        PaeProtocolo $protocolo,
        PaeProtocoloStatus $novo,
        User $user,
        string $obs = ''
    ): PaeProtocolo {
        try {
            return $this->workflow->transitar($protocolo, $novo, $user, $obs);
        } catch (TransicaoProibidaException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }
    }

    /**
     * Atribui o analista e leva o protocolo a NOTIFICACAO pelo caminho da
     * maquina de estados. Antes gravava o status direto, pulando as etapas.
     */
    public function atribuir(PaeProtocolo $protocolo, User $analista, User $user): PaeProtocolo
    {
        if ($protocolo->analista_atual_id === $analista->id) {
            return $protocolo;
        }

        try {
            return DB::transaction(function () use ($protocolo, $analista, $user): PaeProtocolo {
                $statusAnterior = $protocolo->status;
                $protocolo->update(['analista_atual_id' => $analista->id, 'updated_by' => $user->id]);

                $this->workflow->conduzirAte(
                    $protocolo,
                    PaeProtocoloStatus::NOTIFICACAO,
                    $user,
                    "Analista {$analista->name} atribuído."
                );

                TimelinePae::registrar(
                    $protocolo,
                    'atribuicao',
                    "Protocolo atribuído ao analista {$analista->name}. Status: {$statusAnterior->getLabel()} → Notificação.",
                    $user
                );

                return $protocolo->fresh();
            });
        } catch (TransicaoProibidaException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }
    }

    public function relacionar(PaeProtocolo $base, User $user): PaeProtocolo
    {
        return DB::transaction(function () use ($base, $user) {
            $this->travarSequencialProtocolo();

            $prefixo = $this->prefixoVersionavel($base->num_protocolo);

            $novo = PaeProtocolo::create([
                'num_protocolo'       => sprintf('%s-%03d', $prefixo, $this->proximaVersao($prefixo)),
                'status'              => PaeProtocoloStatus::NOVO->value,
                'user_id'             => $user->id,
                'created_by'          => $user->id,
                'dt_entrada'          => now()->toDateString(),
                'pae_empnto_id'       => $base->pae_empnto_id,
                'empnto_search'       => $base->empnto_search,
                'protocolo_origem_id' => $base->id,
            ]);

            TimelinePae::registrar(
                $base,
                'relacionamento',
                "Versao {$novo->num_protocolo} criada a partir deste protocolo.",
                $user
            );
            TimelinePae::registrar(
                $novo,
                'criacao',
                "Protocolo criado como versao relacionada de {$base->num_protocolo}.",
                $user
            );

            return $novo;
        });
    }

    private function prefixoVersionavel(string $numProtocolo): string
    {
        if (preg_match('/^(\d{2}\.\d{2}\.\d{4}-\d{4})-\d{3}$/', $numProtocolo, $m)) {
            return $m[1];
        }

        throw ValidationException::withMessages([
            'protocolo' => 'Somente protocolos no formato dd.mm.aaaa-XXXX-VVV podem ser relacionados.',
        ]);
    }

    private function proximaVersao(string $prefixo): int
    {
        $max = 0;

        PaeProtocolo::withTrashed()
            ->where('num_protocolo', 'like', $prefixo . '-%')
            ->pluck('num_protocolo')
            ->each(function (string $num) use (&$max) {
                if (preg_match('/-(\d{3})$/', $num, $m)) {
                    $max = max($max, (int) $m[1]);
                }
            });

        return $max + 1;
    }

    public function delete(PaeProtocolo $paeProtocolo): void
    {
        $paeProtocolo->delete();
    }

    public function arquivar(PaeProtocolo $paeProtocolo): PaeProtocolo
    {
        if (! $paeProtocolo->arquivado) {
            $paeProtocolo->update(['arquivado' => true]);
        }

        return $paeProtocolo->fresh();
    }

    public function desarquivar(PaeProtocolo $paeProtocolo): PaeProtocolo
    {
        if ($paeProtocolo->arquivado) {
            $paeProtocolo->update(['arquivado' => false]);
        }

        return $paeProtocolo->fresh();
    }

    public function getStatistics(?int $analistaId = null): array
    {
        $base = fn() => $analistaId
            ? PaeProtocolo::ativo()->where('analista_atual_id', $analistaId)
            : PaeProtocolo::ativo();

        return [
            'total' => $base()->count(),
            'novo' => $base()->where('status', PaeProtocoloStatus::NOVO->value)->count(),
            'analise' => $base()->where('status', PaeProtocoloStatus::ANALISE->value)->count(),
            'aprovado' => $base()->where('status', PaeProtocoloStatus::APROVADO->value)->count(),
            'ccpae' => $base()->where('status', PaeProtocoloStatus::CCPAE->value)->count(),
            'ativo_3_anos' => $base()->where('status', PaeProtocoloStatus::ATIVO_3_ANOS->value)->count(),
            'reprovado' => $base()->where('status', PaeProtocoloStatus::REPROVADO->value)->count(),
            'reprovado_sumariamente' => $base()->where('status', PaeProtocoloStatus::REPROVADO_SUMARIAMENTE->value)->count(),
            'vencidos' => $base()->vencidos()->count(),
            'ciclos_esgotados' => $base()->whereNotNull('ciclos_esgotados_em')->count(),
        ];
    }

}
