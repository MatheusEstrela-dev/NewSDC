<?php

declare(strict_types=1);

namespace App\Modules\Pae\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pae\Models\PaeComunicacao;
use App\Modules\Pae\Requests\RegistrarComunicacaoRequest;
use App\Modules\Pae\Services\PaeComunicacaoService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PaeComunicacaoController extends Controller
{
    public function __construct(private readonly PaeComunicacaoService $comunicacoes)
    {
    }

    public function registrar(RegistrarComunicacaoRequest $request, PaeComunicacao $paeComunicacao): RedirectResponse
    {
        $dados = $request->validated();
        $this->comunicacoes->registrar($paeComunicacao, CarbonImmutable::parse($dados['dt_envio']),
            $dados['num_sei'], $dados['comprovante'], $request->user());

        return back()->with('success', 'Envio oficial registrado com comprovante.');
    }

    public function comprovante(PaeComunicacao $paeComunicacao): StreamedResponse
    {
        abort_unless($paeComunicacao->protocolo()->exists()
            && $paeComunicacao->status === 'registrada'
            && $paeComunicacao->comprovante_path !== null
            && Storage::disk('pae')->exists($paeComunicacao->comprovante_path), 404);

        return Storage::disk('pae')->download($paeComunicacao->comprovante_path,
            $paeComunicacao->comprovante_nome_original);
    }
}
