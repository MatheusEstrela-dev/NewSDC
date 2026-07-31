<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Support;

use Illuminate\Support\Carbon;

/**
 * Traduz a janela de agrupamento de um modulo para o bucket gravado na coluna
 * notifications.group_bucket.
 *
 * O bucket e a janela discretizada: floor(epoch / janela_em_segundos). Duas
 * notificacoes do mesmo assunto caem no mesmo bucket enquanto estiverem na mesma
 * fatia de tempo, e e isso que permite ao Postgres resolver o agrupamento com um
 * indice unico parcial e um unico INSERT ... ON CONFLICT, sem lock de aplicacao.
 *
 * A janela e "tumbling" (fatias fixas), nao deslizante. A diferenca pratica e
 * pequena: um evento no fim da fatia agrupa por menos tempo do que um no inicio.
 * Em troca, o agrupamento fica atomico e imune a corrida.
 */
final class JanelaAgrupamento
{
    /**
     * Janela do modulo em minutos. Zero desliga o agrupamento.
     */
    public function minutos(string $modulo): int
    {
        $janela = config("notificacoes.modulos.{$modulo}.janela");

        if ($janela === null) {
            $janela = config('notificacoes.agrupamento.janela_padrao_minutos', 15);
        }

        return max(0, (int) $janela);
    }

    public function agrupa(string $modulo): bool
    {
        return $this->minutos($modulo) > 0;
    }

    /**
     * Bucket do instante informado (ou de agora) para o modulo.
     * Retorna null quando o modulo nao agrupa.
     */
    public function bucket(string $modulo, ?Carbon $momento = null): ?int
    {
        $minutos = $this->minutos($modulo);

        if ($minutos === 0) {
            return null;
        }

        $momento ??= Carbon::now();

        return intdiv($momento->getTimestamp(), $minutos * 60);
    }
}
