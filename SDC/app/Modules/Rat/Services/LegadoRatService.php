<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\Models\LegadoRat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Consultas do arquivo morto do RAT legado (somente leitura).
 *
 * Resolve municipio/cobrade/tipo por join numa unica query (sem N+1). A ponte de
 * municipio e: legado_rat.municipio_id -> cedec_municipio.id -> Codmundv ->
 * municipios.codigo_ibge.
 */
class LegadoRatService
{
    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listar(array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->baseQuery()
            ->when(
                $this->texto($filtros),
                fn (Builder $q, string $termo) => $q->where(function (Builder $sub) use ($termo): void {
                    $like = '%'.$termo.'%';
                    $sub->where('legado_rat.num_ocorrencia', 'ILIKE', $like)
                        ->orWhere('legado_rat.envolvidos', 'ILIKE', $like)
                        ->orWhere('legado_rat.nome_operacao', 'ILIKE', $like)
                        ->orWhere('legado_rat.lugar_descricao', 'ILIKE', $like);
                })
            )
            ->when(
                isset($filtros['municipio_id']) && $filtros['municipio_id'] !== '',
                fn (Builder $q) => $q->where('m.id', (int) $filtros['municipio_id'])
            )
            ->when(
                isset($filtros['tipo_id']) && $filtros['tipo_id'] !== '',
                fn (Builder $q) => $q->where('legado_rat.ocorrencia_id', (int) $filtros['tipo_id'])
            )
            ->when(
                isset($filtros['ano']) && $filtros['ano'] !== '',
                fn (Builder $q) => $q->whereRaw('EXTRACT(YEAR FROM legado_rat.dt_ocorrencia) = ?', [(int) $filtros['ano']])
            )
            ->orderByRaw('legado_rat.dt_ocorrencia DESC NULLS LAST')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function encontrar(int $id): ?LegadoRat
    {
        /** @var LegadoRat|null $registro */
        $registro = $this->baseQuery()
            ->addSelect('a.alvo as alvo_descricao')
            ->leftJoin('legado_rat_alvo as a', 'a.id', '=', 'legado_rat.alvo_id')
            ->where('legado_rat.id', $id)
            ->first();

        return $registro;
    }

    /**
     * Estatisticas para os cards do topo (que tambem sao filtros rapidos).
     *
     * @return array<string, int>
     */
    public function estatisticas(): array
    {
        return Cache::remember('legado_rat:stats', 600, function (): array {
            $anoAtual = (int) date('Y');

            return [
                'total' => LegadoRat::query()->count(),
                'municipios' => (int) LegadoRat::query()->whereNotNull('municipio_id')->distinct()->count('municipio_id'),
                'esteAno' => LegadoRat::query()->whereRaw('EXTRACT(YEAR FROM dt_ocorrencia) = ?', [$anoAtual])->count(),
            ];
        });
    }

    /**
     * Opcoes para os selects de filtro (municipios com registros, tipos e anos).
     *
     * @return array<string, mixed>
     */
    public function opcoesFiltro(): array
    {
        return Cache::remember('legado_rat:filtros', 600, fn (): array => [
            'municipios' => DB::table('legado_rat as r')
                ->join('cedec_municipio as cm', 'cm.id', '=', 'r.municipio_id')
                ->join('municipios as m', 'm.codigo_ibge', '=', 'cm.Codmundv')
                ->select('m.id', 'm.nome')
                ->distinct()
                ->orderBy('m.nome')
                ->get(),
            'tipos' => DB::table('legado_rat_ocorrencia')
                ->select('id', 'descricao')
                ->orderBy('descricao')
                ->get(),
            'anos' => DB::table('legado_rat')
                ->selectRaw('DISTINCT EXTRACT(YEAR FROM dt_ocorrencia)::int as ano')
                ->whereNotNull('dt_ocorrencia')
                ->orderByDesc('ano')
                ->pluck('ano'),
        ]);
    }

    /**
     * Lista os anexos (fotos/documentos) do RAT legado a partir do disco
     * `legado_rat`, na pasta {id}/ — mesma convencao do legado (rat_uploads/{id}).
     *
     * @return array<int, array{nome: string, url: string, is_imagem: bool}>
     */
    public function anexos(int $id): array
    {
        $disk = Storage::disk('legado_rat');
        $dir = (string) $id;

        if (! $disk->exists($dir)) {
            return [];
        }

        $imagens = ['jpg', 'jpeg', 'jpe', 'jfif', 'png', 'gif', 'webp', 'bmp', 'svg', 'avif'];

        return collect($disk->files($dir))
            ->map(function (string $path) use ($id, $imagens): array {
                $nome = basename($path);
                $ext = strtolower(pathinfo($nome, PATHINFO_EXTENSION));

                return [
                    'nome' => $nome,
                    'url' => route('rat.arquivados.anexo', ['id' => $id, 'arquivo' => $nome]),
                    'is_imagem' => in_array($ext, $imagens, true),
                ];
            })
            ->sortBy('nome')
            ->values()
            ->all();
    }

    /**
     * Resolve o caminho seguro de um anexo (anti path-traversal) ou null.
     */
    public function caminhoAnexo(int $id, string $arquivo): ?string
    {
        $arquivo = basename($arquivo);
        $path = $id.'/'.$arquivo;

        return Storage::disk('legado_rat')->exists($path) ? $path : null;
    }

    /**
     * Query base com os joins de resolucao (municipio, cobrade, tipo).
     */
    private function baseQuery(): Builder
    {
        return LegadoRat::query()
            ->leftJoin('cedec_municipio as cm', 'cm.id', '=', 'legado_rat.municipio_id')
            ->leftJoin('municipios as m', 'm.codigo_ibge', '=', 'cm.Codmundv')
            ->leftJoin('dec_cobrade as c', 'c.id', '=', 'legado_rat.cobrade_id')
            ->leftJoin('legado_rat_ocorrencia as o', 'o.id', '=', 'legado_rat.ocorrencia_id')
            ->select(
                'legado_rat.*',
                'm.nome as municipio_nome',
                'cm.nome as cedec_nome',
                'c.nome as cobrade_nome',
                'c.codigo as cobrade_codigo',
                'o.cod as tipo_codigo',
                'o.descricao as tipo_descricao',
            );
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function texto(array $filtros): ?string
    {
        $termo = trim((string) ($filtros['search'] ?? ''));

        return $termo === '' ? null : $termo;
    }
}
