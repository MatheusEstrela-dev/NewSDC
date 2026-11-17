<?php

declare(strict_types=1);

namespace App\Modules\Pae\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Requests\SalvarChecklistRequest;
use App\Modules\Pae\Services\PaeChecklistService;
use App\Modules\Pae\Support\Checklist\ChecklistGmg83;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Checklist GMG 83 de cada secao da pasta. Responde JSON para que cada tela
 * da pasta embuta o mesmo painel sem mudar as proprias props.
 */
final class PaeChecklistController extends Controller
{
    public function __construct(private readonly PaeChecklistService $checklist)
    {
    }

    public function show(Request $request, PaeProtocolo $paeProtocolo, string $secao): JsonResponse
    {
        return $this->resposta($request, $paeProtocolo, $secao);
    }

    public function salvar(SalvarChecklistRequest $request, PaeProtocolo $paeProtocolo, string $secao): JsonResponse
    {
        $this->checklist->salvar($paeProtocolo, $secao, $request->validated('respostas'), $request->user());

        return $this->resposta($request, $paeProtocolo->refresh(), $secao);
    }

    private function resposta(Request $request, PaeProtocolo $protocolo, string $secao): JsonResponse
    {
        abort_unless(ChecklistGmg83::existeSecao($secao), 404);

        return response()->json([
            ...$this->checklist->secao($protocolo, $secao),
            'pode_editar' => ($request->user()?->can('pae.protocolos.edit') ?? false) && ! $protocolo->arquivado,
        ]);
    }
}
