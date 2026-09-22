<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Listeners;

use App\Core\Events\DomainEvent;
use App\Core\Events\IdempotentListener;
use App\Modules\Ranking\Contracts\ModuleAdapter;
use App\Modules\Ranking\Services\ProcessarFatoDoRanking;
use Illuminate\Support\Facades\Log;

/**
 * Ponte entre os Domain Events dos modulos de origem e o motor de pontuacao.
 *
 * Recebe qualquer DomainEvent despachado pelo OutboxDispatcher, procura o
 * adaptador que sabe traduzi-lo e entrega o fato normalizado ao orquestrador.
 * Evento sem adaptador, ou que o adaptador classifique como nao premiavel, e
 * simplesmente ignorado - a maior parte do trafego de um modulo nao e entrega.
 *
 * DUAS BARREIRAS, EM BANCOS DIFERENTES
 * IdempotentListener grava (event_id, listener_class) em `processed_events`,
 * que vive na base OPERACIONAL, e abre a transacao dele na conexao default. O
 * ranking, porem, escreve em outra database: nao existe transacao compartilhada
 * entre as duas. Se a gravacao do ranking falhar depois do processed_events, ou
 * o contrario, os dois lados ficam momentaneamente divergentes.
 *
 * Quem realmente garante exactly-once e a UNIQUE de `ranking.transacoes.event_id`
 * no destino: o reprocessamento reencontra a transacao gravada e nao duplica
 * ponto. O processed_events e caminho rapido, nao a prova. Por isso este
 * listener nao trata reprocessamento como erro.
 *
 * Falha de traducao nao pode derrubar a fila: um adaptador com defeito
 * bloquearia o despacho de todos os eventos do outbox, inclusive os de outros
 * modulos. O erro e registrado e o evento segue adiante - o fato fica sem
 * pontuacao ate alguem corrigir o adaptador e reprocessar.
 */
class PontuarFatoDeNegocio extends IdempotentListener
{
    /**
     * @param iterable<ModuleAdapter> $adaptadores
     */
    public function __construct(
        private readonly iterable $adaptadores,
        private readonly ProcessarFatoDoRanking $processador,
    ) {}

    protected function execute(DomainEvent $event): void
    {
        $adaptador = $this->adaptadorPara($event);

        if ($adaptador === null) {
            return;
        }

        try {
            $fato = $adaptador->normalizar($event);
        } catch (\Throwable $e) {
            Log::warning('Ranking: adaptador falhou ao normalizar evento.', [
                'evento' => $event->eventName(),
                'event_id' => $event->eventId,
                'adaptador' => $adaptador::class,
                'erro' => $e->getMessage(),
            ]);

            return;
        }

        // Null e resultado legitimo: rascunho, reenvio identico, alteracao
        // cosmetica. Nao e erro e nao merece log.
        if ($fato === null) {
            return;
        }

        try {
            $this->processador->processar($fato);
        } catch (\Throwable $e) {
            Log::error('Ranking: falha ao processar fato pontuavel.', [
                'evento' => $event->eventName(),
                'event_id' => $event->eventId,
                'chave_canonica' => $fato->chaveCanonica,
                'erro' => $e->getMessage(),
            ]);
        }
    }

    private function adaptadorPara(DomainEvent $event): ?ModuleAdapter
    {
        foreach ($this->adaptadores as $adaptador) {
            if ($adaptador->suporta($event)) {
                return $adaptador;
            }
        }

        return null;
    }
}
