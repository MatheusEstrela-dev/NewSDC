<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\DTOs\EstadoItem;
use App\Modules\Treinamento\DTOs\EstadoPasta;
use App\Modules\Treinamento\DTOs\EstadoTrilha;
use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Enums\StatusProgresso;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\ProgressoItem;
use App\Modules\Treinamento\Models\ProgressoPasta;
use App\Modules\Treinamento\Models\ProjetoEntrega;
use App\Modules\Treinamento\Models\Treinamento;
use Illuminate\Support\Collection;

/**
 * Regra de liberacao da trilha: uma pasta so abre quando a anterior esta
 * concluida, e uma pasta concluida nunca reabre (grava em progresso_pastas).
 * Fonte unica de "o aluno pode ver este conteudo?" para os dois escopos.
 */
class TrilhaService
{
    public function estado(Inscricao $inscricao): EstadoTrilha
    {
        $pastas = [];
        $total = 0;
        $concluidos = 0;
        $proximo = null;

        foreach ($this->percorrer($inscricao) as $p) {
            $estadoItens = [];
            foreach ($p['itens'] as $item) {
                $progresso = $p['progressos']->get($item->id);
                $concluido = $progresso?->status === StatusProgresso::CONCLUIDO;
                $entrega = $p['entregas']->get($item->id);

                if ($item->obrigatorio) {
                    $total++;
                    $concluidos += ($concluido || $p['gravada']) ? 1 : 0;
                }
                if ($proximo === null && $p['liberada'] && ! $concluido) {
                    $proximo = $item->id;
                }

                $estadoItens[] = new EstadoItem(
                    $item, $p['liberada'], $progresso?->status, $progresso?->posicao ?? 0, $entrega?->status, $entrega?->parecer,
                );
            }
            $pastas[] = new EstadoPasta($p['modulo'], $p['liberada'], $p['concluida'], $estadoItens);
        }

        $percentual = $total === 0 ? 0.0 : round(100 * $concluidos / $total, 1);

        return new EstadoTrilha($percentual, $proximo, $concluidos, $total, $pastas);
    }

    /**
     * Previa do professor: tudo liberado, nada concluido, sem gravar progresso.
     */
    public function estadoPrevia(Treinamento $treinamento): EstadoTrilha
    {
        $pastas = [];
        $total = 0;
        $proximo = null;

        $modulos = Modulo::query()->porTreinamento($treinamento->id)->ordenados()->with('itens')->get();

        foreach ($modulos as $modulo) {
            $itens = [];
            foreach ($modulo->itens as $item) {
                $total += $item->obrigatorio ? 1 : 0;
                $proximo ??= $item->id;
                $itens[] = new EstadoItem($item, true, null, 0, null, null);
            }
            $pastas[] = new EstadoPasta($modulo, true, false, $itens);
        }

        return new EstadoTrilha(0.0, $proximo, 0, $total, $pastas);
    }

    public function itemLiberado(Inscricao $inscricao, ModuloItem $item): bool
    {
        foreach ($this->percorrer($inscricao) as $p) {
            if ($p['itens']->contains('id', $item->id)) {
                return $p['liberada'];
            }
        }

        return false;
    }

    /**
     * Grava a conclusao das pastas em ordem, parando na primeira que ainda
     * nao foi concluida. Idempotente e segura sob POSTs concorrentes (a
     * chave unica descarta o duplicado).
     */
    public function atualizarPastas(Inscricao $inscricao): void
    {
        foreach ($this->percorrer($inscricao) as $p) {
            if (! $p['liberada']) {
                return;
            }
            if ($p['gravada']) {
                continue;
            }
            if (! $p['concluida']) {
                return;
            }

            ProgressoPasta::query()->insertOrIgnore([
                'inscricao_id' => $inscricao->id,
                'modulo_id' => $p['modulo']->id,
                'concluida_em' => now(),
            ]);
        }
    }

    /**
     * Pastas e itens ativos em ordem, ja com liberacao e conclusao resolvidas.
     *
     * @return list<array{modulo: Modulo, itens: Collection<int, ModuloItem>, progressos: Collection<int, ProgressoItem>, entregas: Collection<int, ProjetoEntrega>, gravada: bool, concluida: bool, liberada: bool}>
     */
    private function percorrer(Inscricao $inscricao): array
    {
        $pastas = Modulo::query()
            ->porTreinamento($inscricao->treinamento_id)
            ->ordenados()
            ->with('itens')
            ->get();

        $progressos = ProgressoItem::query()->where('inscricao_id', $inscricao->id)->get()->keyBy('modulo_item_id');
        $gravadas = ProgressoPasta::query()->where('inscricao_id', $inscricao->id)->pluck('modulo_id')->all();
        $entregas = ProjetoEntrega::query()
            ->where('inscricao_id', $inscricao->id)
            ->orderByDesc('id')
            ->get()
            ->unique('modulo_item_id')
            ->keyBy('modulo_item_id');

        $liberada = $inscricao->status === StatusInscricao::APROVADA;
        $resultado = [];

        foreach ($pastas as $modulo) {
            $gravada = in_array($modulo->id, $gravadas, true);
            $obrigatoriosPendentes = $modulo->itens->where('obrigatorio', true)
                ->contains(fn (ModuloItem $i) => $progressos->get($i->id)?->status !== StatusProgresso::CONCLUIDO);
            $concluida = $gravada || ! $obrigatoriosPendentes;

            $resultado[] = [
                'modulo' => $modulo,
                'itens' => $modulo->itens,
                'progressos' => $progressos,
                'entregas' => $entregas,
                'gravada' => $gravada,
                'concluida' => $concluida,
                'liberada' => $liberada,
            ];
            $liberada = $liberada && $concluida;
        }

        return $resultado;
    }
}
