<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Versao do inbox de cada usuario: um contador que so anda para frente e muda
 * sempre que algo visivel no painel do sino muda.
 *
 * POR QUE EXISTE: o painel consulta /notificacoes/inbox repetidamente e a
 * resposta quase sempre e "nada mudou". O ETag ja evitava trafegar o corpo, mas
 * era calculado A PARTIR das linhas -- ou seja, o 304 so era conhecido DEPOIS de
 * buscar os cards e recontar as nao lidas no banco. Duas consultas por ciclo
 * ocioso, por usuario ativo. Com a versao, o 304 e decidido com uma leitura no
 * Redis e o banco nao e tocado.
 *
 * INVARIANTE: a versao precisa ser invalidada em TODO caminho que altera o que
 * o painel mostra -- entrega, leitura individual, leitura em lote, ler todas e
 * arquivamento. Esquecer um deles faz o servidor responder 304 sobre um inbox
 * que mudou, e o usuario ve o sino congelado. Por isso a invalidacao anda junto
 * de [ContadorNaoLidas::invalidar()], que ja e chamada nesses mesmos pontos: os
 * dois respondem a mesma pergunta ("o inbox deste usuario mudou?").
 *
 * A semente NAO e 1. Se a chave cair (expiracao, flush, Redis reiniciado) e o
 * contador recomecasse do 1, um cliente que ainda guarda o ETag da versao 1
 * anterior receberia 304 sobre um inbox diferente. Semeando com o relogio em
 * milissegundos, uma versao nova nunca coincide com uma antiga.
 */
class VersaoDoInbox
{
    public function atual(Model $notifiable): int
    {
        $chave = $this->chaveDe($notifiable);
        $cache = Cache::store();

        // add() e atomico (SETNX): entre varios workers concorrentes, apenas um
        // semeia e os demais leem o valor ja semeado.
        $cache->add($chave, $this->semente(), $this->ttl());

        return (int) $cache->get($chave, $this->semente());
    }

    /**
     * Marca o inbox de um ou varios destinatarios como alterado.
     *
     * @param  int|string|list<int|string>  $ids
     */
    public function invalidar(int|string|array $ids): void
    {
        $cache = Cache::store();

        foreach ((array) $ids as $id) {
            $chave = $this->chave($id);

            // increment() nao cria a chave em todos os stores; semear antes
            // garante que o primeiro evento tambem produza uma versao nova.
            $cache->add($chave, $this->semente(), $this->ttl());
            $cache->increment($chave);
        }
    }

    private function semente(): int
    {
        return (int) (microtime(true) * 1000);
    }

    private function ttl(): int
    {
        return (int) config('notificacoes.inbox.versao_ttl_segundos', 86400);
    }

    private function chaveDe(Model $notifiable): string
    {
        return $this->chave($notifiable->getKey(), $notifiable->getMorphClass());
    }

    /**
     * Mesma forma da chave do contador: texto legivel e com o tipo do
     * destinatario, porque notifiable e morfico e dois tipos diferentes com o
     * mesmo id dividiriam a versao.
     */
    private function chave(int|string $id, string $morph = User::class): string
    {
        return sprintf(
            '%s%s:%s',
            config('notificacoes.inbox.versao_prefixo', 'notif:inbox:versao:'),
            class_basename($morph),
            $id
        );
    }
}
