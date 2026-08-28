<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes;

use App\Models\UserNotificationPreference;
use App\Modules\Notificacoes\Channels\AgrupavelDatabaseChannel;
use App\Modules\Notificacoes\Channels\EmailNotificacaoChannel;
use App\Modules\Notificacoes\Channels\WebPushChannel;
use App\Modules\Notificacoes\Contracts\Agrupavel;
use App\Modules\Notificacoes\DTO\NotificacaoSpec;
use App\Modules\Notificacoes\Support\TextoSeguro;
use App\Modules\Notificacoes\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificacao unica do sistema: alimenta o inbox do sino, o broadcast em tempo
 * real e os canais externos.
 *
 * Os canais normalmente chegam prontos do NotificacaoDispatcher, que resolve as
 * preferencias de todos os destinatarios em UMA query antes do fan-out. Quando a
 * classe e usada diretamente (Notification::send), ela cai no caminho legado e
 * resolve a preferencia do proprio notifiable, custando uma query por destinatario.
 */
class GeneralNotification extends Notification implements Agrupavel, ShouldQueue
{
    use Queueable;

    /**
     * Canais resolvidos pelo dispatcher. Null indica uso direto (caminho legado).
     *
     * @var list<string>|null
     */
    private ?array $canaisResolvidos = null;

    private ?string $moduloResolvido = null;

    /**
     * Estado real da linha depois da escrita, preenchido pelo canal de database.
     * O broadcast usa isso para o cliente exibir "N novos" sem tocar no banco.
     */
    private ?string $notificacaoId = null;

    private int $contadorAgrupado = 1;

    public function __construct(
        public string $title,
        public string $message,
        public string $type = 'info', // info, success, warning, error, urgent
        public ?string $actionUrl = null,
        public ?string $actionText = null,
        public ?string $groupKey = null // agrupamento: "modulo:identificador"
    ) {}

    /**
     * Constroi a notificacao a partir da spec de dominio, com os canais do
     * destinatario ja decididos.
     *
     * @param  list<string>  $canais
     */
    public static function deSpec(NotificacaoSpec $spec, array $canais): self
    {
        $notificacao = new self(
            title: $spec->titulo,
            message: $spec->mensagem,
            type: $spec->tipo,
            actionUrl: $spec->acaoUrl,
            actionText: $spec->acaoTexto,
            groupKey: $spec->groupKey,
        );

        $notificacao->canaisResolvidos = $canais;
        $notificacao->moduloResolvido = $spec->modulo;
        $notificacao->queue = $spec->ehUrgente()
            ? (string) config('notificacoes.entrega.fila_urgente', 'high')
            : (string) config('notificacoes.entrega.fila', 'default');

        return $notificacao;
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        if ($this->canaisResolvidos !== null) {
            return $this->canaisResolvidos;
        }

        return $this->canaisLegado($notifiable);
    }

    /**
     * Traduz uma preferencia em lista de canais. Ponto unico usado tanto pelo
     * caminho legado quanto pelo dispatcher, para nao existirem duas regras.
     *
     * canal_sistema desligado significa "nao quero isso no sino", e por isso
     * remove tambem o broadcast: ele alimenta o mesmo painel.
     *
     * @return list<string>
     */
    public static function canaisPara(UserNotificationPreference $pref): array
    {
        $canais = [];

        if ($pref->canal_sistema) {
            $canais[] = AgrupavelDatabaseChannel::class;
            $canais[] = 'broadcast';
        }

        if ($pref->canal_email) {
            $canais[] = EmailNotificacaoChannel::class;
        }

        if ($pref->canal_push) {
            $canais[] = WebPushChannel::class;
        }

        if ($pref->canal_telegram) {
            $canais[] = TelegramChannel::class;
        }

        // canal_whatsapp entra quando o respectivo channel existir.

        return $canais;
    }

    /**
     * Payload para o EmailNotificacaoChannel.
     *
     * Manda modulo e group_key junto porque o channel precisa deles para a dedup
     * por janela -- e o mesmo criterio que agrupa os cards no sino.
     *
     * @return array<string, mixed>
     */
    public function toMailNotificacao(object $notifiable): array
    {
        $modulo = $this->modulo();

        return [
            'titulo' => TextoSeguro::titulo($this->title),
            'mensagem' => TextoSeguro::mensagem($this->message),
            'tipo' => $this->type,
            'acao_url' => TextoSeguro::url($this->actionUrl),
            'acao_texto' => $this->actionText === null ? null : TextoSeguro::titulo($this->actionText),
            'modulo' => $modulo,
            'modulo_label' => (string) config("notificacoes.modulos.{$modulo}.label", 'SDC'),
            'group_key' => $this->groupKey,
        ];
    }

    /**
     * Payload para o WebPushChannel, consumido por public/sw-push.js.
     *
     * A tag e o group_key: o navegador substitui o aviso anterior de mesma tag em
     * vez de empilhar, que e o equivalente no sistema operacional ao agrupamento
     * que o sino faz numa linha so.
     *
     * @return array<string, mixed>
     */
    public function toWebPush(object $notifiable): array
    {
        return [
            'titulo' => TextoSeguro::titulo($this->title),
            'mensagem' => TextoSeguro::mensagem($this->message),
            'tipo' => $this->type,
            'url' => TextoSeguro::url($this->actionUrl) ?? '/notificacoes',
            'tag' => $this->groupKey ?? $this->modulo(),
        ];
    }

    /**
     * Payload persistido e enviado ao cliente. Sanitizado aqui, na fronteira, para
     * que nenhum caminho de entrada consiga gravar marcacao no inbox.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => TextoSeguro::titulo($this->title),
            'message' => TextoSeguro::mensagem($this->message),
            'type' => $this->type,
            'action_url' => TextoSeguro::url($this->actionUrl),
            'action_text' => $this->actionText === null ? null : TextoSeguro::titulo($this->actionText),
            'group_key' => $this->groupKey,
            'module' => $this->modulo(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }

    /**
     * Payload do websocket: o mesmo do inbox, mais id e contador reais da linha,
     * para o cliente inserir o card no painel sem requisicao extra.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->notificacaoId,
            'group_count' => $this->contadorAgrupado,
            'created_at' => now()->toIso8601String(),
        ] + $this->toArray($notifiable));
    }

    /**
     * Nome do evento no cliente. Sem isso o Echo receberia o nome completo da
     * classe PHP, que o frontend nao deveria precisar conhecer.
     */
    public function broadcastType(): string
    {
        return 'notificacao';
    }

    public function modulo(): string
    {
        return $this->moduloResolvido ??= $this->moduloPeloGroupKey();
    }

    public function chaveDeAgrupamento(): ?string
    {
        return $this->groupKey;
    }

    public function registrarPersistencia(string $notificacaoId, int $contadorAgrupado): void
    {
        $this->notificacaoId = $notificacaoId;
        $this->contadorAgrupado = $contadorAgrupado;
    }

    /**
     * Payload para o TelegramChannel.
     *
     * @return array{text: string, parse_mode: string}
     */
    public function toTelegram(object $notifiable): array
    {
        $emoji = match ($this->type) {
            'success' => '[OK]',
            'warning' => '[!]',
            'error' => '[X]',
            'urgent' => '[URGENTE]',
            default => '[INFO]',
        };

        $titulo = TextoSeguro::titulo($this->title);
        $mensagem = TextoSeguro::mensagem($this->message);
        $url = TextoSeguro::url($this->actionUrl);

        $lines = [
            "*{$emoji} {$this->escapeMarkdown($titulo)}*",
            '',
            $this->escapeMarkdown($mensagem),
        ];

        if ($url !== null) {
            $label = $this->actionText ?: 'Acessar';
            $lines[] = '';
            $lines[] = "[{$this->escapeMarkdown($label)}]({$url})";
        }

        return [
            'text' => implode("\n", $lines),
            'parse_mode' => 'Markdown',
        ];
    }

    /**
     * Caminho legado, para chamadas diretas de Notification::send.
     *
     * @return list<string>
     */
    private function canaisLegado(object $notifiable): array
    {
        $userId = $this->extractUserId($notifiable);

        if ($userId === null) {
            return [AgrupavelDatabaseChannel::class, 'broadcast'];
        }

        $pref = UserNotificationPreference::query()
            ->where('user_id', $userId)
            ->where('module', $this->modulo())
            ->first() ?? UserNotificationPreference::padrao($userId, $this->modulo());

        return self::canaisPara($pref);
    }

    private function extractUserId(object $notifiable): ?int
    {
        if (property_exists($notifiable, 'id') && is_numeric($notifiable->id ?? null)) {
            return (int) $notifiable->id;
        }

        if (method_exists($notifiable, 'getKey')) {
            $key = $notifiable->getKey();

            return is_numeric($key) ? (int) $key : null;
        }

        return null;
    }

    /**
     * Deriva o modulo do prefixo do group_key ("rat:123" -> "rat"). Chave ausente
     * ou prefixo desconhecido caem em "geral", que existe em
     * config('notificacoes.modulos') justamente como destino dos avisos avulsos.
     */
    private function moduloPeloGroupKey(): string
    {
        if ($this->groupKey === null || $this->groupKey === '') {
            return 'geral';
        }

        $prefixo = strtolower((string) strtok($this->groupKey, ':'));

        return array_key_exists($prefixo, (array) config('notificacoes.modulos', []))
            ? $prefixo
            : 'geral';
    }

    private function escapeMarkdown(string $text): string
    {
        // Markdown legacy do Telegram aceita * _ [ ] ( ); escape minimo.
        return str_replace(['_', '*', '[', ']'], ['\\_', '\\*', '\\[', '\\]'], $text);
    }
}
