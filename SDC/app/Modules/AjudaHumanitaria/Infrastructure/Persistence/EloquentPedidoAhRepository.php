<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Infrastructure\Persistence;

use App\Modules\AjudaHumanitaria\Domain\Repositories\PedidoAhRepositoryInterface;
use App\Modules\AjudaHumanitaria\Enums\SituacaoParecer;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Enums\TipoItemPedido;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAhItem;
use App\Modules\AjudaHumanitaria\Models\PedidoAhParecer;
use App\Modules\AjudaHumanitaria\Models\PedidoAhTramite;

/**
 * Implementacao Eloquent da persistencia do pedido.
 *
 * Unico lugar do modulo, junto com os demais repositorios, onde Eloquent
 * aparece a servico do dominio.
 */
final class EloquentPedidoAhRepository implements PedidoAhRepositoryInterface
{
    /**
     * RN-01. A unicidade real e garantida pela constraint unique(numero, ano);
     * esta consulta apenas sugere o proximo valor. Sob criacao concorrente duas
     * requisicoes podem calcular o mesmo numero e a segunda recebera violacao
     * de constraint, que a camada de servico deve tratar com nova tentativa.
     */
    public function proximoNumeroDoAno(int $ano): int
    {
        $maior = PedidoAh::withTrashed()
            ->where('ano', $ano)
            ->max('numero');

        return ((int) $maior) + 1;
    }

    /**
     * RN-03. Considera apenas o status de edicao, reproduzindo a versao do
     * legado que de fato funcionava.
     */
    public function municipioTemPedidoEmEdicao(int $municipioId): bool
    {
        return PedidoAh::query()
            ->where('municipio_id', $municipioId)
            ->where('status', StatusPedidoAh::EdicaoCompdec->value)
            ->exists();
    }

    public function contarItensPorTipo(int $pedidoId, TipoItemPedido $tipo): int
    {
        return PedidoAhItem::query()
            ->where('pedido_ah_id', $pedidoId)
            ->where('tipo', $tipo->value)
            ->count();
    }

    /** RN-11. */
    public function temParecerFavoravel(int $pedidoId): bool
    {
        return PedidoAhParecer::query()
            ->where('pedido_ah_id', $pedidoId)
            ->where('situacao', SituacaoParecer::Favoravel->value)
            ->exists();
    }

    public function atualizarStatus(int $pedidoId, StatusPedidoAh $novo): void
    {
        PedidoAh::query()
            ->whereKey($pedidoId)
            ->update(['status' => $novo->value]);
    }

    /** RN-14. */
    public function registrarTramite(
        int $pedidoId,
        StatusPedidoAh $anterior,
        StatusPedidoAh $novo,
        ?string $observacao,
        ?int $usuarioId,
    ): void {
        PedidoAhTramite::create([
            'pedido_ah_id'    => $pedidoId,
            'status_anterior' => $anterior->value,
            'status_novo'     => $novo->value,
            'observacao'      => $observacao,
            'user_id'         => $usuarioId,
        ]);
    }
}
