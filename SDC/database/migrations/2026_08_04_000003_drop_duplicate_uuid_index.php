<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * dec_entrada_processo_logs tem dois indices btree na mesma coluna uuid: o
 * UNIQUE criado por ->unique() e um plain criado por ->index() na mesma
 * migration. O UNIQUE ja atende toda busca por igualdade e range, entao o
 * plain e custo puro de escrita em cada insert.
 *
 * Escopo deliberadamente restrito ao duplicado exato. A varredura encontrou
 * outros 29 indices que sao prefixo de um indice composto, mas idx_scan em
 * producao mostrou que o planner escolhe o indice estreito quando ele e menor
 * (idx_decmun_entrada_proc_id tinha 230 buscas). Dropar sem medir seria
 * regressao. Use database/audit_redundant_indexes.sql para decidir.
 */
return new class extends Migration
{
    private const INDEX = 'dec_entrada_processo_logs_uuid_index';

    private const TABLE = 'dec_entrada_processo_logs';

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS "'.self::INDEX.'"');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE INDEX IF NOT EXISTS "'.self::INDEX.'" ON "'.self::TABLE.'" ("uuid")');
    }
};
