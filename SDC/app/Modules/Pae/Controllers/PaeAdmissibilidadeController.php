<?php

declare(strict_types=1);

namespace App\Modules\Pae\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Requests\SalvarTriagemRequest;
use App\Modules\Pae\Requests\SalvarMunicipiosLegadosRequest;
use App\Modules\Pae\Requests\DecidirAdmissibilidadeRequest;
use App\Modules\Pae\Services\PaeAdmissibilidadeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

final class PaeAdmissibilidadeController extends Controller
{
    public function __construct(private readonly PaeAdmissibilidadeService $admissibilidade)
    {
    }

    public function show(PaeProtocolo $paeProtocolo): JsonResponse
    {
        return response()->json([
            'admissibilidade' => $this->admissibilidade->resumo($paeProtocolo),
            'municipios_disponiveis' => Municipio::catalogo(),
        ]);
    }

    public function salvar(SalvarTriagemRequest $request, PaeProtocolo $paeProtocolo): RedirectResponse
    {
        $dados = $request->validated();
        $this->admissibilidade->salvarTriagem($paeProtocolo, $dados['municipios'], $dados['itens'], $request->user());

        return back()->with('success', 'Triagem de admissibilidade atualizada.');
    }

    public function salvarMunicipiosLegados(SalvarMunicipiosLegadosRequest $request, PaeProtocolo $paeProtocolo): RedirectResponse
    {
        $this->admissibilidade->salvarMunicipiosLegados(
            $paeProtocolo, $request->validated('municipios'), $request->user(),
        );

        return back()->with('success', 'Municípios ZAS/ZSS confirmados e pendências conciliadas.');
    }

    public function decidir(DecidirAdmissibilidadeRequest $request, PaeProtocolo $paeProtocolo): RedirectResponse
    {
        $this->admissibilidade->decidir($paeProtocolo, $request->validated(), $request->user());

        return back()->with('success', 'Decisão de admissibilidade registrada.');
    }
}
