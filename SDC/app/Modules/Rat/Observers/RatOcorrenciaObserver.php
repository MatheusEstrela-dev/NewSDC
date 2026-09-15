<?php

declare(strict_types=1);

namespace App\Modules\Rat\Observers;

use App\Modules\Rat\Models\RatOcorrencia;
use App\Modules\Shared\Events\RecursoAtualizado;

/**
 * Avisa a listagem de protocolos quando uma ocorrencia muda.
 *
 * POR QUE OBSERVER, e nao dispatch no ponto de mudanca como em Pedidos e PMDA:
 * o RAT nao tem ponto unico. `RatOcorrencia` e escrito de treze lugares em tres
 * classes (`RatOcorrenciaService`, `RatWriteService`, `EloquentRatRepository`),
 * e nenhum deles e "o" ponto de decisao. Espalhar treze dispatches garantiria
 * que o proximo ponto de escrita esquecesse o seu.
 *
 * O PRECO DESSA ESCOLHA: o observer dispara em QUALQUER update, inclusive num
 * toque so de `updated_by`. Isso e mais evento do que o necessario -- o debounce
 * do cliente coalesce, mas e ruido real, e a diferenca em relacao a Pedidos e
 * PMDA, onde o dispatch mora no ponto de decisao.
 *
 * O QUE ISSO EXIGIU: observer do Eloquent nao dispara para escrita por query
 * builder (`RatOcorrencia::where(...)->update()` / `->delete()`). Havia quatro
 * dessas, e as quatro foram convertidas para escrita via modelo no mesmo commit.
 * Sem essa conversao a cobertura seria PARCIAL, falhando exatamente nos caminhos
 * mais dificeis de perceber -- inclusive no `updateStatus()`, que e a mudanca que
 * mais interessa a esta tela.
 *
 * Se alguem acrescentar uma escrita por query builder no futuro, ela nao emite e
 * nada acusa. E o risco residual desta abordagem, e esta escrito aqui porque nao
 * ha teste que o pegue.
 */
final class RatOcorrenciaObserver
{
    public function created(RatOcorrencia $ocorrencia): void
    {
        $this->avisar();
    }

    public function updated(RatOcorrencia $ocorrencia): void
    {
        $this->avisar();
    }

    public function deleted(RatOcorrencia $ocorrencia): void
    {
        $this->avisar();
    }

    /**
     * O evento e ShouldDispatchAfterCommit, entao mesmo disparando de dentro de
     * uma transacao ele so sai depois do commit -- e varias dessas escritas
     * acontecem dentro de uma.
     */
    private function avisar(): void
    {
        RecursoAtualizado::dispatch('rat');
    }
}
