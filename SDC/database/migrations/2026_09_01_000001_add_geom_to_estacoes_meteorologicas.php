<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // A dimensao permanece no schema public: e cadastro de dominio,
        // referenciado pela aplicacao, nao artefato do pipeline. As matviews de
        // gold fazem join entre schemas, o que o Postgres resolve sem custo.
        DB::statement('ALTER TABLE estacoes_meteorologicas ADD COLUMN IF NOT EXISTS geom geometry(Point, 4326) NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_estacoes_meteorologicas_geom ON estacoes_meteorologicas USING GIST (geom)');

        // Situacao vem do inventario (CD_SITUACAO): Operante, Pane etc. Serve
        // para nao plotar estacao fora de operacao como se estivesse medindo.
        DB::statement('ALTER TABLE estacoes_meteorologicas ADD COLUMN IF NOT EXISTS situacao varchar(32) NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS idx_estacoes_meteorologicas_geom');
        DB::statement('ALTER TABLE estacoes_meteorologicas DROP COLUMN IF EXISTS geom');
        DB::statement('ALTER TABLE estacoes_meteorologicas DROP COLUMN IF EXISTS situacao');
    }
};
