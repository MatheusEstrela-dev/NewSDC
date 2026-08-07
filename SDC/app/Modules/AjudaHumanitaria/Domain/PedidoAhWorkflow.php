<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain;

use App\Modules\AjudaHumanitaria\Domain\Contracts\ContextoTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\GuardaTransicao;
use App\Modules\AjudaHumanitaria\Domain\Contracts\ResultadoGuarda;
use App\Modules\AjudaHumanitaria\Domain\Guards\ExigeItemNoPedido;
use App\Modules\AjudaHumanitaria\Domain\Guards\ExigeItensLiberados;
use App\Modules\AjudaHumanitaria\Domain\Guards\ExigeParecerFavoravel;
use App\Modules\AjudaHumanitaria\Domain\Guards\FinalizacaoSomenteViaHomologacao;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;

/**
 * Unica autoridade sobre a validade de uma transicao de status do pedido.
 *
 * Decide em duas etapas: primeiro se o caminho existe no grafo do enum
 * (RN-12), depois se as condicoes das guardas estao satisfeitas (RN-11,
 * RN-19, RN-21). Nenhum service deve reimplementar essa decisao.
 */
final class PedidoAhWorkflow
{
    /** @var array<int, GuardaTransicao> */
    private array $guardas;

    /**
     * @param  iterable<GuardaTransicao>  $guardas
     */
    public function __construct(iterable $guardas)
    {
        $this->guardas = $guardas instanceof \Traversable
            ? iterator_to_array($guardas, false)
            : array_values($guardas);
    }

    /**
     * Conjunto padrao de guardas. Usado como fallback e em teste unitario;
     * em runtime o container injeta a colecao registrada no provider.
     */
    public static function comGuardasPadrao(): self
    {
        return new self([
            new ExigeItemNoPedido(),
            new ExigeParecerFavoravel(),
            new ExigeItensLiberados(),
            new FinalizacaoSomenteViaHomologacao(),
        ]);
    }

    public function verificar(ContextoTransicao $contexto): ResultadoGuarda
    {
        if (! $contexto->statusAtual->podeTransitarPara($contexto->statusAlvo)) {
            return ResultadoGuarda::bloquear(sprintf(
                'A transição de "%s" para "%s" não é permitida.',
                $contexto->statusAtual->label(),
                $contexto->statusAlvo->label(),
            ));
        }

        foreach ($this->guardas as $guarda) {
            $resultado = $guarda->verificar($contexto);

            if (! $resultado->permitido) {
                return $resultado;
            }
        }

        return ResultadoGuarda::permitir();
    }

    /**
     * Destinos que existem no grafo, sem avaliar condicao.
     *
     * Serve para montar a lista de opcoes na interface; a validade final e
     * sempre confirmada por verificar().
     *
     * @return array<int, StatusPedidoAh>
     */
    public function destinosPossiveis(StatusPedidoAh $atual): array
    {
        return $atual->transicoesPermitidas();
    }
}
