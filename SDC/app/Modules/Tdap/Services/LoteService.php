<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Models\Municipio;
use App\Modules\Tdap\DTOs\LoteDTO;
use App\Modules\Tdap\Models\Lote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LoteService
{
    /**
     * Filtros da listagem aplicados em UM lugar so.
     *
     * Listagem, exportacao, estatisticas e o catalogo de municipios usam este
     * metodo: antes cada um repetia (ou esquecia) as clausulas, e os cards
     * contavam a base inteira enquanto a tabela mostrava o recorte filtrado.
     *
     * @param  array<string, mixed>  $filtros
     */
    private function aplicarFiltros(Builder $query, array $filtros): Builder
    {
        return $query
            ->when(
                array_key_exists('ativo', $filtros) && $filtros['ativo'] !== null && $filtros['ativo'] !== '',
                fn ($q) => $q->where('ativo', (bool) $filtros['ativo']),
            )
            ->when($filtros['ata_id'] ?? null, fn ($q, $id) => $q->daAta((int) $id))
            ->when($filtros['municipio_id'] ?? null, fn ($q, $id) => $q->doMunicipio((int) $id))
            ->when($filtros['prestador_id'] ?? null, fn ($q, $id) => $q->doPrestador((int) $id))
            ->when($filtros['search'] ?? null, fn ($q, $termo) => $q->buscar((string) $termo));
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listar(int $perPage = 15, array $filtros = []): LengthAwarePaginator
    {
        return $this->aplicarFiltros(Lote::query(), $filtros)
            ->with([
                'ata:id,numero,dt_inicio,dt_final',
                'municipio:id,nome,uf',
                'prestador:id,nome,cnpj',
            ])
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Municipios que de fato possuem lote no recorte atual — e a lista do filtro
     * da tela. O catalogo completo (853 municipios) nao serve aqui: oferecia
     * municipios sem nenhum lote e o resultado vinha sempre vazio.
     *
     * O proprio `municipio_id` e retirado dos filtros para que a opcao
     * selecionada continue na lista depois da busca.
     *
     * @param  array<string, mixed>  $filtros
     * @return Collection<int, array{id: int, nome: string, uf: ?string}>
     */
    public function municipiosDisponiveis(array $filtros = []): Collection
    {
        $comLote = $this->aplicarFiltros(Lote::query(), array_diff_key($filtros, ['municipio_id' => null]))
            ->select('municipio_id');

        return Municipio::query()
            ->whereIn('id', $comLote)
            ->orderBy('nome')
            ->get(['id', 'nome', 'uf'])
            ->map(fn (Municipio $m) => ['id' => $m->id, 'nome' => $m->nome, 'uf' => $m->uf]);
    }

    /**
     * Linhas planas para exportacao CSV (respeita os filtros da listagem).
     *
     * @param  array<string, mixed>  $filtros
     * @return array<int, array<string, mixed>>
     */
    public function exportar(array $filtros = []): array
    {
        $rows = $this->aplicarFiltros(Lote::query(), $filtros)
            ->with([
                'ata:id,numero',
                'municipio:id,nome,uf',
                'prestador:id,nome,cnpj',
            ])
            ->orderByDesc('id')
            ->get();

        return $rows->map(fn (Lote $l) => [
            'Numero'         => $l->numero,
            'Nome'           => $l->nome,
            'Contrato'       => $l->contrato,
            'Ata'            => $l->ata?->numero,
            'Municipio'      => $l->municipio?->nome,
            'UF'             => $l->municipio?->uf,
            'Prestador'      => $l->prestador?->nome,
            'CNPJ'           => $l->prestador?->cnpj,
            'Volume (m3)'    => number_format((float) $l->qtd_agua_m3, 2, ',', '.'),
            'Valor m3 (R$)'  => number_format((float) $l->valor_m3, 2, ',', '.'),
            'Valor Total (R$)' => number_format($l->valor_total, 2, ',', '.'),
            'Situacao'       => $l->ativo ? 'Ativo' : 'Inativo',
        ])->all();
    }

    public function obter(int $id): Lote
    {
        return Lote::query()
            ->withCount('cronogramas')
            ->with([
                'ata:id,numero,dt_inicio,dt_final',
                'municipio:id,nome,uf',
                'prestador:id,nome,cnpj,email',
            ])
            ->findOrFail($id);
    }

    public function criar(LoteDTO $dto): Lote
    {
        return DB::transaction(fn () => Lote::create($dto->toArray()));
    }

    public function atualizar(int $id, LoteDTO $dto): Lote
    {
        return DB::transaction(function () use ($id, $dto): Lote {
            $lote = Lote::findOrFail($id);
            $lote->update($dto->toArray());

            return $lote->fresh(['ata', 'municipio', 'prestador']);
        });
    }

    /**
     * A FK de tdap_cronogramas.lote_id e restrictOnDelete: sem este guard o
     * delete estourava violacao de integridade (500). Mesmo contrato de
     * AtaService::deletar — DomainException tratada no controller.
     *
     * @throws \DomainException quando ha cronograma vinculado
     */
    public function deletar(int $id): bool
    {
        $lote = Lote::query()->withCount('cronogramas')->findOrFail($id);

        if ($lote->cronogramas_count > 0) {
            throw new \DomainException(
                "Lote {$lote->numero} possui {$lote->cronogramas_count} cronograma(s) vinculado(s). Remova-os antes."
            );
        }

        return (bool) $lote->delete();
    }

    /**
     * Contadores dos cards. Recebem os MESMOS filtros da listagem — o card
     * "Total" e o numero de lotes exibidos, nao o da base inteira.
     *
     * @param  array<string, mixed>  $filtros
     * @return array<string, int|float>
     */
    public function obterEstatisticas(array $filtros = []): array
    {
        $row = $this->aplicarFiltros(Lote::query(), $filtros)
            ->selectRaw('
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE ativo = TRUE) AS ativos,
                COALESCE(SUM(qtd_agua_m3) FILTER (WHERE ativo = TRUE), 0) AS volume_total_m3,
                COALESCE(SUM(qtd_agua_m3 * valor_m3) FILTER (WHERE ativo = TRUE), 0) AS valor_total
            ')
            ->first();

        return [
            'total'           => (int) ($row->total ?? 0),
            'ativos'          => (int) ($row->ativos ?? 0),
            'volume_total_m3' => (float) ($row->volume_total_m3 ?? 0),
            'valor_total'     => (float) ($row->valor_total ?? 0),
        ];
    }
}
