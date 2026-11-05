<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Support;

use App\Modules\PowerBi\Contracts\Materializador;

/**
 * Base dos materializadores: catalogo vindo do config e chavesDaFonte padrao
 * derivado de linhasAoVivo (sobrescreva com uma consulta so de ids se a tabela
 * for grande).
 */
abstract class MaterializadorBase implements Materializador
{
    public function __construct(protected readonly GravadorBi $gravador)
    {
    }

    /** @return array<string, DefinicaoTabela> */
    public function tabelas(): array
    {
        $tabelas = [];
        foreach ((array) config('powerbi.catalogo.'.$this->modulo(), []) as $nome => $c) {
            $tabelas[$nome] = DefinicaoTabela::dosConfig($this->modulo(), (string) $nome, (array) $c);
        }

        return $tabelas;
    }

    public function definicao(string $tabela): DefinicaoTabela
    {
        return $this->tabelas()[$tabela]
            ?? throw new \InvalidArgumentException("Tabela {$tabela} fora do catalogo de {$this->modulo()}.");
    }

    public function chavesDaFonte(string $tabela): iterable
    {
        $pk = $this->definicao($tabela)->pk;
        foreach ($this->linhasAoVivo($tabela) as $linha) {
            yield array_intersect_key($linha, array_flip($pk));
        }
    }
}
