<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Retencao;

use App\Modules\Notificacoes\Services\ContadorNaoLidas;
use App\Modules\Notificacoes\Services\VersaoDoInbox;
use App\Support\Database\Log\AposPoda;

/**
 * Reacao do log:podar a um lote de notificacoes apagadas.
 *
 * A poda so apaga lida, entao o badge nao muda de numero, mas o historico do
 * sino sim: sem nova versao, o ETag do inbox continuaria respondendo 304 com
 * um card que ja nao existe. Invalida contador e versao de cada destinatario
 * afetado, como o ArquivadorDeNotificacoes faz no Limpar.
 */
final class InvalidaInboxAposPoda implements AposPoda
{
    public function __construct(
        private readonly ContadorNaoLidas $contador,
        private readonly VersaoDoInbox $versao,
    ) {}

    public function aposPodar(array $linhas): void
    {
        $ids = [];

        foreach ($linhas as $linha) {
            $ids[(string) $linha->notifiable_id] = $linha->notifiable_id;
        }

        if ($ids === []) {
            return;
        }

        $ids = array_values($ids);
        $this->contador->invalidar($ids);
        $this->versao->invalidar($ids);
    }
}
