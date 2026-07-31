<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Services;

use App\Models\User;
use App\Modules\Notificacoes\Models\Notificacao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Contador de notificacoes nao lidas: o numero do badge do sino.
 *
 * E lido em toda navegacao (vai no share do Inertia), entao o caminho quente tem
 * de ser um GET no Redis, nunca um COUNT no banco. A contagem conta LINHAS, e nao
 * eventos: um assunto agrupado com group_count 5 pesa 1 no badge, que e o que o
 * usuario percebe como "uma coisa para ver".
 *
 * Protecao contra cache stampede conforme o papiro: quando a chave expira, apenas
 * um processo recalcula sob lock; os demais respondem direto do banco em vez de
 * ficarem bloqueados, para nunca segurar o request do usuario.
 */
class ContadorNaoLidas
{
    public function para(Model $notifiable): int
    {
        $chave = $this->chaveDe($notifiable);
        $cache = $this->store();

        $valor = $cache->get($chave);
        if ($valor !== null) {
            return (int) $valor;
        }

        $lock = Cache::lock($chave.':lock', (int) config('notificacoes.contador.lock_segundos', 10));

        if (!$lock->get()) {
            // Outro processo ja esta recalculando: responder do banco e sair.
            return $this->contar($notifiable);
        }

        try {
            // Reconferir: o dono do lock anterior pode ter acabado de gravar.
            $valor = $cache->get($chave);
            if ($valor !== null) {
                return (int) $valor;
            }

            $total = $this->contar($notifiable);
            $cache->put($chave, $total, (int) config('notificacoes.contador.ttl_segundos', 3600));

            return $total;
        } finally {
            $lock->release();
        }
    }

    /**
     * Conta no banco, ignorando o cache, e grava o resultado.
     *
     * Usado pelo endpoint do inbox, que ja vai ao banco de qualquer forma: ali nao
     * existe motivo para confiar no cache, e confiar nele abria uma janela em que a
     * lista de cards vinha fresca da query enquanto o badge vinha de um valor
     * antigo, ou seja, a UI se contradizia. Como efeito colateral, cada abertura do
     * painel conserta um contador que tenha ficado defasado.
     */
    public function recalcular(Model $notifiable): int
    {
        $total = $this->contar($notifiable);

        $this->store()->put(
            $this->chaveDe($notifiable),
            $total,
            (int) config('notificacoes.contador.ttl_segundos', 3600)
        );

        return $total;
    }

    /**
     * Invalida o contador de um ou varios destinatarios. Chamado na entrega e em
     * toda acao de leitura.
     *
     * @param  int|string|list<int|string>  $ids
     */
    public function invalidar(int|string|array $ids): void
    {
        $cache = $this->store();

        foreach ((array) $ids as $id) {
            $cache->forget($this->chave($id));
        }
    }

    /**
     * Descarta o contador de todos os usuarios. Usado apos operacoes em massa,
     * como a poda diaria.
     */
    public function invalidarTudo(): void
    {
        $this->store()->flush();
    }

    private function contar(Model $notifiable): int
    {
        return Notificacao::query()
            ->doDestinatario($notifiable)
            ->naoLidas()
            ->count();
    }

    private function chaveDe(Model $notifiable): string
    {
        return $this->chave($notifiable->getKey(), $notifiable->getMorphClass());
    }

    /**
     * Chave do contador. Inclui o tipo do destinatario porque notifiable e morfico:
     * sem isso, dois destinatarios de tipos diferentes com o mesmo id dividiriam o
     * mesmo contador.
     *
     * Fica em texto legivel (notif:unread:User:42) de proposito, e nao em hash:
     * quando o badge divergir do banco, a chave precisa ser inspecionavel direto
     * no Redis.
     *
     * O padrao e User porque invalidar() recebe ids em lote, vindos do fan-out, e
     * hoje o unico notifiable do sistema e o usuario.
     */
    private function chave(int|string $id, string $morph = User::class): string
    {
        return sprintf(
            '%s%s:%s',
            config('notificacoes.contador.prefixo', 'notif:unread:'),
            class_basename($morph),
            $id
        );
    }

    /**
     * Repositorio taggado, para que invalidarTudo() nao derrube o cache do resto
     * da aplicacao (papiro: cache taggado em vez de flush geral).
     */
    private function store(): \Illuminate\Cache\TaggedCache
    {
        return Cache::tags([(string) config('notificacoes.contador.tag', 'notificacoes')]);
    }
}
