<?php

declare(strict_types=1);

namespace App\Modules\Rat\Controllers;

use App\Modules\Rat\Http\Resources\LegadoRatListResource;
use App\Modules\Rat\Http\Resources\LegadoRatResource;
use App\Modules\Rat\Services\LegadoRatService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Arquivo morto do RAT legado (somente leitura): listagem, detalhe e impressao.
 *
 * Pagina propria e separada do RAT moderno, pois o schema legado (com_rat) e
 * incompativel com rat_ocorrencias. Autorizacao por `rat.arquivados.view`
 * (definida nas rotas via middleware can:).
 */
class LegadoRatController extends BaseController
{
    public function __construct(private readonly LegadoRatService $service) {}

    public function index(Request $request): Response
    {
        $filtros = $request->only(['search', 'municipio_id', 'tipo_id', 'ano']);

        return Inertia::render('LegadoRatIndex', [
            'rats' => LegadoRatListResource::collection(
                $this->service->listar($filtros, (int) $request->integer('per_page', 15))
            ),
            'statistics' => $this->service->estatisticas(),
            'filterOptions' => $this->service->opcoesFiltro(),
            'filters' => $filtros,
        ]);
    }

    public function show(int $id): Response
    {
        $registro = $this->service->encontrar($id);
        abort_if($registro === null, 404, 'Registro do arquivo morto nao encontrado.');

        return Inertia::render('LegadoRatShow', [
            'rat' => (new LegadoRatResource($registro))->resolve(),
            'anexos' => $this->service->anexos($id),
        ]);
    }

    /**
     * Streama um anexo do RAT legado (arquivo morto) do disco `legado_rat`.
     * Permissionado por rat.arquivados.view (middleware da rota) e protegido
     * contra path-traversal no service (basename + verificacao de existencia).
     */
    public function anexo(int $id, string $arquivo): StreamedResponse
    {
        $path = $this->service->caminhoAnexo($id, $arquivo);
        abort_if($path === null, 404, 'Anexo nao encontrado.');

        return Storage::disk('legado_rat')->response($path);
    }

    public function print(int $id): View
    {
        $registro = $this->service->encontrar($id);
        abort_if($registro === null, 404, 'Registro do arquivo morto nao encontrado.');

        return view('rat.legado-print', [
            'rat' => (new LegadoRatResource($registro))->resolve(),
        ]);
    }
}
