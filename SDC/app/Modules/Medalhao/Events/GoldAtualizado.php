<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Avisa que a camada Gold de um grupo foi refeita.
 *
 * E um AVISO, nao um transporte de dado: o payload leva so o grupo e o carimbo
 * de tempo. Mandar as leituras duplicaria a serializacao que o controller ja faz
 * e criaria duas fontes de verdade que divergem na primeira mudanca de matview.
 * O cliente reage pedindo ao Inertia para rebuscar as props.
 *
 * Efeito colateral desejavel: nada de sensivel atravessa o socket, e a
 * autorizacao de leitura continua sendo a da rota HTTP.
 *
 * Vive no kernel Medalhao, e nao em cada dominio, porque e generico por
 * construcao: parametrizado pelo grupo, fonte nova nao precisa de classe nova.
 */
final class GoldAtualizado implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $grupo,
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("medalhao.{$this->grupo}");
    }

    /** O front escuta por este nome; manter estavel. */
    public function broadcastAs(): string
    {
        return 'GoldAtualizado';
    }

    /** @return array{grupo: string, atualizado_em: string} */
    public function broadcastWith(): array
    {
        return [
            'grupo' => $this->grupo,
            'atualizado_em' => Carbon::now()->toIso8601String(),
        ];
    }
}
