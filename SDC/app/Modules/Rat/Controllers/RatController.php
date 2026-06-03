<?php

declare(strict_types=1);

namespace App\Modules\Rat\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rat\Services\RatService;
use App\Modules\Rat\Enums\Status;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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

    public function show(string $id): Response
    {
        $rat = $this->ratService->findById($id);

        if (!$rat) {
            abort(404, 'RAT nao encontrado');
        }

        return Inertia::render('Rat', [
            'rat'    => $rat,
            'anexos' => $rat->anexos->values(),
        ]);
    }

    public function showJson(string $id): JsonResponse
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

    public function edit(string $id): Response
    {
        $rat = $this->ratService->findById($id);

        if (!$rat) {
            abort(404, 'RAT nao encontrado');
        }

        return Inertia::render('Rat', [
            'rat'    => $rat,
            'anexos' => $rat->anexos->values(),
            'filterOptions' => [
                'status' => Status::toSelectArray(),
            ],
        ]);
    }

    public function destroy(string $id)
    {
        $this->ratService->delete($id);

        return redirect()->route('rat.index')
            ->with('success', 'RAT removido com sucesso!');
    }

    public function finalize(string $id)
    {
        $this->ratService->finalize($id);

        return redirect()->back()->with('success', 'RAT finalizado com sucesso!');
    }

    public function storeAnexos(Request $request, string $id): JsonResponse
    {
        $rat = $this->ratService->findById($id);

        if (!$rat) {
            return response()->json(['error' => 'RAT nao encontrado'], 404);
        }

        $request->validate([
            'documents'   => ['required', 'array', 'min:1'],
            'documents.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt'],
        ]);

        $anexos = $this->ratService->storeAnexos(
            $id,
            $request->file('documents') ?? [],
            $request->user()?->id
        );

        return response()->json([
            'message' => 'Anexos salvos com sucesso.',
            'data'    => $anexos,
        ], 201);
    }

    public function destroyAnexo(string $id, int $anexoId): JsonResponse
    {
        $deleted = $this->ratService->deleteAnexo($id, $anexoId);

        if (!$deleted) {
            return response()->json(['error' => 'Anexo nao encontrado'], 404);
        }

        return response()->json(null, 204);
    }

    public function sync(): \Illuminate\Http\RedirectResponse
    {
        $this->ratService->sync();

        return redirect()->back()->with('success', 'Sincronizacao realizada com sucesso!');
    }

    public function export(Request $request): JsonResponse
    {
        $filters = $request->only(['status']);
        $data = $this->ratService->export($filters);

        return response()->json($data);
    }
}
