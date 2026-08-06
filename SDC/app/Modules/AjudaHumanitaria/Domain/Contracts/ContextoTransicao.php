<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Contracts;

use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;

/**
 * Fatos necessarios para decidir uma transicao de status.
 *
 * Existe para manter as guardas puras: quem monta este contexto e a camada de
 * aplicacao, consultando os repositorios. As guardas recebem apenas booleanos
 * e nunca tocam o banco.
 *
 * Todos os fatos sao falsos por padrao, de modo que uma guarda esquecida
 * bloqueia em vez de liberar.
 */
final readonly class ContextoTransicao
{
    public function __construct(
        public StatusPedidoAh $statusAtual,
        public StatusPedidoAh $statusAlvo,
        public bool $temItemPedido = false,
        public bool $temParecerFavoravel = false,
        public bool $temItemLiberado = false,
        public bool $agendamentoAprovado = false,
        public bool $viaHomologacao = false,
    ) {}

    public function ehTransicao(StatusPedidoAh $de, StatusPedidoAh $para): bool
    {
        return $this->statusAtual === $de && $this->statusAlvo === $para;
    }
}
