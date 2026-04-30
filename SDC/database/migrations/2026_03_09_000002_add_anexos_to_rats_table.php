<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rats', function (Blueprint $table) {
            $table->jsonb('anexos')->nullable()->after('historico');
            $table->index('anexos', 'idx_rats_anexos', 'gin');
        });
    }

    public function down(): void
    {
        Schema::table('rats', function (Blueprint $table) {
            $table->dropColumn('anexos');
        });
    }
};
