<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cisterna\DTOs\NotificacaoDTO;
use App\Modules\Cisterna\Models\CisternaNotificacao;
use App\Modules\Cisterna\Requests\StoreNotificacaoRequest;
use App\Modules\Cisterna\Requests\UpdateNotificacaoRequest;
use App\Modules\Cisterna\Resources\NotificacaoResource;
use App\Modules\Cisterna\Services\NotificacaoFiscalizacaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificacaoFiscalizacaoController extends Controller
{
    public function __construct(
        private readonly NotificacaoFiscalizacaoService $service,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CisternaNotificacao::class);

        $filtros = $request->only(['notificavel_type', 'notificavel_id']);

        if ($request->has('apenas_pendentes')) {
            $filtros['apenas_pendentes'] = $request->boolean('apenas_pendentes');
        }

        return Inertia::render('Cisterna/Notificacoes/Index', [
            'notificacoes' => NotificacaoResource::collection($this->service->listar($filtros)),
            'filtros' => $filtros,
            'tipos' => array_keys(NotificacaoDTO::TIPOS_PERMITIDOS),
            'permissoes' => [
                'criar' => $request->user()?->can('create', CisternaNotificacao::class) ?? false,
            ],
        ]);
    }

    public function store(StoreNotificacaoRequest $request): RedirectResponse
    {
        $this->service->emitir(
            NotificacaoDTO::deValidados($request->validated()),
            $request->file('arquivo'),
        );

        return back()->with('success', 'Notificacao de fiscalizacao registrada.');
    }

    public function update(UpdateNotificacaoRequest $request, CisternaNotificacao $notificacao): RedirectResponse
    {
        $this->service->atualizar(
            $notificacao,
            NotificacaoDTO::deValidados($request->validated()),
            $request->file('arquivo'),
        );

        return back()->with('success', 'Notificacao atualizada.');
    }

    public function responder(Request $request, CisternaNotificacao $notificacao): RedirectResponse
    {
        $this->authorize('update', $notificacao);

        $respondida = $request->boolean('respondida', true);
        $this->service->responder($notificacao, $respondida);

        return back()->with(
            'success',
            $respondida ? 'Notificacao marcada como respondida.' : 'Notificacao reaberta.'
        );
    }

    public function destroy(CisternaNotificacao $notificacao): RedirectResponse
    {
        $this->authorize('delete', $notificacao);

        $this->service->deletar($notificacao);

        return back()->with('success', 'Notificacao excluida.');
    }
}
