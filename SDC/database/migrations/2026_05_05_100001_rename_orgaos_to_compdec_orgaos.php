<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orgaos') && ! Schema::hasTable('compdec_orgaos')) {
            Schema::rename('orgaos', 'compdec_orgaos');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('compdec_orgaos') && ! Schema::hasTable('orgaos')) {
            Schema::rename('compdec_orgaos', 'orgaos');
        }
    }
};
