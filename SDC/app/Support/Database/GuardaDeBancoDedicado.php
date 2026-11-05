<?php

declare(strict_types=1);

namespace App\Support\Database;

use Illuminate\Database\Connection;
use RuntimeException;

/**
 * Trava das migrations de banco dedicado (geo, treinamento): recusa aplicar o
 * DDL fora do banco proprio.
 *
 * Recusa o banco operacional pelo nome e tambem qualquer clone dele: um banco
 * com tabela de modulo no public (ex.: users) e copia do sdc, e o DDL novo
 * ficaria misturado com as tabelas dos modulos. Caso real: um
 * sdc_geoespacial antigo, clonado a mao do sdc, ocupava o nome do banco novo
 * e o provisionamento so diria "ja existe".
 */
final class GuardaDeBancoDedicado
{
    private const NOMES_PROIBIDOS = ['sdc', 'forge'];

    /** Tabelas que so existem no banco operacional. */
    private const MARCAS_DE_CLONE = ['users'];

    public static function validar(Connection $db, string $rotulo): void
    {
        // Em --pretend nao ha query executada; nada a proteger.
        if ($db->pretending()) {
            return;
        }

        $atual = (string) $db->selectOne('SELECT current_database() AS db')->db;
        $operacional = (string) config('database.connections.pgsql.database');

        if (in_array($atual, self::NOMES_PROIBIDOS, true) || $atual === $operacional) {
            throw new RuntimeException(
                "Migration de {$rotulo} recusada: destino '{$atual}' e a base operacional."
            );
        }

        $marcas = $db->table('pg_tables')
            ->where('schemaname', 'public')
            ->whereIn('tablename', self::MARCAS_DE_CLONE)
            ->pluck('tablename')
            ->all();

        if ($marcas !== []) {
            throw new RuntimeException(
                "Migration de {$rotulo} recusada: destino '{$atual}' parece clone do sdc "
                .'(public.'.implode(', public.', $marcas).'). Use um banco vazio, criado por db:provisionar.'
            );
        }
    }
}
