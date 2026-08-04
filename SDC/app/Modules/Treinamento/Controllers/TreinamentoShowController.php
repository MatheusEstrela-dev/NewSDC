<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Resources\TreinamentoResource;
use App\Modules\Treinamento\Services\TreinamentoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TreinamentoShowController extends Controller
{
    public function __construct(
        private readonly TreinamentoService $treinamentoService
    ) {
    }

    public function __invoke(Request $request, int $id): Response
    {
        $treinamento = $this->treinamentoService->findById($id);

        if (!$treinamento) {
            abort(404, 'Treinamento nao encontrado');
        }

        $user = $request->user();
        $minhaInscricao = $treinamento->inscricoes
            ->first(fn ($i) => $i->inscrito_type === $user::class && $i->inscrito_id === $user->id);

        return Inertia::render('Treinamento/TreinamentoShow', [
            'treinamento' => (new TreinamentoResource($treinamento))->resolve(),
            'minhaInscricao' => $minhaInscricao ? [
                'id' => $minhaInscricao->id,
                'status' => $minhaInscricao->status->value,
                'status_label' => $minhaInscricao->status->getLabel(),
                'certificado_disponivel' => $minhaInscricao->certificado?->status->value === 'GERADO',
                'certificado_id' => $minhaInscricao->certificado?->id,
            ] : null,
        ]);
    }
}
