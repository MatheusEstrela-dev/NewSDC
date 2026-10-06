<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Core\Events\DomainEvent;
use App\Core\Outbox\OutboxDispatcher;
use App\Mail\PaeNotificacaoMail;
use App\Models\User;
use App\Modules\Pae\Domain\Events\RevisaoAceitaV1;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeAnalise;
use App\Modules\Pae\Models\PaeDilacao;
use App\Modules\Pae\Models\PaeNotificacao;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Support\CicloProtocolo;
use App\Modules\Pae\Support\Datas;
use App\Modules\Pae\Support\PrazoNotificacao;
use App\Modules\Pae\Support\TimelinePae;
use App\Modules\Notificacoes\DTO\NotificacaoSpec;
use App\Modules\Notificacoes\Jobs\EntregarNotificacaoJob;
use App\Modules\Shared\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class PaeNotificacaoService extends BaseService
{
    /** Mantido para chamadores antigos; a fonte e PrazoNotificacao. */
    public const PRAZO_DIAS = PrazoNotificacao::PRAZO_DIAS;

    /**
     * A renovacao AUTOMATICA para no 3o ciclo. A Resolucao GMG 83/2024 nao
     * limita ciclos; depois disso a decisao e da CEDEC (Art. 139), e a emissao
     * manual continua livre.
     */
    public const MAX_CICLOS_AUTOMATICOS = 3;

    public function __construct(
        private readonly OutboxDispatcher $outbox,
        private readonly PaePrazoService $prazos,
        private readonly PaeComunicacaoService $comunicacoes,
    ) {}

    public function emitir(PaeProtocolo $protocolo, User $user, array $dados, bool $automatica = false): PaeNotificacao
    {
        return DB::transaction(function () use ($protocolo, $user, $dados, $automatica): PaeNotificacao {
            $protocolo = PaeProtocolo::query()->lockForUpdate()->findOrFail($protocolo->getKey());
            $this->assertPodeEmitir($protocolo);

            $analise = PaeAnalise::firstOrCreate(
                ['pae_protocolo_id' => $protocolo->id],
                [
                    'user_id' => $protocolo->analista_atual_id,
                    'status'  => 'EM_ANDAMENTO',
                    'parecer' => '',
                ]
            );

            $ciclo = $analise->notificacoes()->count() + 1;

            if ($automatica && $ciclo > self::MAX_CICLOS_AUTOMATICOS) {
                throw ValidationException::withMessages([
                    'notificacao' => 'A renovacao automatica para no '.self::MAX_CICLOS_AUTOMATICOS.'o ciclo: a proxima notificacao e decisao da CEDEC.',
                ]);
            }

            // A emissao automatica (processarVencimentos) so acontece exatamente
            // porque o ultimo ciclo esta vencido sem devolutiva; nesse caso o
            // ciclo em aberto e a PROPRIA razao da renovacao, entao o bloqueio
            // abaixo se aplica somente a emissao manual. Olha so a ULTIMA
            // notificacao: a renovacao automatica deixa as anteriores sem
            // devolutiva para sempre.
            $ultima = $analise->notificacoes()->reorder()->orderByDesc('dt_notificacao')->orderByDesc('id')->first();
            if (! $automatica && $ultima !== null && $ultima->dt_devolutiva === null) {
                throw ValidationException::withMessages([
                    'notificacao' => 'Existe uma notificacao com prazo em aberto. Registre a devolutiva antes de emitir outra.',
                ]);
            }

            $notificacao = $analise->notificacoes()->create([
                'num_sei'        => $dados['num_sei'],
                'user_id'        => $user->id,
                'dt_notificacao' => now()->toDateString(),
                'prorrogacao'    => false,
                'obs'            => $dados['obs'] ?? null,
            ]);

            // Nova notificacao tira o protocolo da fila "ciclos esgotados" e abre nova pausa.
            PaeProtocolo::query()->whereKey($protocolo->getKey())->update(['ciclos_esgotados_em' => null]);
            $this->prazos->recalcular($protocolo);

            $origem = $automatica ? 'automaticamente por vencimento do ciclo anterior' : "por {$user->name}";
            TimelinePae::registrar(
                $protocolo,
                'notificacao',
                "Notificacao {$ciclo} emitida {$origem}. SEI {$notificacao->num_sei}. Prazo de " . PrazoNotificacao::PRAZO_DIAS . ' dias para devolutiva.',
                $user
            );

            $this->comunicacoes->abrir($protocolo, 'notificacao', $notificacao->id);
            $this->enviarEmail($protocolo, $notificacao, $ciclo, $user);
            $this->avisarAnalistaNoInbox($protocolo, $ciclo, $automatica);

            return $notificacao;
        });
    }

    /**
     * Espelha no inbox do sistema o aviso que ja vai por e-mail.
     *
     * Importa principalmente na emissao automatica (pae:verificar-notificacoes, que
     * roda no schedule): ali nao ha ninguem na tela, e o analista precisa encontrar
     * o fato ao entrar no sistema, sem depender de ter visto o e-mail.
     *
     * Despacha job e retorna: o request que emitiu a notificacao nao espera entrega.
     */
    private function avisarAnalistaNoInbox(PaeProtocolo $protocolo, int $ciclo, bool $automatica): void
    {
        $analista = $protocolo->analista_atual_id ?? $protocolo->user_id;

        if ($analista === null) {
            return;
        }

        $urgente = $ciclo >= self::MAX_CICLOS_AUTOMATICOS;

        EntregarNotificacaoJob::dispatch(
            new NotificacaoSpec(
                modulo: 'pae',
                titulo: $urgente ? 'PAE no ultimo ciclo automatico de notificacao' : 'Notificacao PAE emitida',
                mensagem: sprintf(
                    'Protocolo %s: notificacao %d emitida%s. Prazo de %d dias para devolutiva.',
                    (string) $protocolo->num_protocolo,
                    $ciclo,
                    $automatica ? ' automaticamente' : '',
                    PrazoNotificacao::PRAZO_DIAS,
                ),
                tipo: $urgente ? 'urgent' : 'warning',
                // O modulo pae tem janela 0 em config/notificacoes.php: cada ciclo e
                // um prazo proprio e nao deve ser absorvido por outro card.
                groupKey: null,
                acaoUrl: $protocolo->urlNotificacao(),
                acaoTexto: 'Ver protocolo',
            ),
            [(int) $analista],
        )->afterCommit();
    }

    /** Aviso unico quando a ultima renovacao automatica vence sem devolutiva. */
    private function avisarCiclosEsgotados(PaeProtocolo $protocolo, int $ciclo): void
    {
        $analista = $protocolo->analista_atual_id ?? $protocolo->user_id;

        if ($analista === null) {
            return;
        }

        EntregarNotificacaoJob::dispatch(
            new NotificacaoSpec(
                modulo: 'pae',
                titulo: 'PAE: ciclos de notificacao esgotados',
                mensagem: sprintf(
                    'Protocolo %s: a notificacao %d venceu sem devolutiva. Decisao da CEDEC: suspender, reprovar ou notificar novamente.',
                    (string) $protocolo->num_protocolo,
                    $ciclo,
                ),
                tipo: 'urgent',
                groupKey: null,
                acaoUrl: $protocolo->urlNotificacao(),
                acaoTexto: 'Ver protocolo',
            ),
            [(int) $analista],
        );
    }

    public function registrarDevolutiva(PaeNotificacao $notificacao, User $user, string $dtDevolutiva): PaeNotificacao
    {
        if ($notificacao->dt_devolutiva) {
            throw ValidationException::withMessages([
                'devolutiva' => 'Este ciclo de notificacao ja possui devolutiva registrada.',
            ]);
        }

        if (Datas::dia($dtDevolutiva)->lessThan(Datas::dia($notificacao->dt_notificacao))) {
            throw ValidationException::withMessages([
                'dt_devolutiva' => 'A devolutiva nao pode ser anterior a emissao da notificacao.',
            ]);
        }

        return DB::transaction(function () use ($notificacao, $user, $dtDevolutiva): PaeNotificacao {
            $notificacao->update(['dt_devolutiva' => $dtDevolutiva]);

            $protocolo = $notificacao->analise?->protocolo;
            if ($protocolo) {
                TimelinePae::registrar(
                    $protocolo,
                    'notificacao',
                    "Devolutiva registrada para a notificacao SEI {$notificacao->num_sei} em " .
                        now()->parse($dtDevolutiva)->format('d/m/Y') . '.',
                    $user
                );
            }

            if ($protocolo) {
                PaeProtocolo::query()->whereKey($protocolo->getKey())->update(['ciclos_esgotados_em' => null]);
                $this->prazos->recalcular($protocolo);
            }

            $this->publicarRevisaoAceita($notificacao, $protocolo, $user);

            return $notificacao->fresh();
        });
    }

    /**
     * Dilacao ja decidida pela CEDEC: estende o prazo desta notificacao (Art. 11).
     * O tempo continua fora dos 300 dias porque a diligencia segue aberta.
     */
    public function registrarDilacao(PaeNotificacao $notificacao, int $dias, string $justificativa, User $user): PaeDilacao
    {
        if ($notificacao->dt_devolutiva) {
            throw ValidationException::withMessages([
                'dilacao' => 'Este ciclo ja tem devolutiva: nao cabe dilacao.',
            ]);
        }

        $protocolo = $notificacao->analise?->protocolo
            ?? throw ValidationException::withMessages(['dilacao' => 'Notificacao sem protocolo vinculado.']);

        return DB::transaction(function () use ($notificacao, $dias, $justificativa, $user, $protocolo): PaeDilacao {
            $dilacao = PaeDilacao::create([
                'protocolo_id' => $protocolo->id,
                'pae_notificacao_id' => $notificacao->id,
                'status' => PaeDilacao::STATUS_APROVADA,
                'dias_adicionais' => $dias,
                'justificativa' => $justificativa,
                'aprovado_por' => $user->id,
            ]);

            $notificacao->load('dilacoes');
            $vencimento = PrazoNotificacao::vencimento($notificacao->dt_notificacao, $notificacao->diasDilacao());

            if (! PrazoNotificacao::vencida($notificacao->dt_notificacao, $notificacao->diasDilacao(), null)) {
                PaeProtocolo::query()->whereKey($protocolo->getKey())->update(['ciclos_esgotados_em' => null]);
            }

            TimelinePae::registrar(
                $protocolo,
                'dilacao',
                "Dilacao de {$dias} dias registrada para a notificacao SEI {$notificacao->num_sei}. "
                    ."Novo vencimento: {$vencimento->format('d/m/Y')}. Justificativa: {$justificativa}",
                $user
            );

            $this->prazos->recalcular($protocolo);

            return $dilacao;
        });
    }

    /**
     * RevisaoAceitaV1 no outbox, na MESMA transacao do registro da devolutiva.
     *
     * A guarda de reentrada ja esta acima: devolutiva duas vezes no mesmo ciclo
     * lanca ValidationException e nao chega aqui. Reprocessar o mesmo ciclo
     * produz a mesma chave canonica no adaptador (notificacao + ciclo + marco).
     *
     * O creditado e o servidor que registrou o aceite. A revisao em si vem do
     * empreendimento, que nao e usuario do sistema -- nao existe terceiro a
     * creditar e por isso nao ha validador_user_id aqui.
     *
     * As duas datas sao colunas de marco, nunca updated_at: prazo e
     * dt_notificacao + PRAZO_DIAS (mais dilacoes aprovadas), entrega e dt_devolutiva.
     */
    private function publicarRevisaoAceita(
        PaeNotificacao $notificacao,
        ?PaeProtocolo $protocolo,
        User $user,
    ): void {
        $prazo = $notificacao->dt_notificacao === null
            ? null
            : PrazoNotificacao::vencimento($notificacao->dt_notificacao, $notificacao->diasDilacao());

        $this->outbox->persist(new RevisaoAceitaV1(
            eventId:       DomainEvent::newId(),
            aggregateType: 'pae_notificacao',
            aggregateId:   (string) $notificacao->id,
            occurredAt:    new \DateTimeImmutable(),
            metadata: [
                'notificacao_id'    => (int) $notificacao->id,
                'analise_id'        => $notificacao->pae_analise_id === null ? null : (int) $notificacao->pae_analise_id,
                'protocolo_id'      => $protocolo === null ? null : (int) $protocolo->id,
                // Ciclo real da notificacao (1..n), nao o ciclo do
                // protocolo: aqui o recurso premiado e o ciclo de notificacao.
                'ciclo'             => $this->cicloDaNotificacao($notificacao),
                'ciclo_protocolo'   => CicloProtocolo::de($protocolo?->num_protocolo),
                'num_sei'           => $notificacao->num_sei,
                'actor_user_id'     => (int) $user->id,
                'credited_user_id'  => (int) $user->id,
                'validador_user_id' => null,
                'prazo_em'          => $prazo?->toDateString(),
                'entregue_em'       => $notificacao->fresh()?->dt_devolutiva?->toDateString(),
            ],
        ));
    }

    /**
     * Posicao da notificacao na fila da analise (1..n), a mesma
     * contagem que listarPorProtocolo mostra na tela. Sem analise vinculada
     * cai em 1 -- o ciclo compoe a chave canonica e nao pode ficar indefinido.
     */
    private function cicloDaNotificacao(PaeNotificacao $notificacao): int
    {
        $analise = $notificacao->analise;

        if ($analise === null) {
            return 1;
        }

        $ids = array_map('intval', $analise->notificacoes()->pluck('id')->all());
        $posicao = array_search((int) $notificacao->id, $ids, true);

        return $posicao === false ? 1 : $posicao + 1;
    }

    public function processarVencimentos(): int
    {
        $processadas = 0;

        $analises = PaeAnalise::query()
            ->whereHas('notificacoes', fn ($q) => $q->whereNull('dt_devolutiva'))
            ->with(['notificacoes.dilacoes', 'protocolo.analistaAtual', 'protocolo.usuario', 'protocolo.empreendimento'])
            ->get();

        foreach ($analises as $analise) {
            $protocolo = $analise->protocolo;

            if (! $protocolo || $protocolo->arquivado || $protocolo->status->isTerminal()
                || $protocolo->status === PaeProtocoloStatus::SUSPENSO) {
                continue;
            }

            $ultima = $analise->notificacoes->last();

            if (! $ultima || ! PrazoNotificacao::vencida($ultima->dt_notificacao, $ultima->diasDilacao(), $ultima->dt_devolutiva)) {
                continue;
            }

            $autor = $protocolo->analistaAtual ?? $protocolo->usuario;
            $ciclo = $analise->notificacoes->count();

            if ($ciclo >= self::MAX_CICLOS_AUTOMATICOS) {
                // Sinaliza uma vez; a decisao (suspender, reprovar, notificar) e da CEDEC (Art. 139).
                if ($protocolo->ciclos_esgotados_em === null) {
                    PaeProtocolo::query()->whereKey($protocolo->getKey())->update(['ciclos_esgotados_em' => now()->toDateString()]);
                    TimelinePae::registrar(
                        $protocolo,
                        'ciclos_esgotados',
                        "Notificacao {$ciclo} vencida sem devolutiva. Ciclos automaticos esgotados: aguardando decisao da CEDEC.",
                        $autor
                    );
                    $this->avisarCiclosEsgotados($protocolo, $ciclo);
                    $processadas++;
                }

                continue;
            }

            $this->emitir(
                $protocolo,
                $autor,
                [
                    'num_sei' => $ultima->num_sei,
                    'obs' => 'Emitida automaticamente: ciclo '.$ciclo.' vencido sem devolutiva.',
                ],
                true
            );
            $processadas++;
        }

        return $processadas;
    }

    public function listarPorProtocolo(PaeProtocolo $protocolo): array
    {
        $analise = PaeAnalise::with('notificacoes.dilacoes.aprovador:id,name')
            ->where('pae_protocolo_id', $protocolo->id)
            ->first();

        if (! $analise) {
            return [];
        }

        return $analise->notificacoes
            ->values()
            ->map(function (PaeNotificacao $n, int $i): array {
                $dias = $n->diasDilacao();

                return [
                    'id' => $n->id,
                    'ciclo' => $i + 1,
                    'num_sei' => $n->num_sei,
                    'dt_notificacao' => $n->dt_notificacao->toDateString(),
                    'prazo_final' => PrazoNotificacao::vencimento($n->dt_notificacao, $dias)->toDateString(),
                    'dias_dilacao' => $dias,
                    'dt_devolutiva' => $n->dt_devolutiva?->toDateString(),
                    'vencida' => PrazoNotificacao::vencida($n->dt_notificacao, $dias, $n->dt_devolutiva),
                    'obs' => $n->obs,
                    'dilacoes' => $n->dilacoes->map(fn (PaeDilacao $d): array => [
                        'id' => $d->id,
                        'dias_adicionais' => $d->dias_adicionais,
                        'justificativa' => $d->justificativa,
                        'aprovado_por' => $d->aprovador?->name,
                        'registrada_em' => $d->created_at?->toDateString(),
                    ])->values()->all(),
                ];
            })
            ->all();
    }

    private function assertPodeEmitir(PaeProtocolo $protocolo): void
    {
        if (! $protocolo->analista_atual_id) {
            throw ValidationException::withMessages([
                'notificacao' => 'Delegue o protocolo a um analista antes de emitir notificacoes.',
            ]);
        }

        if ($protocolo->arquivado || $protocolo->status->isTerminal()) {
            throw ValidationException::withMessages([
                'notificacao' => 'Protocolo arquivado ou encerrado nao recebe notificacoes.',
            ]);
        }
    }

    private function enviarEmail(PaeProtocolo $protocolo, PaeNotificacao $notificacao, int $ciclo, User $user): void
    {
        $protocolo->loadMissing('empreendimento');
        $empnto = $protocolo->empreendimento;

        if (! $empnto?->email_coord) {
            TimelinePae::registrar(
                $protocolo,
                'notificacao',
                'Empreendimento sem e-mail de coordenador cadastrado: notificacao registrada apenas no sistema.',
                $user
            );

            return;
        }

        $mail = Mail::to($empnto->email_coord);

        if ($empnto->email_coord_sub) {
            $mail->cc($empnto->email_coord_sub);
        }

        $mail->queue(new PaeNotificacaoMail(
            protocoloNumero: $protocolo->num_protocolo,
            empreendimentoNome: $empnto->nome ?? '',
            ciclo: $ciclo,
            numSei: $notificacao->num_sei,
            dtNotificacao: $notificacao->dt_notificacao->toDateString(),
            prazoFinal: PrazoNotificacao::vencimento($notificacao->dt_notificacao)->toDateString(),
        )->afterCommit());
    }
}
