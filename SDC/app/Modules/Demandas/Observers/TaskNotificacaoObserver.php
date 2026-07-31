<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Observers;

use App\Modules\Demandas\Enums\TaskStatus;
use App\Modules\Demandas\Models\Task;
use App\Modules\Notificacoes\DTO\NotificacaoSpec;
use App\Modules\Notificacoes\Jobs\EntregarNotificacaoJob;

/**
 * Avisa quem tem interesse na demanda quando ela muda de mao ou de estado.
 *
 * Fica num observer, e nao dentro do TaskService, para que a regra de negocio da
 * demanda nao precise saber que notificacao existe. O observer apenas despacha um
 * job: nada de entrega acontece no ciclo da requisicao, entao o usuario que salvou
 * a demanda nao espera por isso.
 *
 * Quem recebe:
 * - atribuicao: o novo responsavel (nunca quem se atribuiu a si mesmo)
 * - mudanca de status: o solicitante e o responsavel, menos quem fez a mudanca
 */
class TaskNotificacaoObserver
{
    public function updated(Task $task): void
    {
        if ($task->wasChanged('atribuido_para_id')) {
            $this->avisarAtribuicao($task);
        }

        if ($task->wasChanged('status')) {
            $this->avisarMudancaDeStatus($task);
        }
    }

    private function avisarAtribuicao(Task $task): void
    {
        $destinatario = $task->atribuido_para_id;

        // Atribuicao removida, ou o proprio usuario pegando a demanda para si:
        // nos dois casos nao ha novidade para comunicar.
        if ($destinatario === null || $destinatario === $this->autorId()) {
            return;
        }

        EntregarNotificacaoJob::dispatch(
            new NotificacaoSpec(
                modulo: 'demandas',
                titulo: 'Demanda atribuida a voce',
                mensagem: sprintf('%s: %s', $task->protocolo, (string) $task->titulo),
                tipo: 'info',
                // Sem agrupamento: cada atribuicao e um fato individual que exige acao.
                groupKey: null,
                acaoUrl: "/demandas/{$task->getKey()}",
                acaoTexto: 'Abrir demanda',
            ),
            [$destinatario],
        );
    }

    private function avisarMudancaDeStatus(Task $task): void
    {
        $destinatarios = array_values(array_unique(array_filter(
            [$task->solicitante_id, $task->atribuido_para_id],
            fn (?int $id): bool => $id !== null && $id !== $this->autorId()
        )));

        if ($destinatarios === []) {
            return;
        }

        EntregarNotificacaoJob::dispatch(
            new NotificacaoSpec(
                modulo: 'demandas',
                titulo: 'Demanda atualizada',
                mensagem: sprintf('%s agora esta em "%s".', $task->protocolo, $task->status->getLabel()),
                tipo: $task->status === TaskStatus::RESOLVIDA ? 'success' : 'info',
                // Varias trocas de status seguidas na mesma demanda viram um card
                // com contador, em vez de empilhar avisos quase identicos.
                groupKey: "demandas:{$task->getKey()}",
                acaoUrl: "/demandas/{$task->getKey()}",
                acaoTexto: 'Ver demanda',
            ),
            $destinatarios,
        );
    }

    /**
     * Quem provocou a mudanca. Em contexto de fila ou console nao ha usuario
     * autenticado, e nesse caso ninguem e excluido da lista.
     */
    private function autorId(): ?int
    {
        $id = auth()->id();

        return $id === null ? null : (int) $id;
    }
}
