<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Services;

use App\Modules\Inventario\Exceptions\RemanejamentoProibido;
use App\Modules\Inventario\Mail\RemanejamentoSeplagMail;
use App\Modules\Inventario\Models\Remanejamento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

final class EnviarRemanejamentoSeplag
{
    public function executar(Remanejamento $remanejamento, int $userId): Remanejamento
    {
        $destinatarios = array_values(array_filter((array) config('inventario.seplag.destinatarios', [])));
        if ($destinatarios === []) {
            throw RemanejamentoProibido::configuracao(
                'Nenhum destinatário da SEPLAG configurado (INVENTARIO_SEPLAG_DESTINATARIOS).'
            );
        }

        return DB::transaction(function () use ($remanejamento, $userId, $destinatarios): Remanejamento {
            $lote = Remanejamento::query()->lockForUpdate()->findOrFail($remanejamento->getKey());
            if (! $lote->estaAtivo()) {
                throw RemanejamentoProibido::loteDesfeito();
            }

            // Enfileira antes de registrar: se o enfileiramento falhar, a excecao
            // desfaz a transacao e o lote nao mostra um envio que nao saiu.
            Mail::to($destinatarios)->queue(new RemanejamentoSeplagMail($lote->id));
            $lote->update([
                'seplag_envios' => $lote->seplag_envios + 1,
                'seplag_enviado_em' => now(),
                'seplag_enviado_por_id' => $userId,
            ]);

            return $lote;
        });
    }
}
