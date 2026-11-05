<?php

declare(strict_types=1);

namespace App\Support\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Conexao de um dominio que pode viver fora da conexao default (log, geo,
 * treinamento), resolvida por uma chave de config em database.*_connection.
 *
 * Sem a chave, o nome cai na conexao default e nada muda: o dominio segue na
 * mesma sessao das operacoes. Com a chave, ganha sessao e pool proprios (e,
 * nos bancos dedicados, outro banco). Rollback e tirar a env e reiniciar.
 *
 * Nunca guardar a Connection devolvida em singleton ou propriedade: sob pool
 * por coroutine ela vale so para a coroutine que a obteve.
 */
final class ConexaoSeparada
{
    private function __construct(private readonly string $chave) {}

    /**
     * @param  string  $chave  chave em config/database.php (ex.: 'log_connection')
     */
    public static function de(string $chave): self
    {
        return new self($chave);
    }

    public function nome(): string
    {
        // ?: e nao ??: env vazia no .env chega como string vazia.
        return (string) (config("database.{$this->chave}") ?: config('database.default'));
    }

    /**
     * true quando o dominio tem sessao propria, diferente da conexao default.
     */
    public function separada(): bool
    {
        return $this->nome() !== (string) config('database.default');
    }

    public function conexao(): Connection
    {
        return DB::connection($this->nome());
    }

    public function tabela(string $tabela): Builder
    {
        return $this->conexao()->table($tabela);
    }
}
