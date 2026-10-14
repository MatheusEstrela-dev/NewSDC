<?php

declare(strict_types=1);

namespace App\Modules\Estoque\Services;

use App\Modules\Estoque\Enums\CategoriaEstoque;
use App\Modules\Estoque\Models\Almoxarifado;
use App\Modules\Estoque\Models\Kit;
use App\Modules\Estoque\Models\Lote;
use App\Modules\Estoque\Models\MovimentacaoEstoque;
use App\Modules\Estoque\Models\Produto;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Leitura do estoque: painel, lista de lotes (scroll infinito), inventario por
 * produto (API), ocupacao por almoxarifado, alertas, kits e extrato.
 */
class EstoqueConsultaService
{
    /**
     * Lotes com saldo, agrupaveis por categoria (#33). Pagina simples: a tela
     * carrega a proxima pagina ao chegar no fim da lista.
     *
     * @param  array<string, mixed>  $filtros  busca, categoria, almoxarifado_id, validade, status
     */
    public function lotes(array $filtros, int $porPagina = 30, ?int $pagina = null): Paginator
    {
        $hoje = Carbon::today();

        $query = Lote::query()
            ->select('estoque_lotes.*')
            ->join('estoque_produtos as p', 'p.id', '=', 'estoque_lotes.produto_id')
            ->whereNull('p.deleted_at')
            ->where('estoque_lotes.saldo', '>', 0)
            ->with(['produto', 'almoxarifado:id,nome'])
            ->when($filtros['busca'] ?? null, function (Builder $q, string $busca): void {
                $termo = '%' . mb_strtolower($busca) . '%';
                $q->where(fn ($w) => $w->whereRaw('LOWER(p.nome) LIKE ?', [$termo])
                    ->orWhereRaw('LOWER(p.sku) LIKE ?', [$termo])
                    ->orWhereRaw('LOWER(p.patrimonio) LIKE ?', [$termo])
                    ->orWhereRaw('LOWER(estoque_lotes.codigo) LIKE ?', [$termo]));
            })
            ->when($filtros['categoria'] ?? null, fn ($q, $c) => $q->where('p.categoria', $c))
            ->when($filtros['almoxarifado_id'] ?? null, fn ($q, $a) => $q->where('estoque_lotes.almoxarifado_id', (int) $a))
            ->when($filtros['validade'] ?? null, function (Builder $q, string $v) use ($hoje): void {
                match ($v) {
                    'vencido'  => $q->whereDate('estoque_lotes.validade', '<', $hoje),
                    'vence_30' => $q->whereBetween('estoque_lotes.validade', [$hoje, $hoje->copy()->addDays(Lote::DIAS_ALERTA_VALIDADE)]),
                    'em_dia'   => $q->where(fn ($w) => $w->whereNull('estoque_lotes.validade')
                        ->orWhereDate('estoque_lotes.validade', '>', $hoje->copy()->addDays(Lote::DIAS_ALERTA_VALIDADE))),
                    default    => null,
                };
            })
            ->when(($filtros['status'] ?? null) === 'quarentena', fn ($q) => $q->where('estoque_lotes.bloqueado', true))
            ->orderBy('p.categoria')
            ->orderBy('p.nome')
            ->orderByRaw('estoque_lotes.validade IS NULL, estoque_lotes.validade')
            ->orderBy('estoque_lotes.id');

        return $query->simplePaginate($porPagina, ['*'], 'page', $pagina)->through(fn (Lote $l) => $this->linhaDeLote($l));
    }

    /**
     * Inventario por produto (API /api/v1/inventario): saldo total, saldo que
     * pode sair e o sinal de estoque baixo.
     */
    public function produtos(array $filtros, int $porPagina = 30): Paginator
    {
        $saldos = Lote::query()
            ->selectRaw('produto_id, SUM(saldo) AS saldo_total')
            ->selectRaw('SUM(CASE WHEN bloqueado = false AND (validade IS NULL OR validade >= CURRENT_DATE) THEN saldo ELSE 0 END) AS saldo_disponivel')
            ->when($filtros['almoxarifado_id'] ?? null, fn ($q, $a) => $q->where('almoxarifado_id', (int) $a))
            ->groupBy('produto_id');

        return Produto::query()
            ->leftJoinSub($saldos, 's', 's.produto_id', '=', 'estoque_produtos.id')
            ->select('estoque_produtos.*')
            ->selectRaw('COALESCE(s.saldo_total, 0) AS saldo_total, COALESCE(s.saldo_disponivel, 0) AS saldo_disponivel')
            ->when($filtros['busca'] ?? null, function (Builder $q, string $busca): void {
                $termo = '%' . mb_strtolower($busca) . '%';
                $q->where(fn ($w) => $w->whereRaw('LOWER(nome) LIKE ?', [$termo])
                    ->orWhereRaw('LOWER(sku) LIKE ?', [$termo])
                    ->orWhereRaw('LOWER(patrimonio) LIKE ?', [$termo]));
            })
            ->when($filtros['categoria'] ?? null, fn ($q, $c) => $q->where('categoria', $c))
            ->when($filtros['patrimonio'] ?? null, fn ($q, $p) => $q->where('patrimonio', $p))
            ->orderBy('categoria')
            ->orderBy('nome')
            ->simplePaginate($porPagina)
            ->through(fn (Produto $p) => $this->linhaDeProduto($p));
    }

    /** @return array<string, int> */
    public function estatisticas(): array
    {
        $hoje = Carbon::today();
        $limite = $hoje->copy()->addDays(Lote::DIAS_ALERTA_VALIDADE);

        $lotes = Lote::query()
            ->join('estoque_produtos as p', 'p.id', '=', 'estoque_lotes.produto_id')
            ->whereNull('p.deleted_at')
            ->where('estoque_lotes.saldo', '>', 0)
            ->selectRaw('COALESCE(SUM(estoque_lotes.saldo), 0) AS saldo_total')
            ->selectRaw('COUNT(*) FILTER (WHERE validade BETWEEN ? AND ?) AS vencendo', [$hoje, $limite])
            ->selectRaw('COUNT(*) FILTER (WHERE validade < ?) AS vencidos', [$hoje])
            ->selectRaw('COUNT(*) FILTER (WHERE bloqueado) AS quarentena')
            ->first();

        return [
            'produtos'       => Produto::query()->where('ativo', true)->count(),
            'saldo_total'    => (int) $lotes->saldo_total,
            'estoque_baixo'  => count($this->produtosAbaixoDoMinimo()),
            'lotes_vencendo' => (int) $lotes->vencendo,
            'lotes_vencidos' => (int) $lotes->vencidos,
            'em_quarentena'  => (int) $lotes->quarentena,
            'kits_montaveis' => (int) collect($this->kits())->sum('montaveis'),
            'requisicoes'    => MovimentacaoEstoque::query()
                ->where('tipo', MovimentacaoEstoque::SAIDA)
                ->where('created_at', '>=', now()->startOfMonth())
                ->distinct()->count('romaneio'),
        ];
    }

    /** Ocupacao de cada almoxarifado ativo (#33, ProgressBar). */
    public function ocupacao(): array
    {
        $saldos = Lote::query()->selectRaw('almoxarifado_id, SUM(saldo) AS saldo')->groupBy('almoxarifado_id')->pluck('saldo', 'almoxarifado_id');

        return Almoxarifado::query()->where('ativo', true)->orderBy('nome')->get()
            ->map(function (Almoxarifado $a) use ($saldos): array {
                $saldo = (int) ($saldos[$a->id] ?? 0);

                return [
                    'id'         => $a->id,
                    'nome'       => $a->nome,
                    'tipo'       => $a->tipo,
                    'saldo'      => $saldo,
                    'capacidade' => $a->capacidade,
                    'percentual' => $a->capacidade ? min(100, (int) round($saldo * 100 / $a->capacidade)) : null,
                ];
            })->all();
    }

    /** Alertas do painel: vencidos, vencendo, quarentena e abaixo do minimo. */
    public function alertas(int $limite = 9): array
    {
        $hoje = Carbon::today();
        $alertas = [];

        $lotes = Lote::query()->with('produto:id,nome', 'almoxarifado:id,nome')
            ->where('saldo', '>', 0)
            ->where(fn ($q) => $q->where('bloqueado', true)
                ->orWhereDate('validade', '<=', $hoje->copy()->addDays(Lote::DIAS_ALERTA_VALIDADE)))
            ->orderByRaw('validade IS NULL, validade')
            ->limit($limite)
            ->get();

        foreach ($lotes as $l) {
            $status = $l->status($hoje);
            $alertas[] = [
                'id'     => "lote-{$l->id}",
                'tipo'   => match ($status) { 'critico' => 'Vencido', 'quarentena' => 'Quarentena', default => 'Vence em breve' },
                'nivel'  => $status === 'atencao' ? 'atencao' : 'critico',
                'titulo' => sprintf('%s - lote %s (%d)%s', $l->produto?->nome, $l->codigo, $l->saldo,
                    $l->validade ? ' - validade ' . $l->validade->format('d/m/Y') : ''),
            ];
        }

        foreach (array_slice($this->produtosAbaixoDoMinimo(), 0, $limite) as $p) {
            $alertas[] = [
                'id'     => "minimo-{$p['id']}",
                'tipo'   => 'Estoque baixo',
                'nivel'  => 'atencao',
                'titulo' => "{$p['nome']}: {$p['saldo']} de minimo {$p['minimo']}",
            ];
        }

        return array_slice($alertas, 0, $limite);
    }

    /** Kits com quantos podem ser montados com o saldo disponivel. */
    public function kits(?int $almoxarifadoId = null): array
    {
        $disponivel = Lote::query()->disponivel()
            ->when($almoxarifadoId, fn ($q, $a) => $q->where('almoxarifado_id', $a))
            ->selectRaw('produto_id, SUM(saldo) AS saldo')->groupBy('produto_id')->pluck('saldo', 'produto_id');

        return Kit::query()->with('itens.produto:id,nome,unidade_base')->orderBy('nome')->get()
            ->map(fn (Kit $k) => [
                'id'          => $k->id,
                'codigo'      => $k->codigo,
                'nome'        => $k->nome,
                'itens'       => $k->itens->map(fn ($i) => ['produto_id' => $i->produto_id, 'nome' => $i->produto?->nome, 'quantidade' => $i->quantidade])->values()->all(),
                'componentes' => $k->itens->map(fn ($i) => "{$i->quantidade}x {$i->produto?->nome}")->values()->all(),
                'montaveis'   => $k->itens->isEmpty() ? 0 : (int) $k->itens
                    ->map(fn ($i) => intdiv((int) ($disponivel[$i->produto_id] ?? 0), max(1, $i->quantidade)))
                    ->min(),
            ])->all();
    }

    public function movimentacoes(array $filtros = [], int $porPagina = 20): Paginator
    {
        return MovimentacaoEstoque::query()
            ->with(['produto:id,nome,unidade_base', 'lote:id,codigo', 'almoxarifado:id,nome', 'destinoMunicipio:id,nome', 'user:id,name'])
            ->when($filtros['tipo'] ?? null, fn ($q, $t) => $q->where('tipo', $t))
            ->when($filtros['produto_id'] ?? null, fn ($q, $p) => $q->where('produto_id', (int) $p))
            ->when($filtros['almoxarifado_id'] ?? null, fn ($q, $a) => $q->where('almoxarifado_id', (int) $a))
            ->when($filtros['romaneio'] ?? null, fn ($q, $r) => $q->where('romaneio', $r))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->simplePaginate($porPagina)
            ->through(fn (MovimentacaoEstoque $m) => $this->linhaDeMovimentacao($m));
    }

    public function linhaDeMovimentacao(MovimentacaoEstoque $m): array
    {
        $destino = match ($m->destino_tipo) {
            'municipio' => 'Municipio ' . ($m->destinoMunicipio?->nome ?? ''),
            'abrigo'    => 'Abrigo ' . ($m->destino_descricao ?? ''),
            default     => $m->destino_descricao ? "Fornecedor/doador {$m->destino_descricao}" : 'Entrada',
        };

        return [
            'id'          => $m->id,
            'data'        => $m->created_at?->toIso8601String(),
            'tipo'        => $m->tipo,
            'produto_id'  => $m->produto_id,
            'produto'     => $m->produto?->nome,
            'quantidade'  => $m->quantidade,
            'unidade'     => $m->produto?->unidade_base,
            'lote'        => $m->lote?->codigo,
            'almoxarifado' => $m->almoxarifado?->nome,
            'saldo_apos'  => $m->saldo_apos,
            'romaneio'    => $m->romaneio,
            'referencia'  => $m->romaneio ?? ('lote ' . $m->lote?->codigo),
            'motivo'      => $destino,
            'destino_tipo' => $m->destino_tipo,
            'usuario'     => $m->user?->name,
        ];
    }

    public function linhaDeLote(Lote $l): array
    {
        $produto = $l->produto;

        return [
            'id'              => $l->id,
            'produto_id'      => $l->produto_id,
            'nome'            => $produto?->nome,
            'sku'             => $produto?->sku,
            'patrimonio'      => $produto?->patrimonio,
            'categoria'       => $produto?->categoria?->value,
            'categoria_label' => $produto?->categoria?->label(),
            'unidade_base'    => $produto?->unidade_base,
            'lote'            => $l->codigo,
            'validade'        => $l->validade?->toDateString(),
            'fornecedor'      => $l->fornecedor,
            'endereco'        => $l->endereco,
            'saldo'           => $l->saldo,
            'almoxarifado_id' => $l->almoxarifado_id,
            'almoxarifado'    => $l->almoxarifado?->nome,
            'status'          => $l->status(),
            'bloqueado'       => $l->bloqueado,
        ];
    }

    public function linhaDeProduto(Produto $p): array
    {
        $saldo = (int) ($p->getAttribute('saldo_total') ?? 0);

        return [
            'id'                => $p->id,
            'sku'               => $p->sku,
            'nome'              => $p->nome,
            'categoria'         => $p->categoria?->value,
            'categoria_label'   => $p->categoria?->label(),
            'unidade_base'      => $p->unidade_base,
            'patrimonio'        => $p->patrimonio,
            'estoque_minimo'    => $p->estoque_minimo,
            'controla_validade' => $p->controla_validade,
            'ativo'             => $p->ativo,
            'saldo_total'       => $saldo,
            'saldo_disponivel'  => (int) ($p->getAttribute('saldo_disponivel') ?? 0),
            'estoque_baixo'     => $p->estoque_minimo > 0 && $saldo < $p->estoque_minimo,
        ];
    }

    /** Catalogo para os seletores de entrada/saida, com o saldo disponivel por almoxarifado. */
    public function catalogo(): array
    {
        $disponivel = Lote::query()->disponivel()
            ->selectRaw('produto_id, almoxarifado_id, SUM(saldo) AS saldo')
            ->groupBy('produto_id', 'almoxarifado_id')->get()
            ->groupBy('produto_id')
            ->map(fn ($linhas) => $linhas->pluck('saldo', 'almoxarifado_id')->map(fn ($s) => (int) $s));

        return Produto::query()->where('ativo', true)->orderBy('nome')
            ->get(['id', 'sku', 'nome', 'categoria', 'unidade_base', 'controla_validade', 'estoque_minimo', 'patrimonio'])
            ->map(fn (Produto $p) => [
                'id'                => $p->id,
                'sku'               => $p->sku,
                'nome'              => $p->nome,
                'categoria'         => $p->categoria?->value,
                'unidade_base'      => $p->unidade_base,
                'controla_validade' => $p->controla_validade,
                'estoque_minimo'    => $p->estoque_minimo,
                'patrimonio'        => $p->patrimonio,
                'disponivel'        => (object) ($disponivel[$p->id] ?? collect())->all(),
            ])->all();
    }

    /** @return list<array{id: int, nome: string, saldo: int, minimo: int}> */
    private function produtosAbaixoDoMinimo(): array
    {
        return DB::table('estoque_produtos as p')
            ->leftJoin('estoque_lotes as l', 'l.produto_id', '=', 'p.id')
            ->whereNull('p.deleted_at')
            ->where('p.ativo', true)
            ->where('p.estoque_minimo', '>', 0)
            ->groupBy('p.id', 'p.nome', 'p.estoque_minimo')
            ->havingRaw('COALESCE(SUM(l.saldo), 0) < p.estoque_minimo')
            ->orderBy('p.nome')
            ->get(['p.id', 'p.nome', 'p.estoque_minimo', DB::raw('COALESCE(SUM(l.saldo), 0) AS saldo')])
            ->map(fn ($r) => ['id' => (int) $r->id, 'nome' => $r->nome, 'saldo' => (int) $r->saldo, 'minimo' => (int) $r->estoque_minimo])
            ->all();
    }

    /** @return list<array{value: string, label: string}> */
    public function categorias(): array
    {
        return CategoriaEstoque::options();
    }
}
