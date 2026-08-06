<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Services;

use App\Modules\AjudaHumanitaria\Domain\Repositories\PrestacaoContaRepositoryInterface;
use App\Modules\AjudaHumanitaria\Domain\Specifications\PrazoPrestacaoContas;
use App\Modules\AjudaHumanitaria\Domain\Specifications\SaldoEntregaBeneficiarios;
use App\Modules\AjudaHumanitaria\DTOs\EntregaBeneficiarioDTO;
use App\Modules\AjudaHumanitaria\Enums\StatusPrestacaoConta;
use App\Modules\AjudaHumanitaria\Models\PrestacaoConta;
use App\Modules\AjudaHumanitaria\Models\PrestacaoContaEntrega;
use App\Modules\AjudaHumanitaria\Models\PrestacaoContaItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Prestacao de contas (RN-17, RN-18, RN-19).
 *
 * A abertura da prestacao nao acontece aqui: e efeito da entrada do pedido em
 * Atendido, disparada pelo TramitacaoService (RN-15).
 */
final class PrestacaoContasService
{
    public function __construct(
        private readonly PrestacaoContaRepositoryInterface $prestacoes,
        private readonly SaldoEntregaBeneficiarios $saldo,
        private readonly PrazoPrestacaoContas $prazo,
        private readonly TramitacaoService $tramitacao,
    ) {}

    /**
     * RN-17 e RN-18.
     *
     * @return array{0: ?PrestacaoContaEntrega, 1: ?string}
     */
    public function lancarEntrega(EntregaBeneficiarioDTO $dto): array
    {
        $item = PrestacaoContaItem::findOrFail($dto->prestacaoContaItemId);

        $veredito = $this->saldo->verificar(
            $this->prestacoes->quantidadeDoItem($item->id),
            $this->prestacoes->quantidadeJaEntregue($item->id),
            $dto->qtd,
        );

        if (! $veredito->permitido) {
            return [null, $veredito->motivo];
        }

        $entrega = DB::transaction(function () use ($dto, $item): PrestacaoContaEntrega {
            $entrega = PrestacaoContaEntrega::create($dto->toArray());

            PrestacaoConta::query()
                ->whereKey($item->prestacao_conta_id)
                ->where('status', StatusPrestacaoConta::Pendente->value)
                ->update(['status' => StatusPrestacaoConta::EmLancamento->value]);

            return $entrega;
        });

        return [$entrega, null];
    }

    public function removerEntrega(int $entregaId): bool
    {
        return (bool) PrestacaoContaEntrega::findOrFail($entregaId)->delete();
    }

    /** RN-18: quanto ainda falta entregar do item. */
    public function saldoDoItem(int $itemId): int
    {
        return $this->saldo->saldo(
            $this->prestacoes->quantidadeDoItem($itemId),
            $this->prestacoes->quantidadeJaEntregue($itemId),
        );
    }

    /**
     * RN-19: homologar a prestacao e finalizar o processo sao o mesmo ato.
     *
     * @return array{0: bool, 1: ?string}
     */
    public function homologar(int $prestacaoId, ?int $usuarioId): array
    {
        $prestacao = PrestacaoConta::with('itens')->findOrFail($prestacaoId);

        foreach ($prestacao->itens as $item) {
            if ($this->saldoDoItem($item->id) > 0) {
                return [false, "Ainda há saldo pendente de entrega em \"{$item->nome_material}\"."];
            }
        }

        [$pedido, $erro] = $this->tramitacao->finalizarPorHomologacao(
            $prestacao->pedido_ah_id,
            $usuarioId,
        );

        if ($erro !== null) {
            return [false, $erro];
        }

        $this->prestacoes->homologar($prestacaoId, $usuarioId);

        return [true, null];
    }

    /** RN-16. */
    public function estaVencida(int $prestacaoId): bool
    {
        $prestacao = PrestacaoConta::findOrFail($prestacaoId);

        if ($prestacao->data_limite === null) {
            return false;
        }

        return $this->prazo->estaVencido(
            CarbonImmutable::parse($prestacao->data_limite),
            CarbonImmutable::now(),
        );
    }
}
