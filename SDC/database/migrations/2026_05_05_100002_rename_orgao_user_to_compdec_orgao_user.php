<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orgao_user') && ! Schema::hasTable('compdec_orgao_user')) {
            Schema::rename('orgao_user', 'compdec_orgao_user');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('compdec_orgao_user') && ! Schema::hasTable('orgao_user')) {
            Schema::rename('compdec_orgao_user', 'orgao_user');
        }
    }
};
