<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Requests\RegistrarPresencaManualRequest;
use App\Modules\Treinamento\Services\PresencaService;

class PresencaManualController extends Controller
{
    public function __construct(
        private readonly PresencaService $presencaService
    ) {
    }

    public function __invoke(RegistrarPresencaManualRequest $request)
    {
        $inscricao = Inscricao::findOrFail($request->validated('inscricao_id'));

        try {
            $this->presencaService->registrarManual(
                $inscricao,
                (int) $request->validated('modulo_id'),
                $request->user()
            );
        } catch (\DomainException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['presenca' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Presenca registrada com sucesso.']);
        }

        return back()->with('success', 'Presenca registrada com sucesso.');
    }
}
