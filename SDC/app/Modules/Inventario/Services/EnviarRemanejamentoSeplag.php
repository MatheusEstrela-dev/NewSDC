<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Services;

use App\Modules\Inventario\Exceptions\RemanejamentoProibido;
use App\Modules\Inventario\Mail\RemanejamentoSeplagMail;
use App\Modules\Inventario\Models\Remanejamento;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envio da planilha do lote a SEPLAG.
 *
 * O registro do envio e gravado sob lock do lote e o e-mail so entra na fila
 * depois do commit (afterCommit): o worker nunca roda antes do registro existir,
 * e um commit que falha nao deixa e-mail saindo sem registro. Se a fila recusar
 * o job ja depois do commit (Redis fora), o registro e compensado e o erro sobe.
 */
final class EnviarRemanejamentoSeplag
{
    public function executar(Remanejamento $remanejamento, int $userId): Remanejamento
    {
        $destinatarios = $this->destinatarios();
        $commitado = false;
        $enfileirado = false;
        $anterior = null;
        $escrito = null;

        try {
            return DB::transaction(function () use ($remanejamento, $userId, $destinatarios, &$commitado, &$enfileirado, &$anterior, &$escrito): Remanejamento {
                $lote = Remanejamento::query()->lockForUpdate()->findOrFail($remanejamento->getKey());
                if (! $lote->estaAtivo()) {
                    throw RemanejamentoProibido::loteDesfeito();
                }

                // Callbacks de commit rodam na ordem de registro e param no primeiro que
                // falha. As marcas cercam tudo que o update e o envio registram (inclusive
                // o aviso de tempo real do observer): commitado sem enfileirado = registro
                // gravado sem e-mail na fila, e so esse caso e compensado.
                DB::afterCommit(static function () use (&$commitado): void {
                    $commitado = true;
                });

                $anterior = [
                    'seplag_enviado_em' => $lote->seplag_enviado_em,
                    'seplag_enviado_por_id' => $lote->seplag_enviado_por_id,
                ];
                // A coluna guarda segundos: o valor escrito precisa bater com o relido na compensacao.
                $escrito = ['seplag_enviado_em' => now()->startOfSecond(), 'seplag_enviado_por_id' => $userId];
                $lote->update(['seplag_envios' => $lote->seplag_envios + 1, ...$escrito]);

                Mail::to($destinatarios)->queue((new RemanejamentoSeplagMail($lote->id))->afterCommit());
                DB::afterCommit(static function () use (&$enfileirado): void {
                    $enfileirado = true;
                });

                return $lote;
            });
        } catch (Throwable $falha) {
            if ($commitado && ! $enfileirado && $anterior !== null && $escrito !== null) {
                $this->compensar((string) $remanejamento->getKey(), $anterior, $escrito);
            }

            throw $falha;
        }
    }

    /** @return list<string> */
    private function destinatarios(): array
    {
        $destinatarios = array_values(array_filter((array) config('inventario.seplag.destinatarios', [])));
        if ($destinatarios === []) {
            throw RemanejamentoProibido::configuracao(
                'Nenhum destinatário da SEPLAG configurado (INVENTARIO_SEPLAG_DESTINATARIOS).'
            );
        }

        return $destinatarios;
    }

    /**
     * Desfaz o registro de um envio que nao chegou a fila. Outro envio pode ter
     * sido gravado no meio tempo: a contagem so perde este, e data/autor so
     * voltam ao anterior se ainda forem os que esta chamada escreveu.
     *
     * @param array{seplag_enviado_em: ?CarbonInterface, seplag_enviado_por_id: ?int} $anterior
     * @param array{seplag_enviado_em: CarbonInterface, seplag_enviado_por_id: int} $escrito
     */
    private function compensar(string $loteId, array $anterior, array $escrito): void
    {
        try {
            DB::transaction(static function () use ($loteId, $anterior, $escrito): void {
                $lote = Remanejamento::query()->lockForUpdate()->find($loteId);
                if ($lote === null) {
                    return;
                }

                $dados = ['seplag_envios' => max(0, $lote->seplag_envios - 1)];
                $aindaEsteEnvio = $lote->seplag_enviado_em?->equalTo($escrito['seplag_enviado_em'])
                    && (int) $lote->seplag_enviado_por_id === $escrito['seplag_enviado_por_id'];
                if ($aindaEsteEnvio) {
                    $dados = [...$dados, ...$anterior];
                }
                $lote->update($dados);
            });
        } catch (Throwable $falha) {
            // A falha original e a que o usuario precisa ver; esta fica no log.
            report($falha);
        }
    }
}
