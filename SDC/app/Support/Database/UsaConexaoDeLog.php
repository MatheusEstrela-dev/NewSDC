<?php

declare(strict_types=1);

namespace App\Support\Database;

/**
 * Model de log: le e grava pela conexao de log (ConexaoDeLog).
 *
 * - A conexao vem da config a cada instancia, nunca fixa no model, para que a
 *   chave DB_LOG_CONNECTION valha sem tocar em codigo.
 * - Insercao passa por ConexaoDeLog::gravar(): adiada para depois do commit da
 *   transacao critica e com retentativa pela fila. Atualizacao segue direta.
 *   Com a escrita adiada o model volta de create() ainda sem exists/chave.
 * - Relacao para model de modulo (User, protocolo...) usa a conexao default, e
 *   nao a de log: sem isto o Eloquent faria o relacionado herdar a conexao do
 *   pai, e `$log->user` leria users pela sessao de log.
 */
trait UsaConexaoDeLog
{
    public function initializeUsaConexaoDeLog(): void
    {
        $this->connection ??= ConexaoDeLog::nome();
    }

    public function save(array $options = []): bool
    {
        if ($this->exists) {
            return parent::save($options);
        }

        $salvo = ConexaoDeLog::gravar(
            fn () => parent::save($options),
            fn (): array => [$this->getTable(), [$this->getAttributes()]],
        );

        // null = adiada ou enfileirada: para quem chama, o log esta a caminho.
        return $salvo ?? true;
    }

    /**
     * @param  class-string  $class
     */
    protected function newRelatedInstance($class)
    {
        return new $class;
    }
}
