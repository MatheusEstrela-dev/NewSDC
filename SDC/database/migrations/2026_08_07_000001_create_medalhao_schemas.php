<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SCHEMAS = ['bronze', 'silver', 'gold'];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::SCHEMAS as $schema) {
            DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS %s', $schema));
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (array_reverse(self::SCHEMAS) as $schema) {
            DB::statement(sprintf('DROP SCHEMA IF EXISTS %s CASCADE', $schema));
        }
    }
};
