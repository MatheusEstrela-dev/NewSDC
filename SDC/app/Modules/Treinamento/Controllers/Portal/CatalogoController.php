<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Enums\CategoriaOferta;
use App\Modules\Treinamento\Enums\PublicoAlvo;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Resources\PortalTreinamentoListResource;
use App\Modules\Treinamento\Resources\TreinamentoResource;
use App\Modules\Treinamento\Services\InscricaoService;
use App\Modules\Treinamento\Support\ClassificacaoDeOferta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CatalogoController extends Controller
{
    public function __construct(
        private readonly InscricaoService $inscricaoService
    ) {
    }

    public function index(Request $request): Response
    {
        // ?categoria= e a categoria de oferta (mosaico); valor invalido e ignorado.
        $categoria = CategoriaOferta::daRequisicao($request->query('categoria'));
        $filters = array_filter([
            'search' => $request->query('search'),
            'categoria' => $categoria?->value,
        ], fn ($valor) => $valor !== null && $valor !== '');

        $query = Treinamento::query()->noCatalogo()->paraPublico(PublicoAlvo::CIDADAOS);

        if (!empty($filters['search'])) {
            $query->buscarTexto((string) $filters['search'], ['titulo']);
        }

        // As pastas contam o catalogo (com a busca), antes do filtro de categoria.
        $totais = ClassificacaoDeOferta::totais($query);

        if ($categoria !== null) {
            $query->daCategoriaOferta($categoria);
        }

        // Raiz sem busca: so as pastas, sem listar cursos. Raiz com busca: lista
        // plana de todas as categorias. Dentro da pasta: cursos paginados dela.
        $soPastas = $categoria === null && empty($filters['search']);

        $treinamentos = $soPastas
            ? ['data' => [], 'meta' => null]
            : PortalTreinamentoListResource::collection(
                $query->with('modulos')->orderBy('data_inicio', 'asc')->paginate(12)->withPath($request->url())
            );

        return Inertia::render('Treinamento/Portal/Catalogo', [
            'treinamentos' => $treinamentos,
            'filters' => $filters,
            'categorias' => CategoriaOferta::mosaico($totais, $categoria),
        ]);
    }

    public function show(string $slug): Response
    {
        $treinamento = Treinamento::query()
            ->noCatalogo()
            ->paraPublico(PublicoAlvo::CIDADAOS)
            ->where('link_publico_slug', $slug)
            ->with('modulos')
            ->firstOrFail();

        $cidadao = Auth::guard('cidadao')->user();

        $minhaInscricao = $cidadao
            ? $treinamento->inscricoes()
                ->where('inscrito_type', $cidadao::class)
                ->where('inscrito_id', $cidadao->id)
                ->with('certificado')
                ->first()
            : null;

        return Inertia::render('Treinamento/Portal/Detalhe', [
            // A capa do SDC exige permissao de servidor: aqui vai a do portal.
            'treinamento' => [
                ...(new TreinamentoResource($treinamento))->resolve(),
                'capa_url' => $treinamento->capaUrl(Treinamento::ROTA_CAPA_PORTAL),
            ],
            'sexoPadrao' => $this->inscricaoService->sexoDaUltimaInscricao($cidadao)?->value,
            'dataNascimentoPadrao' => $this->inscricaoService->dataNascimentoDaUltimaInscricao($cidadao),
            'minhaInscricao' => $minhaInscricao ? [
                'id' => $minhaInscricao->id,
                'status' => $minhaInscricao->status->value,
                'status_label' => $minhaInscricao->status->getLabel(),
                'qr_code_token' => $minhaInscricao->qr_code_token,
                'certificado_disponivel' => (bool) $minhaInscricao->certificado?->liberadoAoAluno(),
                'certificado_id' => $minhaInscricao->certificado?->id,
            ] : null,
        ]);
    }
}
