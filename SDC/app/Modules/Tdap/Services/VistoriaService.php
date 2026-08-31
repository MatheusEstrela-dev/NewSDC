<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Modules\Tdap\DTOs\VistoriaDTO;
use App\Modules\Tdap\Enums\ParecerVistoria;
use App\Modules\Tdap\Models\Vistoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VistoriaService
{
    /**
     * Filtros da listagem aplicados em UM lugar so — listagem, exportacao e
     * cards usam este metodo (antes cada um repetia as clausulas).
     *
     * @param  array<string, mixed>  $filtros
     */
    private function aplicarFiltros(Builder $query, array $filtros): Builder
    {
        return $query
            ->when($filtros['parecer'] ?? null, fn ($q, $p) => $q->where('parecer', (string) $p))
            ->when($filtros['placa_id'] ?? null, fn ($q, $id) => $q->doCaminhao((int) $id))
            ->when(! empty($filtros['vigente']), fn ($q) => $q->vigente())
            ->when($filtros['search'] ?? null, fn ($q, $termo) => $q->buscar((string) $termo));
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listar(int $perPage = 15, array $filtros = []): LengthAwarePaginator
    {
        return $this->aplicarFiltros(Vistoria::query(), $filtros)
            ->with(['caminhao:id,placa,marca,modelo,prestador_id', 'caminhao.prestador:id,nome'])
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Linhas planas para exportacao CSV (respeita os filtros da listagem).
     *
     * @param  array<string, mixed>  $filtros
     * @return array<int, array<string, mixed>>
     */
    public function exportar(array $filtros = []): array
    {
        $rows = $this->aplicarFiltros(Vistoria::query(), $filtros)
            ->with(['caminhao:id,placa,marca,modelo,capacidade_m3,prestador_id', 'caminhao.prestador:id,nome'])
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->get();

        return $rows->map(fn (Vistoria $v) => [
            'Placa'        => $v->caminhao?->placa,
            'Modelo'       => $v->caminhao?->modelo ?? $v->modelo,
            'Prestador'    => $v->caminhao?->prestador?->nome,
            'Data'         => $v->data?->format('d/m/Y'),
            'Vistoriador'  => $v->nome,
            'Edital'       => $v->edital,
            'Ficha'        => $v->ficha,
            'Lacre'        => $v->lacre,
            'Parecer'      => $v->parecer?->label() ?? (string) $v->parecer?->value,
            'Vigente'      => $v->esta_vigente ? 'Sim' : 'Nao',
            'Validade'     => $v->data?->copy()->addMonths(Vistoria::VIGENCIA_MESES)->format('d/m/Y'),
            'Capacidade (m3)' => number_format((float) $v->capacidade, 2, ',', '.'),
        ])->all();
    }

    public function obter(int $id): Vistoria
    {
        return Vistoria::query()
            ->with([
                'caminhao:id,placa,marca,modelo,cor,ano,capacidade_m3,prestador_id',
                'caminhao.prestador:id,nome,cnpj',
                'user:id,name,email',
            ])
            ->findOrFail($id);
    }

    public function criar(VistoriaDTO $dto): Vistoria
    {
        return DB::transaction(function () use ($dto): Vistoria {
            $payload = $dto->toArray();
            $payload['user_id'] = Auth::id();

            return Vistoria::create($payload);
        });
    }

    public function atualizar(int $id, VistoriaDTO $dto): Vistoria
    {
        return DB::transaction(function () use ($id, $dto): Vistoria {
            $vistoria = Vistoria::findOrFail($id);
            $vistoria->update($dto->toArray());

            return $vistoria->fresh();
        });
    }

    public function deletar(int $id): bool
    {
        $vistoria = Vistoria::findOrFail($id);

        return (bool) $vistoria->delete();
    }

    /**
     * Contadores dos cards.
     *
     * Recebem os filtros de RECORTE (busca e caminhao) para que o card "Total"
     * seja o numero de vistorias listadas, e nao o da base inteira. `parecer` e
     * `vigente` ficam de fora de proposito: sao as proprias dimensoes dos
     * cards, e considera-los zeraria os demais contadores assim que um card
     * fosse clicado — deixando o usuario sem como voltar.
     *
     * @param  array<string, mixed>  $filtros
     * @return array<string, int>
     */
    public function obterEstatisticas(array $filtros = []): array
    {
        $limite = now()->subMonths(Vistoria::VIGENCIA_MESES)->toDateString();

        $recorte = array_diff_key($filtros, ['parecer' => null, 'vigente' => null]);

        $row = $this->aplicarFiltros(Vistoria::query(), $recorte)
            ->selectRaw('
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE parecer = ?) AS aprovadas,
                COUNT(*) FILTER (WHERE parecer = ?) AS reprovadas,
                COUNT(*) FILTER (WHERE parecer = ? AND data >= ?) AS vigentes,
                COUNT(*) FILTER (WHERE parecer = ? AND data < ?) AS expiradas
            ', [
                ParecerVistoria::Aprovada->value,
                ParecerVistoria::Reprovada->value,
                ParecerVistoria::Aprovada->value, $limite,
                ParecerVistoria::Aprovada->value, $limite,
            ])
            ->first();

        return [
            'total'      => (int) ($row->total ?? 0),
            'aprovadas'  => (int) ($row->aprovadas ?? 0),
            'reprovadas' => (int) ($row->reprovadas ?? 0),
            'vigentes'   => (int) ($row->vigentes ?? 0),
            'expiradas'  => (int) ($row->expiradas ?? 0),
        ];
    }
}
