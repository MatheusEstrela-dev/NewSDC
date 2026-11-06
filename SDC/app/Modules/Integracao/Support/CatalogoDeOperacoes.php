<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Support;

use App\Modules\Integracao\Contracts\AplicadorDeEntrada;
use RuntimeException;

/** Leitura de config/integracao.php: operacao -> aplicador, fonte e modo. */
final class CatalogoDeOperacoes
{
    public const MODO_ADESAO = 'adesao';

    /** Prefixo antes do primeiro ponto (rat.novo.atualizar -> rat). */
    public function fonte(string $operacao): string
    {
        return explode('.', $operacao, 2)[0];
    }

    /**
     * A fonte aderiu ao assincrono e a operacao tem aplicador carregavel.
     * Aplicador ausente cai no sincrono em vez de 500.
     */
    public function emAdesao(string $operacao): bool
    {
        $classe = $this->classe($operacao);

        return $classe !== null
            && class_exists($classe)
            && config('integracao.modo.'.$this->fonte($operacao)) === self::MODO_ADESAO;
    }

    public function aplicador(string $operacao): AplicadorDeEntrada
    {
        $classe = $this->classe($operacao);
        if ($classe === null || ! class_exists($classe)) {
            throw new RuntimeException("Operacao de integracao sem aplicador: {$operacao}.");
        }

        // app() e nao um Container injetado: sob Octane o singleton nao pode
        // segurar a sandbox de uma requisicao antiga.
        $aplicador = app($classe);
        if (! $aplicador instanceof AplicadorDeEntrada) {
            throw new RuntimeException("{$classe} nao implementa AplicadorDeEntrada.");
        }

        return $aplicador;
    }

    private function classe(string $operacao): ?string
    {
        // A operacao tem pontos: le o mapa inteiro em vez de config('a.b.c').
        $classe = ((array) config('integracao.operacoes', []))[$operacao] ?? null;

        return is_string($classe) && $classe !== '' ? $classe : null;
    }
}
