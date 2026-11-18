<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services\Versoes;

use App\Modules\Treinamento\Enums\StatusEntrega;
use App\Modules\Treinamento\Enums\StatusProgresso;
use App\Modules\Treinamento\Enums\TipoItem;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\ProgressoItem;
use App\Modules\Treinamento\Models\ProjetoEntrega;
use App\Modules\Treinamento\Models\Prova;
use App\Modules\Treinamento\Models\ProvaTentativa;
use App\Modules\Treinamento\Models\Treinamento;
use Illuminate\Database\Eloquent\Collection;

/**
 * Quais itens da versao vigente a pessoa nao precisa refazer: aqueles cuja cadeia
 * de origem (origem_item_id) chega a um item que a inscricao de origem concluiu,
 * sem nenhuma marca de "alterado" nas copias do caminho. Prova e projeto "iguais"
 * so contam com tentativa aprovada / entrega aprovada na origem.
 *
 * A cadeia e carregada por nivel (uma consulta por versao percorrida), nunca por item.
 */
class AproveitamentoDeItens
{
    /** @return list<int> ids dos itens da vigente */
    public function itensIguais(Treinamento $vigente, Inscricao $origem): array
    {
        $concluidosNaOrigem = $this->concluidosNaOrigem($origem);
        if ($concluidosNaOrigem === []) {
            return [];
        }

        $origem->loadMissing('treinamento');
        $saltos = max(1, $vigente->versao - $origem->treinamento->versao);

        $itens = $this->itensDaVersao($vigente);
        $tipos = $itens->pluck('tipo', 'id')->all();

        // Cadeias ainda sem alteracao: item da vigente => item atual da cadeia.
        $atuais = $itens->keyBy('id')->all();
        $cadeias = array_fill_keys(array_keys($atuais), true);
        $iguais = [];

        for ($nivel = 0; $nivel < $saltos && $cadeias !== []; $nivel++) {
            $proximas = [];
            foreach (array_keys($cadeias) as $raiz) {
                $atual = $atuais[$raiz];
                if (! $atual->alterado_desde_copia && $atual->origem_item_id !== null) {
                    $proximas[$raiz] = (int) $atual->origem_item_id;
                }
            }

            $ancestrais = $proximas === []
                ? collect()
                : ModuloItem::query()->withTrashed()
                    ->whereIn('id', array_values(array_unique($proximas)))
                    ->get(['id', 'tipo', 'origem_item_id', 'alterado_desde_copia'])->keyBy('id');

            $cadeias = [];
            foreach ($proximas as $raiz => $ancestralId) {
                if (isset($concluidosNaOrigem[$ancestralId])) {
                    $iguais[$raiz] = $ancestralId;
                } elseif ($ancestrais->has($ancestralId)) {
                    $atuais[$raiz] = $ancestrais[$ancestralId];
                    $cadeias[$raiz] = true;
                }
            }
        }

        return $this->comEvidencia($iguais, $tipos, $origem);
    }

    /** @return array<int, true> modulo_item_id => true dos itens concluidos pela inscricao de origem */
    private function concluidosNaOrigem(Inscricao $origem): array
    {
        return ProgressoItem::query()
            ->where('inscricao_id', $origem->id)
            ->where('status', StatusProgresso::CONCLUIDO->value)
            ->pluck('modulo_item_id')
            ->mapWithKeys(fn ($id) => [(int) $id => true])
            ->all();
    }

    /** @return Collection<int, ModuloItem> */
    private function itensDaVersao(Treinamento $versao): Collection
    {
        return ModuloItem::query()
            ->whereIn('modulo_id', Modulo::query()->where('treinamento_id', $versao->id)->select('id'))
            ->get(['id', 'tipo', 'origem_item_id', 'alterado_desde_copia']);
    }

    /**
     * Prova e projeto concluidos na origem so valem com aprovacao registrada nela.
     * Origem que e ela mesma uma atualizacao herdou o item (sem tentativa propria):
     * a aprovacao ja foi exigida na inscricao que a antecedeu.
     *
     * @param  array<int, int>  $iguais  item da vigente => item concluido na origem
     * @param  array<int, TipoItem>  $tipos
     * @return list<int>
     */
    private function comEvidencia(array $iguais, array $tipos, Inscricao $origem): array
    {
        if ($iguais === [] || $origem->eAtualizacao()) {
            return array_map('intval', array_keys($iguais));
        }

        $aprovados = $this->aprovadosNaOrigem($origem, $iguais, $tipos);

        $validos = [];
        foreach ($iguais as $itemVigente => $itemOrigem) {
            $exigeEvidencia = in_array($tipos[$itemVigente], [TipoItem::PROVA, TipoItem::PROJETO], true);
            if (! $exigeEvidencia || isset($aprovados[$itemOrigem])) {
                $validos[] = (int) $itemVigente;
            }
        }

        return $validos;
    }

    /**
     * @param  array<int, int>  $iguais
     * @param  array<int, TipoItem>  $tipos
     * @return array<int, true> itens da origem (prova ou projeto) com aprovacao registrada
     */
    private function aprovadosNaOrigem(Inscricao $origem, array $iguais, array $tipos): array
    {
        $provas = [];
        $projetos = [];
        foreach ($iguais as $itemVigente => $itemOrigem) {
            match ($tipos[$itemVigente]) {
                TipoItem::PROVA => $provas[] = $itemOrigem,
                TipoItem::PROJETO => $projetos[] = $itemOrigem,
                default => null,
            };
        }

        $aprovados = [];

        if ($provas !== []) {
            $itemDaProva = Prova::query()->whereIn('modulo_item_id', $provas)->pluck('modulo_item_id', 'id');
            ProvaTentativa::query()
                ->where('inscricao_id', $origem->id)
                ->whereIn('prova_id', $itemDaProva->keys())
                ->where('aprovada', true)
                ->pluck('prova_id')
                ->each(function ($provaId) use ($itemDaProva, &$aprovados) {
                    $aprovados[(int) $itemDaProva[$provaId]] = true;
                });
        }

        if ($projetos !== []) {
            ProjetoEntrega::query()
                ->where('inscricao_id', $origem->id)
                ->whereIn('modulo_item_id', $projetos)
                ->where('status', StatusEntrega::APROVADA->value)
                ->pluck('modulo_item_id')
                ->each(function ($itemId) use (&$aprovados) {
                    $aprovados[(int) $itemId] = true;
                });
        }

        return $aprovados;
    }
}
