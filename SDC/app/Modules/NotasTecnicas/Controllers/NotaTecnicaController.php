<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\NotasTecnicas\DTOs\NotaTecnicaFiltroDTO;
use App\Modules\NotasTecnicas\Models\NotaTecnica;
use App\Modules\NotasTecnicas\Resources\NotaTecnicaDetalheResource;
use App\Modules\NotasTecnicas\Resources\NotaTecnicaListaResource;
use App\Modules\NotasTecnicas\Services\NotaTecnicaColecaoService;
use App\Modules\NotasTecnicas\Services\NotaTecnicaConsultaService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Workspace de leitura (estilo Notes): pastas, lista, leitor de PDF e metadados
 * numa tela so. A nota aberta vem na URL (?nota=&versao=), entao o link pode ser
 * compartilhado e o voltar do navegador funciona.
 */
final class NotaTecnicaController extends Controller
{
    public function __construct(
        private readonly NotaTecnicaConsultaService $consulta,
        private readonly NotaTecnicaColecaoService $colecoes,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $filtro = NotaTecnicaFiltroDTO::fromArray($request->query());

        return Inertia::render('NotasTecnicas/Index', [
            'notas' => fn () => $this->paraResource($this->consulta->listar($filtro, $user), $request),
            'filtros' => $filtro->toArray(),
            // Em closure: a selecao de nota recarrega so `selecionada`, e os
            // agregados abaixo nao sao refeitos a cada clique na lista.
            'estatisticas' => fn () => $this->consulta->estatisticas($user),
            'colecoes' => fn () => $this->consulta->colecoes($user),
            'categorias' => fn () => $this->consulta->arvoreCategorias($user),
            'selecionada' => fn () => $this->selecionada($request),
            'permissoes' => fn () => [
                'criar' => $user->can('notas_tecnicas.notas.create'),
                'editar' => $user->can('notas_tecnicas.notas.edit'),
                'publicar' => $user->can('notas_tecnicas.notas.publicar'),
                'categorias' => $user->can('notas_tecnicas.categorias.manage'),
                'leituras' => $user->can('notas_tecnicas.leituras.view'),
            ],
        ]);
    }

    /**
     * O Pagination do front recebe as chaves achatadas do paginador ({data,
     * current_page, last_page, ...}); so a linha passa pelo Resource.
     */
    private function paraResource(LengthAwarePaginator $paginador, Request $request): LengthAwarePaginator
    {
        $paginador->getCollection()->transform(
            fn (NotaTecnica $nota): array => (new NotaTecnicaListaResource($nota))->resolve($request),
        );

        return $paginador;
    }

    /** @return array<string, mixed>|null */
    private function selecionada(Request $request): ?array
    {
        $notaId = filter_var($request->query('nota'), FILTER_VALIDATE_INT);

        if ($notaId === false) {
            return null;
        }

        $nota = NotaTecnica::query()->find($notaId);

        // Nota inexistente ou fora do alcance do leitor (rascunho) abre o
        // workspace vazio, em vez de 403/404 numa tela que ainda serve.
        if ($nota === null || $request->user()->cannot('view', $nota)) {
            return null;
        }

        // Abrir a nota (e so isso) alimenta a colecao Recentes do usuario.
        $this->colecoes->registrarAcesso($nota, $request->user());

        $versaoId = filter_var($request->query('versao'), FILTER_VALIDATE_INT);

        return (new NotaTecnicaDetalheResource(
            $this->consulta->detalhe($nota, $versaoId === false ? null : $versaoId, $request->user()),
        ))->resolve($request);
    }
}
