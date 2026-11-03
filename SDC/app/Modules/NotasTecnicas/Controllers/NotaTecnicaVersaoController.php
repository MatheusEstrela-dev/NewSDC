<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\NotasTecnicas\DTOs\NotaTecnicaVersaoDTO;
use App\Modules\NotasTecnicas\Enums\NotaTecnicaStatus;
use App\Modules\NotasTecnicas\Models\NotaTecnica;
use App\Modules\NotasTecnicas\Requests\StoreNotaTecnicaVersaoRequest;
use App\Modules\NotasTecnicas\Services\NotaTecnicaService;
use Illuminate\Http\RedirectResponse;

/** Envio de PDF como nova versao de uma nota existente. */
final class NotaTecnicaVersaoController extends Controller
{
    public function __construct(private readonly NotaTecnicaService $notas) {}

    public function store(StoreNotaTecnicaVersaoRequest $request, NotaTecnica $nota): RedirectResponse
    {
        $versao = $this->notas->novaVersao($nota, NotaTecnicaVersaoDTO::fromRequest($request->validated()), $request->user());

        $mensagem = $nota->status === NotaTecnicaStatus::Publicada
            ? "Versão {$versao->numero} publicada. Quem já tinha lido a anterior volta a ver a nota como não lida."
            : "Versão {$versao->numero} enviada.";

        return back()->with('success', $mensagem);
    }
}
