<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Services;

use App\Modules\Inventario\Enums\SituacaoEquipamento;
use App\Modules\Inventario\Enums\StatusMovimentacao;
use App\Modules\Inventario\Enums\TipoMovimentacao;
use App\Modules\Inventario\Models\Equipamento;
use App\Modules\Inventario\Models\Movimentacao;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Movimentacao avulsa (emprestimo). O remanejamento em lote vive em
 * RemanejamentoService; aqui so se garante que um nao desfaz o outro. O que
 * prende o equipamento esta em Movimentacao::scopeSeguraEquipamento.
 */
final class MovimentacaoService
{
    public function registrar(array $data, int $actorId): Movimentacao
    {
        return DB::transaction(function () use ($data, $actorId): Movimentacao {
            $equipamento = Equipamento::query()->lockForUpdate()->findOrFail($data['equipamento_id']);
            if (in_array($equipamento->situacao, [SituacaoEquipamento::MANUTENCAO, SituacaoEquipamento::BAIXADO], true)) {
                throw ValidationException::withMessages(['equipamento_id' => 'Equipamento indisponível para movimentação.']);
            }
            if ($data['tipo'] === TipoMovimentacao::EMPRESTIMO->value && ! $equipamento->emprestavel) {
                throw ValidationException::withMessages(['equipamento_id' => 'Equipamento não permite empréstimo.']);
            }

            $ativos = (int) $equipamento->movimentacoes()->seguraEquipamento()->sum('quantidade');
            if ($ativos > 0 || $data['quantidade'] !== $equipamento->quantidade) {
                throw ValidationException::withMessages(['quantidade' => 'Movimente a quantidade integral do equipamento, sem movimentação ativa.']);
            }

            $movimentacao = $equipamento->movimentacoes()->create([
                ...$data,
                'registrado_por_id' => $actorId,
                'usuario_origem_id' => $equipamento->user_id,
                'estacao_origem_id' => $equipamento->estacao_id,
                'situacao_origem' => $equipamento->situacao->value,
                'data_saida' => now(),
                'status' => StatusMovimentacao::ATIVO->value,
            ]);
            $equipamento->update([
                'user_id' => $data['usuario_destino_id'] ?? null,
                'estacao_id' => $data['estacao_destino_id'] ?? null,
                'situacao' => SituacaoEquipamento::EM_USO,
            ]);

            return $movimentacao;
        });
    }

    public function devolver(int $id): Movimentacao
    {
        return DB::transaction(function () use ($id): Movimentacao {
            $referencia = Movimentacao::query()->findOrFail($id);
            if ($referencia->lote_id !== null) {
                throw ValidationException::withMessages([
                    'movimentacao' => 'Esta movimentação faz parte de um remanejamento em lote. Use "Desfazer" no lote.',
                ]);
            }
            $equipamento = Equipamento::query()->lockForUpdate()->findOrFail($referencia->equipamento_id);
            $movimentacao = Movimentacao::query()->lockForUpdate()->findOrFail($id);
            if ($movimentacao->status !== StatusMovimentacao::ATIVO->value) {
                throw ValidationException::withMessages(['movimentacao' => 'Movimentação já encerrada.']);
            }

            $movimentacao->update(['status' => StatusMovimentacao::DEVOLVIDO->value, 'data_devolucao' => now()]);
            if (! $equipamento->movimentacoes()->seguraEquipamento()->exists()) {
                $equipamento->update([
                    'user_id' => $movimentacao->usuario_origem_id,
                    'estacao_id' => $movimentacao->estacao_origem_id,
                    'situacao' => $movimentacao->usuario_origem_id || $movimentacao->estacao_origem_id
                        ? SituacaoEquipamento::EM_USO : SituacaoEquipamento::DISPONIVEL,
                ]);
            }

            return $movimentacao;
        });
    }
}
