<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use Illuminate\Http\JsonResponse;

/**
 * RF07 - lista completa (sem paginacao) dos inscritos aprovados de um
 * treinamento, para o frontend cachear em IndexedDB antes de ir a campo
 * (onde a conectividade pode faltar) e continuar validando QR Codes
 * localmente mesmo offline.
 */
class PresencaRosterController extends Controller
{
    public function __invoke(Treinamento $treinamento): JsonResponse
    {
        $inscritos = $treinamento->inscricoes()
            ->aprovadas()
            ->with('inscrito')
            ->get()
            ->map(fn ($inscricao) => [
                'inscricao_id' => $inscricao->id,
                'qr_code_token' => $inscricao->qr_code_token,
                'nome' => $inscricao->inscrito?->name,
            ]);

        $modulos = $treinamento->modulos()->orderBy('ordem')->get(['id', 'titulo', 'data_prevista']);

        return response()->json([
            'treinamento_id' => $treinamento->id,
            'inscritos' => $inscritos,
            'modulos' => $modulos,
        ]);
    }
}
