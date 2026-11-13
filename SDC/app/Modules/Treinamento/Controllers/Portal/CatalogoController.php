<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Enums\PublicoAlvo;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Resources\PortalTreinamentoListResource;
use App\Modules\Treinamento\Resources\TreinamentoResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CatalogoController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'categoria']);

        $query = Treinamento::query()->noCatalogo()->paraPublico(PublicoAlvo::CIDADAOS)->with('modulos');

        if (!empty($filters['search'])) {
            $query->where('titulo', 'like', "%{$filters['search']}%");
        }

        if (!empty($filters['categoria'])) {
            $query->where('categoria', $filters['categoria']);
        }

        $treinamentos = $query->orderBy('data_inicio', 'asc')->paginate(12);

        return Inertia::render('Treinamento/Portal/Catalogo', [
            'treinamentos' => PortalTreinamentoListResource::collection($treinamentos->withPath($request->url())),
            'filters' => $filters,
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
                'capa_url' => $treinamento->isEad() ? $treinamento->capaUrl(Treinamento::ROTA_CAPA_PORTAL) : null,
            ],
            'minhaInscricao' => $minhaInscricao ? [
                'id' => $minhaInscricao->id,
                'status' => $minhaInscricao->status->value,
                'status_label' => $minhaInscricao->status->getLabel(),
                'qr_code_token' => $minhaInscricao->qr_code_token,
                'certificado_disponivel' => $minhaInscricao->certificado?->status->value === 'GERADO',
                'certificado_id' => $minhaInscricao->certificado?->id,
            ] : null,
        ]);
    }
}
