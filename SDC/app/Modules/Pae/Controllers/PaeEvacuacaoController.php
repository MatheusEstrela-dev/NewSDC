<?php

declare(strict_types=1);

namespace App\Modules\Pae\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Requests\RegistrarEvacuacaoRequest;
use App\Modules\Pae\Requests\SimularEvacuacaoRequest;
use App\Modules\Pae\Services\PaeEvacuacaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PaeEvacuacaoController extends Controller
{
    public function __construct(private readonly PaeEvacuacaoService $evacuacao)
    {
    }

    public function show(Request $request, PaeProtocolo $paeProtocolo, ?int $versao = null): Response
    {
        $resumo = $this->evacuacao->visualizar($paeProtocolo, $versao);

        return Inertia::render('PaeEvacuacao', [
            ...$resumo,
            'protocolo' => [
                'id' => $paeProtocolo->id,
                'num_protocolo' => $paeProtocolo->num_protocolo,
                'status' => $paeProtocolo->status->value,
                'arquivado' => $paeProtocolo->arquivado,
            ],
            'can_edit' => ($request->user()?->can('pae.protocolos.edit') ?? false)
                && ! $paeProtocolo->arquivado && ! $resumo['historica'],
        ]);
    }

    public function simular(SimularEvacuacaoRequest $request, PaeProtocolo $paeProtocolo): JsonResponse
    {
        return response()->json($this->evacuacao->simular($request->validated()));
    }

    public function registrar(RegistrarEvacuacaoRequest $request, PaeProtocolo $paeProtocolo): RedirectResponse
    {
        $conferencia = $this->evacuacao->registrar($paeProtocolo, $request->validated(), $request->user());

        return redirect()->route('pae.protocolo.evacuacao.show', $paeProtocolo)
            ->with('success', 'Conferência de evacuação registrada na versão '.$conferencia->versao.'.');
    }
}
