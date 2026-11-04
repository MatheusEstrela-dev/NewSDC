<?php

declare(strict_types=1);

namespace App\Support\Database;

/**
 * Model do Treinamento (schemas cadastro e treinamento): conexao
 * database.treinamento_connection. Escrita sincrona: inscricao e login nao
 * podem ser fire-and-forget como o log.
 */
trait UsaConexaoDeTreinamento
{
    use UsaConexaoSeparada;

    protected static function chaveDeConexao(): string
    {
        return 'treinamento_connection';
    }
}
