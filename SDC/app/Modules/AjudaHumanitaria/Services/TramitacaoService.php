<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Services;

use App\Modules\AjudaHumanitaria\Domain\Contracts\ContextoTransicao;
use App\Modules\AjudaHumanitaria\Domain\PedidoAhWorkflow;
use App\Modules\AjudaHumanitaria\Domain\Repositories\PedidoAhRepositoryInterface;
use App\Modules\AjudaHumanitaria\Domain\Repositories\PrestacaoContaRepositoryInterface;
use App\Modules\AjudaHumanitaria\Domain\Specifications\PrazoPrestacaoContas;
use App\Modules\AjudaHumanitaria\DTOs\TransicaoPedidoDTO;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Enums\TipoItemPedido;
use App\Modules\AjudaHumanitaria\Models\ParametroAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\Shared\Events\RecursoAtualizado;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Unico ponto do modulo que altera o status do pedido.
 *
 * A decisao sobre a validade da transicao nao mora aqui: e delegada ao
 * PedidoAhWorkflow, que combina o grafo do enum com as guardas. Este servico
 * monta os fatos que o workflow precisa, persiste o resultado e dispara os
 * efeitos colaterais.
 */
final class TramitacaoService
{
    public function __construct(
        private readonly PedidoAhWorkflow $workflow,
        private readonly PedidoAhRepositoryInterface $pedidos,
        private readonly PrestacaoContaRepositoryInterface $prestacoes,
        private readonly PrazoPrestacaoContas $prazo,
        private readonly AjudaHumanitariaNotificacaoService $notificacoes,
    ) {}

    /**
     * @return array{0: ?PedidoAh, 1: ?string}
     */
    public function tramitar(int $pedidoId, TransicaoPedidoDTO $dto, ?int $usuarioId): array
    {
        return $this->executar($pedidoId, $dto->statusAlvo, $dto->observacao, $usuarioId, viaHomologacao: false);
    }

    /**
     * RN-19: o unico caminho para Finalizado.
     *
     * @return array{0: ?PedidoAh, 1: ?string}
     */
    public function finalizarPorHomologacao(int $pedidoId, ?int $usuarioId): array
    {
        return $this->executar(
            $pedidoId,
            StatusPedidoAh::Finalizado,
            'Prestação de contas homologada.',
            $usuarioId,
            viaHomologacao: true,
        );
    }

    /**
     * Destinos existentes no grafo, sem avaliar condicao. Serve para montar a
     * lista de opcoes na interface; a validade final e sempre confirmada por
     * tramitar().
     *
     * @return array<int, StatusPedidoAh>
     */
    public function destinosPossiveis(int $pedidoId): array
    {
        return $this->workflow->destinosPossiveis(
            PedidoAh::findOrFail($pedidoId)->status
        );
    }

    /**
     * @return array{0: ?PedidoAh, 1: ?string}
     */
    private function executar(
        int $pedidoId,
        StatusPedidoAh $alvo,
        ?string $observacao,
        ?int $usuarioId,
        bool $viaHomologacao,
    ): array {
        $pedido = PedidoAh::findOrFail($pedidoId);
        $origem = $pedido->status;

        $contexto = new ContextoTransicao(
            statusAtual:         $origem,
            statusAlvo:          $alvo,
            temItemPedido:       $this->pedidos->contarItensPorTipo($pedidoId, TipoItemPedido::Pedido) > 0,
            temParecerFavoravel: $this->pedidos->temParecerFavoravel($pedidoId),
            temItemLiberado:     $this->pedidos->contarItensPorTipo($pedidoId, TipoItemPedido::Liberado) > 0,
            viaHomologacao:      $viaHomologacao,
        );

        $veredito = $this->workflow->verificar($contexto);

        if (! $veredito->permitido) {
            return [null, $veredito->motivo];
        }

        DB::transaction(function () use ($pedido, $pedidoId, $origem, $alvo, $observacao, $usuarioId): void {
            $this->pedidos->atualizarStatus($pedidoId, $alvo);
            $this->pedidos->registrarTramite($pedidoId, $origem, $alvo, $observacao, $usuarioId);

            if ($alvo === StatusPedidoAh::Aprovado && $pedido->data_aprovacao === null) {
                $pedido->forceFill(['data_aprovacao' => now()])->save();
            }

            if ($alvo === StatusPedidoAh::Atendido) {
                $this->abrirPrestacao($pedido);
            }
        });

        $atualizado = $pedido->fresh();

        // Notifica so depois do commit: aviso de mudanca que nao aconteceu e
        // pior do que aviso nenhum.
        $this->notificacoes->pedidoTramitado($atualizado, $origem, $alvo, $usuarioId);

        if ($alvo === StatusPedidoAh::Atendido) {
            $this->notificacoes->prestacaoAberta(
                $atualizado,
                $atualizado->prestacaoConta?->data_limite?->format('d/m/Y'),
            );
        }

        // Avisa quem esta com a FILA aberta, e nao o pedido. A notificacao acima
        // vai para os envolvidos (autor, analista, diretor); o coordenador que
        // acompanha a listagem nao esta entre eles, e e justamente quem recarrega
        // a pagina para descobrir se mudou alguma coisa.
        //
        // O evento e ShouldDispatchAfterCommit, entao nao e a posicao aqui fora
        // que garante a ordem: e a interface. Importa porque executar() pode ser
        // chamado de dentro de outra transacao, e ai "depois do bloco" ainda
        // seria antes do commit de verdade.
        RecursoAtualizado::dispatch('pedidos-ah');

        return [$atualizado, null];
    }

    /**
     * RN-15 e RN-16.
     *
     * O prazo conta da data de aprovacao. Quando o Diretor despacha direto para
     * Atendido, sem passar por Aprovado, essa data e nula e o prazo passa a
     * contar do proprio atendimento. O legado registra 208 despachos assim.
     */
    private function abrirPrestacao(PedidoAh $pedido): void
    {
        if ($pedido->prestacaoConta()->exists()) {
            return;
        }

        $base = $pedido->data_aprovacao !== null
            ? CarbonImmutable::parse($pedido->data_aprovacao)
            : CarbonImmutable::now();

        $dias = ParametroAh::atual()->prazo_prestacao_contas_dias;

        $prestacaoId = $this->prestacoes->abrirParaPedido(
            $pedido->id,
            $this->prazo->calcular($base, $dias),
        );

        $this->prestacoes->copiarItensLiberados($pedido->id, $prestacaoId);
    }
}
