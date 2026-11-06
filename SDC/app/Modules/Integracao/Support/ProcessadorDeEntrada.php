<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Support;

use App\Models\User;
use App\Modules\Integracao\Exceptions\EntradaJaAplicadaException;
use App\Modules\Integracao\Exceptions\EntradaRejeitadaException;
use App\Modules\Integracao\Jobs\AplicarEntradaJob;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Aplica uma mensagem da caixa no sdc, exatamente uma vez.
 *
 * Os dois bancos nao compartilham transacao: o sdc e o juiz. O efeito e a
 * linha de integracao_aplicacoes commitam juntos; a entrada so e marcada
 * depois. Se a marcacao falhar, a trava expira, a mensagem volta e encontra
 * a linha no sdc: so copia o resultado, sem reaplicar.
 */
final class ProcessadorDeEntrada
{
    /** 4xx transitorios: tentam de novo. */
    private const STATUS_TRANSITORIOS = [408, 423, 425, 429];

    public function __construct(
        private readonly CaixaDeEntrada $caixa,
        private readonly CatalogoDeOperacoes $catalogo,
        private readonly RegistroDeAplicacoes $registro,
    ) {
    }

    public function processar(string $mensagemId): void
    {
        $mensagem = $this->caixa->reivindicar($mensagemId);
        if ($mensagem === null) {
            return; // travada por outro worker, terminal, fora de hora ou atras de outra do mesmo recurso
        }

        try {
            $terminou = $this->desfecho($mensagem);
        } finally {
            Auth::forgetUser();
        }

        if ($terminou && $mensagem->recursoChave !== null) {
            $proxima = $this->caixa->proximaDoRecurso($mensagem->recursoChave);
            if ($proxima !== null) {
                AplicarEntradaJob::despachar($proxima);
            }
        }
    }

    /** @return bool true quando a mensagem chegou a estado terminal */
    private function desfecho(MensagemReivindicada $mensagem): bool
    {
        try {
            $resultado = $this->aplicarUmaVez($mensagem);
        } catch (EntradaJaAplicadaException $e) {
            $this->caixa->marcarAplicada($mensagem->id, $e->resultado);

            return true;
        } catch (Throwable $e) {
            return $this->registrarErro($mensagem, $e);
        }

        $this->caixa->marcarAplicada($mensagem->id, $resultado);

        return true;
    }

    /** @return array<string, mixed> */
    private function aplicarUmaVez(MensagemReivindicada $mensagem): array
    {
        return DB::connection()->transaction(function () use ($mensagem): array {
            if (! $this->registro->reservar($mensagem->id, $mensagem->operacao)) {
                return $this->registro->resultado($mensagem->id);
            }

            $usuario = $this->autenticar($mensagem);
            $aplicador = $this->catalogo->aplicador($mensagem->operacao);

            // Revalida no momento da aplicacao (exists/unique podem ter mudado).
            $validados = Validator::make(
                $mensagem->payload,
                $aplicador->regras($this->requisicao($mensagem, $usuario))
            )->validate();

            $resultado = $aplicador->aplicar($mensagem->paraAplicador($validados));
            $this->registro->concluir($mensagem->id, $resultado);

            return $resultado;
        });
    }

    private function autenticar(MensagemReivindicada $mensagem): User
    {
        $usuario = $mensagem->userId !== null ? User::query()->find($mensagem->userId) : null;
        if (! $usuario instanceof User) {
            throw new EntradaRejeitadaException('Mensagem sem usuario valido para aplicar.');
        }

        // Token ainda existente: tokenCan() nos services enxerga o mesmo escopo do envio.
        $token = $mensagem->tokenId !== null ? Sanctum::$personalAccessTokenModel::query()->find($mensagem->tokenId) : null;
        if ($token !== null) {
            $usuario->withAccessToken($token);
        }

        Auth::setUser($usuario);

        return $usuario;
    }

    /**
     * Requisicao equivalente para regras(): metodo, corpo, usuario, os
     * parametros crus da rota (sem model binding) e a marca de reaplicacao.
     */
    private function requisicao(MensagemReivindicada $mensagem, User $usuario): Request
    {
        $request = Request::create('/'.ltrim($mensagem->caminho, '/'), $mensagem->metodo, $mensagem->payload);
        $request->headers->set('Accept', 'application/json');
        // So aqui (nunca na borda): o aplicador pode tirar regras que barrariam a repeticao.
        $request->attributes->set(MensagemDeEntrada::ATRIBUTO_REAPLICACAO, true);
        $request->setUserResolver(static fn (): User => $usuario);

        $rota = new Route([$mensagem->metodo], $mensagem->caminho, ['uses' => static fn () => null]);
        $rota->parameters = $mensagem->parametrosRota;
        $request->setRouteResolver(static fn (): Route => $rota);

        return $request;
    }

    /** @return bool true quando o erro encerra a mensagem */
    private function registrarErro(MensagemReivindicada $mensagem, Throwable $e): bool
    {
        if ($this->ehRejeicao($e)) {
            $this->caixa->marcarRejeitada($mensagem->id, $this->mensagemDeRejeicao($e), $this->detalhe($e));

            return true;
        }

        $tentativas = $this->caixa->marcarFalha($mensagem->id, $e->getMessage(), ['tipo' => $e::class]);
        $contexto = ['mensagem_id' => $mensagem->id, 'operacao' => $mensagem->operacao, 'tentativas' => $tentativas, 'erro' => $e->getMessage()];

        if ($tentativas >= (int) config('integracao.max_tentativas', 6)) {
            $this->caixa->moverParaMortas($mensagem->id);
            Log::error('Integracao: mensagem enviada para entrada.mortas', $contexto);

            return true;
        }

        Log::warning('Integracao: falha transitoria ao aplicar mensagem', $contexto);

        return false;
    }

    private function ehRejeicao(Throwable $e): bool
    {
        $status = match (true) {
            $e instanceof HttpExceptionInterface => $e->getStatusCode(),
            $e instanceof HttpResponseException => $e->getResponse()->getStatusCode(),
            default => null,
        };

        if ($status !== null) {
            return $status >= 400 && $status < 500 && ! in_array($status, self::STATUS_TRANSITORIOS, true);
        }

        return $e instanceof ValidationException
            || $e instanceof EntradaRejeitadaException
            || $e instanceof DomainException
            || $e instanceof ModelNotFoundException
            || $e instanceof AuthorizationException
            || $e instanceof AuthenticationException;
    }

    private function mensagemDeRejeicao(Throwable $e): string
    {
        return match (true) {
            $e instanceof ValidationException => 'Dados invalidos.',
            $e instanceof ModelNotFoundException => 'Recurso nao encontrado.',
            $e instanceof AuthorizationException => 'Acao nao autorizada.',
            $e->getMessage() !== '' => $e->getMessage(),
            default => 'Mensagem rejeitada.',
        };
    }

    /** @return array<string, mixed> */
    private function detalhe(Throwable $e): array
    {
        return match (true) {
            $e instanceof ValidationException => ['errors' => $e->errors()],
            $e instanceof HttpExceptionInterface => ['status' => $e->getStatusCode()],
            $e instanceof HttpResponseException => ['status' => $e->getResponse()->getStatusCode()],
            default => [],
        };
    }
}
