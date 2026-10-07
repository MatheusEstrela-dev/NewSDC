<?php

declare(strict_types=1);

namespace App\Modules\Pae\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Requests\SalvarFichaAnexoBRequest;
use App\Modules\Pae\Services\PaeFichaAnexoBService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PaeFichaAnexoBController extends Controller
{
    public function __construct(private readonly PaeFichaAnexoBService $fichas)
    {
    }

    public function show(Request $request, PaeProtocolo $paeProtocolo): Response
    {
        $query = $request->validate(['versao' => ['sometimes', 'integer', 'min:1']]);
        $resumo = $this->fichas->visualizar($paeProtocolo, isset($query['versao']) ? (int) $query['versao'] : null);

        return Inertia::render('PaeFichaAnexoB', [
            ...$resumo,
            'protocolo' => [
                'id' => $paeProtocolo->id,
                'num_protocolo' => $paeProtocolo->num_protocolo,
                'status' => $paeProtocolo->status->value,
                'arquivado' => $paeProtocolo->arquivado,
            ],
            'municipios_disponiveis' => Municipio::catalogo(),
            'can_edit' => $request->user()?->can('pae.protocolos.edit')
                && ! $paeProtocolo->arquivado && ! $resumo['historica'],
        ]);
    }

    public function salvar(SalvarFichaAnexoBRequest $request, PaeProtocolo $paeProtocolo): RedirectResponse
    {
        $ficha = $this->fichas->salvar($paeProtocolo, $request->validated(), $request->user());

        return redirect()->route('pae.protocolo.ficha-anexo-b.show', $paeProtocolo)
            ->with('success', 'Ficha cadastral salva na versão '.$ficha->versao.'.');
    }
}
