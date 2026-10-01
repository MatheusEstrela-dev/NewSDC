<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Queries;

use App\Modules\Inventario\Enums\StatusMovimentacao;
use App\Modules\Inventario\Enums\StatusRemanejamento;
use App\Modules\Inventario\Models\Movimentacao;
use App\Modules\Inventario\Models\Remanejamento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Listagem da tela de movimentacoes: lotes de remanejamento e, em paginacao
 * propria, as movimentacoes avulsas (emprestimos).
 *
 * O filtro `status` aceita os valores das duas listas (ativo/desfeito do lote,
 * ativo/devolvido da avulsa) e cada lista traduz para o seu vocabulario.
 */
final class RemanejamentoListagemQuery
{
    public const POR_PAGINA = 15;

    /** @param array{search?:string|null,status?:string|null,data?:string|null} $filtros */
    public function lotes(array $filtros): LengthAwarePaginator
    {
        $query = Remanejamento::query()->with([
            'registradoPor:id,name',
            'pessoas' => static fn ($q) => $q->orderBy('id'),
            'pessoas.usuario:id,name',
            'itensRemanejados' => static fn ($q) => $q->orderBy('id'),
            'itensRemanejados.equipamento' => static fn ($q) => $q->withTrashed()->select('id', 'nome', 'patrimonio'),
            'itensRemanejados.usuarioDestino:id,name',
            'itensRemanejados.estacaoDestino:id,nome',
            'demanda:id,protocolo',
        ]);

        $termo = trim((string) ($filtros['search'] ?? ''));
        if ($termo !== '') {
            $like = '%'.$termo.'%';
            $query->where(static fn (Builder $q) => $q
                ->whereHas('pessoas.usuario', static fn (Builder $u) => $u->where('name', 'ilike', $like))
                ->orWhereHas('itensRemanejados.equipamento', static fn (Builder $e) => $e->where(
                    static fn (Builder $x) => $x->where('nome', 'ilike', $like)->orWhere('patrimonio', 'ilike', $like)
                )));
        }

        $status = match ($filtros['status'] ?? null) {
            'ativo' => StatusRemanejamento::ATIVO,
            'desfeito', 'devolvido' => StatusRemanejamento::DESFEITO,
            default => null,
        };
        if ($status !== null) {
            $query->where('status', $status->value);
        }
        if (! empty($filtros['data'])) {
            $query->whereDate('created_at', $filtros['data']);
        }

        return $query->latest('created_at')->paginate(self::POR_PAGINA)->withQueryString();
    }

    /** @param array{search?:string|null,status?:string|null} $filtros */
    public function avulsas(array $filtros): LengthAwarePaginator
    {
        $query = Movimentacao::query()->whereNull('lote_id')
            ->with(['equipamento:id,nome,patrimonio', 'registradoPor:id,name', 'usuarioDestino:id,name']);

        $termo = trim((string) ($filtros['search'] ?? ''));
        if ($termo !== '') {
            $like = '%'.$termo.'%';
            $query->whereHas('equipamento', static fn (Builder $e) => $e->where(
                static fn (Builder $x) => $x->where('nome', 'ilike', $like)->orWhere('patrimonio', 'ilike', $like)
            ));
        }

        $status = match ($filtros['status'] ?? null) {
            'ativo' => StatusMovimentacao::ATIVO,
            'desfeito', 'devolvido' => StatusMovimentacao::DEVOLVIDO,
            default => null,
        };
        if ($status !== null) {
            $query->where('status', $status->value);
        }

        return $query->latest('data_saida')->paginate(self::POR_PAGINA, ['*'], 'pagina_avulsas')->withQueryString();
    }
}
