<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Channels;

use App\Mail\NotificacaoGenericaMail;
use App\Modules\Notificacoes\Support\JanelaAgrupamento;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Entrega a notificacao por e-mail.
 *
 * Nao usa o canal 'mail' nativo do Laravel por dois motivos concretos:
 *
 * 1. FILA. O NotificacaoDispatcher entrega com Notification::sendNow(), porque ja
 *    roda dentro de um listener enfileirado e os canais locais (inbox, broadcast)
 *    sao baratos. SMTP nao e: com ate 200 destinatarios por lote, mandar sincrono
 *    prenderia o worker por minutos. Aqui a mensagem vai para a fila e o job
 *    original segue.
 *
 * 2. DEDUP. O inbox agrupa eventos do mesmo assunto numa linha so (group_key +
 *    janela do modulo). Sem o mesmo criterio no e-mail, uma rajada de alertas de
 *    meteorologia viraria um card no sino e vinte mensagens na caixa de entrada.
 *    A chave de dedup usa o MESMO bucket de JanelaAgrupamento, entao "um card" e
 *    "um e-mail" significam exatamente a mesma janela de tempo.
 */
class EmailNotificacaoChannel
{
    public function __construct(
        private readonly JanelaAgrupamento $janela,
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toMailNotificacao')) {
            return;
        }

        $email = $this->enderecoDe($notifiable);
        if ($email === null) {
            return;
        }

        /** @var array<string, mixed> $payload */
        $payload = $notification->toMailNotificacao($notifiable);

        $modulo = (string) ($payload['modulo'] ?? 'geral');
        $groupKey = $payload['group_key'] ?? null;

        if (!$this->primeiroDaJanela($notifiable, $modulo, $groupKey)) {
            return;
        }

        try {
            Mail::to($email)->queue(
                (new NotificacaoGenericaMail(
                    titulo: (string) ($payload['titulo'] ?? ''),
                    mensagem: (string) ($payload['mensagem'] ?? ''),
                    tipo: (string) ($payload['tipo'] ?? 'info'),
                    acaoUrl: $payload['acao_url'] ?? null,
                    acaoTexto: $payload['acao_texto'] ?? null,
                    moduloLabel: (string) ($payload['modulo_label'] ?? 'SDC'),
                ))->onQueue($this->fila($payload))
            );
        } catch (\Throwable $e) {
            // Falha ao enfileirar nao pode derrubar os outros canais do mesmo
            // destinatario: o card no sino vale mais do que o e-mail.
            Log::channel('jobs')->warning('Falha ao enfileirar e-mail de notificacao', [
                'modulo' => $modulo,
                'group_key' => $groupKey,
                'erro' => $e->getMessage(),
            ]);
        }
    }

    /**
     * True apenas para o primeiro evento do assunto dentro da janela do modulo.
     *
     * Cache::add e atomico, entao dois eventos simultaneos do mesmo assunto nao
     * geram dois e-mails. Sem group_key nao ha agrupamento nenhum -- e o caso de
     * "seu export terminou", que e evento unico e sempre deve chegar.
     */
    private function primeiroDaJanela(object $notifiable, string $modulo, ?string $groupKey): bool
    {
        if ($groupKey === null || !$this->janela->agrupa($modulo)) {
            return true;
        }

        $bucket = $this->janela->bucket($modulo);
        $chave = sprintf('notif:mail:%s:%s:%s', $this->idDe($notifiable) ?? 'anon', $groupKey, $bucket);

        // TTL uma janela inteira: a chave nunca sobrevive ao bucket que ela protege.
        $ttl = max(60, $this->janela->minutos($modulo) * 60);

        return Cache::add($chave, true, $ttl);
    }

    /**
     * Notificacao urgente sai na fila de maior prioridade, igual ao inbox.
     *
     * @param  array<string, mixed>  $payload
     */
    private function fila(array $payload): string
    {
        return ($payload['tipo'] ?? 'info') === 'urgent'
            ? (string) config('notificacoes.entrega.fila_urgente', 'high')
            : (string) config('notificacoes.entrega.fila', 'default');
    }

    private function enderecoDe(object $notifiable): ?string
    {
        $email = $notifiable->email ?? null;

        if (!is_string($email) || $email === '') {
            return null;
        }

        // Base legada tem endereco malformado em alguns cadastros; mandar assim
        // so gera bounce no SMTP institucional.
        return filter_var($email, FILTER_VALIDATE_EMAIL) === false ? null : $email;
    }

    private function idDe(object $notifiable): ?int
    {
        if (method_exists($notifiable, 'getKey')) {
            $key = $notifiable->getKey();

            return is_numeric($key) ? (int) $key : null;
        }

        return is_numeric($notifiable->id ?? null) ? (int) $notifiable->id : null;
    }
}
