<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tdap\Models\Historico;
use App\Modules\Tdap\Resources\HistoricoResource;
use App\Modules\Tdap\Services\HistoricoService;
use App\Modules\Tdap\Support\EntidadeDoHistorico;
use App\Modules\Tdap\Support\EscopoDeLeitura;
use App\Modules\Tdap\Support\ExportadorCsv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Historico de auditoria do TDAP.
 *
 * RECORTE POR MUNICIPIO: quem le o estado inteiro (EscopoDeLeitura null) ve
 * tudo, pelo HistoricoService como antes. Quem e lotado num municipio ve so os
 * eventos cuja entidade leva a um municipio -- cronograma, alocacao de
 * caminhao (crono_caminhao) e viagem -- e apenas os do proprio municipio.
 * Ata, lote, prestador, caminhao e vistoria nao tem municipio unico e ficam
 * fora para esse perfil.
 *
 * O recorte mora aqui porque o HistoricoService ainda nao recebe escopo; ao
 * levar para o service, os filtros de `consultaRecortada` vao junto.
 */
class HistoricoController extends Controller
{
    private const FILTROS = ['entity_type', 'entity_id', 'tipo_evento', 'user_id', 'de', 'ate'];

    public function __construct(
        private readonly HistoricoService $service,
    ) {}

    public function index(Request $request): Response
    {
        $perPage = (int) $request->integer('per_page', 25);
        $filtros = $request->only(self::FILTROS);
        $escopo = EscopoDeLeitura::municipioId($request->user());

        $historicos = $escopo === null
            ? $this->service->listar($perPage, $filtros)
            : $this->consultaRecortada($filtros, $escopo)
                ->with('user:id,name')
                ->paginate($perPage)
                ->withQueryString();
        EntidadeDoHistorico::resolver($historicos->getCollection());

        return Inertia::render('Tdap/Historicos/Index', [
            'historicos'   => HistoricoResource::collection($historicos),
            'estatisticas' => fn () => $escopo === null
                ? $this->service->obterEstatisticas()
                : $this->estatisticasRecortadas($escopo),
            'filtros'      => $filtros,
            // Filtro por evento em lista, e nao texto livre: so os tipos que
            // existem no banco, com rotulo legivel.
            'tiposEvento'  => fn () => Historico::query()
                ->when($escopo !== null, fn (Builder $q) => $this->recortar($q, $escopo))
                ->distinct()->orderBy('tipo_evento')->pluck('tipo_evento')
                ->map(fn (string $t) => ['value' => $t, 'label' => EntidadeDoHistorico::rotuloDoEvento($t)])
                ->values(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filtros = $request->only(self::FILTROS);
        $escopo = EscopoDeLeitura::municipioId($request->user());

        $data = $escopo === null
            ? $this->service->exportar($filtros)
            : $this->consultaRecortada($filtros, $escopo)
                ->with('user:id,name')
                ->limit(5000)
                ->get()
                ->map(fn (Historico $h) => [
                    'Data Evento' => $h->data_evento?->format('d/m/Y H:i:s'),
                    'Tipo Evento' => $h->tipo_evento,
                    'Entidade'    => $h->entity_type,
                    'Entidade Id' => (int) $h->entity_id,
                    'Responsavel' => $h->user?->name ?? 'Sistema',
                    'Observacao'  => $h->obs,
                ])
                ->all();

        return ExportadorCsv::baixar($data, 'historicos');
    }

    public function show(Historico $historico, Request $request): Response
    {
        $escopo = EscopoDeLeitura::municipioId($request->user());
        abort_if(
            $escopo !== null && ! $this->recortar(Historico::query()->whereKey($historico->id), $escopo)->exists(),
            403,
        );

        EntidadeDoHistorico::resolver([$historico]);

        return Inertia::render('Tdap/Historicos/Show', [
            'historico' => HistoricoResource::make($historico->load('user')),
        ]);
    }

    /**
     * Endpoint AJAX para timeline embutida em Show de outras entidades.
     */
    public function porEntidade(Request $request): JsonResponse
    {
        $request->validate([
            'entity_type' => ['required', 'string', 'max:60'],
            'entity_id'   => ['required', 'integer', 'min:1'],
        ]);

        $escopo = EscopoDeLeitura::municipioId($request->user());

        $rows = Historico::query()
            ->doEntity((string) $request->input('entity_type'), (int) $request->integer('entity_id'))
            ->when($escopo !== null, fn (Builder $q) => $this->recortar($q, $escopo))
            ->with('user:id,name')
            ->orderByDesc('data_evento')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => HistoricoResource::collection($rows)->resolve(),
        ]);
    }

    /**
     * Mesmos filtros de HistoricoService::listar/exportar, com o recorte.
     *
     * @param  array<string, mixed>  $filtros
     */
    private function consultaRecortada(array $filtros, int $municipioId): Builder
    {
        return $this->recortar(Historico::query(), $municipioId)
            ->when($filtros['entity_type'] ?? null, fn ($q, $t) => $q->where('entity_type', (string) $t))
            ->when($filtros['entity_id'] ?? null, fn ($q, $id) => $q->where('entity_id', (int) $id))
            ->when($filtros['tipo_evento'] ?? null, fn ($q, $t) => $q->where('tipo_evento', (string) $t))
            ->when($filtros['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', (int) $id))
            ->when($filtros['de'] ?? null, fn ($q, $de) => $q->whereDate('data_evento', '>=', (string) $de))
            ->when($filtros['ate'] ?? null, fn ($q, $ate) => $q->whereDate('data_evento', '<=', (string) $ate))
            ->orderByDesc('data_evento')
            ->orderByDesc('id');
    }

    /**
     * @return array<string, int>
     */
    private function estatisticasRecortadas(int $municipioId): array
    {
        $base = fn (): Builder => $this->recortar(Historico::query(), $municipioId);

        return [
            'total'                  => $base()->count(),
            'mes_atual'              => $base()->where('data_evento', '>=', now()->startOfMonth())->count(),
            'cronogramas_envolvidos' => $base()->where('entity_type', 'cronograma')->distinct()->count('entity_id'),
        ];
    }

    /**
     * So eventos de entidade que leva ao municipio. DB::table, e nao o model,
     * para incluir cronograma/alocacao/viagem ja excluidos: o historico deles
     * continua sendo do municipio.
     */
    private function recortar(Builder $query, int $municipioId): Builder
    {
        $cronogramas = DB::table('tdap_cronogramas')
            ->where('municipio_id', $municipioId)
            ->select('id');

        $alocacoes = DB::table('tdap_crono_caminhoes as cc')
            ->join('tdap_cronogramas as c', 'c.id', '=', 'cc.cronograma_id')
            ->where('c.municipio_id', $municipioId)
            ->select('cc.id');

        $viagens = DB::table('tdap_crono_viagens as v')
            ->join('tdap_crono_caminhoes as cc', 'cc.id', '=', 'v.crono_caminhao_id')
            ->join('tdap_cronogramas as c', 'c.id', '=', 'cc.cronograma_id')
            ->where('c.municipio_id', $municipioId)
            ->select('v.id');

        return $query->where(function (Builder $q) use ($cronogramas, $alocacoes, $viagens): void {
            $q->where(fn (Builder $q) => $q->where('entity_type', 'cronograma')->whereIn('entity_id', $cronogramas))
                ->orWhere(fn (Builder $q) => $q->where('entity_type', 'crono_caminhao')->whereIn('entity_id', $alocacoes))
                ->orWhere(fn (Builder $q) => $q->where('entity_type', 'viagem')->whereIn('entity_id', $viagens));
        });
    }
}
