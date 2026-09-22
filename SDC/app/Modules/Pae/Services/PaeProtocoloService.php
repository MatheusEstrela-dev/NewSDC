<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Core\Events\DomainEvent;
use App\Core\Outbox\OutboxDispatcher;
use App\Models\User;
use App\Modules\Pae\Domain\Events\ParecerConcluidoV1;
use App\Modules\Pae\Domain\Events\ProtocoloEnviadoV1;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeEmpnto;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Models\PaeTramitacao;
use App\Modules\Pae\Models\PaeTimeline;
use App\Modules\Pae\Support\CicloProtocolo;
use App\Modules\Shared\BaseService;
use App\Support\Database\PgCompat;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaeProtocoloService extends BaseService
{
    public function __construct(
        private readonly OutboxDispatcher $outbox,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PaeProtocolo::query()
            ->with(['analistaAtual:id,name', 'empreendimento:id,pae_empdor_id,nome', 'empreendimento.empdor:id,nome']);

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

        if (!empty($filters['analista_id'])) {
            $query->where('analista_atual_id', $filters['analista_id']);
        }

        if (!empty($filters['data_inicio'])) {
            $query->where('dt_entrada', '>=', $filters['data_inicio']);
        }

        if (!empty($filters['data_fim'])) {
            $query->where('dt_entrada', '<=', $filters['data_fim']);
        }

        return $query->orderBy('dt_entrada', 'desc')->paginate($perPage);
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

            $this->registrarTimeline($protocolo, 'criacao', 'Protocolo criado no sistema SDC.', $user);

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
        if ($protocolo->status === $novo) {
            return $protocolo->fresh();
        }

        $podeConcluirParaCcpae = $novo === PaeProtocoloStatus::CCPAE
            && in_array($protocolo->status, [
                PaeProtocoloStatus::NOVO,
                PaeProtocoloStatus::ENTRADA_PROCESSO,
                PaeProtocoloStatus::CRIACAO_SDC,
                PaeProtocoloStatus::GERENCIAMENTO,
                PaeProtocoloStatus::NOTIFICACAO,
                PaeProtocoloStatus::ANALISE,
                PaeProtocoloStatus::APROVADO,
                PaeProtocoloStatus::ESPERAR_TRATATIVA,
                PaeProtocoloStatus::DILACAO,
            ], true);

        if (!$protocolo->validarTransicaoStatus($novo) && !$podeConcluirParaCcpae) {
            throw ValidationException::withMessages([
                'status' => "Transição inválida: {$protocolo->status->getLabel()} → {$novo->getLabel()}.",
            ]);
        }

        $statusAnterior = $protocolo->status;
        $estavaArquivado = (bool) $protocolo->arquivado;
        $desarquivarAuto = $estavaArquivado && $novo === PaeProtocoloStatus::CCPAE;

        $atributos = [
            'status' => $novo->value,
            'updated_by' => $user->id,
        ];

        // Validar (transitar para CCPAE) reativa um protocolo arquivado:
        // ao validar para CCPAE, o protocolo volta para fluxo ativo.
        if ($desarquivarAuto) {
            $atributos['arquivado'] = false;
        }

        return DB::transaction(function () use ($protocolo, $novo, $user, $obs, $statusAnterior, $atributos, $desarquivarAuto): PaeProtocolo {
            $protocolo->update($atributos);

            $tramitacao = PaeTramitacao::create([
                'protocolo_id' => $protocolo->id,
                'user_id' => $user->id,
                'status' => $novo->value,
                'obs' => $obs ?: null,
            ]);

            $descricaoTimeline = "Status alterado de '{$statusAnterior->getLabel()}' para '{$novo->getLabel()}'. {$obs}";
            if ($desarquivarAuto) {
                $descricaoTimeline .= ' Protocolo desarquivado automaticamente pela transicao para CCPAE.';
            }

            $this->registrarTimeline(
                $protocolo,
                'status_alterado',
                $descricaoTimeline,
                $user
            );

            if ($statusAnterior === PaeProtocoloStatus::ANALISE
                && in_array($novo, [PaeProtocoloStatus::APROVADO, PaeProtocoloStatus::REPROVADO], true)) {
                $this->publicarParecerConcluido($protocolo, $statusAnterior, $novo, $user, $tramitacao);
            }

            return $protocolo->fresh();
        });
    }

    /**
     * ParecerConcluidoV1 no outbox, na MESMA transacao da transicao.
     *
     * A saida de ANALISE para APROVADO/REPROVADO e o unico ponto do codigo em
     * que a analise se fecha com decisao: a coluna pae_analises.parecer existe
     * mas nenhuma rota, controller ou service jamais escreve nela.
     *
     * O creditado e o PROPRIO analista que emitiu o parecer -- ato sem terceiro
     * a premiar, por isso validador_user_id nao entra.
     *
     * Entrega comprovada pelo dt_status da tramitacao recem-gravada (default do
     * banco, por isso o refresh) e prazo por limite_analise. Sem a tramitacao
     * legivel, o evento sai sem data e o fato vai a apuracao -- updated_at nao
     * substitui marco.
     */
    private function publicarParecerConcluido(
        PaeProtocolo $protocolo,
        PaeProtocoloStatus $statusAnterior,
        PaeProtocoloStatus $novo,
        User $user,
        PaeTramitacao $tramitacao,
    ): void {
        $this->outbox->persist(new ParecerConcluidoV1(
            eventId:       DomainEvent::newId(),
            aggregateType: 'pae_protocolo',
            aggregateId:   (string) $protocolo->id,
            occurredAt:    new \DateTimeImmutable(),
            metadata: [
                'protocolo_id'     => (int) $protocolo->id,
                'num_protocolo'    => $protocolo->num_protocolo,
                'ciclo'            => CicloProtocolo::de($protocolo->num_protocolo),
                'decisao'          => $novo->value,
                'status_anterior'  => $statusAnterior->value,
                'actor_user_id'    => (int) $user->id,
                'credited_user_id' => (int) $user->id,
                'prazo_em'         => $protocolo->limite_analise?->toDateString(),
                'entregue_em'      => $tramitacao->fresh()?->dt_status?->toIso8601String(),
            ],
        ));
    }

    public function atribuir(PaeProtocolo $protocolo, User $analista, User $user): PaeProtocolo
    {
        $analistaJaAtribuido = $protocolo->analista_atual_id === $analista->id;
        $statusAnterior = $protocolo->status;
        $statusJaNotificacao = $statusAnterior === PaeProtocoloStatus::NOTIFICACAO;

        if ($analistaJaAtribuido) {
            return $protocolo;
        }

        $protocolo->update([
            'analista_atual_id' => $analista->id,
            'status' => PaeProtocoloStatus::NOTIFICACAO->value,
            'updated_by' => $user->id,
        ]);

        if (!$statusJaNotificacao) {
            PaeTramitacao::create([
                'protocolo_id' => $protocolo->id,
                'user_id' => $user->id,
                'status' => PaeProtocoloStatus::NOTIFICACAO->value,
                'obs' => "Analista {$analista->name} atribuído.",
            ]);
        }

        $descricao = $analistaJaAtribuido
            ? "Analista reatribuído: {$analista->name}."
            : "Protocolo atribuído ao analista {$analista->name}. Status: {$statusAnterior->getLabel()} → Notificação.";

        $this->registrarTimeline($protocolo, 'atribuicao', $descricao, $user);

        return $protocolo->fresh();
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

            $this->registrarTimeline(
                $base,
                'relacionamento',
                "Versao {$novo->num_protocolo} criada a partir deste protocolo.",
                $user
            );
            $this->registrarTimeline(
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
            'vencidos' => $base()
                ->whereNotNull('limite_analise')
                ->where('limite_analise', '<', now()->toDateString())
                ->whereNotIn('status', [
                    PaeProtocoloStatus::APROVADO->value,
                    PaeProtocoloStatus::CCPAE->value,
                    PaeProtocoloStatus::ATIVO_3_ANOS->value,
                    PaeProtocoloStatus::REPROVADO->value,
                    PaeProtocoloStatus::REVOGADO->value,
                ])
                ->count(),
        ];
    }

    private function registrarTimeline(PaeProtocolo $protocolo, string $evento, string $descricao, User $user): void
    {
        PaeTimeline::create([
            'protocolo_id' => $protocolo->id,
            'evento' => $evento,
            'descricao' => $descricao,
            'user_id' => $user->id,
        ]);
    }
}
