<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Queries;

use App\Models\User;
use App\Modules\Inventario\Enums\SituacaoEquipamento;
use App\Modules\Inventario\Enums\StatusMovimentacao;
use App\Modules\Inventario\Enums\TipoMovimentacao;
use App\Modules\Inventario\Models\Equipamento;
use App\Modules\Inventario\Models\Estacao;
use App\Modules\Inventario\Models\Movimentacao;

/**
 * Opcoes do formulario de lote. Equipamento bloqueado vem marcado (e nao
 * omitido) para a tela mostrar por que ele fica com a pessoa.
 */
final class OpcoesRemanejamentoQuery
{
    public function paraFormulario(): array
    {
        $emprestados = Movimentacao::query()
            ->where('tipo', TipoMovimentacao::EMPRESTIMO->value)
            ->where('status', StatusMovimentacao::ATIVO->value)
            ->pluck('equipamento_id')->map(static fn ($id): int => (int) $id)->all();

        return [
            'usuarios' => User::query()->orderBy('name')->get(['id', 'name'])
                ->map(static fn (User $u): array => ['value' => $u->id, 'label' => $u->name])->all(),
            'estacoes' => Estacao::query()->with('usuario:id,name')->orderBy('nome')->get(['id', 'nome', 'ponto_rede', 'user_id'])
                ->map(static fn (Estacao $e): array => [
                    'value' => $e->id,
                    'label' => $e->nome,
                    'ponto_rede' => $e->ponto_rede,
                    'user_id' => $e->user_id,
                    'ocupante' => $e->usuario?->name,
                ])->all(),
            'equipamentos' => Equipamento::query()->with('categoria:id,nome')
                ->where('situacao', '!=', SituacaoEquipamento::BAIXADO->value)
                ->orderBy('nome')->orderBy('id')
                ->get(['id', 'nome', 'patrimonio', 'categoria_id', 'user_id', 'estacao_id', 'situacao'])
                ->map(static fn (Equipamento $e): array => [
                    'id' => $e->id,
                    'nome' => $e->nome,
                    'patrimonio' => $e->patrimonio,
                    'categoria' => $e->categoria?->nome,
                    'user_id' => $e->user_id,
                    'estacao_id' => $e->estacao_id,
                    'bloqueio' => match (true) {
                        $e->situacao === SituacaoEquipamento::MANUTENCAO => 'Em manutenção',
                        in_array($e->id, $emprestados, true) => 'Emprestado',
                        default => null,
                    },
                ])->all(),
        ];
    }
}
