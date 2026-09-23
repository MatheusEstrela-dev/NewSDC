<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Demandas\Domain\Contracts\DemandaRepository;
use App\Modules\Demandas\DTOs\CriarDemandaData;
use App\Modules\Demandas\DTOs\AtualizarDemandaData;
use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Enums\TipoDemanda;
use App\Modules\Demandas\Enums\Prioridade;
use App\Modules\Demandas\Requests\StoreDemandaRequest;
use App\Modules\Demandas\Requests\UpdateDemandaRequest;
use App\Modules\Demandas\Domain\Events\DemandaCriadaV1;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Models\DemandaCategoria;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DemandaController extends Controller
{
    public function __construct(
        private readonly DemandaRepository $repository
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status', 'tipo', 'responsavel_id']);
        
        $tasks = $this->repository->paginate($filters, 15);
        $statistics = $this->repository->getStatistics();

                $categorias = DemandaCategoria::whereNull('parent_id')->with('subcategorias')->where('ativo', true)->get();
        $usuarios = User::select('id', 'name')->where('active', true)->get();

        return Inertia::render('Demandas/DemandasIndex', [
            'tasks' => $tasks,
            'statistics' => $statistics,
            'filters' => $filters,
            'filterOptions' => [
                'status' => StatusDemanda::toSelectArray(),
                'tipos' => TipoDemanda::toSelectArray(),
                'prioridades' => Prioridade::toSelectArray(),
                'categorias' => $categorias,
                'usuarios' => $usuarios,
            ]
        ]);
    }

    public function create()
    {
        // Agora usamos o modal NovaDemandaModal na index, mas mantemos a rota de fallback
        return Inertia::render('Demandas/DemandasCreate', [
            'filterOptions' => [
                'tipos' => TipoDemanda::toSelectArray(),
            ]
        ]);
    }

    public function store(StoreDemandaRequest $request)
    {
        $dto = CriarDemandaData::fromRequest($request);

        DB::transaction(function () use ($dto) {
            $demanda = new Demanda($dto->toArray());
            $demanda->status = StatusDemanda::ABERTA;
            
            $this->repository->save($demanda);

            event(DemandaCriadaV1::create(
                $demanda->id,
                $demanda->protocolo,
                $demanda->tipo->value,
                $demanda->prioridade?->value ?? '3',
                $demanda->solicitante_id
            ));
        });

        return redirect()->route('demandas.index')->with('success', 'Demanda criada com sucesso!');
    }

    public function show(int $id)
    {
        $demanda = $this->repository->findById($id);
        
        if (!$demanda) {
            abort(404, 'Demanda não encontrada.');
        }

        return Inertia::render('Demandas/DemandasShow', [
            'demanda' => $demanda,
        ]);
    }

    public function update(int $id, UpdateDemandaRequest $request)
    {
        $demanda = $this->repository->findById($id);
        if (!$demanda) abort(404);

        $dto = AtualizarDemandaData::fromRequest($request);

        DB::transaction(function () use ($demanda, $dto) {
            $demanda->fill($dto->toArray());
            $this->repository->save($demanda);
        });

        return redirect()->back()->with('success', 'Demanda atualizada com sucesso!');
    }
}



