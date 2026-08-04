<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * PostgreSQL does not create an index for the referencing side of a foreign key
 * (MySQL does). Every FK without one forces a sequential scan on JOIN and, for
 * ON DELETE CASCADE / SET NULL, on every delete of the parent row.
 *
 * Catalog driven on purpose: it indexes whatever is missing in the environment
 * it runs against, so dev and prod converge even when they drifted apart.
 */
return new class extends Migration
{
    // CREATE INDEX CONCURRENTLY cannot run inside a transaction block.
    public $withinTransaction = false;

    private const SUFFIX = '_fkidx';

    private const MAX_IDENTIFIER = 63;

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // A previous CONCURRENTLY run that failed midway leaves invalid indexes
        // behind, and IF NOT EXISTS would happily skip them. Collected once to
        // keep the migration inside the project query budget.
        $invalid = $this->invalidIndexNames();

        foreach ($this->unindexedForeignKeys() as $fk) {
            $columns = explode(',', $fk->columns);
            $name = $this->indexName($fk->table_name, $columns);

            if (in_array($name, $invalid, true)) {
                DB::statement('DROP INDEX CONCURRENTLY IF EXISTS "'.$name.'"');
            }

            $columnList = implode(', ', array_map(fn (string $c): string => '"'.$c.'"', $columns));

            DB::statement(
                'CREATE INDEX CONCURRENTLY IF NOT EXISTS "'.$name.'" ON "'.$fk->table_name.'" ('.$columnList.')'
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $indexes = DB::select(
            "select indexname from pg_indexes where schemaname = 'public' and indexname like ?",
            ['%'.self::SUFFIX]
        );

        foreach ($indexes as $index) {
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS "'.$index->indexname.'"');
        }
    }

    /**
     * Foreign keys whose referencing columns are not the leading columns of any
     * valid, non-partial index.
     *
     * @return array<int, object{table_name: string, columns: string}>
     */
    private function unindexedForeignKeys(): array
    {
        return DB::select(<<<'SQL'
            select con.conrelid::regclass::text as table_name,
                   (
                     select string_agg(a.attname, ',' order by k.ord)
                     from unnest(con.conkey) with ordinality k(attnum, ord)
                     join pg_attribute a on a.attrelid = con.conrelid and a.attnum = k.attnum
                   ) as columns
            from pg_constraint con
            join pg_class c on c.oid = con.conrelid
            join pg_namespace n on n.oid = c.relnamespace and n.nspname = 'public'
            where con.contype = 'f'
              and not exists (
                select 1
                from pg_index i
                where i.indrelid = con.conrelid
                  and i.indisvalid
                  and i.indpred is null
                  and (i.indkey::int2[])[0:array_length(con.conkey, 1) - 1] = con.conkey
              )
            order by pg_total_relation_size(con.conrelid) desc, 1
        SQL);
    }

    /**
     * @param array<int, string> $columns
     */
    private function indexName(string $table, array $columns): string
    {
        $name = $table.'_'.implode('_', $columns).self::SUFFIX;

        if (strlen($name) <= self::MAX_IDENTIFIER) {
            return $name;
        }

        // Deterministic fallback so re-runs resolve to the same identifier.
        return substr($table, 0, 20).'_'.substr(md5($name), 0, 12).self::SUFFIX;
    }

    /**
     * @return array<int, string>
     */
    private function invalidIndexNames(): array
    {
        $rows = DB::select(
            "select c.relname as name
             from pg_index i
             join pg_class c on c.oid = i.indexrelid
             join pg_namespace n on n.oid = c.relnamespace and n.nspname = 'public'
             where not i.indisvalid"
        );

        return array_map(fn (object $row): string => $row->name, $rows);
    }
};
