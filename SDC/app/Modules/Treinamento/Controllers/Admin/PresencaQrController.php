<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Requests\RegistrarPresencaRequest;
use App\Modules\Treinamento\Services\PresencaService;

class PresencaQrController extends Controller
{
    public function __construct(
        private readonly PresencaService $presencaService
    ) {
    }

    public function __invoke(RegistrarPresencaRequest $request)
    {
        try {
            $frequencia = $this->presencaService->registrarPorQr(
                $request->validated('qr_code_token'),
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
            return response()->json(['message' => 'Presenca registrada com sucesso.', 'frequencia_id' => $frequencia->id]);
        }

        return back()->with('success', 'Presenca registrada com sucesso.');
    }
}
