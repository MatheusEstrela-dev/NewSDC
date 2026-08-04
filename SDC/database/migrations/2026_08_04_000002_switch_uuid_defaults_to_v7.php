<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * UUIDv7 carries a millisecond timestamp in its high bits, so generated values
 * are monotonic: inserts land on the rightmost B-tree page instead of scattering
 * across the whole index like v4 does. Same locality as a BIGSERIAL, without
 * exposing a row count. PostgreSQL 18 ships uuidv7() natively.
 *
 * The application side already emits v7 through Eloquent (HasUuids), but these
 * defaults are what protect raw SQL inserts: seeders, bulk imports and the RAT
 * legacy archive import.
 *
 * Only columns holding the row's own identity get a default. Archive tables,
 * projections and idempotency keys receive their id from the source row, so a
 * default there would silently mint a new id on a forgotten column.
 */
return new class extends Migration
{
    private const PG_18 = 180000;

    /** Tables whose uuid primary key previously defaulted to gen_random_uuid(). */
    private const HAD_V4_DEFAULT = [
        'rat_ocorrencias',
        'rat_ocorrencia_relatos',
        'rat_ocorrencia_historico',
        'rat_relato_dados_gerais',
        'rat_relato_recursos',
        'rat_relato_envolvidos',
        'rat_relato_vistoria',
        'rat_recursos_componentes_guarnicao',
        'rat_anexos',
    ];

    /** Tables whose uuid primary key had no default at all. */
    private const HAD_NO_DEFAULT = [
        'notifications',
        'outbox_events',
        'tdap_processos',
        'tdap_sagas',
        'request_traces',
        'webhook_events',
        'ai_conversations',
        'ai_messages',
        'ai_execution_logs',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // gen_random_uuid() keeps the previous behaviour on PostgreSQL 17 and
        // below, where uuidv7() does not exist yet.
        $generator = $this->supportsNativeV7() ? 'uuidv7()' : 'gen_random_uuid()';

        foreach ([...self::HAD_V4_DEFAULT, ...self::HAD_NO_DEFAULT] as $table) {
            if (! $this->hasUuidPrimaryKey($table)) {
                continue;
            }

            DB::statement('ALTER TABLE "'.$table.'" ALTER COLUMN id SET DEFAULT '.$generator);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::HAD_V4_DEFAULT as $table) {
            if ($this->hasUuidPrimaryKey($table)) {
                DB::statement('ALTER TABLE "'.$table.'" ALTER COLUMN id SET DEFAULT gen_random_uuid()');
            }
        }

        foreach (self::HAD_NO_DEFAULT as $table) {
            if ($this->hasUuidPrimaryKey($table)) {
                DB::statement('ALTER TABLE "'.$table.'" ALTER COLUMN id DROP DEFAULT');
            }
        }
    }

    private function supportsNativeV7(): bool
    {
        $version = DB::selectOne("select current_setting('server_version_num')::int as num");

        return $version !== null && (int) $version->num >= self::PG_18;
    }

    /**
     * Guards against tables that a given environment has not created yet, and
     * against an id column that is not actually uuid.
     */
    private function hasUuidPrimaryKey(string $table): bool
    {
        $column = DB::selectOne(
            "select 1
             from pg_attribute a
             join pg_class c on c.oid = a.attrelid and c.relkind = 'r'
             join pg_namespace n on n.oid = c.relnamespace and n.nspname = 'public'
             where c.relname = ?
               and a.attname = 'id'
               and a.attnum > 0
               and not a.attisdropped
               and format_type(a.atttypid, a.atttypmod) = 'uuid'",
            [$table]
        );

        return $column !== null;
    }
};
