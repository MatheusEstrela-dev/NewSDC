<?php

declare(strict_types=1);

namespace App\Modules\Rat\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rat\Http\Requests\ListRatRequest;
use App\Modules\Rat\Http\Resources\RatResource;
use App\Modules\Rat\Services\RatService;
use App\Modules\Rat\Enums\Status;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RatController extends Controller
{
    public function __construct(
        private readonly RatService $ratService
    ) {
    }

    public function index(ListRatRequest $request): Response
    {
        $filters    = $request->toFilterDTO();
        $rats       = $this->ratService->list($filters);
        $statistics = $this->ratService->getStatistics();

        return Inertia::render('RatIndex', [
            'rats'          => $rats,
            'statistics'    => $statistics,
            'filters'       => $filters->toArray(),
            'filterOptions' => [
                'status' => Status::toSelectArray(),
            ],
        ]);
    }

    public function show(string $id): Response
    {
        $rat = $this->ratService->findById($id);
        abort_if(is_null($rat), 404, 'RAT não encontrado');

        return Inertia::render('Rat', [
            'rat' => RatResource::make($rat),
        ]);
    }

    public function showJson(string $id): JsonResponse
    {
        $rat = $this->ratService->findById($id);
        abort_if(is_null($rat), 404, 'RAT não encontrado');

        return response()->json(RatResource::make($rat));
    }

    public function create(): Response
    {
        return Inertia::render('Rat');
    }

    public function edit(string $id): Response
    {
        $rat = $this->ratService->findById($id);
        abort_if(is_null($rat), 404, 'RAT não encontrado');

        return Inertia::render('Rat', [
            'rat' => RatResource::make($rat),
        ]);
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->ratService->delete($id);

        return redirect()->route('rat.index')
            ->with('success', 'RAT removido com sucesso!');
    }

    public function export(ListRatRequest $request): JsonResponse
    {
        $data = $this->ratService->export($request->toFilterDTO());

        return response()->json($data);
    }
}
