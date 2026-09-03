<?php

declare(strict_types=1);

namespace App\Modules\Sismos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Medalhao\Models\IngestaoBruta;
use App\Modules\Medalhao\Registry\IngestorRegistry;
use App\Modules\Sismos\Repositories\SismoRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SismosIndexController extends Controller
{
    public function __construct(
        private readonly SismoRepository $repository,
        private readonly IngestorRegistry $registry,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        // Toda a agregacao ja esta materializada na camada Gold: aqui so se le.
        return Inertia::render('Sismos/MapaSismos', [
            'eventos' => $this->repository->mapa()->all(),
            'estatisticas' => $this->repository->estatisticas(),
            // Quando as fontes foram consultadas pela ultima vez, independente
            // de terem trazido novidade.
            //
            // Em sismo, "nenhum evento novo" e a resposta certa na maioria dos
            // ciclos -- houve 6 eventos em MG em 90 dias. Sem este campo, o
            // operador nao tem como distinguir isso de um coletor parado, e a
            // tela mostrava a data do ultimo REFRESH do gold como se fosse a da
            // ultima verificacao.
            //
            // A lista de fontes vem do registry, e nao de constante: fonte nova
            // do grupo passa a contar sozinha.
            'verificado_em' => IngestaoBruta::verificadoEm($this->registry->chavesDoGrupo('sismos')),
            'bbox' => config('medalhao.sismos.bbox'),
        ]);
    }
}
