<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Aplicadores;

use App\Models\User;
use App\Modules\Integracao\Contracts\AplicadorDeEntrada;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

/**
 * Base dos aplicadores: so leitura de parametro cru de rota e do usuario
 * autenticado pelo job. Nenhuma regra de negocio mora aqui.
 */
abstract class AplicadorBase implements AplicadorDeEntrada
{
    /** @param array<string, mixed> $parametrosRota */
    protected function parametroDeRota(array $parametrosRota, string $nome): string
    {
        $valor = $parametrosRota[$nome] ?? null;

        if (! is_scalar($valor) || trim((string) $valor) === '') {
            throw new InvalidArgumentException("Parametro de rota '{$nome}' ausente na mensagem.");
        }

        return trim((string) $valor);
    }

    /** O job faz Auth::setUser() do dono do token antes de aplicar(). */
    protected function usuario(): User
    {
        $usuario = Auth::user();

        if (! $usuario instanceof User) {
            throw new AuthenticationException('Mensagem sem usuario autenticado para aplicar.');
        }

        return $usuario;
    }
}
