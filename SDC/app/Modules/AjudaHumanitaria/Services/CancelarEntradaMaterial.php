<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Services;

use App\Modules\AjudaHumanitaria\Domain\Estoque\MovimentoEstoque;
use App\Modules\AjudaHumanitaria\Models\EntradaAh;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Cancelamento de uma entrada de material.
 *
 * Nao apaga nem reescreve lancamento: para cada item entra um movimento de
 * sinal oposto, do tipo ESTORNO_ENTRADA, apontando para a mesma entrada. O
 * ledger e append-only, e o extrato tem de mostrar o recebimento e o estorno
 * lado a lado, nao um buraco onde havia um recebimento.
 *
 * A entrada fica marcada como cancelada, o que ja era o comportamento do
 * legado: a linha permanece, com cancelado = true.
 */
final class CancelarEntradaMaterial
{
    public function __construct(
        private readonly RegistrarMovimentoEstoque $ledger,
    ) {}

    /**
     * Uma entrada so pode ser cancelada se ela mesma lancou no ledger.
     *
     * As 752 entradas migradas nao tem lancamento: a carga levou o saldo ja
     * consolidado do gestaocedec para um unico movimento de ABERTURA por par
     * material/deposito. Estornar uma entrada de 2022 subtrairia do saldo de
     * hoje uma quantidade que provavelmente ja saiu do deposito faz anos, e
     * corromperia a projecao.
     */
    public function podeCancelar(EntradaAh $entrada): bool
    {
        if ($entrada->cancelado) {
            return false;
        }

        return DB::table('ajuda_h_estoque_movimentos')
            ->where('origem_tipo', 'entrada')
            ->where('origem_id', $entrada->getKey())
            ->where('tipo', 'ENTRADA')
            ->exists();
    }

    /**
     * @throws RuntimeException  quando a entrada nao e cancelavel
     * @throws \App\Modules\AjudaHumanitaria\Domain\Estoque\SaldoInsuficiente
     *         quando o material recebido ja saiu do deposito
     */
    public function cancelar(EntradaAh $entrada, ?int $autorId, ?string $motivo = null): void
    {
        if ($entrada->cancelado) {
            throw new RuntimeException('Esta entrada já está cancelada.');
        }

        if (! $this->podeCancelar($entrada)) {
            throw new RuntimeException(
                'Esta entrada veio do sistema anterior e não tem lançamento próprio no estoque. '
                .'Cancelá-la subtrairia do saldo atual uma quantidade que já pode ter saído do depósito.',
            );
        }

        DB::transaction(function () use ($entrada, $autorId, $motivo): void {
            foreach ($entrada->itens()->get() as $item) {
                // O sinal oposto vem da quantidade lancada, e nao de um campo
                // separado: e o mesmo contrato do MovimentoEstoque, em que o
                // sinal carrega o sentido.
                $this->ledger->registrar(new MovimentoEstoque(
                    materialAhId: (int) $item->material_ah_id,
                    depositoId: (int) $entrada->deposito_id,
                    quantidade: '-'.$item->qtd,
                    tipo: 'ESTORNO_ENTRADA',
                    origemTipo: 'entrada',
                    origemId: (int) $entrada->getKey(),
                    registradoPor: $autorId,
                    // now(), e nao a data do recebimento: o estorno acontece
                    // agora, e datar para tras esconderia quando o erro foi
                    // percebido.
                    ocorridoEm: null,
                ));
            }

            $entrada->cancelado = true;
            $entrada->observacao = $this->observacaoCom($entrada->observacao, $motivo);
            $entrada->save();
        });
    }

    /**
     * O motivo entra na observacao porque a tabela nao tem coluna propria, e
     * criar uma so para isto obrigaria a mexer no schema por um texto livre.
     */
    private function observacaoCom(?string $atual, ?string $motivo): ?string
    {
        $nota = 'Cancelada em '.now()->format('d/m/Y H:i').($motivo !== null ? ': '.$motivo : '.');

        return $atual !== null && $atual !== ''
            ? $atual."\n".$nota
            : $nota;
    }
}
