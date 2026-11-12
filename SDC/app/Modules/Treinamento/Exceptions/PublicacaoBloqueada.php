<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Exceptions;

use DomainException;

/**
 * O curso tem pendencias que impedem a publicacao. A mensagem junta todas em
 * texto corrido; `pendencias` traz uma por item para a tela listar.
 */
class PublicacaoBloqueada extends DomainException
{
    /** Prefixo das chaves de erro que carregam a lista (`pendencias.0`, `pendencias.1`, ...). */
    public const CHAVE_PENDENCIAS = 'pendencias';

    /** @param list<string> $pendencias */
    public function __construct(string $mensagem, public readonly array $pendencias)
    {
        parent::__construct($mensagem);
    }

    /**
     * Erros de validacao para o redirect: `publicar` (mensagem unica) e uma
     * chave `pendencias.N` por pendencia (o Inertia entrega so a primeira
     * mensagem de cada chave, por isso a lista vai indexada, na ordem).
     *
     * @return array<string, string>
     */
    public function comoErros(): array
    {
        $erros = ['publicar' => $this->getMessage()];
        foreach ($this->pendencias as $i => $pendencia) {
            $erros[self::CHAVE_PENDENCIAS.'.'.$i] = $pendencia;
        }

        return $erros;
    }
}
