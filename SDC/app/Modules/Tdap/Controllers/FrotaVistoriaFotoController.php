<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tdap\Models\Vistoria;
use App\Modules\Tdap\Models\VistoriaFoto;
use App\Modules\Tdap\Requests\StoreVistoriaFotoRequest;
use App\Modules\Tdap\Services\VistoriaFotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FrotaVistoriaFotoController extends Controller
{
    public function __construct(
        private readonly VistoriaFotoService $service,
    ) {}

    public function store(StoreVistoriaFotoRequest $request, Vistoria $vistoria): RedirectResponse
    {
        foreach ($request->file('fotos') as $arquivo) {
            $this->service->store($vistoria, $arquivo, $request->input('descricao'));
        }

        return back()->with('success', 'Foto(s) anexada(s) com sucesso.');
    }

    /**
     * Serve a imagem para o <img> da tela.
     *
     * O disco 'tdap' e privado -- em producao aponta para um container do Azure
     * sem acesso anonimo -- entao nao existe URL publica para a miniatura. Quem
     * serve o binario e esta rota, atras do gate tdap.vistorias.view.
     */
    public function show(Vistoria $vistoria, VistoriaFoto $foto): StreamedResponse
    {
        abort_unless($foto->vistoria_id === $vistoria->id, 404);

        return Storage::disk($foto->disk)->response(
            $foto->path,
            $foto->nome_original,
            ['Content-Type' => $foto->mime_type ?? 'application/octet-stream'],
        );
    }

    public function destroy(Vistoria $vistoria, VistoriaFoto $foto): RedirectResponse
    {
        abort_unless($foto->vistoria_id === $vistoria->id, 404);

        $this->service->destroy($foto);

        return back()->with('success', 'Foto removida.');
    }
}
