<?php

declare(strict_types=1);

namespace App\Modules\Shared\Events;

use App\Modules\Shared\Support\CanaisDeListagem;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Avisa que uma listagem tem dado novo.
 *
 * E um AVISO, nao um transporte de dado: o payload leva o recurso, o escopo e o
 * carimbo de tempo. Mandar a linha alterada criaria uma segunda serializacao que
 * diverge do Resource na primeira mudanca -- e, pior, furaria o escopo, porque a
 * linha que interessa a um assinante pode nao ser visivel para outro no mesmo
 * canal. O cliente reage pedindo ao Inertia para rebuscar as props, e o
 * controller segue sendo a unica fonte delas.
 *
 * SO DESPACHA DEPOIS DO COMMIT. O status do pedido muda dentro de
 * DB::transaction (TramitacaoService); um evento transmitido antes do commit
 * faria o viewer rebuscar e ler o estado ANTIGO, e nao ha segundo evento -- o
 * usuario ficaria com dado velho na tela e a impressao de que o tempo real
 * funcionou. PMDA e RAT hoje mudam status FORA de transacao, e nesse caso a
 * interface simplesmente despacha na hora; a protecao passa a valer de graca no
 * dia em que ganharem uma.
 *
 * Vive em Modules/Shared e nao em cada dominio porque e generico por construcao:
 * recurso novo nao precisa de classe nova, so de entrada em CanaisDeListagem.
 */
final class RecursoAtualizado implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $recurso,
        public readonly ?int $escopo = null,
    ) {
        // Validar no construtor, e nao em broadcastOn(), para a falha aparecer
        // no ponto que despachou. Um recurso invalido transmitiria num canal que
        // ninguem consegue assinar: o evento sairia, seria serializado e
        // entregue a lugar nenhum, aparecendo so como "o tempo real nao funciona
        // nessa tela".
        if (CanaisDeListagem::permissaoDe($recurso) === null) {
            throw new InvalidArgumentException(
                "Recurso [{$recurso}] nao esta em CanaisDeListagem. Sem entrada na tabela, nenhum usuario autoriza o canal."
            );
        }

        $exigeEscopo = CanaisDeListagem::exigeEscopo($recurso);

        if ($exigeEscopo && $escopo === null) {
            throw new InvalidArgumentException(
                "Recurso [{$recurso}] e escopado por municipio e exige escopo: sem ele o canal seria global e avisaria quem nao ve o dado."
            );
        }

        if (! $exigeEscopo && $escopo !== null) {
            throw new InvalidArgumentException(
                "Recurso [{$recurso}] nao e escopado: com escopo o canal nao seria assinado por ninguem."
            );
        }
    }

    public function broadcastOn(): PrivateChannel
    {
        $canal = $this->escopo === null
            ? "listagem.{$this->recurso}"
            : "listagem.{$this->recurso}.{$this->escopo}";

        return new PrivateChannel($canal);
    }

    /** O front escuta por este nome; manter estavel. */
    public function broadcastAs(): string
    {
        return 'RecursoAtualizado';
    }

    /** @return array{recurso: string, escopo: int|null, atualizado_em: string} */
    public function broadcastWith(): array
    {
        return [
            'recurso' => $this->recurso,
            'escopo' => $this->escopo,
            'atualizado_em' => Carbon::now()->toIso8601String(),
        ];
    }
}
