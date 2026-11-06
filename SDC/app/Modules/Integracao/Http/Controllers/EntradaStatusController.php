<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Integracao\Support\CaixaDeEntrada;
use App\Support\Database\ConexaoPowerBi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * GET /api/v1/integracao/entradas/{id}: status de uma mensagem aceita com 202.
 * So o mesmo token (ou o mesmo usuario) enxerga; qualquer outro caso e 404.
 */
final class EntradaStatusController extends Controller
{
    public function show(Request $request, string $id, CaixaDeEntrada $caixa): JsonResponse
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        $status = ConexaoPowerBi::ativa() && $user !== null
            ? $caixa->status(
                $id,
                $token instanceof PersonalAccessToken ? (int) $token->getKey() : null,
                (int) $user->getAuthIdentifier(),
            )
            : null;

        if ($status === null) {
            return response()->json(['message' => 'Mensagem nao encontrada.'], 404);
        }

        return response()->json([
            'id'          => $status['id'],
            'operacao'    => $status['operacao'],
            'status'      => $status['status'],
            'recebido_em' => $status['recebido_em'],
            'aplicado_em' => $status['aplicado_em'],
            'resultado'   => $status['resultado'],
            'erro'        => $this->erro($status),
        ]);
    }

    /**
     * Rejeicao expoe o motivo (negocio/validacao); falha transitoria e morta
     * nao expoem o erro interno (pode trazer SQL ou caminho de arquivo).
     *
     * @param  array<string, mixed>  $status
     * @return array<string, mixed>|null
     */
    private function erro(array $status): ?array
    {
        return match ($status['status']) {
            'rejeitada' => ['mensagem' => $status['erro'], 'detalhe' => $status['erro_detalhe']],
            'falhou'    => ['mensagem' => 'Falha temporaria; nova tentativa agendada.', 'tentativas' => $status['tentativas']],
            'morta'     => ['mensagem' => 'Falha definitiva apos '.$status['tentativas'].' tentativas; encaminhada para analise.'],
            default     => null,
        };
    }
}
