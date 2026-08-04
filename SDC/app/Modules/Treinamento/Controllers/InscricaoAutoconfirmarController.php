<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Services\PresencaService;
use Illuminate\Http\Request;

/**
 * Autoconfirmacao de presenca (sem QR) para o proprio servidor interno
 * inscrito em um treinamento ONLINE - RF03/fluxo online.
 */
class InscricaoAutoconfirmarController extends Controller
{
    public function __construct(
        private readonly PresencaService $presencaService
    ) {
    }

    public function __invoke(Request $request, Inscricao $inscricao)
    {
        $user = $request->user();
        abort_unless(
            $inscricao->inscrito_type === $user::class && $inscricao->inscrito_id === $user->id,
            403
        );

        try {
            $this->presencaService->autoconfirmar($inscricao);
        } catch (\DomainException $e) {
            return back()->withErrors(['presenca' => $e->getMessage()]);
        }

        return back()->with('success', 'Presenca confirmada com sucesso!');
    }
}
