<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Services;

use App\Models\User;
use App\Models\UserNotificationPreference;
use App\Modules\Notificacoes\DTO\NotificacaoSpec;
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

                try {
                    Notification::sendNow($usuario, GeneralNotification::deSpec($spec, $canais));
                    $notificados[] = $id;
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

        if ($notificados !== []) {
            $this->contador->invalidar($notificados);
        }

        return count($notificados);
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
