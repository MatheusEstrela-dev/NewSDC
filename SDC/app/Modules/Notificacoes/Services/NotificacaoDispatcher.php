<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Services;

use App\Models\User;
use App\Models\UserNotificationPreference;
use App\Modules\Notificacoes\Channels\AgrupavelDatabaseChannel;
use App\Modules\Notificacoes\Channels\EmailNotificacaoChannel;
use App\Modules\Notificacoes\Channels\WebPushChannel;
use App\Modules\Notificacoes\DTO\NotificacaoSpec;
use App\Modules\Notificacoes\Jobs\EnviarEmailNotificacaoJob;
use App\Modules\Notificacoes\Jobs\EnviarWebPushJob;
use App\Modules\Notificacoes\Support\JanelaAgrupamento;
use App\Modules\Notificacoes\GeneralNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Porta de entrada unica do sistema de notificacoes.
 *
 * Todo gatilho de dominio passa por aqui. Nenhum modulo deve montar canais,
 * consultar preferencia ou falar com o canal de database por conta propria: isso
 * garante uma regra so para agrupamento, preferencia, sanitizacao e contador.
 *
 * Custo do fan-out para N destinatarios:
 * - 1 query para carregar os usuarios (quando vem id)
 * - 1 query para carregar as preferencias de todos
 * - 1 escrita por destinatario que aceita o canal
 * - 1 invalidacao de contador em lote
 *
 * Nao ha um job por (destinatario x canal): a entrega usa sendNow porque este
 * servico ja e chamado de dentro de um listener enfileirado. A resposta HTTP do
 * usuario que disparou a acao nunca espera por nada disso.
 */
class NotificacaoDispatcher
{
    public function __construct(
        private readonly ContadorNaoLidas $contador,
        private readonly JanelaAgrupamento $janela,
    ) {}

    /**
     * Entrega a notificacao aos destinatarios e devolve quantos foram notificados.
     *
     * @param  iterable<Model|int|string>  $destinatarios  models ou ids de usuario
     */
    public function enviar(NotificacaoSpec $spec, iterable $destinatarios): int
    {
        $spec = $this->ajustarAgrupamento($spec);
        $usuarios = $this->resolverDestinatarios($destinatarios);

        if ($usuarios->isEmpty()) {
            return 0;
        }

        $preferencias = UserNotificationPreference::paraUsuarios(
            $usuarios->map(fn (Model $u) => (int) $u->getKey())->all(),
            $spec->modulo,
        );

        $notificados = [];
        $comInbox = [];
        // Canais de rede acumulam destinatarios e saem em lote depois do loop.
        $emLote = [WebPushChannel::class => [], EmailNotificacaoChannel::class => []];
        $chunk = max(1, (int) config('notificacoes.entrega.chunk_destinatarios', 200));

        foreach ($usuarios->chunk($chunk) as $lote) {
            foreach ($lote as $usuario) {
                $id = (int) $usuario->getKey();

                $pref = $preferencias->get($id)
                    ?? UserNotificationPreference::padrao($id, $spec->modulo);

                $canais = GeneralNotification::canaisPara($pref);

                if ($canais === []) {
                    continue;
                }

                // Canais locais (inbox, broadcast) sao baratos e vao agora.
                // Push e e-mail fazem I/O de rede: enfileirar um job por pessoa
                // era o que fazia um disparo em massa entupir a fila.
                $imediatos = [];

                foreach ($canais as $canal) {
                    if (array_key_exists($canal, $emLote)) {
                        $emLote[$canal][] = $id;
                    } else {
                        $imediatos[] = $canal;
                    }
                }

                $notificados[] = $id;

                if ($imediatos === []) {
                    continue;
                }

                try {
                    Notification::sendNow($usuario, GeneralNotification::deSpec($spec, $imediatos));

                    if (in_array(AgrupavelDatabaseChannel::class, $imediatos, true)) {
                        $comInbox[] = $id;
                    }
                } catch (\Throwable $e) {
                    // Falha de um destinatario nao derruba o lote: o job seria
                    // reprocessado inteiro e reenviaria a quem ja recebeu.
                    Log::channel('jobs')->error('Falha ao notificar destinatario', [
                        'user_id' => $id,
                        'modulo' => $spec->modulo,
                        'group_key' => $spec->groupKey,
                        'erro' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->despacharEmLote($spec, $emLote, $chunk);

        // Invalida o contador so de quem realmente ganhou linha no inbox: quem
        // recebeu apenas por e-mail ou push nao teve o badge alterado.
        if ($comInbox !== []) {
            $this->contador->invalidar($comInbox);
        }

        return count($notificados);
    }

    /**
     * Um job por canal a cada chunk de destinatarios, em vez de um job por pessoa.
     *
     * @param  array<class-string, list<int>>  $emLote
     */
    private function despacharEmLote(NotificacaoSpec $spec, array $emLote, int $chunk): void
    {
        $payloadPush = null;

        foreach (array_chunk($emLote[WebPushChannel::class], $chunk) as $ids) {
            // O payload nao depende do destinatario, entao e montado uma vez.
            $payloadPush ??= GeneralNotification::deSpec($spec, [])->toWebPush();

            EnviarWebPushJob::dispatch($ids, $payloadPush, $spec->ehUrgente());
        }

        foreach (array_chunk($emLote[EmailNotificacaoChannel::class], $chunk) as $ids) {
            EnviarEmailNotificacaoJob::dispatch($ids, $spec);
        }
    }

    /**
     * Atalho para o caso mais comum: um unico destinatario.
     */
    public function enviarPara(NotificacaoSpec $spec, Model|int|string $destinatario): bool
    {
        return $this->enviar($spec, [$destinatario]) === 1;
    }

    /**
     * Modulo com janela zero nao agrupa: a chave e descartada aqui, de modo que o
     * canal receba a spec ja coerente e o group_key nao fique gravado sem uso.
     */
    private function ajustarAgrupamento(NotificacaoSpec $spec): NotificacaoSpec
    {
        if ($spec->groupKey !== null && !$this->janela->agrupa($spec->modulo)) {
            return $spec->semAgrupamento();
        }

        return $spec;
    }

    /**
     * Normaliza a entrada para models de usuario, sem duplicatas.
     *
     * @param  iterable<Model|int|string>  $destinatarios
     * @return \Illuminate\Support\Collection<int, Model>
     */
    private function resolverDestinatarios(iterable $destinatarios): \Illuminate\Support\Collection
    {
        $models = collect();
        $ids = [];

        foreach ($destinatarios as $destinatario) {
            if ($destinatario instanceof Model) {
                $models->push($destinatario);

                continue;
            }

            if (is_numeric($destinatario)) {
                $ids[] = (int) $destinatario;
            }
        }

        if ($ids !== []) {
            $jaCarregados = $models->map(fn (Model $u) => (int) $u->getKey())->all();
            $faltantes = array_values(array_diff(array_unique($ids), $jaCarregados));

            if ($faltantes !== []) {
                $models = $models->merge(User::query()->whereIn('id', $faltantes)->get());
            }
        }

        return $models->unique(fn (Model $u) => $u->getMorphClass().':'.$u->getKey())->values();
    }
}
