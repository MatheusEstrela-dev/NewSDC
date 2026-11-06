<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Aplicadores\Rat;

/** Chave `rat:criacao:<hash>` das operacoes de criacao de RAT (sem alvo). */
trait GeraChaveDeCriacao
{
    /**
     * sha256 do json canonico (chaves ordenadas em todos os niveis), 16 chars.
     *
     * @param array<string, mixed> $payload dados ja validados
     */
    private function chaveDeCriacao(array $payload): string
    {
        $canonico = static function (mixed $valor) use (&$canonico): mixed {
            if (! is_array($valor)) {
                return $valor;
            }

            $valor = array_map($canonico, $valor);

            if (! array_is_list($valor)) {
                ksort($valor);
            }

            return $valor;
        };

        $json = json_encode($canonico($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return 'rat:criacao:' . substr(hash('sha256', $json), 0, 16);
    }
}
