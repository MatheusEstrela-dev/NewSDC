<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Models\Plantao;
use App\Modules\Plantao\Services\PlantaoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pagina do formulario de edicao (GET .../edit). Nao fazia parte do
 * inventario original do plano - so citava PlantaoShowController e
 * PlantaoUpdateController -, mas sem uma rota GET dedicada nao ha como o
 * Inertia renderizar `Pages/Plantao/Edit.vue` como pagina propria (visitavel,
 * atualizavel com F5, com botao voltar funcionando). Segue o padrao ja
 * estabelecido no modulo Treinamento (TreinamentoShowController +
 * TreinamentoEditController + TreinamentoUpdateController).
 */
class PlantaoEditController extends Controller
{
    public function __construct(
        private readonly PlantaoService $plantaoService
    ) {
    }

    public function __invoke(Request $request, Plantao $plantao): Response
    {
        $usuario = $request->user();

        // Falta de autorizacao, nao erro de formulario -> 403. Mesma regra
        // aplicada no submit (PlantaoUpdateController): dono + turno ATIVO,
        // ou excecao de supervisao (`encerrar_alheio`, ver PlantaoService).
        abort_unless(
            $usuario !== null && $this->plantaoService->podeEditar($plantao, $usuario),
            403,
            'Voce nao pode editar este turno.'
        );

        return Inertia::render('Plantao/Edit', [
            'plantao' => [
                'id' => $plantao->id,
                'data' => $plantao->data?->format('d/m/Y') ?? '',
                'periodo_label' => $plantao->periodo?->labelCurto() ?? '',
                'status_label' => $plantao->status?->label() ?? '',
                'plantonista_nome' => $plantao->plantonista_nome,
                'localizacao' => $plantao->localizacao,
                'observacoes' => $plantao->observacoes,
                'ocorrencias_destaque' => $plantao->ocorrencias_destaque,
            ],
        ]);
    }
}
