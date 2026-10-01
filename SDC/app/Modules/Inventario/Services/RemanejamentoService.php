<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Services;

use App\Modules\Inventario\DTOs\PessoaRemanejadaData;
use App\Modules\Inventario\DTOs\RemanejamentoData;
use App\Modules\Inventario\Enums\SituacaoEquipamento;
use App\Modules\Inventario\Enums\StatusMovimentacao;
use App\Modules\Inventario\Enums\StatusRemanejamento;
use App\Modules\Inventario\Enums\TipoMovimentacao;
use App\Modules\Inventario\Exceptions\RemanejamentoProibido;
use App\Modules\Inventario\Models\Equipamento;
use App\Modules\Inventario\Models\Estacao;
use App\Modules\Inventario\Models\Movimentacao;
use App\Modules\Inventario\Models\Remanejamento;
use App\Modules\Inventario\Models\RemanejamentoPessoa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Remanejamento em lote: registrar, desfazer e editar.
 *
 * Toda escrita numa transacao, com lockForUpdate em equipamentos e depois em
 * estacoes, cada grupo em ordem crescente de id. A ordem fixa e o que impede
 * dois lotes concorrentes de travarem um esperando o outro.
 */
final class RemanejamentoService
{
    private const SITUACOES_BLOQUEADAS = [SituacaoEquipamento::BAIXADO, SituacaoEquipamento::MANUTENCAO];

    public function registrar(RemanejamentoData $dados): Remanejamento
    {
        return DB::transaction(function () use ($dados): Remanejamento {
            $lote = Remanejamento::create([
                'registrado_por_id' => $dados->autorId,
                'observacao' => $dados->observacao,
                'status' => StatusRemanejamento::ATIVO,
            ]);
            $this->aplicar($lote, $dados);

            return $lote->refresh();
        });
    }

    public function desfazer(Remanejamento $remanejamento, int $userId): Remanejamento
    {
        return DB::transaction(function () use ($remanejamento, $userId): Remanejamento {
            $lote = $this->travarLoteAtivo($remanejamento);
            $this->reverter($lote);
            $lote->update([
                'status' => StatusRemanejamento::DESFEITO,
                'desfeito_em' => now(),
                'desfeito_por_id' => $userId,
            ]);

            return $lote;
        });
    }

    private function travarLoteAtivo(Remanejamento $remanejamento): Remanejamento
    {
        $lote = Remanejamento::query()->lockForUpdate()->findOrFail($remanejamento->getKey());
        if (! $lote->estaAtivo()) {
            throw RemanejamentoProibido::loteDesfeito();
        }

        return $lote;
    }

    /**
     * Devolve equipamentos e estacoes ao estado de antes do lote. Valida tudo
     * antes de escrever qualquer linha: tudo ou nada, mesmo fora da transacao.
     */
    private function reverter(Remanejamento $lote): void
    {
        $movimentacoes = $lote->movimentacoes()->with('equipamento:id,patrimonio')->orderByDesc('id')->get();
        $equipamentos = $this->travarEquipamentos(
            Equipamento::withTrashed()->whereIn('id', $movimentacoes->pluck('equipamento_id')->unique()->values()->all())
        );
        $pessoas = $lote->pessoas()->orderBy('id')->get();
        $estacoes = $this->travarEstacoes(array_values(array_unique(array_filter(array_merge(
            $pessoas->pluck('estacao_origem_id')->all(),
            $pessoas->pluck('estacao_destino_id')->all(),
        )))));

        $this->validarReversao($lote, $movimentacoes);

        // Ordem inversa do registro (id decrescente).
        foreach ($movimentacoes as $movimentacao) {
            $equipamentos->get($movimentacao->equipamento_id)?->update([
                'user_id' => $movimentacao->usuario_origem_id,
                'estacao_id' => $movimentacao->estacao_origem_id,
                'situacao' => $movimentacao->situacao_origem ?? SituacaoEquipamento::DISPONIVEL->value,
            ]);
            $movimentacao->update(['status' => StatusMovimentacao::DEVOLVIDO->value, 'data_devolucao' => now()]);
            if ($movimentacao->tipo === TipoMovimentacao::REMANEJAMENTO->value) {
                $this->reativarSubstituida($movimentacao);
            }
        }

        // Destinos antes das origens: numa troca mutua a mesa e destino de um e
        // origem do outro, e quem fica com ela no fim e o dono original.
        foreach ($pessoas as $pessoa) {
            if ($pessoa->estacao_destino_id !== null) {
                $estacoes->get($pessoa->estacao_destino_id)?->update(['user_id' => $pessoa->estacao_destino_usuario_anterior_id]);
            }
        }
        foreach ($pessoas as $pessoa) {
            if ($pessoa->estacao_origem_id !== null) {
                $estacoes->get($pessoa->estacao_origem_id)?->update(['user_id' => $pessoa->usuario_id]);
            }
        }
    }

    /** @param Collection<int, Movimentacao> $movimentacoes */
    private function validarReversao(Remanejamento $lote, Collection $movimentacoes): void
    {
        $erros = [];

        foreach ($movimentacoes as $movimentacao) {
            // Devolvido nao conta: desfazer em ordem inversa (o lote mais novo
            // primeiro) tem de liberar o lote anterior.
            $posterior = Movimentacao::query()
                ->with('remanejamento:id,created_at')
                ->where('equipamento_id', $movimentacao->equipamento_id)
                ->where('id', '>', $movimentacao->id)
                ->where(static fn (Builder $q) => $q->whereNull('lote_id')->orWhere('lote_id', '!=', $lote->id))
                ->where('status', '!=', StatusMovimentacao::DEVOLVIDO->value)
                ->orderBy('id')
                ->first();
            $substituida = $movimentacao->tipo === TipoMovimentacao::REMANEJAMENTO->value
                && $movimentacao->status !== StatusMovimentacao::ATIVO->value;

            if ($posterior === null && ! $substituida) {
                continue;
            }

            $rotulo = $movimentacao->equipamento?->patrimonio ?? '#'.$movimentacao->equipamento_id;
            $erros['remanejamento'][] = sprintf(
                'O equipamento %s foi movimentado depois deste lote (%s). Desfaça primeiro o que veio depois.',
                $rotulo,
                $posterior !== null ? $this->descrever($posterior) : 'remanejamento posterior',
            );
        }

        if ($erros !== []) {
            throw RemanejamentoProibido::comErros($erros);
        }
    }

    private function descrever(Movimentacao $movimentacao): string
    {
        if ($movimentacao->remanejamento !== null) {
            return 'lote de '.$movimentacao->remanejamento->created_at->format('d/m/Y H:i');
        }

        return TipoMovimentacao::tryFrom($movimentacao->tipo)?->label().' #'.$movimentacao->id;
    }

    /**
     * Uma so movimentacao de remanejamento fica ativa por equipamento; a
     * substituida mais recente antes desta e exatamente a que esta substituiu.
     */
    private function reativarSubstituida(Movimentacao $movimentacao): void
    {
        Movimentacao::query()
            ->where('equipamento_id', $movimentacao->equipamento_id)
            ->where('tipo', TipoMovimentacao::REMANEJAMENTO->value)
            ->where('status', StatusMovimentacao::SUBSTITUIDA->value)
            ->where('id', '<', $movimentacao->id)
            ->orderByDesc('id')
            ->first()
            ?->update(['status' => StatusMovimentacao::ATIVO->value]);
    }

    private function aplicar(Remanejamento $lote, RemanejamentoData $dados): void
    {
        if ($dados->pessoas === []) {
            throw RemanejamentoProibido::comErros(['pessoas' => 'Informe ao menos uma pessoa no remanejamento.']);
        }

        $equipamentos = $this->travarEquipamentos(Equipamento::query()->where(
            static fn (Builder $q) => $q->whereIn('id', $dados->equipamentoIds())->orWhereIn('user_id', $dados->usuarioIds())
        ));
        $estacoes = $this->travarEstacoes($dados->estacaoIds());
        $emprestados = $this->comEmprestimoAtivo($equipamentos->keys()->all());

        $this->validar($dados, $equipamentos, $estacoes, $emprestados);

        // Passo 1: solta todas as origens antes de ocupar qualquer destino. Numa
        // troca mutua, ocupar primeiro gravaria o colega como "ocupante anterior"
        // da mesa que ele mesmo esta deixando.
        foreach ($dados->pessoas as $pessoa) {
            if ($pessoa->estacaoOrigemId !== null) {
                $estacoes[$pessoa->estacaoOrigemId]->update(['user_id' => null]);
            }
        }

        $deixados = $this->deixadosParaTras($dados, $equipamentos, $emprestados);
        $destinos = $dados->estacaoDestinoIds();

        foreach ($dados->pessoas as $pessoa) {
            $destino = $pessoa->estacaoDestinoId !== null ? $estacoes[$pessoa->estacaoDestinoId] : null;
            $registro = $lote->pessoas()->create([
                'usuario_id' => $pessoa->usuarioId,
                'estacao_origem_id' => $pessoa->estacaoOrigemId,
                'estacao_destino_id' => $pessoa->estacaoDestinoId,
                'estacao_destino_usuario_anterior_id' => $destino?->user_id,
                // A origem so nao fica vazia se alguem do lote (inclusive a propria
                // pessoa, quando fica na mesma mesa) a tiver como destino.
                'estacao_origem_ficou_vazia' => ! in_array($pessoa->estacaoOrigemId, $destinos, true),
                'condicao_destino' => $pessoa->condicaoDestino,
            ]);
            $destino?->update(['user_id' => $pessoa->usuarioId]);

            foreach ($deixados[$pessoa->usuarioId] ?? [] as $equipamento) {
                $this->liberar($lote, $registro, $equipamento, $dados);
            }
            foreach ($pessoa->equipamentoIds as $id) {
                $this->remanejar($lote, $registro, $equipamentos[$id], $pessoa, $dados);
            }
        }
    }

    /**
     * Junta TODOS os erros antes de lancar: a tela mostra cada item recusado de
     * uma vez, em vez de o usuario descobrir um por envio.
     *
     * @param Collection<int, Equipamento> $equipamentos
     * @param Collection<int, Estacao> $estacoes
     * @param list<int> $emprestados
     */
    private function validar(RemanejamentoData $dados, Collection $equipamentos, Collection $estacoes, array $emprestados): void
    {
        $erros = [];
        $usuariosVistos = [];
        $equipamentosVistos = [];
        $destinosVistos = [];

        foreach ($dados->pessoas as $i => $pessoa) {
            $chave = "pessoas.{$i}";

            if (in_array($pessoa->usuarioId, $usuariosVistos, true)) {
                $erros["{$chave}.usuario_id"][] = 'Esta pessoa já está em outro bloco do lote.';
            }
            $usuariosVistos[] = $pessoa->usuarioId;

            if ($pessoa->estacaoOrigemId !== null) {
                $origem = $estacoes->get($pessoa->estacaoOrigemId);
                if ($origem === null) {
                    $erros["{$chave}.estacao_origem_id"][] = 'Estação de origem não encontrada.';
                } elseif ((int) $origem->user_id !== $pessoa->usuarioId) {
                    $erros["{$chave}.estacao_origem_id"][] = "A estação {$origem->nome} não está ocupada por esta pessoa.";
                }
            }

            if ($pessoa->estacaoDestinoId !== null) {
                $destino = $estacoes->get($pessoa->estacaoDestinoId);
                if ($destino === null) {
                    $erros["{$chave}.estacao_destino_id"][] = 'Estação de destino não encontrada.';
                } elseif (in_array($pessoa->estacaoDestinoId, $destinosVistos, true)) {
                    $erros["{$chave}.estacao_destino_id"][] = "A estação {$destino->nome} já é destino de outra pessoa do lote.";
                }
                $destinosVistos[] = $pessoa->estacaoDestinoId;
            }

            foreach ($pessoa->equipamentoIds as $id) {
                $equipamento = $equipamentos->get($id);
                if ($equipamento === null) {
                    $erros["{$chave}.equipamento_ids"][] = "Equipamento #{$id} não encontrado.";
                    continue;
                }
                $rotulo = "O equipamento {$equipamento->patrimonio}";
                if (in_array($id, $equipamentosVistos, true)) {
                    $erros["{$chave}.equipamento_ids"][] = "{$rotulo} aparece mais de uma vez no lote.";
                }
                $equipamentosVistos[] = $id;

                if ($equipamento->situacao === SituacaoEquipamento::BAIXADO) {
                    $erros["{$chave}.equipamento_ids"][] = "{$rotulo} está baixado.";
                } elseif ($equipamento->situacao === SituacaoEquipamento::MANUTENCAO) {
                    $erros["{$chave}.equipamento_ids"][] = "{$rotulo} está em manutenção.";
                }
                if (in_array($id, $emprestados, true)) {
                    $erros["{$chave}.equipamento_ids"][] = "{$rotulo} está emprestado; registre a devolução antes.";
                }
            }
        }

        if ($erros !== []) {
            throw RemanejamentoProibido::comErros($erros);
        }
    }

    /**
     * Equipamentos das pessoas do lote que nao foram listados em bloco nenhum.
     * Emprestado e manutencao/baixado nao sao liberados: o emprestimo e a
     * manutencao tem fluxo proprio e nao acabam porque a pessoa mudou de mesa.
     *
     * @param Collection<int, Equipamento> $equipamentos
     * @param list<int> $emprestados
     * @return array<int, list<Equipamento>>
     */
    private function deixadosParaTras(RemanejamentoData $dados, Collection $equipamentos, array $emprestados): array
    {
        $listados = $dados->equipamentoIds();
        $usuarios = $dados->usuarioIds();
        $deixados = [];

        foreach ($equipamentos as $equipamento) {
            $dono = $equipamento->user_id !== null ? (int) $equipamento->user_id : null;
            if ($dono === null || ! in_array($dono, $usuarios, true) || in_array($equipamento->id, $listados, true)) {
                continue;
            }
            if (in_array($equipamento->id, $emprestados, true) || in_array($equipamento->situacao, self::SITUACOES_BLOQUEADAS, true)) {
                continue;
            }
            $deixados[$dono][] = $equipamento;
        }

        return $deixados;
    }

    private function liberar(Remanejamento $lote, RemanejamentoPessoa $registro, Equipamento $equipamento, RemanejamentoData $dados): void
    {
        Movimentacao::create([
            'equipamento_id' => $equipamento->id,
            'registrado_por_id' => $dados->autorId,
            'usuario_origem_id' => $equipamento->user_id,
            'usuario_destino_id' => null,
            'estacao_origem_id' => $equipamento->estacao_id,
            // O equipamento fica fisicamente onde estava; so perde o responsavel.
            'estacao_destino_id' => $equipamento->estacao_id,
            'lote_id' => $lote->id,
            'remanejamento_pessoa_id' => $registro->id,
            'tipo' => TipoMovimentacao::LIBERACAO->value,
            'status' => StatusMovimentacao::ATIVO->value,
            'situacao_origem' => $equipamento->situacao->value,
            'quantidade' => $equipamento->quantidade,
            'data_saida' => now(),
            'observacao' => $dados->observacao,
        ]);
        $equipamento->update(['user_id' => null, 'situacao' => SituacaoEquipamento::DISPONIVEL]);
    }

    private function remanejar(
        Remanejamento $lote,
        RemanejamentoPessoa $registro,
        Equipamento $equipamento,
        PessoaRemanejadaData $pessoa,
        RemanejamentoData $dados,
    ): void {
        // Remanejar de novo e normal: o anterior perde efeito, mas fica no
        // historico e volta a valer se este lote for desfeito.
        Movimentacao::query()
            ->where('equipamento_id', $equipamento->id)
            ->where('tipo', TipoMovimentacao::REMANEJAMENTO->value)
            ->where('status', StatusMovimentacao::ATIVO->value)
            ->update(['status' => StatusMovimentacao::SUBSTITUIDA->value]);

        Movimentacao::create([
            'equipamento_id' => $equipamento->id,
            'registrado_por_id' => $dados->autorId,
            'usuario_origem_id' => $equipamento->user_id,
            'usuario_destino_id' => $pessoa->usuarioId,
            'estacao_origem_id' => $equipamento->estacao_id,
            'estacao_destino_id' => $pessoa->estacaoDestinoId,
            'lote_id' => $lote->id,
            'remanejamento_pessoa_id' => $registro->id,
            'tipo' => TipoMovimentacao::REMANEJAMENTO->value,
            'status' => StatusMovimentacao::ATIVO->value,
            'situacao_origem' => $equipamento->situacao->value,
            'quantidade' => $equipamento->quantidade,
            'data_saida' => now(),
            'observacao' => $dados->observacao,
        ]);
        $equipamento->update([
            'user_id' => $pessoa->usuarioId,
            'estacao_id' => $pessoa->estacaoDestinoId,
            'situacao' => SituacaoEquipamento::EM_USO,
        ]);
    }

    /** @return Collection<int, Equipamento> */
    private function travarEquipamentos(Builder $query): Collection
    {
        return $query->orderBy('id')->lockForUpdate()->get()->keyBy('id');
    }

    /**
     * @param list<int> $ids
     * @return Collection<int, Estacao>
     */
    private function travarEstacoes(array $ids): Collection
    {
        if ($ids === []) {
            return new Collection();
        }

        return Estacao::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
    }

    /**
     * @param list<int> $ids
     * @return list<int>
     */
    private function comEmprestimoAtivo(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return Movimentacao::query()
            ->whereIn('equipamento_id', $ids)
            ->where('tipo', TipoMovimentacao::EMPRESTIMO->value)
            ->where('status', StatusMovimentacao::ATIVO->value)
            ->pluck('equipamento_id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()->values()->all();
    }
}
