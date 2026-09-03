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

    /**
     * Sufixo do canal de quem le o escopo inteiro.
     *
     * Nao pode colidir com id de municipio, e nao colide: id e sempre numerico.
     * A autorizacao usa essa diferenca para separar os dois casos.
     */
    public const ESCOPO_TODOS = 'todos';

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

    /**
     * Recurso global vai para um canal; recurso escopado vai para DOIS.
     *
     * O segundo, `...{recurso}.todos`, existe porque quem le o estado inteiro
     * (CEDEC, REDEC, super-admin) nao teria como escutar: a alternativa seria
     * assinar os 853 canais de municipio, um por um. O canal do escopo serve
     * quem esta restrito aquele municipio; o `todos` serve quem nao esta
     * restrito a nenhum.
     *
     * Sao dois canais e nao dois eventos: um dispatch, uma transmissao, e a
     * autorizacao de cada canal decide quem recebe.
     *
     * @return PrivateChannel|list<PrivateChannel>
     */
    public function broadcastOn(): PrivateChannel|array
    {
        if ($this->escopo === null) {
            return new PrivateChannel("listagem.{$this->recurso}");
        }

        return [
            new PrivateChannel("listagem.{$this->recurso}.{$this->escopo}"),
            new PrivateChannel("listagem.{$this->recurso}." . self::ESCOPO_TODOS),
        ];
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
