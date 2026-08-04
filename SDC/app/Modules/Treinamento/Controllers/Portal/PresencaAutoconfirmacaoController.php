<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Services\PresencaService;
use Illuminate\Support\Facades\Auth;

/**
 * Autoconfirmacao de presenca (sem QR) para o cidadao inscrito em um
 * treinamento ONLINE - RF03/fluxo online.
 */
class PresencaAutoconfirmacaoController extends Controller
{
    public function __construct(
        private readonly PresencaService $presencaService
    ) {
    }

    public function store(Inscricao $inscricao)
    {
        $cidadao = Auth::guard('cidadao')->user();

        abort_unless(
            $inscricao->inscrito_type === $cidadao::class && $inscricao->inscrito_id === $cidadao->id,
            403
        );

        try {
            $this->presencaService->autoconfirmar($inscricao);
        } catch (\DomainException $e) {
            return back()->withErrors(['presenca' => $e->getMessage()]);
        }

        return back()->with('success', 'Presença confirmada com sucesso!');
    }
}
