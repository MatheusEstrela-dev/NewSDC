<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Support;

/** Mensagem da caixa de entrada entregue ao aplicador, ja decifrada. */
final class MensagemDeEntrada
{
    /**
     * Atributo do Request montado pelo job para revalidar na aplicacao. Com
     * ele, o aplicador pode deixar de fora regras que barrariam a repeticao
     * (ex.: unique) e decidir em aplicar() com EntradaJaAplicadaException.
     */
    public const ATRIBUTO_REAPLICACAO = 'integracao.reaplicacao';

    /**
     * @param  array<string, mixed>  $payload  dados validados na borda
     * @param  array<string, mixed>  $parametrosRota  parametros crus da rota (ids)
     */
    public function __construct(
        public readonly string $id,
        public readonly string $operacao,
        public readonly array $payload,
        public readonly array $parametrosRota,
        public readonly ?int $userId,
        public readonly ?int $tokenId,
    ) {
    }
}
