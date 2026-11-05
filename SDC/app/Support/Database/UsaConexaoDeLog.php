<?php

declare(strict_types=1);

namespace App\Support\Database;

/**
 * Model de log: le e grava pela conexao de log (ConexaoDeLog).
 *
 * Conexao e relacoes vem de UsaConexaoSeparada. Alem disso, insercao passa por
 * ConexaoDeLog::gravar(): adiada para depois do commit da transacao do modulo
 * e com retentativa pela fila. Atualizacao segue direta. Com a escrita adiada
 * o model volta de create() ainda sem exists/chave.
 */
trait UsaConexaoDeLog
{
    use UsaConexaoSeparada;

    protected static function chaveDeConexao(): string
    {
        return ConexaoDeLog::CHAVE;
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
}
