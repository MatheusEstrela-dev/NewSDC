<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Cidadao;
use App\Modules\Treinamento\Resources\InscricaoResource;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MinhasInscricoesController extends Controller
{
    public function __invoke(): Response
    {
        /** @var Cidadao $cidadao */
        $cidadao = Auth::guard('cidadao')->user();

        $inscricoes = $cidadao->inscricoes()
            ->with(['treinamento', 'certificado'])
            ->orderBy('data_inscricao', 'desc')
            ->paginate(10);

        return Inertia::render('Treinamento/Portal/MinhasInscricoes', [
            'inscricoes' => InscricaoResource::collection($inscricoes),
        ]);
    }
}
