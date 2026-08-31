<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Extrai o ID do parametro de rota, seja ele um Model ou um escalar.
 *
 * MOTIVO DE EXISTIR: as regras de unicidade dos Update*Request faziam
 * `->ignore((int) $this->route('prestador'))`. Quando o FormRequest e validado,
 * o SubstituteBindings JA substituiu o parametro pelo MODEL -- converter objeto
 * para int lanca
 *
 *     Error: Object of class App\Modules\Tdap\Models\Prestador
 *            could not be converted to int
 *
 * ou seja, TODO PUT do recurso respondia 500 antes de validar qualquer coisa.
 * Aconteceu em quatro lugares (prestador, ata, caminhao, cronograma); o
 * UpdateLoteRequest era o unico que tratava, com codigo proprio. Este trait
 * unifica o tratamento para o proximo Update*Request nascer certo.
 *
 * Devolve null quando nao ha parametro ou quando o valor nao vira id — e o que
 * `Rule::unique()->ignore(null)` espera para "nao ignore nada".
 */
trait ResolveIdDaRota
{
    protected function idDaRota(string $parametro): ?int
    {
        $valor = $this->route($parametro);

        if ($valor instanceof Model) {
            $chave = $valor->getKey();

            return is_numeric($chave) ? (int) $chave : null;
        }

        if (is_numeric($valor)) {
            return (int) $valor;
        }

        return null;
    }

    /**
     * Variante para agregados de chave string (UUID), como o ProcessoTdap.
     */
    protected function chaveDaRota(string $parametro): int|string|null
    {
        $valor = $this->route($parametro);

        if ($valor instanceof Model) {
            $chave = $valor->getKey();

            return is_int($chave) || is_string($chave) ? $chave : null;
        }

        return is_int($valor) || is_string($valor) ? $valor : null;
    }
}
