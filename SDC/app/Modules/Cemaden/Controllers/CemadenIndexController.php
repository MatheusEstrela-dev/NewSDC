<?php

declare(strict_types=1);

namespace App\Modules\Cemaden\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cemaden\Repositories\CemadenRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CemadenIndexController extends Controller
{
    public function __construct(
        private readonly CemadenRepository $repository,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        // Toda a agregacao ja esta materializada na camada Gold: aqui so se le.
        //
        // As chaves 'estacoes' e 'estatisticas' repetem as do INMET de proposito:
        // sao os mesmos nomes que o useAtualizacaoAoVivo recarrega em partial
        // reload, o que mantem um contrato so para as duas redes.
        return Inertia::render('Cemaden/MapaCemaden', [
            'estacoes' => $this->repository->mapa()->all(),
            'estatisticas' => $this->repository->estatisticas(),
            'bbox' => config('medalhao.cemaden.bbox'),
        ]);
    }
}
