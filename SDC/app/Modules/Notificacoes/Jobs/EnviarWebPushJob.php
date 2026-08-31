<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Jobs;

use App\Models\PushSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Entrega um payload de push a todas as inscricoes de um LOTE de usuarios.
 *
 * Roda em job, e nao dentro do Notification::sendNow() do dispatcher, porque
 * cada inscricao e uma requisicao HTTP para um push service externo (FCM,
 * Mozilla, WNS). Segurar o worker do fan-out esperando rede seria o mesmo
 * problema do e-mail, multiplicado pelo numero de dispositivos.
 *
 * Recebe uma LISTA de destinatarios, nao um so: um disparo para mil pessoas
 * criava mil jobs, cada um com o proprio SELECT. Agora sao mil/chunk jobs e uma
 * query por job. O envio individual segue existindo pelo WebPushChannel, que
 * despacha este mesmo job com um id apenas -- uma implementacao, duas entradas.
 *
 * Endpoint morto e apagado aqui. Sem isso a tabela so cresce: o app.js
 * desregistra o service worker na recuperacao de build velho e no 419, entao o
 * navegador volta com endpoint novo e o antigo fica orfao para sempre,
 * consumindo uma requisicao HTTP em todo envio futuro.
 */
class EnviarWebPushJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @param  list<int>  $userIds  destinatarios deste lote
     * @param  array<string, mixed>  $payload  o que o service worker recebe
     */
    public function __construct(
        private readonly array $userIds,
        private readonly array $payload,
        private readonly bool $urgente = false,
    ) {
        $this->onQueue($urgente
            ? (string) config('notificacoes.entrega.fila_urgente', 'notificacoes_urgente')
            : (string) config('notificacoes.entrega.fila', 'notificacoes'));
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return (array) config('notificacoes.entrega.backoff_segundos', [10, 30, 60]);
    }

    public function handle(): void
    {
        if ($this->userIds === []) {
            return;
        }

        // UMA query para o lote inteiro. Antes era um job (e uma query) por
        // destinatario: mil pessoas geravam mil jobs e mil SELECTs.
        $inscricoes = PushSubscription::query()
            ->whereIn('user_id', $this->userIds)
            ->get();

        if ($inscricoes->isEmpty()) {
            return;
        }

        $webPush = $this->cliente();
        if ($webPush === null) {
            return;
        }

        $porEndpoint = [];
        $corpo = json_encode($this->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';

        foreach ($inscricoes as $inscricao) {
            $porEndpoint[$inscricao->endpoint] = $inscricao;
            $webPush->queueNotification(Subscription::create($inscricao->paraEnvio()), $corpo);
        }

        $expirados = [];
        $entregues = [];

        try {
            foreach ($webPush->flush() as $relatorio) {
                $inscricao = $porEndpoint[$relatorio->getEndpoint()] ?? null;

                if ($inscricao === null) {
                    continue;
                }

                if ($relatorio->isSuccess()) {
                    $entregues[] = $inscricao->getKey();

                    continue;
                }

                // 404/410: o navegador desinscreveu ou trocou de endpoint.
                if ($relatorio->isSubscriptionExpired()) {
                    $expirados[] = $inscricao->getKey();

                    continue;
                }

                // Demais falhas sao transitorias (rede, 5xx do push service). Nao
                // apagar: o dispositivo continua valido.
                Log::channel('jobs')->warning('Falha ao enviar web push', [
                    'user_ids' => $this->userIds,
                    'motivo' => $relatorio->getReason(),
                ]);
            }
        } catch (\Throwable $e) {
            // flush() e um gerador: se a criptografia de UMA inscricao explode
            // ("Unable to compute the agreement key" para uma chave corrompida), o
            // lote inteiro morre e os dispositivos validos do usuario ficam sem
            // receber. Cair para envio individual isola a linha ruim.
            Log::channel('jobs')->warning('Lote de web push falhou, tentando um a um', [
                'user_ids' => $this->userIds,
                'erro' => $e->getMessage(),
            ]);

            [$expirados, $entregues] = $this->enviarUmAUm($inscricoes, $corpo);
        }

        if ($expirados !== []) {
            PushSubscription::query()->whereIn('id', $expirados)->delete();
        }

        if ($entregues !== []) {
            PushSubscription::query()->whereIn('id', $entregues)->update(['last_used_at' => now()]);
        }
    }

    /**
     * Reenvia inscricao por inscricao, cada uma isolada em seu try.
     *
     * Usado so quando o lote falhou. Mais lento, mas garante que uma linha
     * corrompida nao impeca a entrega nos demais dispositivos do usuario.
     *
     * @param  iterable<PushSubscription>  $inscricoes
     * @return array{0: list<int>, 1: list<int>}  [expirados, entregues]
     */
    private function enviarUmAUm(iterable $inscricoes, string $corpo): array
    {
        $expirados = [];
        $entregues = [];

        foreach ($inscricoes as $inscricao) {
            $cliente = $this->cliente();

            if ($cliente === null) {
                break;
            }

            try {
                $relatorio = $cliente->sendOneNotification(
                    Subscription::create($inscricao->paraEnvio()),
                    $corpo,
                );

                if ($relatorio->isSuccess()) {
                    $entregues[] = $inscricao->getKey();
                } elseif ($relatorio->isSubscriptionExpired()) {
                    $expirados[] = $inscricao->getKey();
                }
            } catch (\Throwable $e) {
                // Inscricao que nem chega a ser cifravel esta inutilizavel: o
                // navegador teria de se reinscrever de qualquer forma. Sai da
                // tabela para nao custar uma tentativa em todo envio futuro.
                $expirados[] = $inscricao->getKey();

                Log::channel('jobs')->warning('Inscricao de push invalida, removendo', [
                    'user_ids' => $this->userIds,
                    'subscription_id' => $inscricao->getKey(),
                    'erro' => $e->getMessage(),
                ]);
            }
        }

        return [$expirados, $entregues];
    }

    /**
     * Cliente configurado, ou null quando o servidor nao tem VAPID. Null nao e
     * erro: e um ambiente sem push, e CanaisDisponiveis ja impede o usuario de
     * ligar o canal nessa situacao.
     */
    private function cliente(): ?WebPush
    {
        $publica = (string) config('webpush.vapid.public_key');
        $privada = (string) config('webpush.vapid.private_key');

        if ($publica === '' || $privada === '') {
            return null;
        }

        try {
            return new WebPush(
                auth: [
                    'VAPID' => [
                        'subject' => (string) config('webpush.vapid.subject'),
                        'publicKey' => $publica,
                        'privateKey' => $privada,
                    ],
                ],
                defaultOptions: [
                    'TTL' => (int) config('webpush.ttl_segundos', 43200),
                    'urgency' => $this->urgente
                        ? (string) config('webpush.urgencia_urgente', 'high')
                        : (string) config('webpush.urgencia_padrao', 'normal'),
                ],
            );
        } catch (\Throwable $e) {
            Log::channel('jobs')->error('VAPID invalido, web push desabilitado', [
                'erro' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
