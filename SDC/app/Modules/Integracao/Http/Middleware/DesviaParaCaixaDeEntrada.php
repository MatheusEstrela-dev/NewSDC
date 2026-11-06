<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Http\Middleware;

use App\Modules\Integracao\Exceptions\ChaveIdempotenteEmConflitoException;
use App\Modules\Integracao\Jobs\AplicarEntradaJob;
use App\Modules\Integracao\Support\CaixaDeEntrada;
use App\Modules\Integracao\Support\CatalogoDeOperacoes;
use App\Modules\Integracao\Support\NovaEntrada;
use App\Support\Database\ConexaoEntrada;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\TransientToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uso na rota: ->middleware('integracao.entrada:<operacao>'), depois do can:.
 *
 * Desvia para a caixa de entrada (202 + status_url) so quando TUDO vale:
 * sdc_entrada ligado, fonte da operacao em adesao, cliente pediu assincrono
 * (Prefer: respond-async ou ability integracao:assincrono), token de API (nao
 * sessao web) e sem arquivo enviado. Senao segue o fluxo sincrono intacto.
 */
final class DesviaParaCaixaDeEntrada
{
    private const TAMANHO_MAXIMO_CHAVE = 200;

    public function __construct(
        private readonly CatalogoDeOperacoes $catalogo,
        private readonly CaixaDeEntrada $caixa,
    ) {
    }

    public function handle(Request $request, Closure $next, string $operacao): Response
    {
        if (! $this->deveDesviar($request, $operacao)) {
            return $next($request);
        }

        $chave = (string) $request->header('Idempotency-Key', '');

        if ($chave === '' && $this->catalogo->exigeChaveIdempotente($operacao)) {
            return response()->json(['message' => 'Idempotency-Key obrigatoria para esta operacao no modo assincrono.'], 428);
        }

        if (mb_strlen($chave) > self::TAMANHO_MAXIMO_CHAVE) {
            return response()->json(['message' => 'Idempotency-Key acima de '.self::TAMANHO_MAXIMO_CHAVE.' caracteres.'], 400);
        }

        $aplicador = $this->catalogo->aplicador($operacao);
        // ValidationException -> 422 no formato padrao do handler da API.
        $validados = $request->validate($aplicador->regras($request));
        $parametros = $request->route()?->originalParameters() ?? [];

        $entrada = NovaEntrada::daRequisicao(
            $request,
            $this->catalogo->fonte($operacao),
            $operacao,
            $validados,
            $parametros,
            $aplicador->recursoChave($validados, $parametros),
        );

        try {
            $recebida = $this->caixa->receber($entrada);
        } catch (ChaveIdempotenteEmConflitoException) {
            return response()->json([
                'message' => 'Idempotency-Key ja utilizada com outro conteudo ou outro recurso.',
            ], 409);
        }

        if (! $recebida->repetida) {
            AplicarEntradaJob::despachar($recebida->id);
        }

        return $this->aceita($recebida->id, $recebida->traceId, $recebida->repetida);
    }

    private function deveDesviar(Request $request, string $operacao): bool
    {
        if (! ConexaoEntrada::ativa() || ! $this->catalogo->emAdesao($operacao)) {
            return false;
        }

        $token = $request->user()?->currentAccessToken();
        if ($token === null || $token instanceof TransientToken || $request->allFiles() !== []) {
            return false;
        }

        return $this->pediuAssincrono($request) || $this->tokenAssincrono($token);
    }

    /** RFC 7240: Prefer: respond-async (pode vir junto de outras preferencias). */
    private function pediuAssincrono(Request $request): bool
    {
        foreach (preg_split('/[,;]/', strtolower((string) $request->header('Prefer', ''))) ?: [] as $preferencia) {
            if (trim($preferencia) === 'respond-async') {
                return true;
            }
        }

        return false;
    }

    /** Comparacao literal: o curinga '*' nao liga o assincrono. */
    private function tokenAssincrono(mixed $token): bool
    {
        return $token instanceof PersonalAccessToken
            && in_array((string) config('integracao.ability_assincrona'), (array) $token->abilities, true);
    }

    private function aceita(string $id, string $traceId, bool $repetida): JsonResponse
    {
        $statusUrl = route('api.v1.integracao.entradas.show', ['id' => $id]);

        $resposta = response()->json([
            'status'     => 'aceita',
            'id'         => $id,
            'status_url' => $statusUrl,
            'trace_id'   => $traceId,
        ], 202)
            ->header('Location', $statusUrl)
            ->header('Preference-Applied', 'respond-async');

        return $repetida ? $resposta->header('Idempotent-Replayed', 'true') : $resposta;
    }
}
