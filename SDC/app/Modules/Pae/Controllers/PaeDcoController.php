<?php

declare(strict_types=1);

namespace App\Modules\Pae\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pae\Models\PaeDcoDocumento;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Requests\AvaliarDcoRequest;
use App\Modules\Pae\Requests\RegistrarDcoRequest;
use App\Modules\Pae\Services\PaeDcoService;
use App\Modules\Pae\Support\PaeArquivoPdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PaeDcoController extends Controller
{
    public function __construct(private readonly PaeDcoService $dco)
    {
    }

    public function show(Request $request, PaeProtocolo $paeProtocolo): Response
    {
        return Inertia::render('PaeDco', [
            'protocolo' => [
                'id' => $paeProtocolo->id,
                'num_protocolo' => $paeProtocolo->num_protocolo,
                'status' => $paeProtocolo->status->value,
                'arquivado' => $paeProtocolo->arquivado,
            ],
            'resumo' => $this->dco->resumo($paeProtocolo, CarbonImmutable::today()),
            'can_validar' => $request->user()?->can('pae.protocolos.validar') && ! $paeProtocolo->arquivado,
            'can_view' => $request->user()?->can('pae.protocolos.view'),
        ]);
    }

    public function avaliar(AvaliarDcoRequest $request, PaeProtocolo $paeProtocolo): RedirectResponse
    {
        abort_if($paeProtocolo->arquivado, 422, 'Protocolo arquivado.');
        $this->dco->avaliar($paeProtocolo, $request->validated(), $request->user());

        return back()->with('success', 'Aplicabilidade da DCO registrada.');
    }

    public function registrar(RegistrarDcoRequest $request, PaeProtocolo $paeProtocolo): RedirectResponse
    {
        abort_if($paeProtocolo->arquivado, 422, 'Protocolo arquivado.');
        $dados = $request->validated();
        $this->dco->registrarDocumento($paeProtocolo, $dados, $dados['arquivo'], $request->user());

        return back()->with('success', 'DCO registrada no histórico.');
    }

    public function download(PaeProtocolo $paeProtocolo, PaeDcoDocumento $paeDcoDocumento): StreamedResponse
    {
        abort_unless($paeDcoDocumento->protocolo_id === $paeProtocolo->id
            && PaeArquivoPdf::existe($paeDcoDocumento->arquivo_path), 404);

        return PaeArquivoPdf::baixar($paeDcoDocumento->arquivo_path, $paeDcoDocumento->arquivo_nome_original);
    }
}
