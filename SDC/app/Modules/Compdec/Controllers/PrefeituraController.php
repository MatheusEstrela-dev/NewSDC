<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compdec\DTOs\PrefeituraDTO;
use App\Modules\Compdec\Models\Orgao;
use App\Modules\Compdec\Requests\UpsertPrefeituraRequest;
use App\Modules\Compdec\Resources\PrefeituraResource;
use App\Modules\Compdec\Services\PrefeituraService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PrefeituraController extends Controller
{
    public function __construct(
        private readonly PrefeituraService $service,
    ) {}

    public function show(Orgao $orgao): JsonResponse
    {
        $this->authorize('compdec.prefeitura.view');

        $prefeitura = $this->service->obterPorOrgao($orgao->id);

        return response()->json([
            'data' => $prefeitura ? PrefeituraResource::make($prefeitura) : null,
        ]);
    }

    public function upsert(UpsertPrefeituraRequest $request, Orgao $orgao): RedirectResponse
    {
        if (! $orgao->municipio_id) {
            return back()->with('error', 'Orgao nao possui municipio vinculado.');
        }

        $this->service->upsertPorOrgao(
            $orgao->id,
            PrefeituraDTO::fromRequest($orgao->municipio_id, $request->validated()),
        );

        return back()->with('success', 'Prefeitura atualizada.');
    }

    public function uploadFoto(Request $request, Orgao $orgao): RedirectResponse
    {
        $this->authorize('compdec.prefeitura.edit');

        $request->validate([
            'foto' => [
                'required',
                'file',
                'mimes:jpeg,png,webp',
                'max:' . (int) (config('compdec.upload_limits.foto_prefeito', 307200) / 1024),
            ],
        ]);

        $prefeitura = $this->service->obterPrefeituraPorOrgaoOuFalhar($orgao->id);
        $this->service->uploadFoto($prefeitura->id, $request->file('foto'));

        return back()->with('success', 'Foto do prefeito atualizada.');
    }

    public function removerFoto(Orgao $orgao): RedirectResponse
    {
        $this->authorize('compdec.prefeitura.edit');

        $prefeitura = $this->service->obterPrefeituraPorOrgaoOuFalhar($orgao->id);
        $this->service->removerFoto($prefeitura->id);

        return back()->with('success', 'Foto do prefeito removida.');
    }
}
