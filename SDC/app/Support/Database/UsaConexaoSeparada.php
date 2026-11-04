<?php

declare(strict_types=1);

namespace App\Support\Database;

/**
 * Base dos models de um dominio em conexao propria (ConexaoSeparada).
 *
 * - A conexao vem da config a cada instancia, nunca fixa no model, para que a
 *   chave de env valha sem tocar em codigo.
 * - Relacao para model de outro dominio (User, protocolo...) usa a conexao
 *   desse model, e nao a deste: sem isto o Eloquent faria o relacionado sem
 *   conexao herdar a do pai, e `$model->user` leria users pela sessao errada.
 *
 * Cada trait de dominio usa esta e informa a chave em chaveDeConexao().
 */
trait UsaConexaoSeparada
{
    abstract protected static function chaveDeConexao(): string;

    public function initializeUsaConexaoSeparada(): void
    {
        $this->connection ??= ConexaoSeparada::de(static::chaveDeConexao())->nome();
    }

    /**
     * @param  class-string  $class
     */
    protected function newRelatedInstance($class)
    {
        return new $class;
    }
}
