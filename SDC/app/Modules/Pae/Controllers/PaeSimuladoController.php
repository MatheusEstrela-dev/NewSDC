<?php

declare(strict_types=1);

namespace App\Modules\Pae\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Models\PaeSimuladoRelatorio;
use App\Modules\Pae\Requests\AvaliarSimuladoRequest;
use App\Modules\Pae\Requests\PreviaIndiciosSimuladoRequest;
use App\Modules\Pae\Requests\RegistrarSimuladoRequest;
use App\Modules\Pae\Services\PaeSimuladoService;
use App\Modules\Pae\Support\PaeArquivoPdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PaeSimuladoController extends Controller
{
    public function __construct(private readonly PaeSimuladoService $simulados)
    {
    }

    public function show(Request $request, PaeProtocolo $paeProtocolo): Response
    {
        return Inertia::render('PaeSimulados', [
            'protocolo' => [
                'id' => $paeProtocolo->id,
                'num_protocolo' => $paeProtocolo->num_protocolo,
                'status' => $paeProtocolo->status->value,
                'arquivado' => $paeProtocolo->arquivado,
            ],
            'resumo' => $this->simulados->resumo($paeProtocolo, CarbonImmutable::today()),
            'can_validar' => (bool) $request->user()?->can('pae.protocolos.validar') && ! $paeProtocolo->arquivado,
            'can_view' => (bool) $request->user()?->can('pae.protocolos.view'),
        ]);
    }

    public function avaliar(AvaliarSimuladoRequest $request, PaeProtocolo $paeProtocolo): RedirectResponse
    {
        $this->simulados->avaliar($paeProtocolo, $request->validated(), $request->user());

        return back()->with('success', 'Exigibilidade do simulado registrada.');
    }

    public function registrar(RegistrarSimuladoRequest $request, PaeProtocolo $paeProtocolo): RedirectResponse
    {
        $dados = $request->validated();
        $relatorio = $this->simulados->registrarRelatorio($paeProtocolo, $dados, $dados['arquivo'], $request->user());

        return back()->with('success', sprintf(
            'Relatório do simulado de %s registrado na versão %d.',
            $relatorio->dt_realizacao->format('d/m/Y'),
            $relatorio->versao,
        ));
    }

    public function indicios(PreviaIndiciosSimuladoRequest $request, PaeProtocolo $paeProtocolo): JsonResponse
    {
        return response()->json($this->simulados->previaIndicios($request->validated()));
    }

    public function download(PaeProtocolo $paeProtocolo, PaeSimuladoRelatorio $paeSimuladoRelatorio): StreamedResponse
    {
        abort_unless($paeSimuladoRelatorio->protocolo_id === $paeProtocolo->id
            && PaeArquivoPdf::existe($paeSimuladoRelatorio->arquivo_path), 404);

        return PaeArquivoPdf::baixar($paeSimuladoRelatorio->arquivo_path, $paeSimuladoRelatorio->arquivo_nome_original);
    }
}
