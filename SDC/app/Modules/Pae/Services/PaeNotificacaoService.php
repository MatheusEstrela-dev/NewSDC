<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Mail\PaeNotificacaoMail;
use App\Models\User;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeAnalise;
use App\Modules\Pae\Models\PaeNotificacao;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Models\PaeTimeline;
use App\Modules\Shared\BaseService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class PaeNotificacaoService extends BaseService
{
    public const PRAZO_DIAS = 30;
    public const MAX_CICLOS = 3;

    public function __construct(
        private readonly PaeProtocoloService $protocoloService
    ) {}

    public function emitir(PaeProtocolo $protocolo, User $user, array $dados, bool $automatica = false): PaeNotificacao
    {
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

        if ($ciclo > self::MAX_CICLOS) {
            throw ValidationException::withMessages([
                'notificacao' => 'Limite de ' . self::MAX_CICLOS . ' notificacoes atingido para este protocolo.',
            ]);
        }

        // A emissao automatica (processarVencimentos) so acontece exatamente
        // porque o ultimo ciclo esta vencido sem devolutiva; nesse caso o
        // ciclo em aberto e a PROPRIA razao da renovacao, entao o bloqueio
        // abaixo se aplica somente a emissao manual.
        if (! $automatica && $analise->notificacoes()->whereNull('dt_devolutiva')->exists()) {
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

        $origem = $automatica ? 'automaticamente por vencimento do ciclo anterior' : "por {$user->name}";
        $this->registrarTimeline(
            $protocolo,
            'notificacao',
            "Notificacao {$ciclo} emitida {$origem}. SEI {$notificacao->num_sei}. Prazo de " . self::PRAZO_DIAS . ' dias para devolutiva.',
            $user
        );

        $this->enviarEmail($protocolo, $notificacao, $ciclo, $user);

        return $notificacao;
    }

    public function registrarDevolutiva(PaeNotificacao $notificacao, User $user, string $dtDevolutiva): PaeNotificacao
    {
        if ($notificacao->dt_devolutiva) {
            throw ValidationException::withMessages([
                'devolutiva' => 'Este ciclo de notificacao ja possui devolutiva registrada.',
            ]);
        }

        $notificacao->update(['dt_devolutiva' => $dtDevolutiva]);

        $protocolo = $notificacao->analise?->protocolo;
        if ($protocolo) {
            $this->registrarTimeline(
                $protocolo,
                'notificacao',
                "Devolutiva registrada para a notificacao SEI {$notificacao->num_sei} em " .
                    now()->parse($dtDevolutiva)->format('d/m/Y') . '.',
                $user
            );
        }

        return $notificacao->fresh();
    }

    public function processarVencimentos(): int
    {
        $processadas = 0;

        $analises = PaeAnalise::query()
            ->whereHas('notificacoes', fn ($q) => $q->whereNull('dt_devolutiva'))
            ->with(['notificacoes', 'protocolo.analistaAtual', 'protocolo.usuario', 'protocolo.empreendimento'])
            ->get();

        foreach ($analises as $analise) {
            $protocolo = $analise->protocolo;

            if (! $protocolo || $protocolo->arquivado || $protocolo->status->isTerminal()) {
                continue;
            }

            if ($protocolo->status === PaeProtocoloStatus::SUSPENSO) {
                continue;
            }

            $ultima = $analise->notificacoes->last();

            if (! $ultima || $ultima->dt_devolutiva) {
                continue;
            }

            $vencida = $ultima->dt_notificacao
                ->copy()
                ->addDays(self::PRAZO_DIAS)
                ->isBefore(now()->startOfDay());

            if (! $vencida) {
                continue;
            }

            $autor = $protocolo->analistaAtual ?? $protocolo->usuario;
            $ciclo = $analise->notificacoes->count();

            if ($ciclo >= self::MAX_CICLOS) {
                $this->protocoloService->changeStatus(
                    $protocolo,
                    PaeProtocoloStatus::SUSPENSO,
                    $autor,
                    'Suspenso automaticamente: 3a notificacao vencida sem devolutiva.'
                );
            } else {
                $this->emitir(
                    $protocolo,
                    $autor,
                    [
                        'num_sei' => $ultima->num_sei,
                        'obs'     => 'Emitida automaticamente: ciclo ' . $ciclo . ' vencido sem devolutiva.',
                    ],
                    true
                );
            }

            $processadas++;
        }

        return $processadas;
    }

    public function listarPorProtocolo(PaeProtocolo $protocolo): array
    {
        $analise = PaeAnalise::with('notificacoes')
            ->where('pae_protocolo_id', $protocolo->id)
            ->first();

        if (! $analise) {
            return [];
        }

        return $analise->notificacoes
            ->values()
            ->map(fn (PaeNotificacao $n, int $i) => [
                'id'             => $n->id,
                'ciclo'          => $i + 1,
                'num_sei'        => $n->num_sei,
                'dt_notificacao' => $n->dt_notificacao->toDateString(),
                'prazo_final'    => $n->dt_notificacao->copy()->addDays(self::PRAZO_DIAS)->toDateString(),
                'dt_devolutiva'  => $n->dt_devolutiva?->toDateString(),
                'vencida'        => ! $n->dt_devolutiva
                    && $n->dt_notificacao->copy()->addDays(self::PRAZO_DIAS)->isBefore(now()->startOfDay()),
                'obs'            => $n->obs,
            ])
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
            $this->registrarTimeline(
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
            prazoFinal: $notificacao->dt_notificacao->copy()->addDays(self::PRAZO_DIAS)->toDateString(),
        ));
    }

    private function registrarTimeline(PaeProtocolo $protocolo, string $evento, string $descricao, User $user): void
    {
        PaeTimeline::create([
            'protocolo_id' => $protocolo->id,
            'evento'       => $evento,
            'descricao'    => $descricao,
            'user_id'      => $user->id,
        ]);
    }
}
