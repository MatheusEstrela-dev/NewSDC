<?php

declare(strict_types=1);

namespace App\Modules\Rat\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rat\Services\RatService;
use App\Modules\Rat\Enums\Status;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;

class RatController extends Controller
{
    public function __construct(
        private readonly RatService $ratService
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status']);
        $rats = $this->ratService->list($filters, 15);
        $statistics = $this->ratService->getStatistics();

        return Inertia::render('RatIndex', [
            'rats' => $rats,
            'statistics' => $statistics,
            'filters' => $filters,
            'filterOptions' => [
                'status' => Status::toSelectArray(),
            ],
        ]);
    }

    public function show(int $id): Response
    {
        $rat = $this->ratService->findById($id);

        if (!$rat) {
            abort(404, 'RAT nao encontrado');
        }

        return Inertia::render('Rat', [
            'rat' => $rat,
        ]);
    }

    public function showJson(int $id): JsonResponse
    {
        $rat = $this->ratService->findByIdAsJson($id);

        if (!$rat) {
            return response()->json(['error' => 'RAT nao encontrado'], 404);
        }

        return response()->json($rat);
    }

    public function create(): Response
    {
        return Inertia::render('Rat', [
            'filterOptions' => [
                'status' => Status::toSelectArray(),
            ],
        ]);
    }

    public function edit(int $id): Response
    {
        $rat = $this->ratService->findById($id);

        if (!$rat) {
            abort(404, 'RAT nao encontrado');
        }

        return Inertia::render('Rat', [
            'rat' => $rat,
            'filterOptions' => [
                'status' => Status::toSelectArray(),
            ],
        ]);
    }

    public function destroy(int $id)
    {
        $this->ratService->delete($id);

        return redirect()->route('rat.index')
            ->with('success', 'RAT removido com sucesso!');
    }

    public function finalize(int $id)
    {
        $this->ratService->finalize($id);

        return redirect()->back()->with('success', 'RAT finalizado com sucesso!');
    }

    public function sync()
    {
        $this->ratService->sync();

        return redirect()->back()->with('success', 'Sincronizacao realizada com sucesso!');
    }

    public function export(Request $request)
    {
        $filters = $request->only(['status']);
        $data = $this->ratService->export($filters);

        return response()->json($data);
    }
}
